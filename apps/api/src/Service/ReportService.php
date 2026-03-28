<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\CustomReportSubmission;
use Guard51\Entity\CustomReportTemplate;
use Guard51\Entity\DailyActivityReport;
use Guard51\Entity\MediaType;
use Guard51\Entity\ReportStatus;
use Guard51\Entity\WatchModeLog;
use Guard51\Exception\ApiException;
use Guard51\Repository\CustomReportSubmissionRepository;
use Guard51\Repository\CustomReportTemplateRepository;
use Guard51\Repository\DailyActivityReportRepository;
use Guard51\Repository\WatchModeLogRepository;
use Psr\Log\LoggerInterface;

final class ReportService
{
    public function __construct(
        private readonly DailyActivityReportRepository $darRepo,
        private readonly CustomReportTemplateRepository $templateRepo,
        private readonly CustomReportSubmissionRepository $submissionRepo,
        private readonly WatchModeLogRepository $watchRepo,
        private readonly LoggerInterface $logger,
    ) {}

    // ── DAR ──────────────────────────────────────────

    public function createDAR(string $tenantId, array $data): DailyActivityReport
    {
        if (empty($data['guard_id']) || empty($data['site_id']) || empty($data['content'])) {
            throw ApiException::validation('guard_id, site_id, and content are required.');
        }
        $dar = new DailyActivityReport();
        $dar->setTenantId($tenantId)->setGuardId($data['guard_id'])->setSiteId($data['site_id'])
            ->setContent($data['content']);
        if (isset($data['shift_id'])) $dar->setShiftId($data['shift_id']);
        if (isset($data['weather'])) $dar->setWeather($data['weather']);
        if (isset($data['report_date'])) $dar->setReportDate(new \DateTimeImmutable($data['report_date']));
        if (isset($data['attachments'])) $dar->setAttachments($data['attachments']);
        if (($data['submit'] ?? false) === true) $dar->submit();
        $this->darRepo->save($dar);
        return $dar;
    }

    public function submitDAR(string $darId): DailyActivityReport
    {
        $dar = $this->darRepo->findOrFail($darId);
        $dar->submit();
        $this->darRepo->save($dar);
        return $dar;
    }

    public function reviewDAR(string $darId, string $userId, bool $approve = false): DailyActivityReport
    {
        $dar = $this->darRepo->findOrFail($darId);
        $approve ? $dar->approve($userId) : $dar->review($userId);
        $this->darRepo->save($dar);
        return $dar;
    }

    public function listDARs(string $tenantId, ?string $siteId = null, ?string $guardId = null, ?string $status = null): array
    {
        return $this->darRepo->findByTenantFiltered($tenantId, $siteId, $guardId, $status);
    }

    // ── Custom Report Templates ──────────────────────

    public function createTemplate(string $tenantId, array $data, string $createdBy): CustomReportTemplate
    {
        if (empty($data['name']) || empty($data['fields'])) throw ApiException::validation('name and fields required.');
        $t = new CustomReportTemplate();
        $t->setTenantId($tenantId)->setName($data['name'])->setFields($data['fields'])->setCreatedBy($createdBy);
        if (isset($data['description'])) $t->setDescription($data['description']);
        $this->templateRepo->save($t);
        return $t;
    }

    public function listTemplates(string $tenantId, bool $activeOnly = false): array
    {
        return $activeOnly ? $this->templateRepo->findActiveByTenant($tenantId) : $this->templateRepo->findByTenant($tenantId);
    }

    // ── Custom Report Submissions ────────────────────

    public function submitCustomReport(string $tenantId, array $data): CustomReportSubmission
    {
        if (empty($data['template_id']) || empty($data['guard_id']) || empty($data['site_id']) || empty($data['data'])) {
            throw ApiException::validation('template_id, guard_id, site_id, data required.');
        }
        $sub = new CustomReportSubmission();
        $sub->setTenantId($tenantId)->setTemplateId($data['template_id'])->setGuardId($data['guard_id'])
            ->setSiteId($data['site_id'])->setData($data['data']);
        if (isset($data['attachments'])) $sub->setAttachments($data['attachments']);
        $this->submissionRepo->save($sub);
        return $sub;
    }

    public function listSubmissions(string $templateId): array { return $this->submissionRepo->findByTemplate($templateId); }

    // ── Watch Mode ───────────────────────────────────

    public function logWatchMedia(string $tenantId, array $data): WatchModeLog
    {
        if (empty($data['guard_id']) || empty($data['site_id']) || empty($data['media_url']) || empty($data['media_type'])) {
            throw ApiException::validation('guard_id, site_id, media_url, media_type required.');
        }
        $log = new WatchModeLog();
        $log->setTenantId($tenantId)->setGuardId($data['guard_id'])->setSiteId($data['site_id'])
            ->setMediaUrl($data['media_url'])->setMediaType(MediaType::from($data['media_type']));
        if (isset($data['caption'])) $log->setCaption($data['caption']);
        if (isset($data['lat'])) $log->setLatitude((float) $data['lat']);
        if (isset($data['lng'])) $log->setLongitude((float) $data['lng']);
        $this->watchRepo->save($log);
        return $log;
    }

    public function getWatchFeed(string $siteId, int $limit = 50): array { return $this->watchRepo->findBySite($siteId, $limit); }
    public function getRecentWatchFeed(string $tenantId, int $hours = 24): array { return $this->watchRepo->findByTenantRecent($tenantId, $hours); }

    // ── PDF Export ───────────────────────────────────

    /**
     * Generate a PDF export of a DAR. Returns an array with 'content' (HTML) for PdfService rendering.
     */
    public function exportDARAsHtml(string $darId): array
    {
        $dar = $this->darRepo->findOrFail($darId);
        $html = '<h1>Daily Activity Report</h1>';
        $html .= '<p><strong>Date:</strong> ' . $dar->getReportDate()->format('Y-m-d') . '</p>';
        $html .= '<p><strong>Status:</strong> ' . $dar->getStatus()->label() . '</p>';
        $html .= '<hr/>';
        $html .= '<div>' . nl2br(htmlspecialchars($dar->getContent())) . '</div>';
        return ['html' => $html, 'dar' => $dar->toArray()];
    }

    // ── Client Sharing ───────────────────────────────

    /**
     * Get DARs for a site that are approved and shareable with the client.
     * Used by Client Portal to display reports for their sites.
     */
    public function getClientShareableReports(string $siteId, int $limit = 20): array
    {
        $approved = $this->darRepo->findByTenantFiltered('', $siteId, null, 'approved');
        return array_slice($approved, 0, $limit);
    }
}
