<?php

declare(strict_types=1);

namespace Guard51\Service;

use Guard51\Entity\GeofenceType;
use Guard51\Entity\PostOrder;
use Guard51\Entity\PostOrderCategory;
use Guard51\Entity\PostOrderPriority;
use Guard51\Entity\Site;
use Guard51\Entity\SiteStatus;
use Guard51\Exception\ApiException;
use Guard51\Repository\PostOrderRepository;
use Guard51\Repository\SiteRepository;
use Guard51\Repository\SubscriptionPlanRepository;
use Guard51\Repository\SubscriptionRepository;
use Guard51\Repository\TenantUsageMetricRepository;
use Psr\Log\LoggerInterface;

final class SiteService
{
    public function __construct(
        private readonly SiteRepository $siteRepo,
        private readonly PostOrderRepository $postOrderRepo,
        private readonly TenantUsageMetricRepository $usageRepo,
        private readonly SubscriptionRepository $subRepo,
        private readonly SubscriptionPlanRepository $planRepo,
        private readonly LoggerInterface $logger,
    ) {}

    // ── Site CRUD ────────────────────────────────────

    public function createSite(string $tenantId, array $data, string $createdBy): Site
    {
        $this->enforceMaxSites($tenantId);

        $site = new Site();
        $site->setTenantId($tenantId);
        $this->hydrateSite($site, $data);

        $this->siteRepo->save($site);

        // Update usage metrics
        $usage = $this->usageRepo->findOrCreateForTenant($tenantId);
        $usage->incrementSites();
        $this->usageRepo->save($usage);

        $this->logger->info('Site created.', ['tenant_id' => $tenantId, 'site' => $site->getName()]);
        return $site;
    }

    public function updateSite(string $siteId, array $data, string $updatedBy): Site
    {
        $site = $this->siteRepo->findOrFail($siteId);
        $this->hydrateSite($site, $data);
        $this->siteRepo->save($site);

        $this->logger->info('Site updated.', ['site_id' => $siteId, 'name' => $site->getName()]);
        return $site;
    }

    public function getSite(string $siteId): Site
    {
        return $this->siteRepo->findOrFail($siteId);
    }

    public function listSites(string $tenantId, ?string $status = null, ?string $search = null): array
    {
        if ($search) {
            return $this->siteRepo->searchByName($tenantId, $search);
        }
        return $this->siteRepo->findByTenant($tenantId, $status);
    }

    public function getSitesForMap(string $tenantId): array
    {
        $sites = $this->siteRepo->findWithCoordinates($tenantId);
        return array_map(fn(Site $s) => [
            'id' => $s->getId(),
            'name' => $s->getName(),
            'lat' => $s->getLatitude(),
            'lng' => $s->getLongitude(),
            'status' => $s->getStatus()->value,
            'geofence_type' => $s->getGeofenceType()->value,
            'geofence_radius' => $s->getGeofenceRadius(),
        ], $sites);
    }

    public function updateStatus(string $siteId, SiteStatus $status): Site
    {
        $site = $this->siteRepo->findOrFail($siteId);
        $site->setStatus($status);
        $this->siteRepo->save($site);
        return $site;
    }

    public function deleteSite(string $siteId, string $tenantId): void
    {
        $site = $this->siteRepo->findOrFail($siteId);
        $site->deactivate();
        $this->siteRepo->save($site);

        $usage = $this->usageRepo->findOrCreateForTenant($tenantId);
        $usage->decrementSites();
        $this->usageRepo->save($usage);
    }

    // ── Post Orders ──────────────────────────────────

    public function createPostOrder(string $tenantId, string $siteId, array $data, string $createdBy): PostOrder
    {
        // Verify site exists and belongs to tenant
        $site = $this->siteRepo->findOrFail($siteId);

        $order = new PostOrder();
        $order->setTenantId($tenantId)
            ->setSiteId($siteId)
            ->setCreatedBy($createdBy);
        $this->hydratePostOrder($order, $data);

        $this->postOrderRepo->save($order);

        $this->logger->info('Post order created.', ['site_id' => $siteId, 'title' => $order->getTitle()]);
        return $order;
    }

    public function updatePostOrder(string $orderId, array $data, string $updatedBy): PostOrder
    {
        $order = $this->postOrderRepo->findOrFail($orderId);
        $order->updateContent(
            $data['title'] ?? $order->getTitle(),
            $data['instructions'] ?? $order->getInstructions(),
            $updatedBy,
        );

        if (isset($data['priority'])) $order->setPriority(PostOrderPriority::from($data['priority']));
        if (isset($data['category'])) $order->setCategory(PostOrderCategory::from($data['category']));
        if (isset($data['effective_from'])) $order->setEffectiveFrom(new \DateTimeImmutable($data['effective_from']));
        if (array_key_exists('effective_to', $data)) {
            $order->setEffectiveTo($data['effective_to'] ? new \DateTimeImmutable($data['effective_to']) : null);
        }
        if (isset($data['is_active'])) $order->setIsActive((bool) $data['is_active']);

        $this->postOrderRepo->save($order);
        return $order;
    }

    public function listPostOrders(string $siteId, bool $effectiveOnly = false): array
    {
        if ($effectiveOnly) {
            return $this->postOrderRepo->findEffectiveBySite($siteId);
        }
        return $this->postOrderRepo->findBySite($siteId);
    }

    public function deletePostOrder(string $orderId): void
    {
        $order = $this->postOrderRepo->findOrFail($orderId);
        $order->setIsActive(false);
        $this->postOrderRepo->save($order);
    }

    // ── Helpers ──────────────────────────────────────

    private function enforceMaxSites(string $tenantId): void
    {
        $subscription = $this->subRepo->findActiveByTenant($tenantId);
        if (!$subscription) return; // Trial or no subscription — allow

        $plan = $this->planRepo->find($subscription->getPlanId());
        if (!$plan) return;

        $usage = $this->usageRepo->findOrCreateForTenant($tenantId);
        if ($usage->wouldExceedSiteLimit($plan->getMaxSites())) {
            throw ApiException::conflict(sprintf(
                'You have reached the maximum number of sites (%d) for your %s plan. Please upgrade to add more sites.',
                $plan->getMaxSites(),
                $plan->getName(),
            ));
        }
    }

    private function hydrateSite(Site $site, array $data): void
    {
        if (isset($data['name'])) $site->setName($data['name']);
        if (isset($data['address'])) $site->setAddress($data['address']);
        if (isset($data['city'])) $site->setCity($data['city']);
        if (isset($data['state'])) $site->setState($data['state']);
        if (isset($data['latitude'])) $site->setLatitude((float) $data['latitude']);
        if (isset($data['longitude'])) $site->setLongitude((float) $data['longitude']);
        if (isset($data['geofence_radius'])) $site->setGeofenceRadius((int) $data['geofence_radius']);
        if (isset($data['geofence_polygon'])) $site->setGeofencePolygon($data['geofence_polygon']);
        if (isset($data['geofence_type'])) $site->setGeofenceType(GeofenceType::from($data['geofence_type']));
        if (isset($data['contact_name'])) $site->setContactName($data['contact_name']);
        if (isset($data['contact_phone'])) $site->setContactPhone($data['contact_phone']);
        if (isset($data['contact_email'])) $site->setContactEmail($data['contact_email']);
        if (isset($data['timezone'])) $site->setTimezone($data['timezone']);
        if (isset($data['client_id'])) $site->setClientId($data['client_id']);
        if (isset($data['notes'])) $site->setNotes($data['notes']);
        if (isset($data['photo_url'])) $site->setPhotoUrl($data['photo_url']);
        if (isset($data['status'])) $site->setStatus(SiteStatus::from($data['status']));
    }

    private function hydratePostOrder(PostOrder $order, array $data): void
    {
        if (isset($data['title'])) $order->setTitle($data['title']);
        if (isset($data['instructions'])) $order->setInstructions($data['instructions']);
        if (isset($data['priority'])) $order->setPriority(PostOrderPriority::from($data['priority']));
        if (isset($data['category'])) $order->setCategory(PostOrderCategory::from($data['category']));
        if (isset($data['effective_from'])) $order->setEffectiveFrom(new \DateTimeImmutable($data['effective_from']));
        if (array_key_exists('effective_to', $data)) {
            $order->setEffectiveTo($data['effective_to'] ? new \DateTimeImmutable($data['effective_to']) : null);
        }
        if (isset($data['is_active'])) $order->setIsActive((bool) $data['is_active']);
    }
}
