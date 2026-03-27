<?php

declare(strict_types=1);

namespace Guard51\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327000002_TrackingToursPanic extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 3: guard_locations, geofence_alerts, idle_alerts, tour_checkpoints, tour_sessions, tour_checkpoint_scans, panic_alerts';
    }

    public function up(Schema $schema): void
    {
        // ── Guard Locations (high volume, time-series) ──
        $this->addSql("
            CREATE TABLE guard_locations (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                guard_id VARCHAR(36) NOT NULL, site_id VARCHAR(36) DEFAULT NULL,
                latitude DECIMAL(10,8) NOT NULL, longitude DECIMAL(11,8) NOT NULL,
                accuracy DECIMAL(6,2) NOT NULL, speed DECIMAL(6,2) DEFAULT NULL,
                heading DECIMAL(5,2) DEFAULT NULL, altitude DECIMAL(8,2) DEFAULT NULL,
                battery_level INTEGER DEFAULT NULL, is_moving BOOLEAN NOT NULL DEFAULT TRUE,
                source VARCHAR(20) NOT NULL,
                recorded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                received_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_gl_tenant ON guard_locations (tenant_id)');
        $this->addSql('CREATE INDEX idx_gl_guard ON guard_locations (guard_id)');
        $this->addSql('CREATE INDEX idx_gl_recorded ON guard_locations (recorded_at)');
        $this->addSql('CREATE INDEX idx_gl_guard_time ON guard_locations (guard_id, recorded_at DESC)');

        // ── Geofence Alerts ──
        $this->addSql("
            CREATE TABLE geofence_alerts (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                guard_id VARCHAR(36) NOT NULL, site_id VARCHAR(36) NOT NULL,
                alert_type VARCHAR(30) NOT NULL, latitude DECIMAL(10,8) NOT NULL, longitude DECIMAL(11,8) NOT NULL,
                message TEXT NOT NULL, is_acknowledged BOOLEAN NOT NULL DEFAULT FALSE,
                acknowledged_by VARCHAR(36) DEFAULT NULL, acknowledged_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_ga_tenant ON geofence_alerts (tenant_id)');
        $this->addSql('CREATE INDEX idx_ga_guard ON geofence_alerts (guard_id)');

        // ── Idle Alerts ──
        $this->addSql("
            CREATE TABLE idle_alerts (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                guard_id VARCHAR(36) NOT NULL, site_id VARCHAR(36) NOT NULL,
                idle_start_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, idle_duration_minutes INTEGER NOT NULL,
                last_known_lat DECIMAL(10,8) NOT NULL, last_known_lng DECIMAL(11,8) NOT NULL,
                is_acknowledged BOOLEAN NOT NULL DEFAULT FALSE, acknowledged_by VARCHAR(36) DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_ia_tenant ON idle_alerts (tenant_id)');
        $this->addSql('CREATE INDEX idx_ia_guard ON idle_alerts (guard_id)');

        // ── Tour Checkpoints ──
        $this->addSql("
            CREATE TABLE tour_checkpoints (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, site_id VARCHAR(36) NOT NULL,
                name VARCHAR(200) NOT NULL, checkpoint_type VARCHAR(20) NOT NULL,
                qr_code_value VARCHAR(255) DEFAULT NULL, nfc_tag_id VARCHAR(255) DEFAULT NULL,
                latitude DECIMAL(10,8) DEFAULT NULL, longitude DECIMAL(11,8) DEFAULT NULL,
                virtual_radius INTEGER DEFAULT NULL, sequence_order INTEGER NOT NULL DEFAULT 0,
                is_required BOOLEAN NOT NULL DEFAULT TRUE, is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_tcp_tenant ON tour_checkpoints (tenant_id)');
        $this->addSql('CREATE INDEX idx_tcp_site ON tour_checkpoints (site_id)');
        $this->addSql('ALTER TABLE tour_checkpoints ADD CONSTRAINT fk_tcp_site FOREIGN KEY (site_id) REFERENCES sites (id) ON DELETE CASCADE');

        // ── Tour Sessions ──
        $this->addSql("
            CREATE TABLE tour_sessions (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                guard_id VARCHAR(36) NOT NULL, site_id VARCHAR(36) NOT NULL, shift_id VARCHAR(36) DEFAULT NULL,
                started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'in_progress',
                total_checkpoints INTEGER NOT NULL, scanned_checkpoints INTEGER NOT NULL DEFAULT 0,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_ts_tenant ON tour_sessions (tenant_id)');
        $this->addSql('CREATE INDEX idx_ts_guard ON tour_sessions (guard_id)');
        $this->addSql('CREATE INDEX idx_ts_site ON tour_sessions (site_id)');

        // ── Tour Checkpoint Scans ──
        $this->addSql("
            CREATE TABLE tour_checkpoint_scans (
                id VARCHAR(36) NOT NULL, session_id VARCHAR(36) NOT NULL, checkpoint_id VARCHAR(36) NOT NULL,
                scanned_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, scan_method VARCHAR(20) NOT NULL,
                latitude DECIMAL(10,8) NOT NULL, longitude DECIMAL(11,8) NOT NULL,
                notes TEXT DEFAULT NULL, photo_url VARCHAR(500) DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_tcs_session ON tour_checkpoint_scans (session_id)');
        $this->addSql('ALTER TABLE tour_checkpoint_scans ADD CONSTRAINT fk_tcs_session FOREIGN KEY (session_id) REFERENCES tour_sessions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tour_checkpoint_scans ADD CONSTRAINT fk_tcs_checkpoint FOREIGN KEY (checkpoint_id) REFERENCES tour_checkpoints (id) ON DELETE CASCADE');

        // ── Panic Alerts ──
        $this->addSql("
            CREATE TABLE panic_alerts (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                guard_id VARCHAR(36) NOT NULL, site_id VARCHAR(36) DEFAULT NULL,
                latitude DECIMAL(10,8) NOT NULL, longitude DECIMAL(11,8) NOT NULL,
                message TEXT DEFAULT NULL, audio_url VARCHAR(500) DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'triggered',
                acknowledged_by VARCHAR(36) DEFAULT NULL, acknowledged_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                resolved_by VARCHAR(36) DEFAULT NULL, resolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                resolution_notes TEXT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_pa_tenant ON panic_alerts (tenant_id)');
        $this->addSql('CREATE INDEX idx_pa_status ON panic_alerts (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS panic_alerts');
        $this->addSql('DROP TABLE IF EXISTS tour_checkpoint_scans');
        $this->addSql('DROP TABLE IF EXISTS tour_sessions');
        $this->addSql('DROP TABLE IF EXISTS tour_checkpoints');
        $this->addSql('DROP TABLE IF EXISTS idle_alerts');
        $this->addSql('DROP TABLE IF EXISTS geofence_alerts');
        $this->addSql('DROP TABLE IF EXISTS guard_locations');
    }
}
