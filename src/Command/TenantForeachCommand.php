<?php

namespace App\Command;

use App\Managers\ServiceManager;
use App\MultiTenancy\Switcher;
use App\MultiTenancy\TenantSwitcher;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TenantForeachCommand extends Command
{
    protected static $defaultName = 'app:tenant:foreach';
    protected static $defaultDescription = 'Executed given command for every tenants';

    public function __construct(private ServiceManager $serviceManager, private Switcher $switcher, private ManagerRegistry $registry)
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('sub', InputArgument::REQUIRED, 'The command to be executed');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $app = $this->getApplication();

        $sub = $input->getArgument("sub");
        $parts = explode(' ', $sub);
        $commandText = $parts[0];
        $output->writeln("start at " . (new \DateTime())->format("H:i:s"));
        // dd($parts);
        try {
            /** @var Command $command */
            $command = $app->find($commandText);
            $definition = $command->getDefinition();
        } catch (CommandNotFoundException $exception) {
            $io->error($exception->getMessage());
            return 0;
        }

        $argumentPosition = 0;

        $arguments = [];
        foreach ($parts as $index => $parameter) {
            /* The first index is the sub command name */
            if ($index === 0) {
                continue;
            }
            $parameter = trim($parameter);

            if ($parameter[0] === '-') {
                $option = explode("=", $parameter);
                $arguments[$option[0]] = $option[1] ?? true;
            } else {
                $argumentDefinition = $definition->getArgument($argumentPosition);
                $arguments[$argumentDefinition->getName()] = $parameter;
                $argumentPosition++;
            }
        }
        $subInput = new ArrayInput($arguments);


        $res = $this->serviceManager->get('AUTH', 'get/tenants');
        if (isset($res['tenants'])) {
            $tenants = $res['tenants'];
            $io->createProgressBar(count($tenants));
            $io->progressStart(count($tenants));
            $io->writeln(PHP_EOL);
            $names = array_column($tenants, 'label');
            $io->note("Executing " . $command->getName() . " on tenants: " . implode(',', $names));
            $io->writeln(PHP_EOL);
            foreach ($tenants as $tenant) {
                try {
                    $io->section("Tenant: " . $tenant['label'] . ',code ' . $tenant['code']);
                    $this->switcher->switchCommandLineTenant($tenant['code']);
                    $command->run($subInput, $output);
                    $io->progressAdvance();
                    $io->writeln(PHP_EOL . PHP_EOL);
                } catch (\Throwable $e) {
                    $io->error('Erreur tenant ' . $tenant['label'] . ',code ' . $tenant['code']);
                    $io->error($e->getMessage());
                }
            }
            $output->writeln("finished at " . (new \DateTime())->format("H:i:s"));
            $io->success('Commands successfully executed');
        } else {
            $io->error('Impossible de récuperer la liste des tenants.');
        }
        return 0;
    }
}
