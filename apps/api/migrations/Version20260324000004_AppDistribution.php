<?php

declare(strict_types=1);

namespace Guard51\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Phase 0F: App Distribution Platform
 * Creates: app_releases, app_download_logs, tenant_app_configs
 */
final class Version20260324000004_AppDistribution extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 0F: Create app release, download log, and tenant app config tables';
    }

    public function up(Schema $schema): void
    {
        // ── App Releases ─────────────────────────────
        $this->addSql("
            CREATE TABLE app_releases (
                id VARCHAR(36) NOT NULL,
                app_key VARCHAR(30) NOT NULL,
                version VARCHAR(20) NOT NULL,
                version_code INTEGER NOT NULL,
                platform VARCHAR(20) NOT NULL,
                release_type VARCHAR(20) NOT NULL DEFAULT 'stable',
                min_api_version VARCHAR(20) DEFAULT NULL,
                file_url VARCHAR(500) NOT NULL,
                file_size_bytes BIGINT NOT NULL,
                file_hash_sha256 VARCHAR(64) NOT NULL,
                release_notes TEXT DEFAULT NULL,
                is_mandatory BOOLEAN NOT NULL DEFAULT FALSE,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                uploaded_by VARCHAR(36) NOT NULL,
                uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                download_count INTEGER NOT NULL DEFAULT 0,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE UNIQUE INDEX uq_ar_version ON app_releases (app_key, platform, version)');
        $this->addSql('CREATE INDEX idx_ar_app_platform ON app_releases (app_key, platform)');
        $this->addSql('CREATE INDEX idx_ar_release_type ON app_releases (release_type)');
        $this->addSql('CREATE INDEX idx_ar_active ON app_releases (is_active)');

        // ── App Download Logs ────────────────────────
        $this->addSql("
            CREATE TABLE app_download_logs (
                id VARCHAR(36) NOT NULL,
                release_id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) DEFAULT NULL,
                downloaded_by VARCHAR(36) DEFAULT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT DEFAULT NULL,
                downloaded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_adl_release ON app_download_logs (release_id)');
        $this->addSql('CREATE INDEX idx_adl_tenant ON app_download_logs (tenant_id)');
        $this->addSql('CREATE INDEX idx_adl_downloaded ON app_download_logs (downloaded_at)');
        $this->addSql('ALTER TABLE app_download_logs ADD CONSTRAINT fk_adl_release FOREIGN KEY (release_id) REFERENCES app_releases (id) ON DELETE CASCADE');

        // ── Tenant App Configs ───────────────────────
        $this->addSql("
            CREATE TABLE tenant_app_configs (
                id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NOT NULL,
                app_key VARCHAR(50) NOT NULL,
                auto_update BOOLEAN NOT NULL DEFAULT TRUE,
                pinned_version VARCHAR(20) DEFAULT NULL,
                settings JSONB NOT NULL DEFAULT '{}',
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE UNIQUE INDEX uq_tac_tenant_app ON tenant_app_configs (tenant_id, app_key)');
        $this->addSql('CREATE INDEX idx_tac_tenant ON tenant_app_configs (tenant_id)');
        $this->addSql('ALTER TABLE tenant_app_configs ADD CONSTRAINT fk_tac_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS tenant_app_configs');
        $this->addSql('DROP TABLE IF EXISTS app_download_logs');
        $this->addSql('DROP TABLE IF EXISTS app_releases');
    }
}
