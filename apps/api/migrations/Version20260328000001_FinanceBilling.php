<?php
declare(strict_types=1);
namespace Guard51\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328000001_FinanceBilling extends AbstractMigration
{
    public function getDescription(): string { return 'Phase 5: invoices, invoice_items, invoice_payments, payroll_periods, payroll_items, pay_rate_multipliers, payslips'; }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS invoices (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, client_id VARCHAR(36) NOT NULL,
            invoice_number VARCHAR(50) NOT NULL, type VARCHAR(10) NOT NULL DEFAULT 'invoice',
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            issue_date DATE NOT NULL, due_date DATE NOT NULL,
            subtotal DECIMAL(12,2) NOT NULL DEFAULT 0, tax_rate DECIMAL(5,2) NOT NULL DEFAULT 7.50,
            tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0, total DECIMAL(12,2) NOT NULL DEFAULT 0,
            amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0, balance_due DECIMAL(12,2) NOT NULL DEFAULT 0,
            currency VARCHAR(3) NOT NULL DEFAULT 'NGN',
            notes TEXT DEFAULT NULL, payment_terms TEXT DEFAULT NULL,
            created_by VARCHAR(36) NOT NULL, sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_inv_tenant ON invoices (tenant_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_inv_client ON invoices (client_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_inv_status ON invoices (status)');

        $this->addSql("CREATE TABLE IF NOT EXISTS invoice_items (
            id VARCHAR(36) NOT NULL, invoice_id VARCHAR(36) NOT NULL,
            description VARCHAR(500) NOT NULL, quantity DECIMAL(10,2) NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL, amount DECIMAL(12,2) NOT NULL,
            is_taxable BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ii_invoice ON invoice_items (invoice_id)');
        $this->addSql('ALTER TABLE invoice_items ADD CONSTRAINT fk_ii_invoice FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE CASCADE');

        $this->addSql("CREATE TABLE IF NOT EXISTS invoice_payments (
            id VARCHAR(36) NOT NULL, invoice_id VARCHAR(36) NOT NULL,
            amount DECIMAL(12,2) NOT NULL, payment_method VARCHAR(20) NOT NULL,
            reference VARCHAR(100) DEFAULT NULL, proof_url VARCHAR(500) DEFAULT NULL,
            received_by VARCHAR(36) NOT NULL, payment_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            notes TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ip_invoice ON invoice_payments (invoice_id)');
        $this->addSql('ALTER TABLE invoice_payments ADD CONSTRAINT fk_ip_invoice FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE CASCADE');

        $this->addSql("CREATE TABLE IF NOT EXISTS payroll_periods (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
            period_start DATE NOT NULL, period_end DATE NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            total_gross DECIMAL(12,2) NOT NULL DEFAULT 0, total_deductions DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_net DECIMAL(12,2) NOT NULL DEFAULT 0,
            approved_by VARCHAR(36) DEFAULT NULL, approved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pp_tenant ON payroll_periods (tenant_id)');

        $this->addSql("CREATE TABLE IF NOT EXISTS payroll_items (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
            payroll_period_id VARCHAR(36) NOT NULL, guard_id VARCHAR(36) NOT NULL,
            regular_hours DECIMAL(6,2) NOT NULL DEFAULT 0, overtime_hours DECIMAL(6,2) NOT NULL DEFAULT 0,
            holiday_hours DECIMAL(6,2) NOT NULL DEFAULT 0,
            regular_rate DECIMAL(10,2) NOT NULL, overtime_rate DECIMAL(10,2) NOT NULL,
            holiday_rate DECIMAL(10,2) NOT NULL,
            gross_pay DECIMAL(10,2) NOT NULL DEFAULT 0, deductions JSONB NOT NULL DEFAULT '{}',
            net_pay DECIMAL(10,2) NOT NULL DEFAULT 0, status VARCHAR(20) NOT NULL DEFAULT 'pending',
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pi_period ON payroll_items (payroll_period_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pi_guard ON payroll_items (guard_id)');
        $this->addSql('ALTER TABLE payroll_items ADD CONSTRAINT fk_pi_period FOREIGN KEY (payroll_period_id) REFERENCES payroll_periods (id) ON DELETE CASCADE');

        $this->addSql("CREATE TABLE IF NOT EXISTS pay_rate_multipliers (
            id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
            name VARCHAR(100) NOT NULL, multiplier DECIMAL(4,2) NOT NULL,
            applies_to VARCHAR(20) NOT NULL, is_active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_prm_tenant ON pay_rate_multipliers (tenant_id)');

        $this->addSql("CREATE TABLE IF NOT EXISTS payslips (
            id VARCHAR(36) NOT NULL, payroll_item_id VARCHAR(36) NOT NULL,
            guard_id VARCHAR(36) NOT NULL, period_start DATE NOT NULL, period_end DATE NOT NULL,
            gross_pay DECIMAL(10,2) NOT NULL, deductions_breakdown JSONB NOT NULL DEFAULT '{}',
            net_pay DECIMAL(10,2) NOT NULL, pdf_url VARCHAR(500) DEFAULT NULL,
            emailed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ps_guard ON payslips (guard_id)');
        $this->addSql('ALTER TABLE payslips ADD CONSTRAINT fk_ps_item FOREIGN KEY (payroll_item_id) REFERENCES payroll_items (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        foreach (['payslips','pay_rate_multipliers','payroll_items','payroll_periods','invoice_payments','invoice_items','invoices'] as $t) {
            $this->addSql("DROP TABLE IF EXISTS {$t}");
        }
    }
}
