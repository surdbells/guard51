<?php

declare(strict_types=1);

namespace Guard51\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Phase 1B+1C+1E: Guards, Skills, Documents, Clients, Contacts, DailySnapshots
 */
final class Version20260326000002_GuardsClientsSnapshots extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 1: Create guards, guard_skills, guard_skill_assignments, guard_documents, clients, client_contacts, daily_snapshots tables';
    }

    public function up(Schema $schema): void
    {
        // ── Guards ───────────────────────────────────
        $this->addSql("
            CREATE TABLE guards (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, user_id VARCHAR(36) DEFAULT NULL,
                employee_number VARCHAR(50) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL,
                phone VARCHAR(50) NOT NULL, email VARCHAR(255) DEFAULT NULL,
                date_of_birth DATE DEFAULT NULL, gender VARCHAR(10) DEFAULT NULL,
                address TEXT DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, state VARCHAR(100) DEFAULT NULL,
                photo_url VARCHAR(500) DEFAULT NULL,
                emergency_contact_name VARCHAR(200) DEFAULT NULL, emergency_contact_phone VARCHAR(50) DEFAULT NULL,
                hire_date DATE NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'active',
                pay_type VARCHAR(20) DEFAULT NULL, pay_rate DECIMAL(10,2) DEFAULT NULL,
                bank_name VARCHAR(100) DEFAULT NULL, bank_account_number VARCHAR(20) DEFAULT NULL, bank_account_name VARCHAR(200) DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_guard_tenant ON guards (tenant_id)');
        $this->addSql('CREATE INDEX idx_guard_user ON guards (user_id)');
        $this->addSql('CREATE INDEX idx_guard_status ON guards (status)');
        $this->addSql('CREATE UNIQUE INDEX uq_guard_employee ON guards (tenant_id, employee_number)');
        $this->addSql('ALTER TABLE guards ADD CONSTRAINT fk_guard_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');

        // ── Guard Skills ─────────────────────────────
        $this->addSql("
            CREATE TABLE guard_skills (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                name VARCHAR(100) NOT NULL, description TEXT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_gs_tenant ON guard_skills (tenant_id)');
        $this->addSql('CREATE UNIQUE INDEX uq_gs_name ON guard_skills (tenant_id, name)');
        $this->addSql('ALTER TABLE guard_skills ADD CONSTRAINT fk_gs_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');

        // ── Guard Skill Assignments ──────────────────
        $this->addSql("
            CREATE TABLE guard_skill_assignments (
                id VARCHAR(36) NOT NULL, guard_id VARCHAR(36) NOT NULL, skill_id VARCHAR(36) NOT NULL,
                certified_at DATE DEFAULT NULL, expires_at DATE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE UNIQUE INDEX uq_gsa ON guard_skill_assignments (guard_id, skill_id)');
        $this->addSql('CREATE INDEX idx_gsa_guard ON guard_skill_assignments (guard_id)');
        $this->addSql('ALTER TABLE guard_skill_assignments ADD CONSTRAINT fk_gsa_guard FOREIGN KEY (guard_id) REFERENCES guards (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE guard_skill_assignments ADD CONSTRAINT fk_gsa_skill FOREIGN KEY (skill_id) REFERENCES guard_skills (id) ON DELETE CASCADE');

        // ── Guard Documents ──────────────────────────
        $this->addSql("
            CREATE TABLE guard_documents (
                id VARCHAR(36) NOT NULL, guard_id VARCHAR(36) NOT NULL,
                document_type VARCHAR(30) NOT NULL, title VARCHAR(200) NOT NULL, file_url VARCHAR(500) NOT NULL,
                issue_date DATE DEFAULT NULL, expiry_date DATE DEFAULT NULL,
                is_verified BOOLEAN NOT NULL DEFAULT FALSE, notes TEXT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_gd_guard ON guard_documents (guard_id)');
        $this->addSql('CREATE INDEX idx_gd_expiry ON guard_documents (expiry_date)');
        $this->addSql('ALTER TABLE guard_documents ADD CONSTRAINT fk_gd_guard FOREIGN KEY (guard_id) REFERENCES guards (id) ON DELETE CASCADE');

        // ── Clients ──────────────────────────────────
        $this->addSql("
            CREATE TABLE clients (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                company_name VARCHAR(200) NOT NULL, contact_name VARCHAR(200) NOT NULL,
                contact_email VARCHAR(255) NOT NULL, contact_phone VARCHAR(50) NOT NULL,
                address TEXT DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, state VARCHAR(100) DEFAULT NULL,
                contract_start DATE DEFAULT NULL, contract_end DATE DEFAULT NULL,
                billing_rate DECIMAL(10,2) DEFAULT NULL, billing_type VARCHAR(20) DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active', notes TEXT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_client_tenant ON clients (tenant_id)');
        $this->addSql('CREATE INDEX idx_client_status ON clients (status)');
        $this->addSql('ALTER TABLE clients ADD CONSTRAINT fk_client_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');

        // Add FK from sites.client_id → clients.id
        $this->addSql('ALTER TABLE sites ADD CONSTRAINT fk_site_client FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE SET NULL');

        // ── Client Contacts ──────────────────────────
        $this->addSql("
            CREATE TABLE client_contacts (
                id VARCHAR(36) NOT NULL, client_id VARCHAR(36) NOT NULL,
                name VARCHAR(200) NOT NULL, role VARCHAR(100) DEFAULT NULL,
                email VARCHAR(255) NOT NULL, phone VARCHAR(50) NOT NULL,
                is_primary BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_cc_client ON client_contacts (client_id)');
        $this->addSql('ALTER TABLE client_contacts ADD CONSTRAINT fk_cc_client FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE');

        // ── Daily Snapshots ──────────────────────────
        $this->addSql("
            CREATE TABLE daily_snapshots (
                id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
                snapshot_date DATE NOT NULL,
                total_guards INTEGER NOT NULL DEFAULT 0, guards_on_duty INTEGER NOT NULL DEFAULT 0,
                guards_late INTEGER NOT NULL DEFAULT 0, guards_absent INTEGER NOT NULL DEFAULT 0,
                total_sites INTEGER NOT NULL DEFAULT 0, sites_covered INTEGER NOT NULL DEFAULT 0,
                incidents_count INTEGER NOT NULL DEFAULT 0,
                shifts_total INTEGER NOT NULL DEFAULT 0, shifts_filled INTEGER NOT NULL DEFAULT 0,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE UNIQUE INDEX uq_ds_tenant_date ON daily_snapshots (tenant_id, snapshot_date)');
        $this->addSql('CREATE INDEX idx_ds_tenant ON daily_snapshots (tenant_id)');
        $this->addSql('ALTER TABLE daily_snapshots ADD CONSTRAINT fk_ds_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS daily_snapshots');
        $this->addSql('DROP TABLE IF EXISTS client_contacts');
        $this->addSql('ALTER TABLE sites DROP CONSTRAINT IF EXISTS fk_site_client');
        $this->addSql('DROP TABLE IF EXISTS clients');
        $this->addSql('DROP TABLE IF EXISTS guard_documents');
        $this->addSql('DROP TABLE IF EXISTS guard_skill_assignments');
        $this->addSql('DROP TABLE IF EXISTS guard_skills');
        $this->addSql('DROP TABLE IF EXISTS guards');
    }
}
