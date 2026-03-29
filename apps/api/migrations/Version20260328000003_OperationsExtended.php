<?php
declare(strict_types=1);
namespace Guard51\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328000003_OperationsExtended extends AbstractMigration
{
    public function getDescription(): string { return 'Phase 7: patrol_vehicles, vehicle_patrol_routes, vehicle_patrol_hits, visitors, visitor_vehicles, parking_areas, parking_lots, parking_vehicles, parking_incident_types, parking_incidents'; }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS patrol_vehicles (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, vehicle_name VARCHAR(100) NOT NULL,
            plate_number VARCHAR(20) NOT NULL, vehicle_type VARCHAR(15) NOT NULL, status VARCHAR(15) NOT NULL DEFAULT 'active',
            assigned_guard_id VARCHAR(36) DEFAULT NULL, notes TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pv_tenant ON patrol_vehicles (tenant_id)');

        $this->addSql("CREATE TABLE IF NOT EXISTS vehicle_patrol_routes (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, name VARCHAR(200) NOT NULL,
            description TEXT DEFAULT NULL, sites JSONB NOT NULL DEFAULT '[]',
            expected_hits_per_day INT NOT NULL DEFAULT 1, reset_time VARCHAR(5) NOT NULL DEFAULT '00:00',
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_vpr_tenant ON vehicle_patrol_routes (tenant_id)');

        $this->addSql("CREATE TABLE IF NOT EXISTS vehicle_patrol_hits (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, route_id VARCHAR(36) NOT NULL,
            vehicle_id VARCHAR(36) NOT NULL, guard_id VARCHAR(36) NOT NULL, site_id VARCHAR(36) NOT NULL,
            hit_number INT NOT NULL, latitude DECIMAL(10,8) NOT NULL, longitude DECIMAL(11,8) NOT NULL,
            notes TEXT DEFAULT NULL, photo_url VARCHAR(500) DEFAULT NULL,
            recorded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_vph_tenant ON vehicle_patrol_hits (tenant_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_vph_route ON vehicle_patrol_hits (route_id)');

        $this->addSql("CREATE TABLE IF NOT EXISTS visitors (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, site_id VARCHAR(36) NOT NULL,
            first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL,
            phone VARCHAR(50) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, company VARCHAR(200) DEFAULT NULL,
            purpose VARCHAR(300) NOT NULL, host_name VARCHAR(200) DEFAULT NULL,
            id_type VARCHAR(20) DEFAULT NULL, id_number VARCHAR(50) DEFAULT NULL,
            photo_url VARCHAR(500) DEFAULT NULL, vehicle_plate VARCHAR(20) DEFAULT NULL,
            status VARCHAR(15) NOT NULL DEFAULT 'checked_in',
            check_in_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, check_out_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            checked_in_by VARCHAR(36) NOT NULL, checked_out_by VARCHAR(36) DEFAULT NULL,
            notes TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_vis_tenant ON visitors (tenant_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_vis_site ON visitors (site_id)');

        $this->addSql("CREATE TABLE IF NOT EXISTS visitor_vehicles (
            id VARCHAR(36) NOT NULL, visitor_id VARCHAR(36) NOT NULL, plate_number VARCHAR(20) NOT NULL,
            make VARCHAR(50) DEFAULT NULL, model VARCHAR(50) DEFAULT NULL, color VARCHAR(30) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_vv_visitor ON visitor_vehicles (visitor_id)');

        $this->addSql("CREATE TABLE IF NOT EXISTS parking_areas (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, site_id VARCHAR(36) NOT NULL,
            name VARCHAR(200) NOT NULL, total_spaces INT NOT NULL, status VARCHAR(10) NOT NULL DEFAULT 'active',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pa_tenant ON parking_areas (tenant_id)');

        $this->addSql("CREATE TABLE IF NOT EXISTS parking_lots (
            id VARCHAR(36) NOT NULL, parking_area_id VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL,
            capacity INT NOT NULL, lot_type VARCHAR(10) NOT NULL DEFAULT 'regular',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pl_area ON parking_lots (parking_area_id)');
        $this->addSql('ALTER TABLE parking_lots ADD CONSTRAINT fk_pl_area FOREIGN KEY (parking_area_id) REFERENCES parking_areas (id) ON DELETE CASCADE');

        $this->addSql("CREATE TABLE IF NOT EXISTS parking_vehicles (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, site_id VARCHAR(36) NOT NULL,
            parking_lot_id VARCHAR(36) DEFAULT NULL, plate_number VARCHAR(20) NOT NULL,
            make VARCHAR(50) DEFAULT NULL, model VARCHAR(50) DEFAULT NULL, color VARCHAR(30) DEFAULT NULL,
            owner_name VARCHAR(200) DEFAULT NULL, owner_phone VARCHAR(50) DEFAULT NULL,
            owner_type VARCHAR(10) NOT NULL DEFAULT 'unknown', status VARCHAR(10) NOT NULL DEFAULT 'parked',
            entry_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, exit_time TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            logged_by VARCHAR(36) NOT NULL, notes TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pkv_tenant ON parking_vehicles (tenant_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pkv_site ON parking_vehicles (site_id)');

        $this->addSql("CREATE TABLE IF NOT EXISTS parking_incident_types (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL,
            form_fields JSONB NOT NULL DEFAULT '[]', is_active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pit_tenant ON parking_incident_types (tenant_id)');

        $this->addSql("CREATE TABLE IF NOT EXISTS parking_incidents (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, site_id VARCHAR(36) NOT NULL,
            vehicle_id VARCHAR(36) DEFAULT NULL, incident_type_id VARCHAR(36) NOT NULL,
            description TEXT NOT NULL, attachments JSONB NOT NULL DEFAULT '[]',
            reported_by VARCHAR(36) NOT NULL, status VARCHAR(10) NOT NULL DEFAULT 'reported',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pki_tenant ON parking_incidents (tenant_id)');
    }

    public function down(Schema $schema): void
    {
        foreach (['parking_incidents','parking_incident_types','parking_vehicles','parking_lots','parking_areas','visitor_vehicles','visitors','vehicle_patrol_hits','vehicle_patrol_routes','patrol_vehicles'] as $t) {
            $this->addSql("DROP TABLE IF EXISTS {$t}");
        }
    }
}
