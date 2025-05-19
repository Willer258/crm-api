<?php

namespace App\Command;

use App\DataFixtures\InitFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:fixtures:init',
    aliases: ['a:f:i'],
    description: 'Charge uniquement les données d\'initialisation de base (types, tags, etc.)',
)]
class InitFixturesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Chargement des données d\'initialisation...</info>');
        $fixture = new InitFixtures();
        $fixture->load($this->entityManager);
        $this->entityManager->flush();
        $output->writeln('<info>Données d\'initialisation chargées avec succès !</info>');
        return Command::SUCCESS;
    }
}
