<?php

namespace App\Command;

use App\MultiTenancy\Switcher;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TenantGetCommand extends Command
{
    protected static $defaultName = 'app:tenant:get';
    protected static $defaultDescription = 'Displays the current tenant';
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->success($this->switcher->getCommandLineTenant());

        return 0;
    }
}
