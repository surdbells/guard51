<?php
declare(strict_types=1);
namespace Guard51\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328000005_Permissions extends AbstractMigration
{
    public function getDescription(): string { return 'Granular module permissions per user'; }
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE permissions (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NOT NULL,
            module_key VARCHAR(100) NOT NULL,
            can_view BOOLEAN NOT NULL DEFAULT FALSE, can_create BOOLEAN NOT NULL DEFAULT FALSE,
            can_edit BOOLEAN NOT NULL DEFAULT FALSE, can_delete BOOLEAN NOT NULL DEFAULT FALSE,
            can_export BOOLEAN NOT NULL DEFAULT FALSE, can_approve BOOLEAN NOT NULL DEFAULT FALSE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id), CONSTRAINT uq_perm_user_module UNIQUE (user_id, module_key))");
        $this->addSql('CREATE INDEX idx_perm_tenant ON permissions (tenant_id)');
        $this->addSql('CREATE INDEX idx_perm_user ON permissions (user_id)');
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE IF EXISTS permissions'); }
}
