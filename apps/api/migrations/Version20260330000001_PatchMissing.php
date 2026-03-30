<?php
declare(strict_types=1);
namespace Guard51\Migrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330000001_PatchMissing extends AbstractMigration
{
    public function getDescription(): string { return 'Patch: add missing columns found during production audit'; }
    public function up(Schema $schema): void
    {
        // ShiftTemplate.color — missing from initial migration
        $this->addSql("ALTER TABLE shift_templates ADD COLUMN IF NOT EXISTS color VARCHAR(20) DEFAULT NULL");
        
        // Ensure BillingType column can hold new values (varchar already, just data)
        // No DDL change needed for enum expansion — it's a varchar in PostgreSQL
    }
    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE shift_templates DROP COLUMN IF EXISTS color");
    }
}
