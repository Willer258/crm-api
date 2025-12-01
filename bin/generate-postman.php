#!/usr/bin/env php
<?php

/**
 * Standalone Postman Collection Generator
 *
 * Génère des collections Postman depuis les controllers Symfony
 * Sans nécessiter de connexion à la base de données
 *
 * Usage:
 *   php bin/generate-postman.php
 *   php bin/generate-postman.php --split
 *   php bin/generate-postman.php --base-url=https://api.example.com
 */

$projectDir = dirname(__DIR__);
$controllersDir = $projectDir . '/src/Controller';

// Parse CLI options
$options = [
    'split' => in_array('--split', $argv),
    'base-url' => 'http://localhost:8000',
    'output' => 'CRM_API_Complete.postman_collection.json',
];

foreach ($argv as $arg) {
    if (strpos($arg, '--base-url=') === 0) {
        $options['base-url'] = substr($arg, strlen('--base-url='));
    }
    if (strpos($arg, '--output=') === 0) {
        $options['output'] = substr($arg, strlen('--output='));
    }
}

echo "🚀 Postman Collection Generator\n";
echo "================================\n\n";

// 1. Scan controllers
echo "📁 Scanning controllers...\n";
$controllers = [];
$files = glob($controllersDir . '/*.php');

foreach ($files as $file) {
    $controllerName = basename($file, '.php');
    $controllers[] = [
        'name' => $controllerName,
        'file' => $file,
    ];
    echo "  ✓ Found: {$controllerName}\n";
}

echo "\n  📊 Total: " . count($controllers) . " controllers\n\n";

// 2. Parse routes
echo "🔍 Parsing routes...\n";
$collection = [];
$totalRoutes = 0;

foreach ($controllers as $controller) {
    $content = file_get_contents($controller['file']);
    $routes = parseRoutes($content, $controller['name']);

    if (!empty($routes)) {
        $collection[$controller['name']] = [
            'category' => categorizeController($controller['name']),
            'routes' => $routes,
        ];
        $totalRoutes += count($routes);
    }
}

echo "  📊 Total: {$totalRoutes} routes found\n\n";

// 3. Generate collection(s)
echo "📦 Generating collection(s)...\n";

if ($options['split']) {
    generateSplitCollections($collection, $options['base-url'], $projectDir);
} else {
    generateSingleCollection($collection, $options['base-url'], $options['output'], $projectDir);
}

echo "\n✅ Postman collection(s) generated successfully!\n";

// ============================================================================
// Helper Functions
// ============================================================================

function parseRoutes(string $content, string $controllerName): array
{
    $routes = [];

    // Extract controller-level Route
    $controllerPrefix = '';
    if (preg_match('/#\[Route\([\'"]([^\'")]+)[\'"]/', $content, $matches)) {
        $controllerPrefix = $matches[1];
    }

    // Extract method-level Routes
    preg_match_all('/\s+#\[Route\(([^\]]+)\)\]\s+public function (\w+)\(/s', $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $routeAttr = $match[1];
        $methodName = $match[2];

        // Parse path
        if (preg_match('/[\'"]([^\'")]+)[\'"]/', $routeAttr, $pathMatch)) {
            $path = $controllerPrefix . $pathMatch[1];
        } else {
            continue;
        }

        // Parse methods
        $methods = ['GET'];
        if (preg_match('/methods:\s*\[([^\]]+)\]/', $routeAttr, $methodsMatch)) {
            $methodsStr = str_replace(['"', "'", ' '], '', $methodsMatch[1]);
            $methods = explode(',', $methodsStr);
        }

        // Parse description
        $description = '';
        if (preg_match('/description[\'"\s]*=>[\'"\s]*[\'"]([^\'")]+)[\'"]/', $routeAttr, $descMatch)) {
            $description = $descMatch[1];
        }

        // Extract parameters from path
        preg_match_all('/\{([^}]+)\}/', $path, $paramMatches);
        $parameters = $paramMatches[1] ?? [];

        $routes[] = [
            'name' => $methodName,
            'path' => $path,
            'methods' => $methods,
            'description' => $description,
            'parameters' => $parameters,
        ];
    }

    return $routes;
}

function categorizeController(string $controllerName): string
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

function getCategoryEmoji(string $category): string
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

function generateSingleCollection(array $collection, string $baseUrl, string $outputFile, string $projectDir): void
{
    $postmanCollection = [
        'info' => [
            '_postman_id' => 'crm-api-complete-' . date('Ymd-His'),
            'name' => 'CRM API - Complete Collection',
            'description' => "Complete API collection for CRM application.\n\nAuto-generated on " . date('Y-m-d H:i:s') . "\n\nTotal endpoints: " . array_sum(array_map(function($c) { return count($c['routes']); }, $collection)),
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ],
        'item' => [],
        'variable' => getDefaultVariables($baseUrl),
    ];

    // Group by category
    $categorized = [];
    foreach ($collection as $controllerName => $data) {
        $category = $data['category'];
        if (!isset($categorized[$category])) {
            $categorized[$category] = [];
        }
        $categorized[$category][$controllerName] = $data;
    }

    // Build folders
    foreach ($categorized as $category => $controllers) {
        $folder = [
            'name' => getCategoryEmoji($category) . ' ' . $category,
            'item' => [],
            'description' => "Endpoints for {$category}",
        ];

        foreach ($controllers as $controllerName => $data) {
            $controllerFolder = [
                'name' => str_replace('Controller', '', $controllerName),
                'item' => [],
            ];

            foreach ($data['routes'] as $route) {
                $controllerFolder['item'][] = generatePostmanRequest($route, $baseUrl);
            }

            $folder['item'][] = $controllerFolder;
        }

        $postmanCollection['item'][] = $folder;
    }

    // Save
    $filepath = $projectDir . '/' . $outputFile;
    file_put_contents(
        $filepath,
        json_encode($postmanCollection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );

    echo "  ✓ Saved to: {$outputFile}\n";
    echo "  📊 Categories: " . count($postmanCollection['item']) . "\n";
    echo "  📊 Total endpoints: " . array_sum(array_map(function($f) { return array_sum(array_map(function($c) { return count($c['item']); }, $f['item'])); }, $postmanCollection['item'])) . "\n";
}

function generateSplitCollections(array $collection, string $baseUrl, string $projectDir): void
{
    $categorized = [];
    foreach ($collection as $controllerName => $data) {
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
                '_postman_id' => 'crm-' . strtolower(str_replace([' ', '-'], '_', $category)) . '-' . date('Ymd'),
                'name' => "CRM API - {$category}",
                'description' => "{$category} endpoints.\n\nAuto-generated on " . date('Y-m-d H:i:s'),
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
            'variable' => getDefaultVariables($baseUrl),
        ];

        foreach ($controllers as $controllerName => $data) {
            $controllerFolder = [
                'name' => str_replace('Controller', '', $controllerName),
                'item' => [],
            ];

            foreach ($data['routes'] as $route) {
                $controllerFolder['item'][] = generatePostmanRequest($route, $baseUrl);
            }

            $postmanCollection['item'][] = $controllerFolder;
        }

        $filepath = $projectDir . '/' . $filename;
        file_put_contents(
            $filepath,
            json_encode($postmanCollection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        $routeCount = array_sum(array_map(function($c) { return count($c['item']); }, $postmanCollection['item']));
        echo "  ✓ {$filename} ({$routeCount} endpoints)\n";
    }
}

function generatePostmanRequest(array $route, string $baseUrl): array
{
    $method = $route['methods'][0] ?? 'GET';
    $path = $route['path'];

    // Replace {param} with :param for Postman
    $postmanPath = preg_replace('/\{([^}]+)\}/', ':$1', $path);
    $pathParts = array_filter(explode('/', $postmanPath));

    $request = [
        'name' => ucfirst($route['name']),
        'request' => [
            'method' => $method,
            'header' => [],
            'url' => [
                'raw' => "{{base_url}}{$postmanPath}",
                'host' => ['{{base_url}}'],
                'path' => $pathParts,
            ],
            'description' => $route['description'] ?: "Endpoint: {$method} {$path}",
        ],
        'response' => [],
    ];

    // Add auth for protected endpoints
    if (!isPublicEndpoint($path)) {
        $request['request']['auth'] = [
            'type' => 'bearer',
            'bearer' => [
                ['key' => 'token', 'value' => '{{jwt_token}}', 'type' => 'string']
            ],
        ];
    }

    // Add tenant header for certain endpoints
    if (needsTenantHeader($path)) {
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
            'raw' => generateBodyExample($route),
            'options' => ['raw' => ['language' => 'json']],
        ];
    }

    // Add path variables
    if (!empty($route['parameters'])) {
        $request['request']['url']['variable'] = [];
        foreach ($route['parameters'] as $param) {
            $request['request']['url']['variable'][] = [
                'key' => $param,
                'value' => "{{$param}}",
                'description' => ucfirst($param) . " parameter",
            ];
        }
    }

    return $request;
}

function isPublicEndpoint(string $path): bool
{
    $publicPaths = ['/auth/register', '/auth/login', '/auth/verify', '/auth/forgot', '/auth/reset', '/webhooks', '/api/plans'];
    foreach ($publicPaths as $public) {
        if (strpos($path, $public) !== false) {
            return true;
        }
    }
    return false;
}

function needsTenantHeader(string $path): bool
{
    return strpos($path, '/api/subscription') !== false
        || strpos($path, '/contact') !== false
        || strpos($path, '/company') !== false
        || strpos($path, '/deal') !== false;
}

function generateBodyExample(array $route): string
{
    $path = $route['path'];

    // Auth
    if (strpos($path, '/auth/register') !== false) {
        return json_encode(['email' => 'user@example.com', 'password' => 'SecurePass123!', 'firstname' => 'John', 'lastname' => 'Doe'], JSON_PRETTY_PRINT);
    }
    if (strpos($path, '/auth/login') !== false) {
        return json_encode(['email' => 'user@example.com', 'password' => 'SecurePass123!'], JSON_PRETTY_PRINT);
    }

    // Subscription
    if (strpos($path, '/subscribe') !== false) {
        return json_encode(['plan_code' => 'pro_monthly', 'email' => 'user@example.com', 'payment_method_id' => 'pm_card_visa'], JSON_PRETTY_PRINT);
    }

    // Contact
    if (strpos($path, '/contact/edit') !== false) {
        return json_encode(['email' => 'contact@example.com', 'firstname' => 'Jane', 'lastname' => 'Smith'], JSON_PRETTY_PRINT);
    }

    // List
    if (strpos($path, '/list') !== false) {
        return json_encode(['pagination' => ['page' => 1, 'limit' => 25], 'filters' => []], JSON_PRETTY_PRINT);
    }

    return json_encode(['data' => 'example'], JSON_PRETTY_PRINT);
}

function getDefaultVariables(string $baseUrl): array
{
    return [
        ['key' => 'base_url', 'value' => $baseUrl, 'type' => 'string'],
        ['key' => 'jwt_token', 'value' => '', 'type' => 'string'],
        ['key' => 'refresh_token', 'value' => '', 'type' => 'string'],
        ['key' => 'tenant_id', 'value' => 'tenant_123', 'type' => 'string'],
        ['key' => 'user_id', 'value' => '', 'type' => 'string'],
        ['key' => 'id', 'value' => 'example_id', 'type' => 'string'],
    ];
}
