<?php
declare(strict_types=1);
namespace Guard51\Migrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402000001_EncryptedColumns extends AbstractMigration
{
    public function getDescription(): string { return 'Widen PII columns for encrypted data storage'; }

    public function up(Schema $schema): void
    {
        // Encrypted base64 is ~2x plaintext + 'enc:' prefix + nonce + tag
        // A 50-char phone becomes ~120 chars encrypted. Use 500 to be safe.
        $this->addSql('ALTER TABLE guards ALTER COLUMN phone TYPE VARCHAR(500)');
        $this->addSql('ALTER TABLE guards ALTER COLUMN bank_account_number TYPE VARCHAR(500)');
        $this->addSql('ALTER TABLE guards ALTER COLUMN bank_account_name TYPE VARCHAR(500)');
        $this->addSql('ALTER TABLE guards ALTER COLUMN emergency_contact_phone TYPE VARCHAR(500)');
        $this->addSql('ALTER TABLE users ALTER COLUMN phone TYPE VARCHAR(500)');
        // Add password_changed_at for password policy
        $this->addSql('ALTER TABLE users ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guards ALTER COLUMN phone TYPE VARCHAR(50)');
        $this->addSql('ALTER TABLE guards ALTER COLUMN bank_account_number TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE guards ALTER COLUMN bank_account_name TYPE VARCHAR(200)');
        $this->addSql('ALTER TABLE guards ALTER COLUMN emergency_contact_phone TYPE VARCHAR(50)');
        $this->addSql('ALTER TABLE users ALTER COLUMN phone TYPE VARCHAR(50)');
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS password_changed_at');
    }
}
