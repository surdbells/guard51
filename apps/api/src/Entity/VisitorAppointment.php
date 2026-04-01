<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'visitor_appointments')]
#[ORM\Index(name: 'idx_va_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_va_code', columns: ['access_code'])]
class VisitorAppointment
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'string', length: 36)]
    private string $tenantId;

    #[ORM\Column(name: 'site_id', type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(name: 'host_user_id', type: 'string', length: 36, nullable: true)]
    private ?string $hostUserId = null;

    #[ORM\Column(name: 'host_name', type: 'string', length: 200)]
    private string $hostName;

    #[ORM\Column(name: 'host_email', type: 'string', length: 255, nullable: true)]
    private ?string $hostEmail = null;

    #[ORM\Column(name: 'host_phone', type: 'string', length: 50, nullable: true)]
    private ?string $hostPhone = null;

    #[ORM\Column(name: 'visitor_name', type: 'string', length: 200)]
    private string $visitorName;

    #[ORM\Column(name: 'visitor_email', type: 'string', length: 255, nullable: true)]
    private ?string $visitorEmail = null;

    #[ORM\Column(name: 'visitor_phone', type: 'string', length: 50, nullable: true)]
    private ?string $visitorPhone = null;

    #[ORM\Column(name: 'visitor_company', type: 'string', length: 200, nullable: true)]
    private ?string $visitorCompany = null;

    #[ORM\Column(name: 'purpose', type: 'string', length: 200)]
    private string $purpose;

    #[ORM\Column(name: 'scheduled_date', type: 'date_immutable')]
    private \DateTimeImmutable $scheduledDate;

    #[ORM\Column(name: 'scheduled_time', type: 'string', length: 10, nullable: true)]
    private ?string $scheduledTime = null;

    #[ORM\Column(name: 'access_code', type: 'string', length: 10, unique: true)]
    private string $accessCode;

    #[ORM\Column(name: 'status', type: 'string', length: 30)]
    private string $status = 'pending'; // pending, checked_in, completed, cancelled, expired

    #[ORM\Column(name: 'notify_sms', type: 'boolean', options: ['default' => false])]
    private bool $notifySms = false;

    #[ORM\Column(name: 'notify_email', type: 'boolean', options: ['default' => true])]
    private bool $notifyEmail = true;

    #[ORM\Column(name: 'notify_whatsapp', type: 'boolean', options: ['default' => false])]
    private bool $notifyWhatsapp = false;

    #[ORM\Column(name: 'checked_in_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $checkedInAt = null;

    #[ORM\Column(name: 'checked_out_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $checkedOutAt = null;

    #[ORM\Column(name: 'checked_in_by', type: 'string', length: 36, nullable: true)]
    private ?string $checkedInBy = null;

    #[ORM\Column(name: 'notes', type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'created_by', type: 'string', length: 36)]
    private string $createdBy;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->createdAt = new \DateTimeImmutable();
        $this->accessCode = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    // Getters + Setters
    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function setTenantId(string $v): static { $this->tenantId = $v; return $this; }
    public function getSiteId(): string { return $this->siteId; }
    public function setSiteId(string $v): static { $this->siteId = $v; return $this; }
    public function getHostUserId(): ?string { return $this->hostUserId; }
    public function setHostUserId(?string $v): static { $this->hostUserId = $v; return $this; }
    public function getHostName(): string { return $this->hostName; }
    public function setHostName(string $v): static { $this->hostName = $v; return $this; }
    public function getHostEmail(): ?string { return $this->hostEmail; }
    public function setHostEmail(?string $v): static { $this->hostEmail = $v; return $this; }
    public function getHostPhone(): ?string { return $this->hostPhone; }
    public function setHostPhone(?string $v): static { $this->hostPhone = $v; return $this; }
    public function getVisitorName(): string { return $this->visitorName; }
    public function setVisitorName(string $v): static { $this->visitorName = $v; return $this; }
    public function getVisitorEmail(): ?string { return $this->visitorEmail; }
    public function setVisitorEmail(?string $v): static { $this->visitorEmail = $v; return $this; }
    public function getVisitorPhone(): ?string { return $this->visitorPhone; }
    public function setVisitorPhone(?string $v): static { $this->visitorPhone = $v; return $this; }
    public function getVisitorCompany(): ?string { return $this->visitorCompany; }
    public function setVisitorCompany(?string $v): static { $this->visitorCompany = $v; return $this; }
    public function getPurpose(): string { return $this->purpose; }
    public function setPurpose(string $v): static { $this->purpose = $v; return $this; }
    public function getScheduledDate(): \DateTimeImmutable { return $this->scheduledDate; }
    public function setScheduledDate(\DateTimeImmutable $v): static { $this->scheduledDate = $v; return $this; }
    public function getScheduledTime(): ?string { return $this->scheduledTime; }
    public function setScheduledTime(?string $v): static { $this->scheduledTime = $v; return $this; }
    public function getAccessCode(): string { return $this->accessCode; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): static { $this->status = $v; return $this; }
    public function getNotifySms(): bool { return $this->notifySms; }
    public function setNotifySms(bool $v): static { $this->notifySms = $v; return $this; }
    public function getNotifyEmail(): bool { return $this->notifyEmail; }
    public function setNotifyEmail(bool $v): static { $this->notifyEmail = $v; return $this; }
    public function getNotifyWhatsapp(): bool { return $this->notifyWhatsapp; }
    public function setNotifyWhatsapp(bool $v): static { $this->notifyWhatsapp = $v; return $this; }
    public function getCheckedInAt(): ?\DateTimeImmutable { return $this->checkedInAt; }
    public function setCheckedInAt(?\DateTimeImmutable $v): static { $this->checkedInAt = $v; return $this; }
    public function getCheckedOutAt(): ?\DateTimeImmutable { return $this->checkedOutAt; }
    public function setCheckedOutAt(?\DateTimeImmutable $v): static { $this->checkedOutAt = $v; return $this; }
    public function getCheckedInBy(): ?string { return $this->checkedInBy; }
    public function setCheckedInBy(?string $v): static { $this->checkedInBy = $v; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $v): static { $this->notes = $v; return $this; }
    public function getCreatedBy(): string { return $this->createdBy; }
    public function setCreatedBy(string $v): static { $this->createdBy = $v; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'site_id' => $this->siteId,
            'host_user_id' => $this->hostUserId, 'host_name' => $this->hostName,
            'host_email' => $this->hostEmail, 'host_phone' => $this->hostPhone,
            'visitor_name' => $this->visitorName, 'visitor_email' => $this->visitorEmail,
            'visitor_phone' => $this->visitorPhone, 'visitor_company' => $this->visitorCompany,
            'purpose' => $this->purpose,
            'scheduled_date' => $this->scheduledDate->format('Y-m-d'),
            'scheduled_time' => $this->scheduledTime, 'access_code' => $this->accessCode,
            'status' => $this->status,
            'notify_sms' => $this->notifySms, 'notify_email' => $this->notifyEmail, 'notify_whatsapp' => $this->notifyWhatsapp,
            'checked_in_at' => $this->checkedInAt?->format(\DateTimeInterface::ATOM),
            'checked_out_at' => $this->checkedOutAt?->format(\DateTimeInterface::ATOM),
            'checked_in_by' => $this->checkedInBy, 'notes' => $this->notes,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'created_by' => $this->createdBy,
        ];
    }
}
