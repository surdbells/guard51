<?php
declare(strict_types=1);
namespace Guard51\Migrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402000002_SupportHelpCenter extends AbstractMigration
{
    public function getDescription(): string { return 'Support tickets + help articles tables'; }
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS support_tickets (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NOT NULL,
            subject VARCHAR(200) NOT NULL, description TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'open', priority VARCHAR(20) NOT NULL DEFAULT 'medium',
            category VARCHAR(50) DEFAULT NULL, assigned_to VARCHAR(36) DEFAULT NULL,
            resolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX idx_st_tenant ON support_tickets (tenant_id)');
        $this->addSql('CREATE INDEX idx_st_status ON support_tickets (status)');

        $this->addSql("CREATE TABLE IF NOT EXISTS help_articles (
            id VARCHAR(36) NOT NULL, title VARCHAR(200) NOT NULL, category VARCHAR(100) NOT NULL,
            content TEXT NOT NULL, sort_order INTEGER NOT NULL DEFAULT 0,
            is_published BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX idx_ha_category ON help_articles (category)');

        // Seed initial help articles
        $this->addSql("INSERT INTO help_articles (id, title, category, content, sort_order, is_published, created_at) VALUES
            (gen_random_uuid()::text, 'Getting Started with Guard51', 'Getting Started', 'Welcome to Guard51! This guide will help you set up your security company on the platform.\n\n1. Complete the onboarding wizard\n2. Add your first site\n3. Add guards\n4. Create shift schedules\n5. Start tracking!', 1, true, NOW()),
            (gen_random_uuid()::text, 'Adding and Managing Guards', 'Guards', 'Navigate to Guards from the sidebar. Click Add Guard to create a new guard profile with their personal details, bank information, and emergency contacts.', 2, true, NOW()),
            (gen_random_uuid()::text, 'Creating Shift Schedules', 'Scheduling', 'Go to Scheduling to create shift templates and assign guards to shifts. You can set up recurring templates or one-time shifts.', 3, true, NOW()),
            (gen_random_uuid()::text, 'Managing Client Sites', 'Sites', 'Sites represent physical locations where guards are posted. Add GPS coordinates for geofencing and assign clients to sites.', 4, true, NOW()),
            (gen_random_uuid()::text, 'Invoice and Billing Guide', 'Billing', 'Create invoices from the Invoices page. You can generate invoices from timesheets, send them to clients, and track payments.', 5, true, NOW()),
            (gen_random_uuid()::text, 'Visitor Management', 'Visitors', 'Schedule visitor appointments with access codes. Visitors receive codes via email/SMS. Security verifies codes at the gate.', 6, true, NOW()),
            (gen_random_uuid()::text, 'Using the Panic Button', 'Safety', 'Guards can trigger the panic button from the mobile app. This sends an immediate alert with GPS coordinates to all dispatchers.', 7, true, NOW()),
            (gen_random_uuid()::text, 'Setting Up Patrol Tours', 'Operations', 'Create checkpoints at your sites (QR codes or NFC tags). Guards scan checkpoints during their patrol tours to prove compliance.', 8, true, NOW())
        ");
    }
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS support_tickets');
        $this->addSql('DROP TABLE IF EXISTS help_articles');
    }
}
