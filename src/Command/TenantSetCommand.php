<?php

namespace App\Command;

use App\MultiTenancy\Switcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


#[AsCommand(
    name: 'app:tenant:set',
    description: 'Generer l\'équivalent JS d\'une entité PHP',
    hidden: false,
    aliases: ['a:t:s']
)]
class TenantSetCommand extends Command
{
    protected static $defaultName = 'app:tenant:set';
    protected static $defaultDescription = 'Changes the current tenant';
    protected $switcher;

    public function __construct(Switcher $switcher)
    {
        parent::__construct(self::$defaultName);
        $this->switcher = $switcher;
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('tenant', InputArgument::REQUIRED, 'The tenant')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tenant = $input->getArgument('tenant');

        try{
            $this->switcher->switchCommandLineTenant($tenant);
            $io->success('Current tenant is successfully set to ' . $tenant . ', all subsequent commands will be done with its database.');
        }catch (\Exception $e){
            $io->error($e->getMessage());
        }

        return 0;
    }
}
