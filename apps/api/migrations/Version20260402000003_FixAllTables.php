<?php
declare(strict_types=1);
namespace Guard51\Migrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Idempotent migration that ensures ALL tables, indexes, and columns exist.
 * Safe to run multiple times — uses IF NOT EXISTS and TRY/CATCH patterns.
 */
final class Version20260402000003_FixAllTables extends AbstractMigration
{
    public function getDescription(): string { return 'Ensure all tables, indexes, and columns exist (idempotent)'; }

    public function up(Schema $schema): void
    {
        // === Visitor Appointments ===
        $this->addSql("CREATE TABLE IF NOT EXISTS visitor_appointments (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, site_id VARCHAR(36) NOT NULL,
            host_user_id VARCHAR(36) DEFAULT NULL, host_name VARCHAR(200) NOT NULL,
            host_email VARCHAR(255) DEFAULT NULL, host_phone VARCHAR(50) DEFAULT NULL,
            visitor_name VARCHAR(200) NOT NULL, visitor_email VARCHAR(255) DEFAULT NULL,
            visitor_phone VARCHAR(50) DEFAULT NULL, visitor_company VARCHAR(200) DEFAULT NULL,
            purpose VARCHAR(200) NOT NULL, scheduled_date DATE NOT NULL,
            scheduled_time VARCHAR(10) DEFAULT NULL, access_code VARCHAR(10) NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            notify_sms BOOLEAN NOT NULL DEFAULT FALSE, notify_email BOOLEAN NOT NULL DEFAULT TRUE,
            notify_whatsapp BOOLEAN NOT NULL DEFAULT FALSE,
            checked_in_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            checked_out_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            checked_in_by VARCHAR(36) DEFAULT NULL, notes TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_by VARCHAR(36) NOT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uq_va_code ON visitor_appointments (access_code)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_va_tenant ON visitor_appointments (tenant_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_va_site ON visitor_appointments (site_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_va_date ON visitor_appointments (scheduled_date)');

        // === Support Tickets ===
        $this->addSql("CREATE TABLE IF NOT EXISTS support_tickets (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NOT NULL,
            subject VARCHAR(200) NOT NULL, description TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'open', priority VARCHAR(20) NOT NULL DEFAULT 'medium',
            category VARCHAR(50) DEFAULT NULL, assigned_to VARCHAR(36) DEFAULT NULL,
            resolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_st_tenant ON support_tickets (tenant_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_st_status ON support_tickets (status)');

        // === Help Articles ===
        $this->addSql("CREATE TABLE IF NOT EXISTS help_articles (
            id VARCHAR(36) NOT NULL, title VARCHAR(200) NOT NULL, category VARCHAR(100) NOT NULL,
            content TEXT NOT NULL, sort_order INTEGER NOT NULL DEFAULT 0,
            is_published BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ha_category ON help_articles (category)');

        // === Widen encrypted PII columns ===
        $this->addSql("DO $$ BEGIN ALTER TABLE guards ALTER COLUMN phone TYPE VARCHAR(500); EXCEPTION WHEN others THEN NULL; END $$");
        $this->addSql("DO $$ BEGIN ALTER TABLE guards ALTER COLUMN bank_account_number TYPE VARCHAR(500); EXCEPTION WHEN others THEN NULL; END $$");
        $this->addSql("DO $$ BEGIN ALTER TABLE guards ALTER COLUMN bank_account_name TYPE VARCHAR(500); EXCEPTION WHEN others THEN NULL; END $$");
        $this->addSql("DO $$ BEGIN ALTER TABLE guards ALTER COLUMN emergency_contact_phone TYPE VARCHAR(500); EXCEPTION WHEN others THEN NULL; END $$");
        $this->addSql("DO $$ BEGIN ALTER TABLE users ALTER COLUMN phone TYPE VARCHAR(500); EXCEPTION WHEN others THEN NULL; END $$");

        // === Add password_changed_at to users ===
        $this->addSql('ALTER TABLE users ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        // === Seed help articles if empty ===
        $this->addSql("INSERT INTO help_articles (id, title, category, content, sort_order, is_published, created_at)
            SELECT gen_random_uuid()::text, 'Getting Started with Guard51', 'Getting Started',
                'Welcome to Guard51! This guide will help you set up your security company on the platform.\n\n1. Complete the onboarding wizard\n2. Add your first site\n3. Add guards\n4. Create shift schedules\n5. Start tracking!',
                1, true, NOW()
            WHERE NOT EXISTS (SELECT 1 FROM help_articles LIMIT 1)");

        $this->addSql("INSERT INTO help_articles (id, title, category, content, sort_order, is_published, created_at)
            SELECT gen_random_uuid()::text, t.title, t.category, t.content, t.sort_order, true, NOW()
            FROM (VALUES
                ('Adding and Managing Guards', 'Guards', 'Navigate to Guards from the sidebar. Click Add Guard to create a new guard profile with personal details, bank information, and emergency contacts.', 2),
                ('Creating Shift Schedules', 'Scheduling', 'Go to Scheduling to create shift templates and assign guards to shifts. You can set up recurring templates or one-time shifts.', 3),
                ('Managing Client Sites', 'Sites', 'Sites represent physical locations where guards are posted. Add GPS coordinates for geofencing and assign clients to sites.', 4),
                ('Invoice and Billing Guide', 'Billing', 'Create invoices from the Invoices page. You can generate invoices from timesheets, send them to clients, and track payments.', 5),
                ('Visitor Management', 'Visitors', 'Schedule visitor appointments with access codes. Visitors receive codes via email or SMS. Security verifies codes at the gate.', 6),
                ('Using the Panic Button', 'Safety', 'Guards can trigger the panic button from the mobile app. This sends an immediate alert with GPS coordinates to all dispatchers.', 7),
                ('Setting Up Patrol Tours', 'Operations', 'Create checkpoints at your sites using QR codes or NFC tags. Guards scan checkpoints during their patrol tours to prove compliance.', 8)
            ) AS t(title, category, content, sort_order)
            WHERE NOT EXISTS (SELECT 1 FROM help_articles WHERE help_articles.title = t.title)");
    }

    public function down(Schema $schema): void
    {
        // Intentionally empty — this is a fix migration
    }
}
