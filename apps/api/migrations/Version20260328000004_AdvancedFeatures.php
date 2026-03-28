<?php
declare(strict_types=1);
namespace Guard51\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328000004_AdvancedFeatures extends AbstractMigration
{
    public function getDescription(): string { return 'Phase 8: guard_licenses, two_factor_secrets, audit_logs, guard_performance_index, properties'; }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE guard_licenses (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, guard_id VARCHAR(36) NOT NULL,
            license_type VARCHAR(30) NOT NULL, name VARCHAR(200) NOT NULL,
            license_number VARCHAR(100) DEFAULT NULL, issuing_authority VARCHAR(200) DEFAULT NULL,
            issue_date DATE NOT NULL, expiry_date DATE NOT NULL,
            document_url VARCHAR(500) DEFAULT NULL,
            is_valid BOOLEAN NOT NULL DEFAULT TRUE, expiry_alert_sent BOOLEAN NOT NULL DEFAULT FALSE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX idx_gl_tenant ON guard_licenses (tenant_id)');
        $this->addSql('CREATE INDEX idx_gl_guard ON guard_licenses (guard_id)');

        $this->addSql("CREATE TABLE two_factor_secrets (
            id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NOT NULL,
            secret VARCHAR(64) NOT NULL, is_enabled BOOLEAN NOT NULL DEFAULT FALSE,
            backup_codes JSONB NOT NULL DEFAULT '[]',
            verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id), CONSTRAINT uq_2fa_user UNIQUE (user_id))");

        $this->addSql("CREATE TABLE audit_logs (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
            user_id VARCHAR(36) DEFAULT NULL, action VARCHAR(30) NOT NULL,
            resource_type VARCHAR(100) NOT NULL, resource_id VARCHAR(36) DEFAULT NULL,
            description TEXT DEFAULT NULL, metadata JSONB NOT NULL DEFAULT '{}',
            ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(500) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX idx_al_tenant ON audit_logs (tenant_id)');
        $this->addSql('CREATE INDEX idx_al_user ON audit_logs (user_id)');
        $this->addSql('CREATE INDEX idx_al_action ON audit_logs (action)');
        $this->addSql('CREATE INDEX idx_al_created ON audit_logs (created_at)');

        $this->addSql("CREATE TABLE guard_performance_index (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, guard_id VARCHAR(36) NOT NULL,
            period_month VARCHAR(20) NOT NULL,
            punctuality_score DECIMAL(5,2) NOT NULL DEFAULT 0, tour_compliance_score DECIMAL(5,2) NOT NULL DEFAULT 0,
            report_completion_score DECIMAL(5,2) NOT NULL DEFAULT 0, incident_response_score DECIMAL(5,2) NOT NULL DEFAULT 0,
            overall_score DECIMAL(5,2) NOT NULL DEFAULT 0, grade VARCHAR(2) NOT NULL DEFAULT 'C',
            breakdown JSONB NOT NULL DEFAULT '{}',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX idx_gpi_tenant ON guard_performance_index (tenant_id)');
        $this->addSql('CREATE INDEX idx_gpi_guard ON guard_performance_index (guard_id)');

        $this->addSql("CREATE TABLE properties (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
            name VARCHAR(200) NOT NULL, address VARCHAR(500) DEFAULT NULL,
            city VARCHAR(100) DEFAULT NULL, state VARCHAR(100) DEFAULT NULL,
            manager_id VARCHAR(36) DEFAULT NULL, is_active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX idx_prop_tenant ON properties (tenant_id)');
    }

    public function down(Schema $schema): void
    {
        foreach (['properties','guard_performance_index','audit_logs','two_factor_secrets','guard_licenses'] as $t) {
            $this->addSql("DROP TABLE IF EXISTS {$t}");
        }
    }
}
