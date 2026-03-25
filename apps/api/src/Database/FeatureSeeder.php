<?php

declare(strict_types=1);

namespace Guard51\Database;

use Doctrine\ORM\EntityManagerInterface;
use Guard51\Entity\FeatureModule;
use Guard51\Entity\SubscriptionPlan;
use Guard51\Entity\SubscriptionTier;

/**
 * Seeds all 52 feature modules and 4 default subscription plans.
 * Idempotent: skips if modules already exist.
 */
class FeatureSeeder
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function run(): void
    {
        echo "🧩 Feature Module & Plan Seeder\n";
        echo "────────────────────────────────\n\n";

        $this->seedModules();
        $this->seedPlans();

        $this->em->flush();
        echo "\n✅ Feature seeding complete.\n";
    }

    private function seedModules(): void
    {
        $existing = $this->em->getRepository(FeatureModule::class)->findAll();
        if (!empty($existing)) {
            echo "  ⏭️  Feature modules already seeded (" . count($existing) . " modules).\n";
            return;
        }

        $allTypes = ['private_security', 'state_police', 'neighborhood_watch', 'lg_security', 'nscdc'];
        $order = 0;

        $modules = [
            // ── Core ─────────────────────────────────
            ['auth', 'Authentication & Authorization', 'core', 'all', true, [], $allTypes],
            ['guard_management', 'Guard Management', 'core', 'all', true, [], $allTypes],
            ['client_management', 'Client Management', 'core', 'all', true, [], $allTypes],
            ['site_management', 'Site/Post Management', 'core', 'all', true, [], $allTypes],
            ['basic_dashboard', 'Basic Dashboard', 'core', 'all', true, [], $allTypes],
            ['post_orders', 'Post Orders', 'core', 'all', true, ['site_management'], $allTypes],

            // ── Tracking ─────────────────────────────
            ['live_tracker', 'Live GPS Tracker', 'tracking', 'all', true, ['guard_management', 'site_management'], $allTypes],
            ['geofencing', 'Geo-Fencing & Alerts', 'tracking', 'starter', false, ['live_tracker', 'site_management'], $allTypes],
            ['idle_detection', 'Idle Detection', 'tracking', 'starter', false, ['live_tracker'], $allTypes],
            ['patrol_history', 'Patrol History & Replay', 'tracking', 'starter', false, ['live_tracker'], $allTypes],

            // ── Scheduling ───────────────────────────
            ['scheduling', 'Shift Scheduling', 'scheduling', 'all', true, ['guard_management', 'site_management'], $allTypes],
            ['shift_templates', 'Shift Templates', 'scheduling', 'starter', false, ['scheduling'], $allTypes],
            ['open_shifts', 'Open Shifts', 'scheduling', 'starter', false, ['scheduling'], $allTypes],
            ['shift_swap', 'Shift Swap & Exchange', 'scheduling', 'professional', false, ['scheduling'], $allTypes],
            ['guard_availability', 'Guard Availability', 'scheduling', 'professional', false, ['scheduling'], $allTypes],

            // ── Attendance ───────────────────────────
            ['time_clock', 'Time Clock (Check-In/Out)', 'attendance', 'all', true, ['guard_management', 'site_management'], $allTypes],
            ['geofence_clock', 'Geofence-Based Clock In/Out', 'attendance', 'starter', false, ['time_clock', 'geofencing'], $allTypes],
            ['attendance_reconciliation', 'Attendance Reconciliation', 'attendance', 'professional', false, ['time_clock'], $allTypes],
            ['break_management', 'Break Management', 'attendance', 'professional', false, ['time_clock'], $allTypes],
            ['leave_management', 'Time-Off / Leave Management', 'attendance', 'professional', false, ['guard_management'], $allTypes],

            // ── Operations ───────────────────────────
            ['site_tours', 'Site Tours (NFC/QR/Virtual)', 'operations', 'starter', false, ['guard_management', 'site_management'], $allTypes],
            ['passdown_logs', 'Passdown Logs', 'operations', 'starter', false, ['guard_management', 'site_management'], $allTypes],
            ['task_management', 'Task Management', 'operations', 'starter', false, ['guard_management', 'site_management'], $allTypes],
            ['guard_web_portal', 'Guard Web Portal', 'operations', 'all', true, ['guard_management', 'scheduling'], $allTypes],

            // ── Reporting ────────────────────────────
            ['daily_activity_report', 'Daily Activity Reports (DAR)', 'reporting', 'all', true, ['guard_management', 'site_management'], $allTypes],
            ['incident_reporting', 'Incident Reporting', 'reporting', 'all', true, ['guard_management', 'site_management'], $allTypes],
            ['custom_report_builder', 'Custom Report Builder', 'reporting', 'professional', false, ['daily_activity_report'], $allTypes],
            ['watch_mode', 'Watch Mode (Video/Photo Logs)', 'reporting', 'professional', false, ['guard_management'], $allTypes],
            ['auto_report_sharing', 'Automated Report Sharing (Client)', 'reporting', 'professional', false, ['daily_activity_report', 'client_management'], $allTypes],

            // ── Emergency ────────────────────────────
            ['panic_button', 'Panic Button', 'emergency', 'all', true, ['guard_management'], $allTypes],
            ['dispatcher_console', 'Dispatcher Console', 'emergency', 'starter', false, ['guard_management', 'site_management'], $allTypes],
            ['incident_escalation', 'Incident Escalation Workflow', 'emergency', 'professional', false, ['incident_reporting'], $allTypes],

            // ── Vehicle ──────────────────────────────
            ['vehicle_patrol', 'Vehicle Patrol Management', 'vehicle', 'professional', false, ['guard_management', 'site_management'], $allTypes],
            ['vehicle_patrol_reports', 'Vehicle Patrol Reports', 'vehicle', 'professional', false, ['vehicle_patrol'], $allTypes],

            // ── Visitor ──────────────────────────────
            ['visitor_management', 'Visitor Management', 'visitor', 'professional', false, ['site_management'], $allTypes],

            // ── Parking ──────────────────────────────
            ['parking_manager', 'Parking Manager', 'parking', 'professional', false, ['site_management'], $allTypes],

            // ── Finance ──────────────────────────────
            ['invoicing', 'Invoice & Estimate Management', 'finance', 'starter', false, ['client_management'], $allTypes],
            ['payroll', 'Payroll Generation', 'finance', 'professional', false, ['time_clock', 'guard_management'], $allTypes],
            ['pay_rate_multiplier', 'Pay Rate Multiplier (Overtime)', 'finance', 'professional', false, ['payroll'], $allTypes],

            // ── Communication ────────────────────────
            ['messenger', 'Messenger / Chat', 'communication', 'starter', false, ['guard_management'], $allTypes],
            ['in_app_notifications', 'In-App Notifications', 'communication', 'all', true, [], $allTypes],
            ['sms_alerts', 'SMS Alerts (Termii)', 'communication', 'starter', false, [], $allTypes],
            ['email_notifications', 'Email Notifications (ZeptoMail)', 'communication', 'all', true, [], $allTypes],

            // ── Client Experience ────────────────────
            ['client_portal', 'Client Web Portal', 'client_experience', 'starter', false, ['client_management'], $allTypes],
            ['client_mobile_app', 'Client Mobile App', 'client_experience', 'professional', false, ['client_portal'], $allTypes],

            // ── Analytics ────────────────────────────
            ['basic_analytics', 'Basic Analytics', 'analytics', 'all', false, [], $allTypes],
            ['advanced_analytics', 'Advanced Analytics & BI', 'analytics', 'business', false, ['basic_analytics'], $allTypes],
            ['custom_reports_export', 'Custom Reports (Export)', 'analytics', 'business', false, ['basic_analytics'], $allTypes],

            // ── Security ─────────────────────────────
            ['audit_logging', 'Audit Logging', 'security', 'business', false, [], $allTypes],
            ['two_factor_auth', '2FA / Advanced Security', 'security', 'business', false, ['auth'], $allTypes],
            ['guard_license_mgmt', 'Guard License Management', 'security', 'professional', false, ['guard_management'], $allTypes],

            // ── Customization ────────────────────────
            ['white_label', 'White-Label Branding', 'customization', 'enterprise', false, [], $allTypes],
            ['multi_property', 'Multi-Property Support', 'customization', 'enterprise', false, [], $allTypes],
            ['dark_mode', 'Dark Mode', 'customization', 'all', false, [], $allTypes],

            // ── Integrations ─────────────────────────
            ['multi_language', 'Multi-Language (EN, Pidgin, Yoruba, Hausa)', 'integrations', 'enterprise', false, [], $allTypes],
            ['offline_mode', 'Offline Mode (Guard App)', 'integrations', 'all', false, [], $allTypes],

            // ── Platform ─────────────────────────────
            ['app_distribution', 'App Distribution Platform', 'platform', 'all', true, [], $allTypes],
            ['desktop_app', 'Desktop Electron App', 'platform', 'all', false, [], $allTypes],
        ];

        foreach ($modules as [$key, $name, $category, $tier, $isCore, $deps, $types]) {
            $module = new FeatureModule();
            $module->setModuleKey($key)
                ->setName($name)
                ->setCategory($category)
                ->setMinimumTier(SubscriptionTier::from($tier))
                ->setIsCore($isCore)
                ->setDependencies($deps)
                ->setTenantTypes($types)
                ->setSortOrder($order++)
                ->setIsActive(true);
            $this->em->persist($module);
        }

        echo "  ✅ " . count($modules) . " feature modules seeded.\n";
    }

    private function seedPlans(): void
    {
        $existing = $this->em->getRepository(SubscriptionPlan::class)->findAll();
        if (!empty($existing)) {
            echo "  ⏭️  Subscription plans already seeded (" . count($existing) . " plans).\n";
            return;
        }

        $allPrivateTypes = ['private_security'];
        $allGovTypes = ['state_police', 'neighborhood_watch', 'lg_security', 'nscdc'];
        $allTypes = array_merge($allPrivateTypes, $allGovTypes);

        // ── Private Security Plans ───────────────────

        $starter = new SubscriptionPlan();
        $starter->setName('Starter')
            ->setDescription('Essential features for small security companies getting started with digital operations.')
            ->setTier(SubscriptionTier::STARTER)
            ->setMonthlyPrice('25000.00')
            ->setAnnualPrice('250000.00')
            ->setMaxGuards(25)->setMaxSites(5)->setMaxClients(5)
            ->setIncludedModules([
                'auth', 'guard_management', 'client_management', 'site_management', 'basic_dashboard', 'post_orders',
                'live_tracker', 'geofencing', 'idle_detection', 'patrol_history',
                'scheduling', 'shift_templates', 'open_shifts',
                'time_clock', 'geofence_clock',
                'site_tours', 'passdown_logs', 'task_management', 'guard_web_portal',
                'daily_activity_report', 'incident_reporting',
                'panic_button', 'dispatcher_console',
                'invoicing', 'messenger',
                'in_app_notifications', 'sms_alerts', 'email_notifications',
                'client_portal', 'basic_analytics',
                'app_distribution', 'dark_mode', 'offline_mode',
            ])
            ->setTenantTypes($allTypes)
            ->setSortOrder(1);
        $this->em->persist($starter);

        $professional = new SubscriptionPlan();
        $professional->setName('Professional')
            ->setDescription('Advanced features for growing security companies managing multiple sites and clients.')
            ->setTier(SubscriptionTier::PROFESSIONAL)
            ->setMonthlyPrice('75000.00')
            ->setAnnualPrice('750000.00')
            ->setMaxGuards(100)->setMaxSites(20)->setMaxClients(15)
            ->setIncludedModules(array_merge($starter->getIncludedModules(), [
                'shift_swap', 'guard_availability',
                'attendance_reconciliation', 'break_management', 'leave_management',
                'custom_report_builder', 'watch_mode', 'auto_report_sharing',
                'incident_escalation',
                'vehicle_patrol', 'vehicle_patrol_reports',
                'visitor_management', 'parking_manager',
                'payroll', 'pay_rate_multiplier',
                'client_mobile_app', 'guard_license_mgmt',
            ]))
            ->setTenantTypes($allTypes)
            ->setSortOrder(2);
        $this->em->persist($professional);

        $business = new SubscriptionPlan();
        $business->setName('Business')
            ->setDescription('Full-featured platform for established security companies with advanced analytics and compliance.')
            ->setTier(SubscriptionTier::BUSINESS)
            ->setMonthlyPrice('150000.00')
            ->setAnnualPrice('1500000.00')
            ->setMaxGuards(300)->setMaxSites(50)->setMaxClients(30)
            ->setIncludedModules(array_merge($professional->getIncludedModules(), [
                'advanced_analytics', 'custom_reports_export',
                'audit_logging', 'two_factor_auth',
            ]))
            ->setTenantTypes($allTypes)
            ->setSortOrder(3);
        $this->em->persist($business);

        $enterprise = new SubscriptionPlan();
        $enterprise->setName('Enterprise')
            ->setDescription('Unlimited, fully customizable platform for large security operations and government agencies.')
            ->setTier(SubscriptionTier::ENTERPRISE)
            ->setMonthlyPrice('0.00')
            ->setMaxGuards(999999)->setMaxSites(999999)->setMaxClients(999999)
            ->setIncludedModules(array_merge($business->getIncludedModules(), [
                'white_label', 'multi_property', 'multi_language', 'desktop_app',
            ]))
            ->setTenantTypes($allTypes)
            ->setIsCustom(true)
            ->setSortOrder(4);
        $this->em->persist($enterprise);

        echo "  ✅ 4 subscription plans seeded (Starter, Professional, Business, Enterprise).\n";
    }
}
