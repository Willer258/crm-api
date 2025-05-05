<?php

namespace App\Command;

use App\Entity\ResponseGroup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:debug',
    description: 'Add a short description for your command',
)]
class DebugCommand extends Command
{

    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct("debug");
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $this->em->getFilters()->disable('softdeleteable');
        $rgs = $this->em->getRepository(ResponseGroup::class)->findBy(['createBy' => '0789986979', 'source' => 'WiCare']);
        $count= count($rgs);
        foreach ($rgs as $rg) {
            $this->em->remove($rg);
        }
        $this->em->flush();
        $io = new SymfonyStyle($input, $output);

        $io->success($count.' rg supprimés');

        return Command::SUCCESS;
    }
}
