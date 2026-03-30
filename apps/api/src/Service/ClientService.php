<?php

declare(strict_types=1);

namespace Guard51\Service;

use Guard51\Entity\BillingType;
use Guard51\Entity\Client;
use Guard51\Entity\ClientContact;
use Guard51\Entity\ClientStatus;
use Guard51\Exception\ApiException;
use Guard51\Repository\ClientContactRepository;
use Guard51\Repository\ClientRepository;
use Guard51\Repository\SubscriptionPlanRepository;
use Guard51\Repository\SubscriptionRepository;
use Guard51\Repository\TenantUsageMetricRepository;
use Psr\Log\LoggerInterface;

final class ClientService
{
    public function __construct(
        private readonly ClientRepository $clientRepo,
        private readonly ClientContactRepository $contactRepo,
        private readonly TenantUsageMetricRepository $usageRepo,
        private readonly SubscriptionRepository $subRepo,
        private readonly SubscriptionPlanRepository $planRepo,
        private readonly LoggerInterface $logger,
    ) {}

    public function createClient(string $tenantId, array $data): Client
    {
        $this->enforceMaxClients($tenantId);
        if (empty($data['company_name']) || empty($data['contact_name']) || empty($data['contact_email']) || empty($data['contact_phone'])) {
            throw ApiException::validation('Company name, contact name, email, and phone are required.');
        }
        $client = new Client();
        $client->setTenantId($tenantId);
        $this->hydrateClient($client, $data);
        $this->clientRepo->save($client);
        $usage = $this->usageRepo->findOrCreateForTenant($tenantId);
        $usage->incrementClients();
        $this->usageRepo->save($usage);
        $this->logger->info('Client created.', ['tenant_id' => $tenantId, 'client' => $client->getCompanyName()]);
        return $client;
    }

    public function updateClient(string $clientId, array $data): Client
    {
        $client = $this->clientRepo->findOrFail($clientId);
        $this->hydrateClient($client, $data);
        $this->clientRepo->save($client);
        return $client;
    }

    public function getClient(string $clientId): array
    {
        $client = $this->clientRepo->findOrFail($clientId);
        $contacts = $this->contactRepo->findByClient($clientId);
        return [
            'client' => $client->toArray(),
            'contacts' => array_map(fn($c) => $c->toArray(), $contacts),
        ];
    }

    public function listClients(string $tenantId, ?string $status = null, ?string $search = null): array
    {
        if ($search) return $this->clientRepo->searchByName($tenantId, $search);
        return $this->clientRepo->findByTenant($tenantId, $status);
    }

    public function deleteClient(string $clientId, string $tenantId): void
    {
        $client = $this->clientRepo->findOrFail($clientId);
        $client->setStatus(ClientStatus::INACTIVE);
        $this->clientRepo->save($client);
        $usage = $this->usageRepo->findOrCreateForTenant($tenantId);
        $usage->decrementClients();
        $this->usageRepo->save($usage);
    }

    // ── Contacts ─────────────────────────────────────

    public function addContact(string $clientId, array $data): ClientContact
    {
        if (empty($data['name']) || empty($data['email']) || empty($data['phone'])) {
            throw ApiException::validation('Contact name, email, and phone are required.');
        }
        $contact = new ClientContact();
        $contact->setClientId($clientId)
            ->setName($data['name'])
            ->setEmail($data['email'])
            ->setPhone($data['phone'])
            ->setRole($data['role'] ?? null)
            ->setIsPrimary((bool) ($data['is_primary'] ?? false));
        $this->contactRepo->save($contact);
        return $contact;
    }

    public function updateContact(string $contactId, array $data): ClientContact
    {
        $contact = $this->contactRepo->findOrFail($contactId);
        if (isset($data['name'])) $contact->setName($data['name']);
        if (isset($data['email'])) $contact->setEmail($data['email']);
        if (isset($data['phone'])) $contact->setPhone($data['phone']);
        if (isset($data['role'])) $contact->setRole($data['role']);
        if (isset($data['is_primary'])) $contact->setIsPrimary((bool) $data['is_primary']);
        $this->contactRepo->save($contact);
        return $contact;
    }

    public function deleteContact(string $contactId): void
    {
        $contact = $this->contactRepo->findOrFail($contactId);
        $this->contactRepo->delete($contact);
    }

    private function enforceMaxClients(string $tenantId): void
    {
        $sub = $this->subRepo->findActiveByTenant($tenantId);
        if (!$sub) return;
        $plan = $this->planRepo->find($sub->getPlanId());
        if (!$plan) return;
        $usage = $this->usageRepo->findOrCreateForTenant($tenantId);
        if ($usage->wouldExceedClientLimit($plan->getMaxClients())) {
            throw ApiException::conflict(sprintf(
                'Maximum clients (%d) reached for your %s plan.', $plan->getMaxClients(), $plan->getName()
            ));
        }
    }

    private function hydrateClient(Client $client, array $data): void
    {
        if (isset($data['company_name'])) $client->setCompanyName($data['company_name']);
        if (isset($data['contact_name'])) $client->setContactName($data['contact_name']);
        if (isset($data['contact_email'])) $client->setContactEmail($data['contact_email']);
        if (isset($data['contact_phone'])) $client->setContactPhone($data['contact_phone']);
        if (isset($data['address'])) $client->setAddress($data['address']);
        if (isset($data['city'])) $client->setCity($data['city']);
        if (isset($data['state'])) $client->setState($data['state']);
        if (isset($data['contract_start'])) $client->setContractStart(new \DateTimeImmutable($data['contract_start']));
        if (isset($data['contract_end'])) $client->setContractEnd(new \DateTimeImmutable($data['contract_end']));
        if (isset($data['billing_rate'])) $client->setBillingRate((float) $data['billing_rate']);
        if (!empty($data['billing_type'])) $client->setBillingType(BillingType::from($data['billing_type']));
        if (!empty($data['status'])) $client->setStatus(ClientStatus::from($data['status']));
        if (isset($data['notes'])) $client->setNotes($data['notes']);
    }
}
