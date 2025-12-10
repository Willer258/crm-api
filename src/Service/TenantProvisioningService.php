<?php

namespace App\Service;

use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\User;
use App\Entity\ItemType;
use App\Entity\Pipeline;
use App\Entity\PipelineStep;
use App\MultiTenancy\Switcher;
use App\MultiTenancy\Zone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Service responsible for provisioning new tenants
 * - Creates tenant database
 * - Runs migrations
 * - Initializes default data
 * - Creates admin user
 */
class TenantProvisioningService
{
    public function __construct(
        private Connection $connection,
        private EntityManagerInterface $em,
        private Switcher $switcher,
        private Zone $zone,
        private LoggerInterface $logger,
        private string $projectDir
    ) {}

    /**
     * Provision a complete new tenant
     *
     * @param string $tenantId Unique tenant identifier (e.g., 'acme-corp')
     * @param Plan $plan The subscription plan
     * @param array $adminData Admin user data ['email', 'password', 'firstName', 'lastName']
     * @param string $zoneName Zone name (e.g., 'eu', 'us', 'wiassur')
     * @return array Result with status and details
     */
    public function provisionTenant(
        string $tenantId,
        Plan $plan,
        array $adminData,
        string $zoneName = 'wiassur'
    ): array {
        $this->logger->info('Starting tenant provisioning', [
            'tenant_id' => $tenantId,
            'zone' => $zoneName,
            'plan' => $plan->getCode()
        ]);

        try {
            // Step 1: Validate tenant ID
            $this->validateTenantId($tenantId);

            // Step 2: Create database
            $databaseName = $this->createTenantDatabase($tenantId, $zoneName);

            // Step 3: Run migrations on new database
            $this->runMigrations($tenantId, $zoneName);

            // Step 4: Initialize default data
            $this->initializeDefaultData($tenantId, $zoneName);

            // Step 5: Create admin user
            $adminUser = $this->createAdminUser($tenantId, $zoneName, $adminData);

            $this->logger->info('Tenant provisioning completed successfully', [
                'tenant_id' => $tenantId,
                'database' => $databaseName,
                'admin_email' => $adminData['email']
            ]);

            return [
                'success' => true,
                'tenant_id' => $tenantId,
                'database' => $databaseName,
                'admin_user_id' => $adminUser->getId(),
                'message' => 'Tenant provisioned successfully'
            ];

        } catch (\Exception $e) {
            $this->logger->error('Tenant provisioning failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Attempt rollback
            $this->rollbackProvisioning($tenantId, $zoneName);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Tenant provisioning failed'
            ];
        }
    }

    /**
     * Validate tenant ID format
     */
    private function validateTenantId(string $tenantId): void
    {
        // Only allow alphanumeric, hyphens, underscores (3-50 chars)
        if (!preg_match('/^[a-z0-9_-]{3,50}$/i', $tenantId)) {
            throw new \InvalidArgumentException(
                'Tenant ID must be 3-50 characters and contain only letters, numbers, hyphens, and underscores'
            );
        }

        // Check if tenant already exists
        if ($this->tenantExists($tenantId)) {
            throw new \InvalidArgumentException(
                sprintf('Tenant "%s" already exists', $tenantId)
            );
        }
    }

    /**
     * Check if tenant database already exists
     */
    private function tenantExists(string $tenantId): bool
    {
        $params = $this->connection->getParams();
        $baseName = $params['dbname'];

        // Check all possible zones
        $zones = ['wiassur', 'eu', 'us', 'main'];

        foreach ($zones as $zone) {
            $dbName = $zone . '_' . $baseName;

            try {
                $sql = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :dbname";
                $stmt = $this->connection->prepare($sql);
                $result = $stmt->executeQuery(['dbname' => $dbName]);

                if ($result->fetchOne()) {
                    return true;
                }
            } catch (\Exception $e) {
                // Continue checking other zones
            }
        }

        return false;
    }

    /**
     * Create tenant database
     */
    private function createTenantDatabase(string $tenantId, string $zoneName): string
    {
        $params = $this->connection->getParams();
        $baseName = $params['dbname'];
        $databaseName = $zoneName . '_' . $baseName;

        $this->logger->info('Creating tenant database', [
            'tenant_id' => $tenantId,
            'database' => $databaseName
        ]);

        // Create database
        $sql = sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $databaseName
        );

        $this->connection->executeStatement($sql);

        $this->logger->info('Tenant database created', [
            'database' => $databaseName
        ]);

        return $databaseName;
    }

    /**
     * Run Doctrine migrations on tenant database
     */
    private function runMigrations(string $tenantId, string $zoneName): void
    {
        $this->logger->info('Running migrations for tenant', [
            'tenant_id' => $tenantId,
            'zone' => $zoneName
        ]);

        // Switch to tenant database
        $this->zone->setCurrent($zoneName);
        $this->switcher->switchTenant($tenantId);

        // Run migrations via command
        $process = new Process([
            'php',
            $this->projectDir . '/bin/console',
            'doctrine:migrations:migrate',
            '--no-interaction',
            '--allow-no-migration'
        ]);

        $process->setTimeout(300); // 5 minutes timeout
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                'Migration failed: ' . $process->getErrorOutput()
            );
        }

        $this->logger->info('Migrations completed for tenant', [
            'tenant_id' => $tenantId
        ]);
    }

    /**
     * Initialize default data for tenant
     */
    private function initializeDefaultData(string $tenantId, string $zoneName): void
    {
        $this->logger->info('Initializing default data for tenant', [
            'tenant_id' => $tenantId
        ]);

        // Ensure we're connected to tenant database
        $this->zone->setCurrent($zoneName);
        $this->switcher->switchTenant($tenantId);

        // Create default item types
        $this->createDefaultItemTypes();

        // Create default pipeline
        $this->createDefaultPipeline();

        $this->em->flush();

        $this->logger->info('Default data initialized for tenant', [
            'tenant_id' => $tenantId
        ]);
    }

    /**
     * Create default item types
     */
    private function createDefaultItemTypes(): void
    {
        $itemTypes = [
            ['code' => 'contact', 'name' => 'Contact', 'description' => 'Contact person'],
            ['code' => 'company', 'name' => 'Company', 'description' => 'Company or organization'],
            ['code' => 'deal', 'name' => 'Deal', 'description' => 'Sales opportunity'],
            ['code' => 'activity', 'name' => 'Activity', 'description' => 'Task or meeting'],
            ['code' => 'note', 'name' => 'Note', 'description' => 'Note or comment'],
        ];

        foreach ($itemTypes as $data) {
            $itemType = new ItemType();
            $itemType->setCode($data['code']);
            $itemType->setName($data['name']);
            $itemType->setDescription($data['description']);

            $this->em->persist($itemType);
        }
    }

    /**
     * Create default sales pipeline
     */
    private function createDefaultPipeline(): void
    {
        $pipeline = new Pipeline();
        $pipeline->setName('Sales Pipeline');
        $pipeline->setDescription('Default sales pipeline');
        $pipeline->setIsDefault(true);

        $this->em->persist($pipeline);
        $this->em->flush(); // Flush to get pipeline ID

        // Create default steps
        $steps = [
            ['name' => 'Lead', 'order' => 1, 'probability' => 10],
            ['name' => 'Qualified', 'order' => 2, 'probability' => 25],
            ['name' => 'Proposal', 'order' => 3, 'probability' => 50],
            ['name' => 'Negotiation', 'order' => 4, 'probability' => 75],
            ['name' => 'Closed Won', 'order' => 5, 'probability' => 100],
        ];

        foreach ($steps as $data) {
            $step = new PipelineStep();
            $step->setName($data['name']);
            $step->setOrderIndex($data['order']);
            $step->setProbability($data['probability']);
            $step->setPipeline($pipeline);

            $this->em->persist($step);
        }
    }

    /**
     * Create admin user for tenant
     */
    private function createAdminUser(string $tenantId, string $zoneName, array $adminData): User
    {
        $this->logger->info('Creating admin user for tenant', [
            'tenant_id' => $tenantId,
            'email' => $adminData['email']
        ]);

        // Ensure we're connected to tenant database
        $this->zone->setCurrent($zoneName);
        $this->switcher->switchTenant($tenantId);

        $user = new User();
        $user->setEmail($adminData['email']);
        $user->setPassword(
            password_hash($adminData['password'], PASSWORD_BCRYPT)
        );
        $user->setFirstName($adminData['firstName'] ?? 'Admin');
        $user->setLastName($adminData['lastName'] ?? 'User');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setIsActive(true);

        $this->em->persist($user);
        $this->em->flush();

        $this->logger->info('Admin user created for tenant', [
            'tenant_id' => $tenantId,
            'user_id' => $user->getId()
        ]);

        return $user;
    }

    /**
     * Rollback provisioning in case of failure
     */
    private function rollbackProvisioning(string $tenantId, string $zoneName): void
    {
        $this->logger->warning('Attempting to rollback tenant provisioning', [
            'tenant_id' => $tenantId
        ]);

        try {
            $params = $this->connection->getParams();
            $baseName = $params['dbname'];
            $databaseName = $zoneName . '_' . $baseName;

            // Drop database if it was created
            $sql = sprintf('DROP DATABASE IF EXISTS `%s`', $databaseName);
            $this->connection->executeStatement($sql);

            $this->logger->info('Tenant database rolled back', [
                'database' => $databaseName
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Rollback failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Deprovision a tenant (delete all data)
     */
    public function deprovisionTenant(string $tenantId, string $zoneName = 'wiassur'): array
    {
        $this->logger->warning('Starting tenant deprovisioning', [
            'tenant_id' => $tenantId,
            'zone' => $zoneName
        ]);

        try {
            $params = $this->connection->getParams();
            $baseName = $params['dbname'];
            $databaseName = $zoneName . '_' . $baseName;

            // Drop database
            $sql = sprintf('DROP DATABASE IF EXISTS `%s`', $databaseName);
            $this->connection->executeStatement($sql);

            $this->logger->info('Tenant deprovisioned successfully', [
                'tenant_id' => $tenantId,
                'database' => $databaseName
            ]);

            return [
                'success' => true,
                'message' => 'Tenant deprovisioned successfully'
            ];

        } catch (\Exception $e) {
            $this->logger->error('Tenant deprovisioning failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get tenant provisioning status
     */
    public function getTenantStatus(string $tenantId, string $zoneName = 'wiassur'): array
    {
        $params = $this->connection->getParams();
        $baseName = $params['dbname'];
        $databaseName = $zoneName . '_' . $baseName;

        try {
            $sql = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :dbname";
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->executeQuery(['dbname' => $databaseName]);

            $exists = (bool) $result->fetchOne();

            return [
                'exists' => $exists,
                'tenant_id' => $tenantId,
                'database' => $databaseName,
                'zone' => $zoneName
            ];

        } catch (\Exception $e) {
            return [
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
