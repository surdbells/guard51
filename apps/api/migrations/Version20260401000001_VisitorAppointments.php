<?php
declare(strict_types=1);
namespace Guard51\Migrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401000001_VisitorAppointments extends AbstractMigration
{
    public function getDescription(): string { return 'Create visitor_appointments table'; }
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE IF NOT EXISTS visitor_appointments (
                id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NOT NULL,
                site_id VARCHAR(36) NOT NULL,
                host_user_id VARCHAR(36) DEFAULT NULL,
                host_name VARCHAR(200) NOT NULL,
                host_email VARCHAR(255) DEFAULT NULL,
                host_phone VARCHAR(50) DEFAULT NULL,
                visitor_name VARCHAR(200) NOT NULL,
                visitor_email VARCHAR(255) DEFAULT NULL,
                visitor_phone VARCHAR(50) DEFAULT NULL,
                visitor_company VARCHAR(200) DEFAULT NULL,
                purpose VARCHAR(200) NOT NULL,
                scheduled_date DATE NOT NULL,
                scheduled_time VARCHAR(10) DEFAULT NULL,
                access_code VARCHAR(10) NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'pending',
                notify_sms BOOLEAN NOT NULL DEFAULT FALSE,
                notify_email BOOLEAN NOT NULL DEFAULT TRUE,
                notify_whatsapp BOOLEAN NOT NULL DEFAULT FALSE,
                checked_in_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                checked_out_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                checked_in_by VARCHAR(36) DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                created_by VARCHAR(36) NOT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE UNIQUE INDEX uq_va_code ON visitor_appointments (access_code)');
        $this->addSql('CREATE INDEX idx_va_tenant ON visitor_appointments (tenant_id)');
        $this->addSql('CREATE INDEX idx_va_site ON visitor_appointments (site_id)');
        $this->addSql('CREATE INDEX idx_va_date ON visitor_appointments (scheduled_date)');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS visitor_appointments');
    }
}
