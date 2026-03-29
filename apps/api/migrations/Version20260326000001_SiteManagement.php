<?php

declare(strict_types=1);

namespace Guard51\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Phase 1A: Site/Post Management
 * Creates: sites, post_orders
 */
final class Version20260326000001_SiteManagement extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 1A: Create sites and post_orders tables';
    }

    public function up(Schema $schema): void
    {
        // ── Sites ────────────────────────────────────
        $this->addSql("
            CREATE TABLE sites (
                id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NOT NULL,
                client_id VARCHAR(36) DEFAULT NULL,
                name VARCHAR(200) NOT NULL,
                address TEXT DEFAULT NULL,
                city VARCHAR(100) DEFAULT NULL,
                state VARCHAR(100) DEFAULT NULL,
                latitude DECIMAL(10,8) DEFAULT NULL,
                longitude DECIMAL(11,8) DEFAULT NULL,
                geofence_radius INTEGER NOT NULL DEFAULT 100,
                geofence_polygon TEXT DEFAULT NULL,
                geofence_type VARCHAR(20) NOT NULL DEFAULT 'circle',
                contact_name VARCHAR(200) DEFAULT NULL,
                contact_phone VARCHAR(50) DEFAULT NULL,
                contact_email VARCHAR(255) DEFAULT NULL,
                timezone VARCHAR(50) NOT NULL DEFAULT 'Africa/Lagos',
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                notes TEXT DEFAULT NULL,
                photo_url VARCHAR(500) DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_site_tenant ON sites (tenant_id)');
        $this->addSql('CREATE INDEX idx_site_client ON sites (client_id)');
        $this->addSql('CREATE INDEX idx_site_status ON sites (status)');
        $this->addSql('CREATE INDEX idx_site_coords ON sites (latitude, longitude) WHERE latitude IS NOT NULL');
        $this->addSql('ALTER TABLE sites ADD CONSTRAINT fk_site_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');

        // Polygon geofence storage — uses TEXT (GeoJSON) instead of PostGIS geometry
        // If PostGIS is installed later, this can be migrated to a proper geometry column
        $this->addSql("ALTER TABLE sites ADD COLUMN geom TEXT DEFAULT NULL");
        $this->addSql('COMMENT ON COLUMN sites.geom IS $$GeoJSON polygon for complex geofences (optional PostGIS upgrade)$$');

        // ── Post Orders ──────────────────────────────
        $this->addSql("
            CREATE TABLE post_orders (
                id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NOT NULL,
                site_id VARCHAR(36) NOT NULL,
                title VARCHAR(200) NOT NULL,
                instructions TEXT NOT NULL,
                priority VARCHAR(20) NOT NULL DEFAULT 'medium',
                category VARCHAR(30) NOT NULL DEFAULT 'general',
                effective_from DATE NOT NULL,
                effective_to DATE DEFAULT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_by VARCHAR(36) NOT NULL,
                last_updated_by VARCHAR(36) DEFAULT NULL,
                version INTEGER NOT NULL DEFAULT 1,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_po_tenant ON post_orders (tenant_id)');
        $this->addSql('CREATE INDEX idx_po_site ON post_orders (site_id)');
        $this->addSql('CREATE INDEX idx_po_active ON post_orders (is_active)');
        $this->addSql('ALTER TABLE post_orders ADD CONSTRAINT fk_po_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_orders ADD CONSTRAINT fk_po_site FOREIGN KEY (site_id) REFERENCES sites (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS post_orders');
        $this->addSql('DROP TABLE IF EXISTS sites');
    }
}
