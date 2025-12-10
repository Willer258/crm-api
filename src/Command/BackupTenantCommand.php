<?php

namespace App\Command;

use App\MultiTenancy\Switcher;
use App\MultiTenancy\Zone;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:backup-tenant',
    description: 'Backup tenant database'
)]
class BackupTenantCommand extends Command
{
    public function __construct(
        private Connection $connection,
        private Switcher $switcher,
        private Zone $zone,
        private LoggerInterface $logger,
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('tenant', InputArgument::OPTIONAL, 'Tenant ID to backup (or "all" for all tenants)')
            ->addOption('zone', 'z', InputOption::VALUE_REQUIRED, 'Zone name', 'wiassur')
            ->addOption('output-dir', 'o', InputOption::VALUE_REQUIRED, 'Output directory for backups', $this->projectDir . '/var/backups')
            ->addOption('compress', 'c', InputOption::VALUE_NONE, 'Compress backup with gzip')
            ->addOption('retention-days', 'r', InputOption::VALUE_REQUIRED, 'Number of days to keep backups', '30')
            ->setHelp('This command creates a backup of tenant database(s)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tenantId = $input->getArgument('tenant');
        $zoneName = $input->getOption('zone');
        $outputDir = $input->getOption('output-dir');
        $compress = $input->getOption('compress');
        $retentionDays = (int) $input->getOption('retention-days');

        $io->title('Tenant Database Backup');

        // Create output directory if it doesn't exist
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        if ($tenantId === 'all') {
            return $this->backupAllTenants($io, $zoneName, $outputDir, $compress);
        }

        if (!$tenantId) {
            $io->error('Please specify a tenant ID or "all"');
            return Command::FAILURE;
        }

        try {
            $backupFile = $this->backupTenant($tenantId, $zoneName, $outputDir, $compress);

            $io->success("Backup completed successfully!");
            $io->info("Backup file: {$backupFile}");

            // Cleanup old backups
            $deleted = $this->cleanupOldBackups($outputDir, $retentionDays);
            if ($deleted > 0) {
                $io->info("Cleaned up {$deleted} old backup(s)");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Backup failed: ' . $e->getMessage());
            $this->logger->error('Tenant backup failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Backup a single tenant
     */
    private function backupTenant(
        string $tenantId,
        string $zoneName,
        string $outputDir,
        bool $compress
    ): string {
        $this->logger->info('Starting tenant backup', [
            'tenant_id' => $tenantId,
            'zone' => $zoneName
        ]);

        $params = $this->connection->getParams();
        $databaseName = $zoneName . '_' . $params['dbname'];

        // Generate backup filename
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "{$tenantId}_{$zoneName}_{$timestamp}.sql";

        if ($compress) {
            $filename .= '.gz';
        }

        $backupPath = $outputDir . '/' . $filename;

        // Build mysqldump command
        $command = [
            'mysqldump',
            '--user=' . $params['user'],
            '--password=' . $params['password'],
            '--host=' . $params['host'],
            '--port=' . ($params['port'] ?? '3306'),
            '--single-transaction',
            '--quick',
            '--lock-tables=false',
            $databaseName
        ];

        if ($compress) {
            // Pipe through gzip
            $command = implode(' ', $command) . ' | gzip > ' . escapeshellarg($backupPath);
            $process = Process::fromShellCommandline($command);
        } else {
            $command[] = '--result-file=' . $backupPath;
            $process = new Process($command);
        }

        $process->setTimeout(3600); // 1 hour timeout
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Backup failed: ' . $process->getErrorOutput());
        }

        // Verify backup file was created
        if (!file_exists($backupPath)) {
            throw new \RuntimeException('Backup file was not created');
        }

        $fileSize = filesize($backupPath);
        $this->logger->info('Tenant backup completed', [
            'tenant_id' => $tenantId,
            'file' => $backupPath,
            'size' => $fileSize
        ]);

        return $backupPath;
    }

    /**
     * Backup all tenants
     */
    private function backupAllTenants(
        SymfonyStyle $io,
        string $zoneName,
        string $outputDir,
        bool $compress
    ): int {
        $io->section('Backing up all tenants in zone: ' . $zoneName);

        // Get list of all databases
        $databases = $this->getAllTenantDatabases($zoneName);

        if (empty($databases)) {
            $io->warning('No tenant databases found');
            return Command::SUCCESS;
        }

        $io->progressStart(count($databases));

        $successful = 0;
        $failed = 0;

        foreach ($databases as $database) {
            // Extract tenant ID from database name (format: {zone}_{base_name})
            $tenantId = str_replace($zoneName . '_', '', $database);

            try {
                $this->backupTenant($tenantId, $zoneName, $outputDir, $compress);
                $successful++;
            } catch (\Exception $e) {
                $failed++;
                $io->error("Failed to backup {$tenantId}: " . $e->getMessage());
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        $io->success("Backup completed!");
        $io->table(
            ['Metric', 'Count'],
            [
                ['Total databases', count($databases)],
                ['Successful backups', $successful],
                ['Failed backups', $failed]
            ]
        );

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Get all tenant databases for a zone
     */
    private function getAllTenantDatabases(string $zoneName): array
    {
        $params = $this->connection->getParams();
        $baseName = $params['dbname'];
        $pattern = $zoneName . '_' . $baseName;

        $sql = "SELECT SCHEMA_NAME
                FROM INFORMATION_SCHEMA.SCHEMATA
                WHERE SCHEMA_NAME LIKE :pattern";

        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery(['pattern' => $pattern . '%']);

        return $result->fetchFirstColumn();
    }

    /**
     * Clean up old backup files
     */
    private function cleanupOldBackups(string $outputDir, int $retentionDays): int
    {
        $this->logger->info('Cleaning up old backups', [
            'retention_days' => $retentionDays
        ]);

        $cutoffDate = new \DateTime("-{$retentionDays} days");
        $deleted = 0;

        $files = glob($outputDir . '/*.sql*');

        foreach ($files as $file) {
            $fileTime = filemtime($file);

            if ($fileTime < $cutoffDate->getTimestamp()) {
                unlink($file);
                $deleted++;

                $this->logger->debug('Deleted old backup', [
                    'file' => basename($file),
                    'age_days' => floor((time() - $fileTime) / 86400)
                ]);
            }
        }

        return $deleted;
    }

    /**
     * Restore a backup (bonus feature)
     */
    public function restoreBackup(string $backupFile, string $tenantId, string $zoneName): bool
    {
        $this->logger->warning('Starting backup restoration', [
            'backup_file' => $backupFile,
            'tenant_id' => $tenantId,
            'zone' => $zoneName
        ]);

        if (!file_exists($backupFile)) {
            throw new \RuntimeException('Backup file not found: ' . $backupFile);
        }

        $params = $this->connection->getParams();
        $databaseName = $zoneName . '_' . $params['dbname'];

        // Determine if file is compressed
        $isCompressed = str_ends_with($backupFile, '.gz');

        $command = [
            'mysql',
            '--user=' . $params['user'],
            '--password=' . $params['password'],
            '--host=' . $params['host'],
            '--port=' . ($params['port'] ?? '3306'),
            $databaseName
        ];

        if ($isCompressed) {
            // Decompress on the fly
            $command = 'gunzip < ' . escapeshellarg($backupFile) . ' | ' . implode(' ', $command);
            $process = Process::fromShellCommandline($command);
        } else {
            $command[] = '<';
            $command[] = $backupFile;
            $process = new Process($command);
        }

        $process->setTimeout(3600); // 1 hour timeout
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Restore failed: ' . $process->getErrorOutput());
        }

        $this->logger->info('Backup restored successfully', [
            'backup_file' => $backupFile,
            'tenant_id' => $tenantId
        ]);

        return true;
    }
}
