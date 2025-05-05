<?php

namespace App\Command;

use App\Entity\User;
use App\Managers\JsClassGenerator;
use App\Managers\MobileJsClassGenerator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Validator;

#[AsCommand(
    name: 'app:gen:js',
    description: 'Generer l\'équivalent JS d\'une entité PHP',
    hidden: false,
    aliases: ['app:gen', 'a:g:j']
)]
class GenerateJsClassCommand extends Command
{
    /** @var JsClassGenerator  */
    protected $jsGen;

    // private $doctrineHelper;

    public function __construct(JsClassGenerator $classGenerator, private MobileJsClassGenerator $mobileJsClassGenerator, private EntityManagerInterface $em)
    {
        $this->jsGen = $classGenerator;
        // $this->doctrineHelper = $doctrineHelper;
        parent::__construct();
    }

    protected function configure()
    {
        // $this
        // ->addOption('mobile', 'm', InputOption::VALUE_OPTIONAL, 'React native ?');
        //     ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
        //     ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $output->writeln([
            '',
            'Js Class Generator',
            '============',
            '',
        ]);

        // $avoid = ['User'];
        $avoid = [];

        $classes = array();
        $metas = $this->em->getMetadataFactory()->getAllMetadata();
        // $optionValue = $input->getOption('mobile');
        // if ($optionValue) {
        //     $io->warning('Generating mobile version');
        //     $this->ge->setMobile();
        // }
        // dd($optionValue);
        foreach ($metas as $meta) {
            $class = str_replace('App\Entity\\', '', $meta->getName());
            if (!in_array($class, $avoid)) {
                $this->jsGen->generate($class);
                $this->mobileJsClassGenerator->generate($class);
                $io->success($class . ' Generated');
            }
        }

        return Command::SUCCESS;
    }
}
