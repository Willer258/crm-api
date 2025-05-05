<?php

namespace App\Controller;

use App\MultiTenancy\Zone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{

    public function __construct(private UrlGeneratorInterface $router, private Zone $zone)
    {
    }

    #[Route('/get/routes', name: 'get_routes', options: ['description' => 'Liste des endpoints'])]
    public function getRoutes(): Response
    {

        $routes = [];
        /** @var Route $item */
        foreach ($this->router->getRouteCollection() as $key => $item) {
            $route = ['path' => $item->getPath(), 'name' => $key, 'options' => $item->getOptions()];
            $routes[] = $route;
        }
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
        }
//        dd($routes[0],$accesses);
        return $this->json(['status' => 'success', 'routes' => $routes]);
    }

    #[Route('/save/route/requirements', name: 'save_route_requirements', options: ['description' => 'Enregistrer les droits nécessaires pour accéder à une route'])]
    public function saveRouteRequirements(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $dir = __DIR__ . '/../../menus/';
        $filename = 'access_map_' . $this->zone->getCurrent() . '.json';
        $routes = $this->getAccessList($this->zone);
        $i = -1;
        foreach ($routes as $index => $route) {
            if ($route['name'] === $data["name"]) {
                $i = $index;
            }
        }
        if ($i !== -1) {
            $routes[$i] = $data;

        } else {
            $routes[] = $data;
        }
        file_put_contents($dir . $filename, json_encode($routes, JSON_UNESCAPED_SLASHES));
        return $this->json(['status' => 'success', 'routes' => $data]);
    }

    public static function getAccessList($zone)
    {
        $dir = __DIR__ . '/../../menus/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $filename = 'access_map_' . $zone->getCurrent() . '.json';
        if (!file_exists($dir . $filename)) {
            file_put_contents($dir . $filename, '[]');
        }
        $routes = json_decode(file_get_contents($dir . $filename), true);
        return $routes;
    }

    public static function getRoute($router, $name, $zone)
    {
        foreach ($router->getRouteCollection() as $key => $item) {
            if ($item->getPath() === $name || $name === $key) {
                $route = ['path' => $item->getPath(), 'name' => $key, 'options' => $item->getOptions()];
                $accesses = AdminController::getAccessList($zone);
                foreach ($accesses as $access) {
                    if ($key === $access['name'] || $item->getPath() === $access['path']) {
                        if (isset($access['roles'])) {
                            $route['roles'] = $access['roles'];
                        }
                    }
                }
                return $route;
            }
        }
    }
}
