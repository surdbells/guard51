<?php

declare(strict_types=1);

namespace Guard51\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Phase 0D: Feature Modules & Subscription Engine
 * Creates: feature_modules, tenant_feature_modules, subscription_plans,
 *          subscriptions, subscription_invoices, tenant_usage_metrics
 */
final class Version20260324000002_FeatureSubscription extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 0D: Create feature module, subscription plan, subscription, invoice, and usage tables';
    }

    public function up(Schema $schema): void
    {
        // ── Feature Modules ──────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS feature_modules (
                id VARCHAR(36) NOT NULL,
                module_key VARCHAR(100) NOT NULL,
                name VARCHAR(200) NOT NULL,
                description TEXT DEFAULT NULL,
                category VARCHAR(50) NOT NULL,
                minimum_tier VARCHAR(30) NOT NULL DEFAULT 'all',
                is_core BOOLEAN NOT NULL DEFAULT FALSE,
                dependencies JSONB NOT NULL DEFAULT '[]',
                tenant_types JSONB NOT NULL DEFAULT '[\"private_security\",\"state_police\",\"neighborhood_watch\",\"lg_security\",\"nscdc\"]',
                sort_order INTEGER NOT NULL DEFAULT 0,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE UNIQUE INDEX uq_fm_key ON feature_modules (module_key)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_fm_category ON feature_modules (category)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_fm_tier ON feature_modules (minimum_tier)');

        // ── Tenant Feature Modules ───────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS tenant_feature_modules (
                id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NOT NULL,
                module_key VARCHAR(100) NOT NULL,
                is_enabled BOOLEAN NOT NULL DEFAULT TRUE,
                enabled_by VARCHAR(50) DEFAULT NULL,
                enabled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                disabled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE UNIQUE INDEX uq_tfm_tenant_module ON tenant_feature_modules (tenant_id, module_key)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_tfm_tenant ON tenant_feature_modules (tenant_id)');
        $this->addSql('ALTER TABLE tenant_feature_modules ADD CONSTRAINT fk_tfm_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');

        // ── Subscription Plans ───────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS subscription_plans (
                id VARCHAR(36) NOT NULL,
                name VARCHAR(200) NOT NULL,
                description TEXT DEFAULT NULL,
                tier VARCHAR(30) NOT NULL,
                monthly_price DECIMAL(12,2) NOT NULL,
                annual_price DECIMAL(12,2) DEFAULT NULL,
                currency VARCHAR(3) NOT NULL DEFAULT 'NGN',
                max_guards INTEGER NOT NULL DEFAULT 25,
                max_sites INTEGER NOT NULL DEFAULT 5,
                max_clients INTEGER NOT NULL DEFAULT 5,
                max_staff INTEGER DEFAULT NULL,
                included_modules JSONB NOT NULL DEFAULT '[]',
                tenant_types JSONB NOT NULL DEFAULT '[\"private_security\"]',
                feature_flags JSONB NOT NULL DEFAULT '{}',
                is_custom BOOLEAN NOT NULL DEFAULT FALSE,
                private_tenant_id VARCHAR(36) DEFAULT NULL,
                paystack_plan_code VARCHAR(100) DEFAULT NULL,
                trial_days INTEGER NOT NULL DEFAULT 14,
                sort_order INTEGER NOT NULL DEFAULT 0,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_sp_tier ON subscription_plans (tier)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_sp_active ON subscription_plans (is_active)');

        // ── Subscriptions ────────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS subscriptions (
                id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NOT NULL,
                plan_id VARCHAR(36) NOT NULL,
                billing_cycle VARCHAR(20) NOT NULL DEFAULT 'monthly',
                amount DECIMAL(12,2) NOT NULL,
                currency VARCHAR(3) NOT NULL DEFAULT 'NGN',
                status VARCHAR(30) NOT NULL DEFAULT 'pending',
                payment_method VARCHAR(30) NOT NULL DEFAULT 'paystack',
                paystack_subscription_code VARCHAR(200) DEFAULT NULL,
                paystack_customer_code VARCHAR(200) DEFAULT NULL,
                paystack_authorization_code VARCHAR(200) DEFAULT NULL,
                current_period_start DATE DEFAULT NULL,
                current_period_end DATE DEFAULT NULL,
                trial_ends_at DATE DEFAULT NULL,
                cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                cancellation_reason VARCHAR(500) DEFAULT NULL,
                bank_transfer_reference VARCHAR(200) DEFAULT NULL,
                bank_transfer_proof_url VARCHAR(500) DEFAULT NULL,
                payment_confirmed_by VARCHAR(36) DEFAULT NULL,
                payment_confirmed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_sub_tenant ON subscriptions (tenant_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_sub_status ON subscriptions (status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_sub_plan ON subscriptions (plan_id)');
        $this->addSql('ALTER TABLE subscriptions ADD CONSTRAINT fk_sub_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subscriptions ADD CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans (id)');

        // ── Subscription Invoices ────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS subscription_invoices (
                id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NOT NULL,
                subscription_id VARCHAR(36) NOT NULL,
                invoice_number VARCHAR(50) NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                currency VARCHAR(3) NOT NULL DEFAULT 'NGN',
                status VARCHAR(30) NOT NULL DEFAULT 'pending',
                payment_method VARCHAR(30) NOT NULL,
                paystack_reference VARCHAR(200) DEFAULT NULL,
                bank_transfer_reference VARCHAR(200) DEFAULT NULL,
                bank_transfer_proof_url VARCHAR(500) DEFAULT NULL,
                period_start DATE NOT NULL,
                period_end DATE NOT NULL,
                due_date DATE NOT NULL,
                paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                confirmed_by VARCHAR(36) DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_si_tenant ON subscription_invoices (tenant_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_si_subscription ON subscription_invoices (subscription_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_si_status ON subscription_invoices (status)');
        $this->addSql('ALTER TABLE subscription_invoices ADD CONSTRAINT fk_si_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subscription_invoices ADD CONSTRAINT fk_si_subscription FOREIGN KEY (subscription_id) REFERENCES subscriptions (id) ON DELETE CASCADE');

        // ── Tenant Usage Metrics ─────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS tenant_usage_metrics (
                id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NOT NULL,
                guards_count INTEGER NOT NULL DEFAULT 0,
                sites_count INTEGER NOT NULL DEFAULT 0,
                clients_count INTEGER NOT NULL DEFAULT 0,
                staff_count INTEGER NOT NULL DEFAULT 0,
                reports_this_month INTEGER NOT NULL DEFAULT 0,
                storage_used_bytes INTEGER NOT NULL DEFAULT 0,
                sms_used_this_month INTEGER NOT NULL DEFAULT 0,
                last_recalculated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql('CREATE UNIQUE INDEX uq_tum_tenant ON tenant_usage_metrics (tenant_id)');
        $this->addSql('ALTER TABLE tenant_usage_metrics ADD CONSTRAINT fk_tum_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS tenant_usage_metrics');
        $this->addSql('DROP TABLE IF EXISTS subscription_invoices');
        $this->addSql('DROP TABLE IF EXISTS subscriptions');
        $this->addSql('DROP TABLE IF EXISTS subscription_plans');
        $this->addSql('DROP TABLE IF EXISTS tenant_feature_modules');
        $this->addSql('DROP TABLE IF EXISTS feature_modules');
    }
}
