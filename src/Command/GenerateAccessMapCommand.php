<?php

namespace App\Command;

use App\Entity\Avenant;
use App\MultiTenancy\Zone;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:generate:accessmap',
    description: 'Générer un access map',
)]
class GenerateAccessMapCommand extends Command
{
    public function __construct(
        private ManagerRegistry $registry,
        private UrlGeneratorInterface $router,
        private Zone $zone
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $routes = [];
        $roles = [];
        
        $routeCollection = $this->router->getRouteCollection();
        if (!$routeCollection instanceof RouteCollection) {
            $io->error('Impossible de récupérer les routes.');
            return Command::FAILURE;
        }

        foreach ($routeCollection->all() as $key => $item) {
            if ($item instanceof Route) {
                $routes[] = [
                    'name' => $key,
                    'path' => $item->getPath(),
                    //  dd($item->getPath()),
                    'options' => $item->getOptions(),
                    'roles' => [
                        [
                            [
                                'code' => 'ROLE_'. $_ENV['API_CODE'].'_' . strtoupper($key),
                            ],
                        ],
                    ],
                ];
                $roles[] = [
                    "code"=> 'ROLE_'. $_ENV['API_CODE'].'_' . strtoupper($key),
                    "description"=> isset($item->getOptions()['description']) ? 'Role de l\'API '.$_ENV['API_CODE']. ' permettant la fontionnalité de:'. ' ' .$item->getOptions()['description'] : 'Aucune description',
                ]; 

                // dd($roles);
                

                // dd($routes);
            }
        }
        // dd($roles);
        
        $accesses = $this->getAccessList($this->zone);

        foreach ($routes as &$route) {
            foreach ($accesses as $access) {
                if ($route['name'] === $access['name']) {
                    if (isset($access['roles'])) {
                        $route['roles'] = $access['roles'];
                    }
                    if (isset($access['options']) && isset($access['options']['description'])) {
                        $route['options']['description'] = $access['options']['description'];
                    }
                }
            }
            if (isset($route['options']['description'])) {
                $route['options']['description'] = $route['options']['description'];
            }
            if (isset($route['options']['utf8'])) {
                $route['options']['utf8'] = $route['options']['utf8'];
            }
            if (isset($route['options']['compiler_class'])) {
                $route['options']['compiler_class'] = $route['options']['compiler_class'];
            }
        }
        // dd(($routes));
        $accessMapJson = json_encode($routes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $accessMapCodeJson = json_encode($roles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
        $emplacement = __DIR__ . '/../../menus/';
        if (!is_dir($emplacement)) {
            mkdir($emplacement);
        }
        file_put_contents($emplacement . 'access_map_' . $this->zone->getCurrent() . '.json', $accessMapJson);
        file_put_contents($emplacement . 'role' . $this->zone->getCurrent() . '.json', $accessMapCodeJson);
        // $output->writeln($json1);
        
        // $today = new \DateTime();
        // $limit = 100;
        // $page = 1;
        // do {
        //     gc_collect_cycles();
        //     memory_get_peak_usage();
        //     $offset = ($page - 1) * $limit;
        //     $avenants = $this->registry->getRepository(Avenant::class)->findBy(
        //         ['dateEffet' => $today, 'status' => Avenant::STATUS_VALIDE],
        //         ['id' => 'ASC'],
        //         $limit,
        //         $offset
        //     );
        //     $count = count($avenants);
        //     foreach ($avenants as $avenant) {
        //         if ($this->avenantStateMachine->can($avenant, 'apply')) {
        //             $this->avenantStateMachine->apply($avenant, 'apply');
        //         }
        //     }
        //     $this->registry->getManager()->flush();
        //     unset($avenants);
        //     $page++;
        // } while ($count > 0);

        
        $io->success('Fichier terminé');
        return Command::SUCCESS;
    }

    private function getAccessList(Zone $zone): array
    {
        return [
            [
                'name' => 'example_route',
                'roles' => [['code' => 'ROLE_ADMIN']],
                'options' => ['description' => 'Exemple de description'],
            ],
        ];
    }
}
