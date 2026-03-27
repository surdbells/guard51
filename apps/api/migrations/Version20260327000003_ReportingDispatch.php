<?php

declare(strict_types=1);

namespace Guard51\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327000003_ReportingDispatch extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 4: daily_activity_reports, custom_report_templates, custom_report_submissions, watch_mode_logs, incident_reports, incident_escalations, dispatch_calls, dispatch_assignments, tasks';
    }

    public function up(Schema $schema): void
    {
        // ── Daily Activity Reports ──
        $this->addSql("
            CREATE TABLE daily_activity_reports (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                guard_id VARCHAR(36) NOT NULL, site_id VARCHAR(36) NOT NULL,
                shift_id VARCHAR(36) DEFAULT NULL, report_date DATE NOT NULL,
                content TEXT NOT NULL, weather VARCHAR(50) DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'draft',
                submitted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                reviewed_by VARCHAR(36) DEFAULT NULL, reviewed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                attachments JSONB NOT NULL DEFAULT '[]',
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_dar_tenant ON daily_activity_reports (tenant_id)');
        $this->addSql('CREATE INDEX idx_dar_guard ON daily_activity_reports (guard_id)');
        $this->addSql('CREATE INDEX idx_dar_date ON daily_activity_reports (report_date)');

        // ── Custom Report Templates ──
        $this->addSql("
            CREATE TABLE custom_report_templates (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                name VARCHAR(200) NOT NULL, description TEXT DEFAULT NULL,
                fields JSONB NOT NULL DEFAULT '[]',
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_by VARCHAR(36) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_crt_tenant ON custom_report_templates (tenant_id)');

        // ── Custom Report Submissions ──
        $this->addSql("
            CREATE TABLE custom_report_submissions (
                id VARCHAR(36) NOT NULL, template_id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NOT NULL, guard_id VARCHAR(36) NOT NULL,
                site_id VARCHAR(36) NOT NULL,
                data JSONB NOT NULL DEFAULT '{}', attachments JSONB NOT NULL DEFAULT '[]',
                status VARCHAR(20) NOT NULL DEFAULT 'submitted',
                submitted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                reviewed_by VARCHAR(36) DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_crs_tenant ON custom_report_submissions (tenant_id)');
        $this->addSql('CREATE INDEX idx_crs_template ON custom_report_submissions (template_id)');
        $this->addSql('ALTER TABLE custom_report_submissions ADD CONSTRAINT fk_crs_template FOREIGN KEY (template_id) REFERENCES custom_report_templates (id) ON DELETE CASCADE');

        // ── Watch Mode Logs ──
        $this->addSql("
            CREATE TABLE watch_mode_logs (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                guard_id VARCHAR(36) NOT NULL, site_id VARCHAR(36) NOT NULL,
                media_type VARCHAR(10) NOT NULL, media_url VARCHAR(500) NOT NULL,
                caption TEXT DEFAULT NULL,
                latitude DECIMAL(10,8) DEFAULT NULL, longitude DECIMAL(11,8) DEFAULT NULL,
                recorded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_wml_tenant ON watch_mode_logs (tenant_id)');
        $this->addSql('CREATE INDEX idx_wml_site ON watch_mode_logs (site_id)');

        // ── Incident Reports ──
        $this->addSql("
            CREATE TABLE incident_reports (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                guard_id VARCHAR(36) NOT NULL, site_id VARCHAR(36) NOT NULL,
                incident_type VARCHAR(30) NOT NULL, severity VARCHAR(10) NOT NULL,
                title VARCHAR(300) NOT NULL, description TEXT NOT NULL,
                location_detail VARCHAR(200) DEFAULT NULL,
                latitude DECIMAL(10,8) DEFAULT NULL, longitude DECIMAL(11,8) DEFAULT NULL,
                occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                reported_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                attachments JSONB NOT NULL DEFAULT '[]',
                status VARCHAR(20) NOT NULL DEFAULT 'reported',
                assigned_to VARCHAR(36) DEFAULT NULL,
                resolution TEXT DEFAULT NULL,
                resolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                resolved_by VARCHAR(36) DEFAULT NULL,
                client_notified BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_ir_tenant ON incident_reports (tenant_id)');
        $this->addSql('CREATE INDEX idx_ir_site ON incident_reports (site_id)');
        $this->addSql('CREATE INDEX idx_ir_status ON incident_reports (status)');

        // ── Incident Escalations ──
        $this->addSql("
            CREATE TABLE incident_escalations (
                id VARCHAR(36) NOT NULL, incident_id VARCHAR(36) NOT NULL,
                escalated_to VARCHAR(36) NOT NULL, escalated_by VARCHAR(36) NOT NULL,
                reason TEXT NOT NULL,
                escalated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_ie_incident ON incident_escalations (incident_id)');
        $this->addSql('ALTER TABLE incident_escalations ADD CONSTRAINT fk_ie_incident FOREIGN KEY (incident_id) REFERENCES incident_reports (id) ON DELETE CASCADE');

        // ── Dispatch Calls ──
        $this->addSql("
            CREATE TABLE dispatch_calls (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                caller_name VARCHAR(200) NOT NULL, caller_phone VARCHAR(50) DEFAULT NULL,
                client_id VARCHAR(36) DEFAULT NULL, site_id VARCHAR(36) DEFAULT NULL,
                call_type VARCHAR(20) NOT NULL, priority VARCHAR(10) NOT NULL,
                description TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'received',
                received_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                dispatched_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                resolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                resolution TEXT DEFAULT NULL,
                created_by VARCHAR(36) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_dc_tenant ON dispatch_calls (tenant_id)');
        $this->addSql('CREATE INDEX idx_dc_status ON dispatch_calls (status)');

        // ── Dispatch Assignments ──
        $this->addSql("
            CREATE TABLE dispatch_assignments (
                id VARCHAR(36) NOT NULL, dispatch_id VARCHAR(36) NOT NULL,
                guard_id VARCHAR(36) NOT NULL,
                assigned_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                acknowledged_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                arrived_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'assigned',
                notes TEXT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_da_dispatch ON dispatch_assignments (dispatch_id)');
        $this->addSql('ALTER TABLE dispatch_assignments ADD CONSTRAINT fk_da_dispatch FOREIGN KEY (dispatch_id) REFERENCES dispatch_calls (id) ON DELETE CASCADE');

        // ── Tasks ──
        $this->addSql("
            CREATE TABLE tasks (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                site_id VARCHAR(36) NOT NULL, assigned_to VARCHAR(36) NOT NULL,
                assigned_by VARCHAR(36) NOT NULL,
                title VARCHAR(300) NOT NULL, description TEXT NOT NULL,
                priority VARCHAR(10) NOT NULL DEFAULT 'medium',
                due_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                completion_notes TEXT DEFAULT NULL,
                attachments JSONB NOT NULL DEFAULT '[]',
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_task_tenant ON tasks (tenant_id)');
        $this->addSql('CREATE INDEX idx_task_site ON tasks (site_id)');
        $this->addSql('CREATE INDEX idx_task_guard ON tasks (assigned_to)');
        $this->addSql('CREATE INDEX idx_task_status ON tasks (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS tasks');
        $this->addSql('DROP TABLE IF EXISTS dispatch_assignments');
        $this->addSql('DROP TABLE IF EXISTS dispatch_calls');
        $this->addSql('DROP TABLE IF EXISTS incident_escalations');
        $this->addSql('DROP TABLE IF EXISTS incident_reports');
        $this->addSql('DROP TABLE IF EXISTS watch_mode_logs');
        $this->addSql('DROP TABLE IF EXISTS custom_report_submissions');
        $this->addSql('DROP TABLE IF EXISTS custom_report_templates');
        $this->addSql('DROP TABLE IF EXISTS daily_activity_reports');
    }
}
