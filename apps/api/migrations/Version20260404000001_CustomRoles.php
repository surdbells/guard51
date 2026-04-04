<?php
declare(strict_types=1);
namespace Guard51\Migrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404000001_CustomRoles extends AbstractMigration
{
    public function getDescription(): string { return 'Custom roles table for RBAC'; }
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS custom_roles (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            color VARCHAR(10) DEFAULT '#3B82F6',
            permissions TEXT DEFAULT '[]',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cr_tenant ON custom_roles (tenant_id)');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS custom_roles');
    }
}
