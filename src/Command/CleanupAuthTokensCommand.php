<?php

namespace App\Command;

use App\Managers\AuthManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:auth:cleanup-tokens',
    description: 'Clean up expired authentication tokens (refresh tokens, verification tokens, reset tokens, old login history)'
)]
class CleanupAuthTokensCommand extends Command
{
    public function __construct(
        private AuthManager $authManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show what would be deleted without actually deleting'
            )
            ->setHelp(<<<'HELP'
This command cleans up expired authentication tokens from the database:

- Expired refresh tokens
- Expired email verification tokens
- Expired password reset tokens
- Login history older than 90 days

It should be run periodically (e.g., daily via cron) to keep the database clean.

Example cron entry (run daily at 2 AM):
0 2 * * * cd /path/to/app && php bin/console app:auth:cleanup-tokens

Usage:
  # Clean up expired tokens
  php bin/console app:auth:cleanup-tokens

  # Preview what would be deleted (dry run)
  php bin/console app:auth:cleanup-tokens --dry-run
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No data will be deleted');
        }

        $io->title('Authentication Token Cleanup');

        try {
            if (!$dryRun) {
                $stats = $this->authManager->cleanupExpiredTokens();

                $io->success('Cleanup completed successfully!');

                $io->section('Results:');
                $io->listing([
                    sprintf('Refresh tokens deleted: %d', $stats['refreshTokens']),
                    sprintf('Email verification tokens deleted: %d', $stats['emailVerificationTokens']),
                    sprintf('Password reset tokens deleted: %d', $stats['passwordResetTokens']),
                    sprintf('Login history records deleted: %d', $stats['loginHistory']),
                ]);

                $total = array_sum($stats);
                $io->info(sprintf('Total records deleted: %d', $total));

                if ($total === 0) {
                    $io->note('No expired tokens found. Database is clean!');
                }
            } else {
                // In dry-run mode, we could query to show what would be deleted
                // For now, just show the message
                $io->info('Dry run mode: Would delete expired tokens and old login history');
                $io->note('Run without --dry-run to actually perform the cleanup');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to clean up tokens: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
