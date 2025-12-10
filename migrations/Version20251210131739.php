<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210131739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE activity (id INT AUTO_INCREMENT NOT NULL, workspace_id INT NOT NULL, deal_id INT DEFAULT NULL, contact_id INT DEFAULT NULL, company_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, location VARCHAR(255) NOT NULL, performed TINYINT(1) NOT NULL, notify TINYINT(1) NOT NULL, notify_date DATETIME DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, managers JSON NOT NULL, name VARCHAR(255) NOT NULL, uuid CHAR(36) DEFAULT NULL COMMENT '(DC2Type:uuid)', created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, create_by VARCHAR(255) DEFAULT NULL, update_by VARCHAR(255) DEFAULT NULL, remove_at DATETIME DEFAULT NULL, remove_by VARCHAR(255) DEFAULT NULL, created_from_ip VARCHAR(45) DEFAULT NULL, updated_from_ip VARCHAR(45) DEFAULT NULL, restored_at DATETIME DEFAULT NULL, restored_by VARCHAR(255) DEFAULT NULL, INDEX IDX_AC74095A82D40A1F (workspace_id), INDEX IDX_AC74095AF60E2305 (deal_id), INDEX IDX_AC74095AE7A1254A (contact_id), INDEX IDX_AC74095A979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE asset (id INT AUTO_INCREMENT NOT NULL, contact_id INT DEFAULT NULL, company_id INT DEFAULT NULL, deal_id INT DEFAULT NULL, src VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_2AF5A5CE7A1254A (contact_id), INDEX IDX_2AF5A5C979B1AD6 (company_id), INDEX IDX_2AF5A5CF60E2305 (deal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE company (id INT AUTO_INCREMENT NOT NULL, workspace_id INT NOT NULL, item_type_id INT DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, manager VARCHAR(255) DEFAULT NULL, uuid CHAR(36) DEFAULT NULL COMMENT '(DC2Type:uuid)', created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, create_by VARCHAR(255) DEFAULT NULL, update_by VARCHAR(255) DEFAULT NULL, remove_at DATETIME DEFAULT NULL, remove_by VARCHAR(255) DEFAULT NULL, created_from_ip VARCHAR(45) DEFAULT NULL, updated_from_ip VARCHAR(45) DEFAULT NULL, restored_at DATETIME DEFAULT NULL, restored_by VARCHAR(255) DEFAULT NULL, INDEX IDX_4FBF094F82D40A1F (workspace_id), INDEX IDX_4FBF094FCE11AAC7 (item_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, workspace_id INT NOT NULL, company_id INT DEFAULT NULL, item_type_id INT DEFAULT NULL, source VARCHAR(255) NOT NULL, manager VARCHAR(255) DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, uuid CHAR(36) DEFAULT NULL COMMENT '(DC2Type:uuid)', created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, create_by VARCHAR(255) DEFAULT NULL, update_by VARCHAR(255) DEFAULT NULL, remove_at DATETIME DEFAULT NULL, remove_by VARCHAR(255) DEFAULT NULL, created_from_ip VARCHAR(45) DEFAULT NULL, updated_from_ip VARCHAR(45) DEFAULT NULL, restored_at DATETIME DEFAULT NULL, restored_by VARCHAR(255) DEFAULT NULL, INDEX IDX_4C62E63882D40A1F (workspace_id), INDEX IDX_4C62E638979B1AD6 (company_id), INDEX IDX_4C62E638CE11AAC7 (item_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE deal (id INT AUTO_INCREMENT NOT NULL, workspace_id INT NOT NULL, contact_id INT DEFAULT NULL, step_id INT DEFAULT NULL, company_id INT DEFAULT NULL, object VARCHAR(255) NOT NULL, manager VARCHAR(255) NOT NULL, products JSON DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, uuid CHAR(36) DEFAULT NULL COMMENT '(DC2Type:uuid)', created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, create_by VARCHAR(255) DEFAULT NULL, update_by VARCHAR(255) DEFAULT NULL, remove_at DATETIME DEFAULT NULL, remove_by VARCHAR(255) DEFAULT NULL, created_from_ip VARCHAR(45) DEFAULT NULL, updated_from_ip VARCHAR(45) DEFAULT NULL, restored_at DATETIME DEFAULT NULL, restored_by VARCHAR(255) DEFAULT NULL, INDEX IDX_E3FEC11682D40A1F (workspace_id), INDEX IDX_E3FEC116E7A1254A (contact_id), INDEX IDX_E3FEC11673B21E9C (step_id), INDEX IDX_E3FEC116979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE deal_contact (deal_id INT NOT NULL, contact_id INT NOT NULL, INDEX IDX_A8786EB0F60E2305 (deal_id), INDEX IDX_A8786EB0E7A1254A (contact_id), PRIMARY KEY(deal_id, contact_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE device (id INT AUTO_INCREMENT NOT NULL, mac VARCHAR(255) DEFAULT NULL, brand VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, os VARCHAR(255) DEFAULT NULL, browser VARCHAR(255) DEFAULT NULL, model VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE dunning_attempt (id INT AUTO_INCREMENT NOT NULL, subscription_id INT NOT NULL, attempt_number INT NOT NULL, action VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, scheduled_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', executed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', resolved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', message LONGTEXT DEFAULT NULL, metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX idx_subscription (subscription_id), INDEX idx_status (status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE email_verification_tokens (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, is_used TINYINT(1) NOT NULL, used_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_C81CA2AC5F37A13B (token), INDEX IDX_C81CA2ACA76ED395 (user_id), INDEX idx_email_verification_token (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, subscription_id INT NOT NULL, invoice_number VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, subtotal NUMERIC(10, 2) NOT NULL, tax NUMERIC(10, 2) NOT NULL, total NUMERIC(10, 2) NOT NULL, tax_rate NUMERIC(5, 2) NOT NULL, currency VARCHAR(3) NOT NULL, line_items JSON NOT NULL, invoice_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', due_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', paid_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', voided_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', customer_name VARCHAR(255) DEFAULT NULL, customer_email VARCHAR(255) DEFAULT NULL, customer_address LONGTEXT DEFAULT NULL, customer_vat_number VARCHAR(50) DEFAULT NULL, stripe_invoice_id VARCHAR(255) DEFAULT NULL, stripe_hosted_invoice_url VARCHAR(500) DEFAULT NULL, stripe_invoice_pdf VARCHAR(500) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_906517442DA68207 (invoice_number), INDEX IDX_906517449A1887DC (subscription_id), INDEX idx_invoice_number (invoice_number), INDEX idx_status (status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE item_type (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE location (id INT AUTO_INCREMENT NOT NULL, country VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, region VARCHAR(255) DEFAULT NULL, company VARCHAR(255) DEFAULT NULL, vpn TINYINT(1) DEFAULT NULL, proxy TINYINT(1) DEFAULT NULL, tor TINYINT(1) DEFAULT NULL, ip VARCHAR(255) NOT NULL, country_code VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE login_history (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, success TINYINT(1) NOT NULL, ip_address VARCHAR(45) NOT NULL, user_agent VARCHAR(255) DEFAULT NULL, failure_reason VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, location VARCHAR(50) DEFAULT NULL, device VARCHAR(50) DEFAULT NULL, INDEX IDX_37976E36A76ED395 (user_id), INDEX idx_user_login_history (user_id, created_at), INDEX idx_ip_login_attempts (ip_address, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mail (id INT AUTO_INCREMENT NOT NULL, contact_id INT DEFAULT NULL, company_id INT DEFAULT NULL, email VARCHAR(255) NOT NULL, type VARCHAR(255) DEFAULT NULL, INDEX IDX_5126AC48E7A1254A (contact_id), INDEX IDX_5126AC48979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, workspace_id INT NOT NULL, deal_id INT DEFAULT NULL, activity_id INT DEFAULT NULL, company_id INT DEFAULT NULL, contact_id INT DEFAULT NULL, content LONGTEXT NOT NULL, uuid CHAR(36) DEFAULT NULL COMMENT '(DC2Type:uuid)', created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, create_by VARCHAR(255) DEFAULT NULL, update_by VARCHAR(255) DEFAULT NULL, remove_at DATETIME DEFAULT NULL, remove_by VARCHAR(255) DEFAULT NULL, created_from_ip VARCHAR(45) DEFAULT NULL, updated_from_ip VARCHAR(45) DEFAULT NULL, restored_at DATETIME DEFAULT NULL, restored_by VARCHAR(255) DEFAULT NULL, INDEX IDX_CFBDFA1482D40A1F (workspace_id), INDEX IDX_CFBDFA14F60E2305 (deal_id), INDEX IDX_CFBDFA1481C06096 (activity_id), INDEX IDX_CFBDFA14979B1AD6 (company_id), INDEX IDX_CFBDFA14E7A1254A (contact_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE onboarding_step (id INT AUTO_INCREMENT NOT NULL, tenant_id VARCHAR(100) NOT NULL, step_type VARCHAR(50) NOT NULL, step_name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, order_index INT NOT NULL, status VARCHAR(20) NOT NULL, is_required TINYINT(1) NOT NULL, started_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', completed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX idx_tenant (tenant_id), INDEX idx_status (status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE password_reset_tokens (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, is_used TINYINT(1) NOT NULL, used_at DATETIME DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, UNIQUE INDEX UNIQ_3967A2165F37A13B (token), INDEX IDX_3967A216A76ED395 (user_id), INDEX idx_password_reset_token (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, invoice_id INT NOT NULL, status VARCHAR(20) NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, payment_method VARCHAR(30) NOT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, stripe_charge_id VARCHAR(255) DEFAULT NULL, receipt_url VARCHAR(500) DEFAULT NULL, failure_reason LONGTEXT DEFAULT NULL, paid_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', failed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', refunded_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_6D28840D2989F1FD (invoice_id), INDEX idx_status (status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE phone_number (id INT AUTO_INCREMENT NOT NULL, contact_id INT DEFAULT NULL, company_id INT DEFAULT NULL, number VARCHAR(255) NOT NULL, type VARCHAR(255) DEFAULT NULL, INDEX IDX_6B01BC5BE7A1254A (contact_id), INDEX IDX_6B01BC5B979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE pipeline (id INT AUTO_INCREMENT NOT NULL, workspace_id INT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, roles JSON DEFAULT NULL, uuid CHAR(36) DEFAULT NULL COMMENT '(DC2Type:uuid)', created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, create_by VARCHAR(255) DEFAULT NULL, update_by VARCHAR(255) DEFAULT NULL, remove_at DATETIME DEFAULT NULL, remove_by VARCHAR(255) DEFAULT NULL, created_from_ip VARCHAR(45) DEFAULT NULL, updated_from_ip VARCHAR(45) DEFAULT NULL, restored_at DATETIME DEFAULT NULL, restored_by VARCHAR(255) DEFAULT NULL, INDEX IDX_7DFCD9D982D40A1F (workspace_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE pipeline_step (id INT AUTO_INCREMENT NOT NULL, pipeline_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, success_probability DOUBLE PRECISION DEFAULT NULL, color VARCHAR(255) NOT NULL, ranking VARCHAR(255) DEFAULT NULL, code VARCHAR(255) DEFAULT NULL, uuid CHAR(36) DEFAULT NULL COMMENT '(DC2Type:uuid)', created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, create_by VARCHAR(255) DEFAULT NULL, update_by VARCHAR(255) DEFAULT NULL, remove_at DATETIME DEFAULT NULL, remove_by VARCHAR(255) DEFAULT NULL, created_from_ip VARCHAR(45) DEFAULT NULL, updated_from_ip VARCHAR(45) DEFAULT NULL, restored_at DATETIME DEFAULT NULL, restored_by VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_E76AE81377153098 (code), INDEX IDX_E76AE813E80B93 (pipeline_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE plan (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(100) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, billing_interval VARCHAR(20) NOT NULL, trial_days INT DEFAULT NULL, is_active TINYINT(1) NOT NULL, is_public TINYINT(1) NOT NULL, sort_order INT NOT NULL, max_contacts INT DEFAULT NULL, max_companies INT DEFAULT NULL, max_deals INT DEFAULT NULL, max_users INT DEFAULT NULL, max_storage_bytes BIGINT DEFAULT NULL, max_api_calls_per_day INT DEFAULT NULL, features JSON NOT NULL, stripe_price_id VARCHAR(255) DEFAULT NULL, stripe_product_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_DD5A5B7D77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE property (id INT AUTO_INCREMENT NOT NULL, contact_id INT DEFAULT NULL, company_id INT DEFAULT NULL, property_model_id INT DEFAULT NULL, value VARCHAR(255) NOT NULL, INDEX IDX_8BF21CDEE7A1254A (contact_id), INDEX IDX_8BF21CDE979B1AD6 (company_id), INDEX IDX_8BF21CDE27F283A3 (property_model_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE property_model (id INT AUTO_INCREMENT NOT NULL, item_type_id INT DEFAULT NULL, label VARCHAR(255) NOT NULL, identifier TINYINT(1) NOT NULL, type VARCHAR(255) NOT NULL, class VARCHAR(255) DEFAULT NULL, INDEX IDX_1186B3D4CE11AAC7 (item_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE quota (id INT AUTO_INCREMENT NOT NULL, tenant_id VARCHAR(100) NOT NULL, quota_type VARCHAR(50) NOT NULL, limit_value BIGINT DEFAULT NULL, current_usage BIGINT NOT NULL, is_hard_limit TINYINT(1) NOT NULL, notify_at_threshold TINYINT(1) NOT NULL, notification_threshold INT NOT NULL, last_notified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', last_reset_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', reset_interval VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX unique_tenant_quota (tenant_id, quota_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE refresh_tokens (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(128) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, is_revoked TINYINT(1) NOT NULL, user_agent VARCHAR(255) DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, device_fingerprint VARCHAR(64) DEFAULT NULL, UNIQUE INDEX UNIQ_9BACE7E15F37A13B (token), INDEX IDX_9BACE7E1A76ED395 (user_id), INDEX idx_refresh_token (token), INDEX idx_user_active_tokens (user_id, is_revoked), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, plan_id INT NOT NULL, tenant_id VARCHAR(100) DEFAULT NULL, status VARCHAR(20) NOT NULL, start_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', trial_ends_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', current_period_start DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', current_period_end DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', canceled_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', cancels_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', cancel_at_period_end TINYINT(1) NOT NULL, stripe_subscription_id VARCHAR(255) DEFAULT NULL, stripe_customer_id VARCHAR(255) DEFAULT NULL, stripe_payment_method_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_A3C664D3E899029B (plan_id), INDEX idx_status (status), INDEX idx_tenant (tenant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE support_ticket (id INT AUTO_INCREMENT NOT NULL, ticket_number VARCHAR(50) NOT NULL, tenant_id VARCHAR(100) NOT NULL, subject VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, category VARCHAR(50) NOT NULL, priority VARCHAR(20) NOT NULL, status VARCHAR(30) NOT NULL, requester_email VARCHAR(255) NOT NULL, requester_name VARCHAR(255) DEFAULT NULL, assigned_to_user_id INT DEFAULT NULL, assigned_to_name VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', first_response_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', resolved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', closed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', tags JSON DEFAULT NULL, metadata JSON DEFAULT NULL, UNIQUE INDEX UNIQ_1F5A4D53ECD2759F (ticket_number), INDEX idx_tenant (tenant_id), INDEX idx_status (status), INDEX idx_priority (priority), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE support_ticket_message (id INT AUTO_INCREMENT NOT NULL, ticket_id INT NOT NULL, message LONGTEXT NOT NULL, is_from_agent TINYINT(1) NOT NULL, author_name VARCHAR(255) DEFAULT NULL, author_email VARCHAR(255) DEFAULT NULL, is_internal TINYINT(1) NOT NULL, attachments JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_73251A5C700047D2 (ticket_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, color VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tag_deal (tag_id INT NOT NULL, deal_id INT NOT NULL, INDEX IDX_DF17EA0BAD26311 (tag_id), INDEX IDX_DF17EA0F60E2305 (deal_id), PRIMARY KEY(tag_id, deal_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tag_contact (tag_id INT NOT NULL, contact_id INT NOT NULL, INDEX IDX_7E53CB92BAD26311 (tag_id), INDEX IDX_7E53CB92E7A1254A (contact_id), PRIMARY KEY(tag_id, contact_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tag_company (tag_id INT NOT NULL, company_id INT NOT NULL, INDEX IDX_7D8E24E5BAD26311 (tag_id), INDEX IDX_7D8E24E5979B1AD6 (company_id), PRIMARY KEY(tag_id, company_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE usage_metric (id INT AUTO_INCREMENT NOT NULL, subscription_id INT NOT NULL, metric_type VARCHAR(50) NOT NULL, value BIGINT NOT NULL, `limit` BIGINT DEFAULT NULL, metric_date DATE NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_DD593F9B9A1887DC (subscription_id), INDEX idx_subscription_date (subscription_id, metric_date), INDEX idx_metric_type (metric_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, current_workspace_id INT DEFAULT NULL, uuid CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)', email VARCHAR(180) NOT NULL, code VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, godfather VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649D17F50A6 (uuid), UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D64977153098 (code), INDEX IDX_8D93D6497D65B4C4 (current_workspace_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE workspace (id INT AUTO_INCREMENT NOT NULL, uuid CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)', name VARCHAR(255) NOT NULL, slug VARCHAR(100) NOT NULL, logo VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, industry VARCHAR(100) DEFAULT NULL, employee_count INT DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, address LONGTEXT DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, country VARCHAR(100) DEFAULT NULL, siret VARCHAR(20) DEFAULT NULL, vat_number VARCHAR(20) DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D940019D17F50A6 (uuid), UNIQUE INDEX UNIQ_8D940019989D9B62 (slug), INDEX idx_slug (slug), INDEX idx_is_active (is_active), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE workspace_member (id INT AUTO_INCREMENT NOT NULL, workspace_id INT NOT NULL, user_id INT NOT NULL, role VARCHAR(20) NOT NULL, is_active TINYINT(1) NOT NULL, joined_at DATETIME NOT NULL, INDEX idx_workspace (workspace_id), INDEX idx_user (user_id), INDEX idx_role (role), UNIQUE INDEX unique_workspace_user (workspace_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE activity ADD CONSTRAINT FK_AC74095A82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE activity ADD CONSTRAINT FK_AC74095AF60E2305 FOREIGN KEY (deal_id) REFERENCES deal (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE activity ADD CONSTRAINT FK_AC74095AE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE activity ADD CONSTRAINT FK_AC74095A979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset ADD CONSTRAINT FK_2AF5A5CE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset ADD CONSTRAINT FK_2AF5A5C979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset ADD CONSTRAINT FK_2AF5A5CF60E2305 FOREIGN KEY (deal_id) REFERENCES deal (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company ADD CONSTRAINT FK_4FBF094F82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company ADD CONSTRAINT FK_4FBF094FCE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact ADD CONSTRAINT FK_4C62E63882D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact ADD CONSTRAINT FK_4C62E638979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact ADD CONSTRAINT FK_4C62E638CE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE deal ADD CONSTRAINT FK_E3FEC11682D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE deal ADD CONSTRAINT FK_E3FEC116E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE deal ADD CONSTRAINT FK_E3FEC11673B21E9C FOREIGN KEY (step_id) REFERENCES pipeline_step (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE deal ADD CONSTRAINT FK_E3FEC116979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE deal_contact ADD CONSTRAINT FK_A8786EB0F60E2305 FOREIGN KEY (deal_id) REFERENCES deal (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE deal_contact ADD CONSTRAINT FK_A8786EB0E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dunning_attempt ADD CONSTRAINT FK_9118DBD69A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE email_verification_tokens ADD CONSTRAINT FK_C81CA2ACA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE invoice ADD CONSTRAINT FK_906517449A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE login_history ADD CONSTRAINT FK_37976E36A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mail ADD CONSTRAINT FK_5126AC48E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mail ADD CONSTRAINT FK_5126AC48979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD CONSTRAINT FK_CFBDFA1482D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14F60E2305 FOREIGN KEY (deal_id) REFERENCES deal (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD CONSTRAINT FK_CFBDFA1481C06096 FOREIGN KEY (activity_id) REFERENCES activity (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE password_reset_tokens ADD CONSTRAINT FK_3967A216A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payment ADD CONSTRAINT FK_6D28840D2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE phone_number ADD CONSTRAINT FK_6B01BC5BE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE phone_number ADD CONSTRAINT FK_6B01BC5B979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE pipeline ADD CONSTRAINT FK_7DFCD9D982D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE pipeline_step ADD CONSTRAINT FK_E76AE813E80B93 FOREIGN KEY (pipeline_id) REFERENCES pipeline (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE property ADD CONSTRAINT FK_8BF21CDEE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE27F283A3 FOREIGN KEY (property_model_id) REFERENCES property_model (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE property_model ADD CONSTRAINT FK_1186B3D4CE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE refresh_tokens ADD CONSTRAINT FK_9BACE7E1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES plan (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE support_ticket_message ADD CONSTRAINT FK_73251A5C700047D2 FOREIGN KEY (ticket_id) REFERENCES support_ticket (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_deal ADD CONSTRAINT FK_DF17EA0BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_deal ADD CONSTRAINT FK_DF17EA0F60E2305 FOREIGN KEY (deal_id) REFERENCES deal (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_contact ADD CONSTRAINT FK_7E53CB92BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_contact ADD CONSTRAINT FK_7E53CB92E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_company ADD CONSTRAINT FK_7D8E24E5BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_company ADD CONSTRAINT FK_7D8E24E5979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE usage_metric ADD CONSTRAINT FK_DD593F9B9A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD CONSTRAINT FK_8D93D6497D65B4C4 FOREIGN KEY (current_workspace_id) REFERENCES workspace (id) ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workspace_member ADD CONSTRAINT FK_40242BD082D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workspace_member ADD CONSTRAINT FK_40242BD0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE activity DROP FOREIGN KEY FK_AC74095A82D40A1F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE activity DROP FOREIGN KEY FK_AC74095AF60E2305
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE activity DROP FOREIGN KEY FK_AC74095AE7A1254A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE activity DROP FOREIGN KEY FK_AC74095A979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset DROP FOREIGN KEY FK_2AF5A5CE7A1254A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset DROP FOREIGN KEY FK_2AF5A5C979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset DROP FOREIGN KEY FK_2AF5A5CF60E2305
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company DROP FOREIGN KEY FK_4FBF094F82D40A1F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company DROP FOREIGN KEY FK_4FBF094FCE11AAC7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact DROP FOREIGN KEY FK_4C62E63882D40A1F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638CE11AAC7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE deal DROP FOREIGN KEY FK_E3FEC11682D40A1F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE deal DROP FOREIGN KEY FK_E3FEC116E7A1254A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE deal DROP FOREIGN KEY FK_E3FEC11673B21E9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE deal DROP FOREIGN KEY FK_E3FEC116979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE deal_contact DROP FOREIGN KEY FK_A8786EB0F60E2305
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE deal_contact DROP FOREIGN KEY FK_A8786EB0E7A1254A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dunning_attempt DROP FOREIGN KEY FK_9118DBD69A1887DC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE email_verification_tokens DROP FOREIGN KEY FK_C81CA2ACA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE invoice DROP FOREIGN KEY FK_906517449A1887DC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE login_history DROP FOREIGN KEY FK_37976E36A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mail DROP FOREIGN KEY FK_5126AC48E7A1254A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mail DROP FOREIGN KEY FK_5126AC48979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA1482D40A1F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14F60E2305
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA1481C06096
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14E7A1254A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE password_reset_tokens DROP FOREIGN KEY FK_3967A216A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D2989F1FD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE phone_number DROP FOREIGN KEY FK_6B01BC5BE7A1254A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE phone_number DROP FOREIGN KEY FK_6B01BC5B979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE pipeline DROP FOREIGN KEY FK_7DFCD9D982D40A1F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE pipeline_step DROP FOREIGN KEY FK_E76AE813E80B93
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDEE7A1254A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE27F283A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE property_model DROP FOREIGN KEY FK_1186B3D4CE11AAC7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE refresh_tokens DROP FOREIGN KEY FK_9BACE7E1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3E899029B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE support_ticket_message DROP FOREIGN KEY FK_73251A5C700047D2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_deal DROP FOREIGN KEY FK_DF17EA0BAD26311
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_deal DROP FOREIGN KEY FK_DF17EA0F60E2305
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_contact DROP FOREIGN KEY FK_7E53CB92BAD26311
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_contact DROP FOREIGN KEY FK_7E53CB92E7A1254A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_company DROP FOREIGN KEY FK_7D8E24E5BAD26311
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag_company DROP FOREIGN KEY FK_7D8E24E5979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE usage_metric DROP FOREIGN KEY FK_DD593F9B9A1887DC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP FOREIGN KEY FK_8D93D6497D65B4C4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workspace_member DROP FOREIGN KEY FK_40242BD082D40A1F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workspace_member DROP FOREIGN KEY FK_40242BD0A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE activity
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE asset
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE company
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE contact
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE deal
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE deal_contact
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE device
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE dunning_attempt
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE email_verification_tokens
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE invoice
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE item_type
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE location
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE login_history
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE mail
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE note
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE onboarding_step
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE password_reset_tokens
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE payment
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE phone_number
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE pipeline
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE pipeline_step
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE plan
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE property
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE property_model
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE quota
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE refresh_tokens
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE subscription
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE support_ticket
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE support_ticket_message
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tag
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tag_deal
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tag_contact
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tag_company
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE usage_metric
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE workspace
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE workspace_member
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
