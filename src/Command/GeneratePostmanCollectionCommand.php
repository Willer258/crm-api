<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:generate-postman-collection',
    description: 'Generate Postman collection from Symfony controllers',
)]
class GeneratePostmanCollectionCommand extends Command
{
    private string $projectDir;
    private array $collection = [];
    private array $controllers = [];

    public function __construct(string $projectDir)
    {
        parent::__construct();
        $this->projectDir = $projectDir;
    }

    protected function configure(): void
    {
        $this
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file path', 'CRM_API_Complete.postman_collection.json')
            ->addOption('split', null, InputOption::VALUE_NONE, 'Split into multiple collections by category')
            ->addOption('base-url', null, InputOption::VALUE_OPTIONAL, 'Base URL for API', 'http://localhost:8000')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $baseUrl = $input->getOption('base-url');
        $outputFile = $input->getOption('output');
        $split = $input->getOption('split');

        $io->title('🚀 Postman Collection Generator');

        // 1. Scan all controllers
        $io->section('📁 Scanning controllers...');
        $this->scanControllers($io);

        // 2. Parse routes from controllers
        $io->section('🔍 Parsing routes...');
        $this->parseRoutes($io);

        // 3. Generate collection(s)
        $io->section('📦 Generating collection(s)...');
        if ($split) {
            $this->generateSplitCollections($io, $baseUrl, $outputFile);
        } else {
            $this->generateSingleCollection($io, $baseUrl, $outputFile);
        }

        $io->success('✅ Postman collection(s) generated successfully!');

        return Command::SUCCESS;
    }

    private function scanControllers(SymfonyStyle $io): void
    {
        $finder = new Finder();
        $finder->files()->in($this->projectDir . '/src/Controller')->name('*.php');

        foreach ($finder as $file) {
            $className = 'App\\Controller\\' . $file->getBasename('.php');

            if (class_exists($className)) {
                $this->controllers[] = $className;
                $io->writeln("  ✓ Found: {$file->getBasename()}");
            }
        }

        $io->writeln("\n  📊 Total: " . count($this->controllers) . " controllers");
    }

    private function parseRoutes(SymfonyStyle $io): void
    {
        $totalRoutes = 0;

        foreach ($this->controllers as $className) {
            $reflection = new \ReflectionClass($className);
            $controllerName = $reflection->getShortName();

            // Get controller-level route prefix
            $controllerPrefix = '';
            $controllerAttributes = $reflection->getAttributes(Route::class);
            if (!empty($controllerAttributes)) {
                $routeAttr = $controllerAttributes[0]->newInstance();
                $controllerPrefix = $routeAttr->getPath();
            }

            $routes = [];

            // Parse methods
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $className) {
                    continue; // Skip inherited methods
                }

                $routeAttributes = $method->getAttributes(Route::class);

                foreach ($routeAttributes as $attribute) {
                    $route = $attribute->newInstance();
                    $routePath = $controllerPrefix . $route->getPath();
                    $routeMethods = $route->getMethods() ?: ['GET'];
                    $routeOptions = $route->getOptions();
                    $description = $routeOptions['description'] ?? '';

                    $routes[] = [
                        'name' => $method->getName(),
                        'path' => $routePath,
                        'methods' => $routeMethods,
                        'description' => $description,
                        'parameters' => $this->extractParameters($routePath),
                    ];

                    $totalRoutes++;
                }
            }

            if (!empty($routes)) {
                $this->collection[$controllerName] = [
                    'category' => $this->categorizeController($controllerName),
                    'routes' => $routes,
                ];
            }
        }

        $io->writeln("  📊 Total: {$totalRoutes} routes found");
    }

    private function extractParameters(string $path): array
    {
        preg_match_all('/\{([^}]+)\}/', $path, $matches);
        return $matches[1] ?? [];
    }

    private function categorizeController(string $controllerName): string
    {
        $categories = [
            'Auth' => ['AuthController'],
            'Billing' => ['PlanController', 'SubscriptionController', 'StripeWebhookController'],
            'CRM - Core' => ['ContactController', 'CompanyController', 'DealController'],
            'CRM - Activities' => ['ActivityController', 'PipelineController', 'PipelineStepController'],
            'CRM - Content' => ['NoteController', 'TagController'],
            'Communication' => ['MailController', 'PhoneNumberController'],
            'Files' => ['FileController', 'ExportController'],
            'Configuration' => ['PropertyController', 'PropertyModelController', 'ItemTypeController'],
            'Administration' => ['UserController', 'AdminController', 'SyncController', 'ImportValidationController'],
        ];

        foreach ($categories as $category => $controllers) {
            if (in_array($controllerName, $controllers)) {
                return $category;
            }
        }

        return 'Other';
    }

    private function generateSingleCollection(SymfonyStyle $io, string $baseUrl, string $outputFile): void
    {
        $postmanCollection = [
            'info' => [
                '_postman_id' => 'crm-api-complete-' . date('Y-m-d'),
                'name' => 'CRM API - Complete Collection',
                'description' => "Complete API collection for CRM application.\n\nAuto-generated on " . date('Y-m-d H:i:s'),
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
            'variable' => $this->getDefaultVariables($baseUrl),
        ];

        // Group by category
        $categorized = [];
        foreach ($this->collection as $controllerName => $data) {
            $category = $data['category'];
            if (!isset($categorized[$category])) {
                $categorized[$category] = [];
            }
            $categorized[$category][$controllerName] = $data;
        }

        // Build folders
        foreach ($categorized as $category => $controllers) {
            $folder = [
                'name' => $this->getCategoryEmoji($category) . ' ' . $category,
                'item' => [],
            ];

            foreach ($controllers as $controllerName => $data) {
                $controllerFolder = [
                    'name' => str_replace('Controller', '', $controllerName),
                    'item' => [],
                ];

                foreach ($data['routes'] as $route) {
                    $controllerFolder['item'][] = $this->generatePostmanRequest($route, $baseUrl);
                }

                $folder['item'][] = $controllerFolder;
            }

            $postmanCollection['item'][] = $folder;
        }

        // Save to file
        $filepath = $this->projectDir . '/' . $outputFile;
        file_put_contents(
            $filepath,
            json_encode($postmanCollection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        $io->writeln("  ✓ Saved to: {$outputFile}");
        $io->writeln("  📊 " . count($postmanCollection['item']) . " categories");
    }

    private function generateSplitCollections(SymfonyStyle $io, string $baseUrl, string $outputBase): void
    {
        $categorized = [];
        foreach ($this->collection as $controllerName => $data) {
            $category = $data['category'];
            if (!isset($categorized[$category])) {
                $categorized[$category] = [];
            }
            $categorized[$category][$controllerName] = $data;
        }

        foreach ($categorized as $category => $controllers) {
            $filename = str_replace([' ', '-'], '_', strtolower($category)) . '.postman_collection.json';

            $postmanCollection = [
                'info' => [
                    '_postman_id' => 'crm-api-' . strtolower(str_replace(' ', '-', $category)) . '-' . date('Ymd'),
                    'name' => "CRM API - {$category}",
                    'description' => "{$category} endpoints for CRM application.\n\nAuto-generated on " . date('Y-m-d H:i:s'),
                    'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
                ],
                'item' => [],
                'variable' => $this->getDefaultVariables($baseUrl),
            ];

            foreach ($controllers as $controllerName => $data) {
                $controllerFolder = [
                    'name' => str_replace('Controller', '', $controllerName),
                    'item' => [],
                ];

                foreach ($data['routes'] as $route) {
                    $controllerFolder['item'][] = $this->generatePostmanRequest($route, $baseUrl);
                }

                $postmanCollection['item'][] = $controllerFolder;
            }

            $filepath = $this->projectDir . '/' . $filename;
            file_put_contents(
                $filepath,
                json_encode($postmanCollection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );

            $io->writeln("  ✓ Saved: {$filename} (" . count($postmanCollection['item']) . " controllers)");
        }
    }

    private function generatePostmanRequest(array $route, string $baseUrl): array
    {
        $method = $route['methods'][0] ?? 'GET';
        $path = $route['path'];

        // Replace parameters with Postman variables
        $postmanPath = preg_replace('/\{([^}]+)\}/', ':$1', $path);

        $request = [
            'name' => ucfirst($route['name']),
            'request' => [
                'method' => $method,
                'header' => [],
                'url' => [
                    'raw' => "{{base_url}}{$postmanPath}",
                    'host' => ['{{base_url}}'],
                    'path' => array_filter(explode('/', $postmanPath)),
                ],
                'description' => $route['description'] ?: "Endpoint: {$method} {$path}",
            ],
            'response' => [],
        ];

        // Add auth header if needed (exclude public endpoints)
        if (!$this->isPublicEndpoint($path)) {
            $request['request']['auth'] = [
                'type' => 'bearer',
                'bearer' => [
                    ['key' => 'token', 'value' => '{{jwt_token}}', 'type' => 'string']
                ],
            ];
        }

        // Add headers for specific endpoints
        if (strpos($path, '/api/subscription') !== false || strpos($path, '/api/plans') === false) {
            $request['request']['header'][] = [
                'key' => 'X-Tenant-ID',
                'value' => '{{tenant_id}}',
                'type' => 'text',
            ];
        }

        // Add body for POST/PUT/PATCH
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $request['request']['header'][] = [
                'key' => 'Content-Type',
                'value' => 'application/json',
                'type' => 'text',
            ];

            $request['request']['body'] = [
                'mode' => 'raw',
                'raw' => $this->generateBodyExample($route),
                'options' => [
                    'raw' => ['language' => 'json']
                ],
            ];
        }

        // Add path variables
        if (!empty($route['parameters'])) {
            $request['request']['url']['variable'] = [];
            foreach ($route['parameters'] as $param) {
                $request['request']['url']['variable'][] = [
                    'key' => $param,
                    'value' => "example_{$param}",
                ];
            }
        }

        return $request;
    }

    private function isPublicEndpoint(string $path): bool
    {
        $publicPaths = [
            '/auth/register',
            '/auth/login',
            '/auth/verify-email',
            '/auth/resend-verification',
            '/auth/forgot-password',
            '/auth/reset-password',
            '/webhooks',
        ];

        foreach ($publicPaths as $publicPath) {
            if (strpos($path, $publicPath) !== false) {
                return true;
            }
        }

        return false;
    }

    private function generateBodyExample(array $route): string
    {
        $path = $route['path'];
        $name = $route['name'];

        // Auth endpoints
        if (strpos($path, '/auth/register') !== false) {
            return json_encode([
                'email' => 'user@example.com',
                'password' => 'SecurePassword123!',
                'firstname' => 'John',
                'lastname' => 'Doe',
            ], JSON_PRETTY_PRINT);
        }

        if (strpos($path, '/auth/login') !== false) {
            return json_encode([
                'email' => 'user@example.com',
                'password' => 'SecurePassword123!',
            ], JSON_PRETTY_PRINT);
        }

        if (strpos($path, '/subscription/subscribe') !== false) {
            return json_encode([
                'plan_code' => 'pro_monthly',
                'email' => 'user@example.com',
                'payment_method_id' => 'pm_card_visa',
            ], JSON_PRETTY_PRINT);
        }

        // Contact endpoints
        if (strpos($path, '/contact/edit') !== false) {
            return json_encode([
                'email' => 'contact@example.com',
                'firstname' => 'Jane',
                'lastname' => 'Smith',
                'phone' => '+1234567890',
            ], JSON_PRETTY_PRINT);
        }

        // Generic list endpoint
        if (strpos($path, '/list') !== false) {
            return json_encode([
                'pagination' => [
                    'page' => 1,
                    'limit' => 25,
                ],
                'filters' => [],
            ], JSON_PRETTY_PRINT);
        }

        // Default
        return json_encode(['data' => 'example'], JSON_PRETTY_PRINT);
    }

    private function getCategoryEmoji(string $category): string
    {
        $emojis = [
            'Auth' => '🔐',
            'Billing' => '💳',
            'CRM - Core' => '📊',
            'CRM - Activities' => '📅',
            'CRM - Content' => '📝',
            'Communication' => '📧',
            'Files' => '📁',
            'Configuration' => '⚙️',
            'Administration' => '👥',
        ];

        return $emojis[$category] ?? '📦';
    }

    private function getDefaultVariables(string $baseUrl): array
    {
        return [
            ['key' => 'base_url', 'value' => $baseUrl, 'type' => 'string'],
            ['key' => 'jwt_token', 'value' => '', 'type' => 'string'],
            ['key' => 'refresh_token', 'value' => '', 'type' => 'string'],
            ['key' => 'tenant_id', 'value' => 'tenant_123', 'type' => 'string'],
            ['key' => 'user_id', 'value' => '', 'type' => 'string'],
        ];
    }
}
