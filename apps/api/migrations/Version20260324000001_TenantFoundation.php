<?php

declare(strict_types=1);

namespace Guard51\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Phase 0B: Tenant & Multi-Tenancy Foundation
 * Creates: tenants, users, tenant_bank_accounts, platform_bank_accounts, refresh_tokens, audit_logs
 */
final class Version20260324000001_TenantFoundation extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 0B: Create tenant, user, bank account, refresh token, and audit log tables';
    }

    public function up(Schema $schema): void
    {
        // Ensure extensions are available
        // UUIDs generated in PHP via Ramsey\Uuid — no DB extension needed
        // Extensions (postgis, pg_trgm) are optional — install manually if needed:
        //   sudo -u postgres psql -d guard51 -c "CREATE EXTENSION IF NOT EXISTS postgis;"
        //   sudo -u postgres psql -d guard51 -c "CREATE EXTENSION IF NOT EXISTS pg_trgm;"

        // ── Create enum types ────────────────────────
        $this->addSql("
            DO $$ BEGIN
                CREATE TYPE tenant_type AS ENUM (
                    'private_security', 'state_police', 'neighborhood_watch', 'lg_security', 'nscdc'
                );
            EXCEPTION WHEN duplicate_object THEN NULL;
            END $$
        ");

        $this->addSql("
            DO $$ BEGIN
                CREATE TYPE tenant_status AS ENUM ('active', 'trial', 'suspended', 'cancelled');
            EXCEPTION WHEN duplicate_object THEN NULL;
            END $$
        ");

        $this->addSql("
            DO $$ BEGIN
                CREATE TYPE user_role AS ENUM (
                    'super_admin', 'company_admin', 'supervisor', 'guard', 'client', 'dispatcher', 'citizen'
                );
            EXCEPTION WHEN duplicate_object THEN NULL;
            END $$
        ");

        // ── Tenants ──────────────────────────────────
        $this->addSql("
            CREATE TABLE tenants (
                id VARCHAR(36) NOT NULL,
                name VARCHAR(200) NOT NULL,
                tenant_type VARCHAR(50) NOT NULL DEFAULT 'private_security',
                org_subtype VARCHAR(100) DEFAULT NULL,
                rc_number VARCHAR(100) DEFAULT NULL,
                email VARCHAR(255) DEFAULT NULL,
                phone VARCHAR(50) DEFAULT NULL,
                address TEXT DEFAULT NULL,
                city VARCHAR(100) DEFAULT NULL,
                state VARCHAR(100) DEFAULT NULL,
                country VARCHAR(100) DEFAULT NULL,
                logo_url VARCHAR(500) DEFAULT NULL,
                branding JSONB NOT NULL DEFAULT '{}',
                custom_domain VARCHAR(200) DEFAULT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'active',
                timezone VARCHAR(50) DEFAULT 'Africa/Lagos',
                currency VARCHAR(3) NOT NULL DEFAULT 'NGN',
                is_onboarded BOOLEAN NOT NULL DEFAULT FALSE,
                onboarded_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                suspended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                suspension_reason VARCHAR(500) DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_tenants_status ON tenants (status)');
        $this->addSql('CREATE INDEX idx_tenants_type ON tenants (tenant_type)');
        $this->addSql('CREATE UNIQUE INDEX uq_tenants_custom_domain ON tenants (custom_domain) WHERE custom_domain IS NOT NULL');

        // ── Users ────────────────────────────────────
        $this->addSql("
            CREATE TABLE users (
                id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) DEFAULT NULL,
                email VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                phone VARCHAR(50) DEFAULT NULL,
                photo_url VARCHAR(500) DEFAULT NULL,
                role VARCHAR(30) NOT NULL DEFAULT 'guard',
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                is_email_verified BOOLEAN NOT NULL DEFAULT FALSE,
                email_verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                last_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                last_login_ip VARCHAR(45) DEFAULT NULL,
                password_reset_token VARCHAR(255) DEFAULT NULL,
                password_reset_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                failed_login_attempts INTEGER NOT NULL DEFAULT 0,
                locked_until TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE UNIQUE INDEX uq_users_email ON users (email)');
        $this->addSql('CREATE INDEX idx_users_tenant ON users (tenant_id)');
        $this->addSql('CREATE INDEX idx_users_role ON users (role)');
        $this->addSql('CREATE INDEX idx_users_status ON users (is_active)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE SET NULL');

        // ── Tenant Bank Accounts ─────────────────────
        $this->addSql("
            CREATE TABLE tenant_bank_accounts (
                id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NOT NULL,
                bank_name VARCHAR(100) NOT NULL,
                account_number VARCHAR(20) NOT NULL,
                account_name VARCHAR(200) NOT NULL,
                bank_code VARCHAR(10) DEFAULT NULL,
                is_primary BOOLEAN NOT NULL DEFAULT TRUE,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_tba_tenant ON tenant_bank_accounts (tenant_id)');
        $this->addSql('ALTER TABLE tenant_bank_accounts ADD CONSTRAINT fk_tba_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');

        // ── Platform Bank Accounts ───────────────────
        $this->addSql("
            CREATE TABLE platform_bank_accounts (
                id VARCHAR(36) NOT NULL,
                bank_name VARCHAR(100) NOT NULL,
                account_number VARCHAR(20) NOT NULL,
                account_name VARCHAR(200) NOT NULL,
                bank_code VARCHAR(10) DEFAULT NULL,
                is_primary BOOLEAN NOT NULL DEFAULT TRUE,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");

        // ── Refresh Tokens ───────────────────────────
        $this->addSql("
            CREATE TABLE refresh_tokens (
                id VARCHAR(36) NOT NULL,
                user_id VARCHAR(36) NOT NULL,
                token_hash VARCHAR(64) NOT NULL,
                user_agent VARCHAR(200) DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                is_revoked BOOLEAN NOT NULL DEFAULT FALSE,
                revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_rt_user ON refresh_tokens (user_id)');
        $this->addSql('CREATE INDEX idx_rt_token ON refresh_tokens (token_hash)');
        $this->addSql('CREATE INDEX idx_rt_expires ON refresh_tokens (expires_at)');
        $this->addSql('ALTER TABLE refresh_tokens ADD CONSTRAINT fk_rt_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

        // ── Audit Logs ───────────────────────────────
        $this->addSql("
            CREATE TABLE audit_logs (
                id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) DEFAULT NULL,
                user_id VARCHAR(36) DEFAULT NULL,
                user_name VARCHAR(200) DEFAULT NULL,
                action VARCHAR(50) NOT NULL,
                entity_type VARCHAR(100) NOT NULL,
                entity_id VARCHAR(36) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                old_values JSONB DEFAULT NULL,
                new_values JSONB DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(500) DEFAULT NULL,
                request_id VARCHAR(36) DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX idx_audit_tenant ON audit_logs (tenant_id)');
        $this->addSql('CREATE INDEX idx_audit_user ON audit_logs (user_id)');
        $this->addSql('CREATE INDEX idx_audit_entity ON audit_logs (entity_type, entity_id)');
        $this->addSql('CREATE INDEX idx_audit_action ON audit_logs (action)');
        $this->addSql('CREATE INDEX idx_audit_created ON audit_logs (created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS audit_logs');
        $this->addSql('DROP TABLE IF EXISTS refresh_tokens');
        $this->addSql('DROP TABLE IF EXISTS platform_bank_accounts');
        $this->addSql('DROP TABLE IF EXISTS tenant_bank_accounts');
        $this->addSql('DROP TABLE IF EXISTS users');
        $this->addSql('DROP TABLE IF EXISTS tenants');
        $this->addSql('DROP TYPE IF EXISTS user_role');
        $this->addSql('DROP TYPE IF EXISTS tenant_status');
        $this->addSql('DROP TYPE IF EXISTS tenant_type');
    }
}
