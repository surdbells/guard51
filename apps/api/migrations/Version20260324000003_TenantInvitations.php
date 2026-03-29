<?php

declare(strict_types=1);

namespace Guard51\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Phase 0E: Tenant Onboarding & Management
 * Creates: tenant_invitations
 */
final class Version20260324000003_TenantInvitations extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 0E: Create tenant_invitations table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE IF NOT EXISTS tenant_invitations (
                id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NOT NULL,
                email VARCHAR(255) NOT NULL,
                first_name VARCHAR(100) DEFAULT NULL,
                last_name VARCHAR(100) DEFAULT NULL,
                role VARCHAR(30) NOT NULL DEFAULT 'guard',
                token_hash VARCHAR(64) NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'pending',
                invited_by VARCHAR(36) NOT NULL,
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                accepted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                accepted_user_id VARCHAR(36) DEFAULT NULL,
                revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                personal_message TEXT DEFAULT NULL,
                resend_count INTEGER NOT NULL DEFAULT 0,
                last_resent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ti_tenant ON tenant_invitations (tenant_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ti_email ON tenant_invitations (email)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ti_token ON tenant_invitations (token_hash)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ti_status ON tenant_invitations (status)');
        $this->addSql('ALTER TABLE tenant_invitations ADD CONSTRAINT fk_ti_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tenant_invitations ADD CONSTRAINT fk_ti_invited_by FOREIGN KEY (invited_by) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS tenant_invitations');
    }
}
