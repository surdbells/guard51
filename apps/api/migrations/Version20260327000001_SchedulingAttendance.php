<?php

declare(strict_types=1);

namespace Guard51\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Phase 2: Scheduling, Attendance, Passdown
 * Creates: shift_templates, shifts, shift_swap_requests, time_clocks,
 *          attendance_records, break_configs, break_logs, passdown_logs
 */
final class Version20260327000001_SchedulingAttendance extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 2: Create scheduling, attendance, and passdown tables';
    }

    public function up(Schema $schema): void
    {
        // ── Shift Templates ──────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS shift_templates (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                name VARCHAR(100) NOT NULL,
                start_time TIME NOT NULL, end_time TIME NOT NULL,
                days_of_week JSONB NOT NULL DEFAULT '[0,1,2,3,4]',
                site_id VARCHAR(36) DEFAULT NULL,
                required_guards INTEGER DEFAULT NULL,
                required_skill VARCHAR(50) DEFAULT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                notes TEXT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_st_tenant ON shift_templates (tenant_id)');
        $this->addSql('ALTER TABLE shift_templates ADD CONSTRAINT fk_st_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');

        // ── Shifts ───────────────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS shifts (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                site_id VARCHAR(36) NOT NULL, template_id VARCHAR(36) DEFAULT NULL,
                guard_id VARCHAR(36) DEFAULT NULL,
                shift_date DATE NOT NULL,
                start_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                end_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'draft',
                is_open BOOLEAN NOT NULL DEFAULT FALSE,
                notes TEXT DEFAULT NULL,
                created_by VARCHAR(36) NOT NULL,
                confirmed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                confirmed_by VARCHAR(36) DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_shift_tenant ON shifts (tenant_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_shift_site ON shifts (site_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_shift_guard ON shifts (guard_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_shift_date ON shifts (shift_date)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_shift_status ON shifts (status)');
        $this->addSql('ALTER TABLE shifts ADD CONSTRAINT fk_shift_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shifts ADD CONSTRAINT fk_shift_site FOREIGN KEY (site_id) REFERENCES sites (id) ON DELETE CASCADE');

        // ── Shift Swap Requests ──────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS shift_swap_requests (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                requesting_guard_id VARCHAR(36) NOT NULL, target_guard_id VARCHAR(36) NOT NULL,
                shift_id VARCHAR(36) NOT NULL, reason TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                reviewed_by VARCHAR(36) DEFAULT NULL,
                reviewed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                review_notes TEXT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ssr_tenant ON shift_swap_requests (tenant_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ssr_shift ON shift_swap_requests (shift_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ssr_status ON shift_swap_requests (status)');
        $this->addSql('ALTER TABLE shift_swap_requests ADD CONSTRAINT fk_ssr_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shift_swap_requests ADD CONSTRAINT fk_ssr_shift FOREIGN KEY (shift_id) REFERENCES shifts (id) ON DELETE CASCADE');

        // ── Time Clocks ──────────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS time_clocks (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                guard_id VARCHAR(36) NOT NULL, shift_id VARCHAR(36) DEFAULT NULL,
                site_id VARCHAR(36) NOT NULL,
                clock_in_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                clock_in_lat DECIMAL(10,8) NOT NULL, clock_in_lng DECIMAL(11,8) NOT NULL,
                clock_in_method VARCHAR(20) NOT NULL,
                clock_in_photo_url VARCHAR(500) DEFAULT NULL,
                clock_out_time TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                clock_out_lat DECIMAL(10,8) DEFAULT NULL, clock_out_lng DECIMAL(11,8) DEFAULT NULL,
                clock_out_method VARCHAR(20) DEFAULT NULL,
                total_hours DECIMAL(5,2) DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'clocked_in',
                is_within_geofence_in BOOLEAN NOT NULL,
                is_within_geofence_out BOOLEAN DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_tc_tenant ON time_clocks (tenant_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_tc_guard ON time_clocks (guard_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_tc_site ON time_clocks (site_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_tc_status ON time_clocks (status)');
        $this->addSql('ALTER TABLE time_clocks ADD CONSTRAINT fk_tc_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE time_clocks ADD CONSTRAINT fk_tc_guard FOREIGN KEY (guard_id) REFERENCES guards (id) ON DELETE CASCADE');

        // ── Attendance Records ────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS attendance_records (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                guard_id VARCHAR(36) NOT NULL, shift_id VARCHAR(36) NOT NULL,
                site_id VARCHAR(36) NOT NULL, attendance_date DATE NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'absent',
                scheduled_start TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                actual_start TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                scheduled_end TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                actual_end TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                late_minutes INTEGER NOT NULL DEFAULT 0,
                early_leave_minutes INTEGER NOT NULL DEFAULT 0,
                total_worked_hours DECIMAL(5,2) NOT NULL DEFAULT 0,
                reconciled BOOLEAN NOT NULL DEFAULT FALSE,
                reconciled_by VARCHAR(36) DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ar_tenant ON attendance_records (tenant_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ar_guard ON attendance_records (guard_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ar_date ON attendance_records (attendance_date)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ar_status ON attendance_records (status)');
        $this->addSql('CREATE UNIQUE INDEX uq_ar_guard_shift ON attendance_records (guard_id, shift_id)');
        $this->addSql('ALTER TABLE attendance_records ADD CONSTRAINT fk_ar_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');

        // ── Break Configs ────────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS break_configs (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                name VARCHAR(100) NOT NULL,
                break_type VARCHAR(10) NOT NULL DEFAULT 'paid',
                duration_minutes INTEGER NOT NULL,
                auto_start BOOLEAN NOT NULL DEFAULT FALSE,
                auto_start_after_minutes INTEGER DEFAULT NULL,
                can_end_early BOOLEAN NOT NULL DEFAULT TRUE,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_bc_tenant ON break_configs (tenant_id)');
        $this->addSql('ALTER TABLE break_configs ADD CONSTRAINT fk_bc_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');

        // ── Break Logs ───────────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS break_logs (
                id VARCHAR(36) NOT NULL,
                time_clock_id VARCHAR(36) NOT NULL,
                break_config_id VARCHAR(36) NOT NULL,
                start_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                end_time TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                duration_minutes INTEGER DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_bl_tc ON break_logs (time_clock_id)');
        $this->addSql('ALTER TABLE break_logs ADD CONSTRAINT fk_bl_tc FOREIGN KEY (time_clock_id) REFERENCES time_clocks (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE break_logs ADD CONSTRAINT fk_bl_bc FOREIGN KEY (break_config_id) REFERENCES break_configs (id) ON DELETE CASCADE');

        // ── Passdown Logs ────────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS passdown_logs (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                site_id VARCHAR(36) NOT NULL, guard_id VARCHAR(36) NOT NULL,
                shift_id VARCHAR(36) DEFAULT NULL,
                incoming_guard_id VARCHAR(36) DEFAULT NULL,
                content TEXT NOT NULL,
                priority VARCHAR(20) NOT NULL DEFAULT 'normal',
                attachments JSONB NOT NULL DEFAULT '[]',
                acknowledged_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                acknowledged_by VARCHAR(36) DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pl_tenant ON passdown_logs (tenant_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pl_site ON passdown_logs (site_id)');
        $this->addSql('ALTER TABLE passdown_logs ADD CONSTRAINT fk_pl_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE passdown_logs ADD CONSTRAINT fk_pl_site FOREIGN KEY (site_id) REFERENCES sites (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS passdown_logs');
        $this->addSql('DROP TABLE IF EXISTS break_logs');
        $this->addSql('DROP TABLE IF EXISTS break_configs');
        $this->addSql('DROP TABLE IF EXISTS attendance_records');
        $this->addSql('DROP TABLE IF EXISTS time_clocks');
        $this->addSql('DROP TABLE IF EXISTS shift_swap_requests');
        $this->addSql('DROP TABLE IF EXISTS shifts');
        $this->addSql('DROP TABLE IF EXISTS shift_templates');
    }
}
