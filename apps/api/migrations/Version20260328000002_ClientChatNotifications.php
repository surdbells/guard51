<?php
declare(strict_types=1);
namespace Guard51\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328000002_ClientChatNotifications extends AbstractMigration
{
    public function getDescription(): string { return 'Phase 6: client_users, chat_conversations, chat_participants, chat_messages, notifications, device_tokens'; }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS client_users (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
            client_id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NOT NULL,
            can_view_reports BOOLEAN NOT NULL DEFAULT TRUE, can_view_tracking BOOLEAN NOT NULL DEFAULT TRUE,
            can_view_invoices BOOLEAN NOT NULL DEFAULT TRUE, can_view_incidents BOOLEAN NOT NULL DEFAULT TRUE,
            can_message BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id), CONSTRAINT uq_cu_user UNIQUE (user_id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cu_tenant ON client_users (tenant_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cu_client ON client_users (client_id)');

        $this->addSql("CREATE TABLE IF NOT EXISTS chat_conversations (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
            type VARCHAR(20) NOT NULL, name VARCHAR(200) DEFAULT NULL,
            site_id VARCHAR(36) DEFAULT NULL, created_by VARCHAR(36) NOT NULL,
            last_message_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cc_tenant ON chat_conversations (tenant_id)');

        $this->addSql("CREATE TABLE IF NOT EXISTS chat_participants (
            id VARCHAR(36) NOT NULL, conversation_id VARCHAR(36) NOT NULL,
            user_id VARCHAR(36) NOT NULL, role VARCHAR(10) NOT NULL DEFAULT 'member',
            joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            left_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            last_read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cp_conv ON chat_participants (conversation_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cp_user ON chat_participants (user_id)');
        $this->addSql('ALTER TABLE chat_participants ADD CONSTRAINT fk_cp_conv FOREIGN KEY (conversation_id) REFERENCES chat_conversations (id) ON DELETE CASCADE');

        $this->addSql("CREATE TABLE IF NOT EXISTS chat_messages (
            id VARCHAR(36) NOT NULL, conversation_id VARCHAR(36) NOT NULL,
            sender_id VARCHAR(36) NOT NULL, content TEXT NOT NULL,
            message_type VARCHAR(10) NOT NULL DEFAULT 'text',
            media_url VARCHAR(500) DEFAULT NULL,
            latitude DECIMAL(10,8) DEFAULT NULL, longitude DECIMAL(11,8) DEFAULT NULL,
            is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cm_conv ON chat_messages (conversation_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cm_sender ON chat_messages (sender_id)');
        $this->addSql('ALTER TABLE chat_messages ADD CONSTRAINT fk_cm_conv FOREIGN KEY (conversation_id) REFERENCES chat_conversations (id) ON DELETE CASCADE');

        $this->addSql("CREATE TABLE IF NOT EXISTS notifications (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
            user_id VARCHAR(36) NOT NULL, type VARCHAR(20) NOT NULL,
            title VARCHAR(300) NOT NULL, body TEXT NOT NULL,
            data JSONB NOT NULL DEFAULT '{}', channel VARCHAR(10) NOT NULL DEFAULT 'in_app',
            is_read BOOLEAN NOT NULL DEFAULT FALSE,
            read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_notif_user ON notifications (user_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_notif_tenant ON notifications (tenant_id)');

        $this->addSql("CREATE TABLE IF NOT EXISTS device_tokens (
            id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NOT NULL,
            token VARCHAR(500) NOT NULL, platform VARCHAR(10) NOT NULL,
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_dt_user ON device_tokens (user_id)');
    }

    public function down(Schema $schema): void
    {
        foreach (['device_tokens','notifications','chat_messages','chat_participants','chat_conversations','client_users'] as $t) {
            $this->addSql("DROP TABLE IF EXISTS {$t}");
        }
    }
}
