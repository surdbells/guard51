<?php
declare(strict_types=1);
namespace Guard51\Service;

use Doctrine\ORM\EntityManagerInterface;
use Guard51\Entity\VisitorAppointment;
use Guard51\Exception\ApiException;
use Psr\Log\LoggerInterface;

final class VisitorAppointmentService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ZeptoMailService $mailer,
        private readonly TermiiService $sms,
        private readonly LoggerInterface $logger,
    ) {}

    public function create(string $tenantId, array $data, string $createdBy): VisitorAppointment
    {
        if (empty($data['visitor_name']) || empty($data['host_name']) || empty($data['site_id']) || empty($data['purpose']) || empty($data['scheduled_date'])) {
            throw ApiException::validation('Visitor name, host name, site, purpose, and date are required.');
        }

        $appt = new VisitorAppointment();
        $appt->setTenantId($tenantId)->setSiteId($data['site_id'])->setCreatedBy($createdBy)
            ->setHostName($data['host_name'])->setHostEmail($data['host_email'] ?? null)->setHostPhone($data['host_phone'] ?? null)
            ->setHostUserId($data['host_user_id'] ?? null)
            ->setVisitorName($data['visitor_name'])->setVisitorEmail($data['visitor_email'] ?? null)
            ->setVisitorPhone($data['visitor_phone'] ?? null)->setVisitorCompany($data['visitor_company'] ?? null)
            ->setPurpose($data['purpose'])->setScheduledDate(new \DateTimeImmutable($data['scheduled_date']))
            ->setScheduledTime($data['scheduled_time'] ?? null)->setNotes($data['notes'] ?? null)
            ->setNotifySms(!empty($data['notify_sms']))->setNotifyEmail(!empty($data['notify_email']))
            ->setNotifyWhatsapp(!empty($data['notify_whatsapp']));

        $this->em->persist($appt);
        $this->em->flush();

        // Send notifications with access code
        $this->sendVisitorNotification($appt);

        $this->logger->info('Visitor appointment created.', ['id' => $appt->getId(), 'code' => $appt->getAccessCode()]);
        return $appt;
    }

    public function verifyAccessCode(string $code, string $tenantId): VisitorAppointment
    {
        $appt = $this->em->getRepository(VisitorAppointment::class)->findOneBy(['accessCode' => strtoupper($code), 'tenantId' => $tenantId]);
        if (!$appt) throw ApiException::notFound('Invalid access code.');
        if ($appt->getStatus() === 'cancelled') throw ApiException::validation('This appointment has been cancelled.');
        if ($appt->getStatus() === 'completed') throw ApiException::validation('This appointment has already been completed.');
        return $appt;
    }

    public function checkIn(string $appointmentId, string $guardId): VisitorAppointment
    {
        $appt = $this->findById($appointmentId);
        $appt->setStatus('checked_in')->setCheckedInAt(new \DateTimeImmutable())->setCheckedInBy($guardId);
        $this->em->flush();

        // Notify host of visitor arrival
        $this->notifyHostOfArrival($appt);

        $this->logger->info('Visitor checked in via appointment.', ['id' => $appt->getId()]);
        return $appt;
    }

    public function checkOut(string $appointmentId): VisitorAppointment
    {
        $appt = $this->findById($appointmentId);
        $appt->setStatus('completed')->setCheckedOutAt(new \DateTimeImmutable());
        $this->em->flush();
        return $appt;
    }

    public function cancel(string $appointmentId): VisitorAppointment
    {
        $appt = $this->findById($appointmentId);
        $appt->setStatus('cancelled');
        $this->em->flush();
        return $appt;
    }

    public function findById(string $id): VisitorAppointment
    {
        $appt = $this->em->find(VisitorAppointment::class, $id);
        if (!$appt) throw ApiException::notFound('Appointment not found.');
        return $appt;
    }

    public function listByTenant(string $tenantId, ?string $status = null, ?string $date = null): array
    {
        $qb = $this->em->createQueryBuilder()->select('a')->from(VisitorAppointment::class, 'a')
            ->where('a.tenantId = :tid')->setParameter('tid', $tenantId)->orderBy('a.scheduledDate', 'DESC');
        if ($status) $qb->andWhere('a.status = :status')->setParameter('status', $status);
        if ($date) $qb->andWhere('a.scheduledDate = :date')->setParameter('date', $date);
        return $qb->getQuery()->getResult();
    }

    private function sendVisitorNotification(VisitorAppointment $appt): void
    {
        $code = $appt->getAccessCode();
        $msg = "Your visit to {$appt->getHostName()} is scheduled for {$appt->getScheduledDate()->format('M j, Y')}. Your access code is: {$code}. Please present this code to security upon arrival.";

        if ($appt->getNotifyEmail() && $appt->getVisitorEmail()) {
            try {
                $this->mailer->send($appt->getVisitorEmail(), $appt->getVisitorName(), 'Your Visit Access Code', $msg);
            } catch (\Throwable $e) { $this->logger->warning('Failed to email visitor.', ['error' => $e->getMessage()]); }
        }

        if ($appt->getNotifySms() && $appt->getVisitorPhone()) {
            try {
                $this->sms->sendSms($appt->getVisitorPhone(), $msg);
            } catch (\Throwable $e) { $this->logger->warning('Failed to SMS visitor.', ['error' => $e->getMessage()]); }
        }

        if ($appt->getNotifyWhatsapp() && $appt->getVisitorPhone()) {
            try {
                $this->sms->sendSms($appt->getVisitorPhone(), $msg); // Termii can send WhatsApp too
            } catch (\Throwable $e) { $this->logger->warning('Failed to WhatsApp visitor.', ['error' => $e->getMessage()]); }
        }
    }

    private function notifyHostOfArrival(VisitorAppointment $appt): void
    {
        $msg = "Your visitor {$appt->getVisitorName()}" . ($appt->getVisitorCompany() ? " from {$appt->getVisitorCompany()}" : '') . " has arrived at the reception. Purpose: {$appt->getPurpose()}.";

        if ($appt->getNotifyEmail() && $appt->getHostEmail()) {
            try { $this->mailer->send($appt->getHostEmail(), $appt->getHostName(), 'Visitor Arrival Notification', $msg); } catch (\Throwable $e) { $this->logger->warning('Failed to email host.', ['error' => $e->getMessage()]); }
        }
        if ($appt->getNotifySms() && $appt->getHostPhone()) {
            try { $this->sms->sendSms($appt->getHostPhone(), $msg); } catch (\Throwable $e) { $this->logger->warning('Failed to SMS host.', ['error' => $e->getMessage()]); }
        }
    }
}
