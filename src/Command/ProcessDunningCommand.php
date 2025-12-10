<?php

namespace App\Command;

use App\Service\DunningService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:process-dunning',
    description: 'Process dunning for past due subscriptions'
)]
class ProcessDunningCommand extends Command
{
    public function __construct(
        private DunningService $dunningService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run without executing actions')
            ->setHelp('This command processes dunning workflow for all past due subscriptions');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->warning('Running in DRY RUN mode - no actions will be executed');
        }

        $io->title('Processing Dunning for Past Due Subscriptions');

        try {
            $stats = $this->dunningService->processDunning();

            $io->success('Dunning processing completed successfully');

            $io->table(
                ['Metric', 'Count'],
                [
                    ['Total Past Due Subscriptions', $stats['total_past_due']],
                    ['Notifications Sent', $stats['notifications_sent']],
                    ['Reminders Sent', $stats['reminders_sent']],
                    ['Accounts Restricted', $stats['accounts_restricted']],
                    ['Accounts Suspended', $stats['accounts_suspended']],
                    ['Deletions Scheduled', $stats['deletions_scheduled']],
                    ['Errors', $stats['errors']]
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Dunning processing failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
