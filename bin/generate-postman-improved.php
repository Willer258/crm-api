#!/usr/bin/env php
<?php
/**
 * FIXED Postman Collection Generator
 * Correction du bug [object Object] dans les URLs
 */

$projectDir = dirname(__DIR__);
$controllersDir = $projectDir . '/src/Controller';

// CLI options
$split = in_array('--split', $argv);
$baseUrl = 'http://localhost:8000';
$output = 'CRM_API_Complete.postman_collection.json';

foreach ($argv as $arg) {
    if (strpos($arg, '--base-url=') === 0) $baseUrl = substr($arg, 11);
    if (strpos($arg, '--output=') === 0) $output = substr($arg, 9);
}

echo "🚀 Postman Collection Generator (Fixed)\n";
echo "=========================================\n\n";

// Scan
echo "📁 Scanning controllers...\n";
$files = glob($controllersDir . '/*.php');
$collection = [];
$totalRoutes = 0;

foreach ($files as $file) {
    $name = basename($file, '.php');
    $content = file_get_contents($file);
    
    // Controller prefix
    $prefix = '';
    if (preg_match('/#\[Route\([\'"]([^\'")]+)[\'"].*?\)\]\s*(?:final\s+)?class/s', $content, $m)) {
        $prefix = $m[1];
    }
    
    // Find all method routes
    preg_match_all('/#\[Route\(([^\n]+(?:\n[^\]]*)?)\)\][^\{]*public\s+function\s+(\w+)/s', $content, $matches, PREG_SET_ORDER);
    
    $routes = [];
    foreach ($matches as $match) {
        $attr = $match[1];
        $func = $match[2];
        
        // Path
        if (!preg_match('/[\'"]([^\'")]+)[\'"]/', $attr, $pm)) continue;
        $path = $prefix . $pm[1];
        
        // Methods
        $methods = ['GET'];
        if (preg_match('/methods:\s*\[[\'"]?([^\]]+?)[\'"]?\]/', $attr, $mm)) {
            $methods = array_map('trim', explode(',', str_replace(["'", '"'], '', $mm[1])));
        }
        
        // Description
        $desc = '';
        if (preg_match('/[\'"]description[\'"]\s*=>\s*[\'"]([^\'")]+)[\'"]/', $attr, $dm)) {
            $desc = $dm[1];
        }
        
        // Parameters
        preg_match_all('/\{([^}]+)\}/', $path, $params);
        
        $routes[] = [
            'name' => $func,
            'path' => $path,
            'methods' => $methods,
            'description' => $desc,
            'parameters' => $params[1] ?? [],
        ];
        $totalRoutes++;
    }
    
    if (!empty($routes)) {
        $collection[$name] = [
            'category' => categorize($name),
            'routes' => $routes,
        ];
        echo "  ✓ {$name}: " . count($routes) . " routes\n";
    }
}

echo "\n  📊 Total: {$totalRoutes} routes from " . count($collection) . " controllers\n\n";

// Generate
echo "📦 Generating collection...\n";

$categorized = [];
foreach ($collection as $name => $data) {
    $cat = $data['category'];
    $categorized[$cat][$name] = $data;
}

if ($split) {
    foreach ($categorized as $cat => $controllers) {
        $filename = strtolower(str_replace([' ', '-'], '_', $cat)) . '.postman_collection.json';
        $json = buildCollection($cat, $controllers, $baseUrl);
        file_put_contents($projectDir . '/' . $filename, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $count = array_sum(array_map(fn($c) => count($c['routes']), $controllers));
        echo "  ✓ {$filename} ({$count} endpoints)\n";
    }
} else {
    $json = buildFullCollection($categorized, $baseUrl, $totalRoutes);
    file_put_contents($projectDir . '/' . $output, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    echo "  ✓ {$output} ({$totalRoutes} endpoints)\n";
}

echo "\n✅ Done! URLs are now correct.\n";

// Functions
function categorize($name) {
    $cats = [
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
    foreach ($cats as $cat => $list) {
        if (in_array($name, $list)) return $cat;
    }
    return 'Other';
}

function emoji($cat) {
    return ['Auth' => '🔐', 'Billing' => '💳', 'CRM - Core' => '📊', 'CRM - Activities' => '📅', 'CRM - Content' => '📝', 
            'Communication' => '📧', 'Files' => '📁', 'Configuration' => '⚙️', 'Administration' => '👥'][$cat] ?? '📦';
}

function buildFullCollection($categorized, $baseUrl, $total) {
    $items = [];
    foreach ($categorized as $cat => $controllers) {
        $folder = ['name' => emoji($cat) . ' ' . $cat, 'item' => []];
        foreach ($controllers as $name => $data) {
            $sub = ['name' => str_replace('Controller', '', $name), 'item' => []];
            foreach ($data['routes'] as $route) {
                $sub['item'][] = makeRequest($route, $baseUrl);
            }
            $folder['item'][] = $sub;
        }
        $items[] = $folder;
    }
    return [
        'info' => [
            '_postman_id' => 'crm-complete-' . date('Ymd-His'),
            'name' => 'CRM API - Complete',
            'description' => "Complete API ({$total} endpoints)\nGenerated: " . date('Y-m-d H:i:s'),
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ],
        'item' => $items,
        'variable' => vars($baseUrl),
    ];
}

function buildCollection($category, $controllers, $baseUrl) {
    $items = [];
    foreach ($controllers as $name => $data) {
        $folder = ['name' => str_replace('Controller', '', $name), 'item' => []];
        foreach ($data['routes'] as $route) {
            $folder['item'][] = makeRequest($route, $baseUrl);
        }
        $items[] = $folder;
    }
    return [
        'info' => [
            '_postman_id' => 'crm-' . strtolower(str_replace([' ', '-'], '_', $category)) . '-' . date('Ymd'),
            'name' => "CRM API - {$category}",
            'description' => "{$category} endpoints\nGenerated: " . date('Y-m-d H:i:s'),
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ],
        'item' => $items,
        'variable' => vars($baseUrl),
    ];
}

function makeRequest($route, $baseUrl) {
    $method = $route['methods'][0] ?? 'GET';
    $path = $route['path'];
    $postmanPath = preg_replace('/\{([^}]+)\}/', ':$1', $path);
    
    // FIX: Utiliser url comme string au lieu d'objet pour éviter [object Object]
    $req = [
        'name' => ucfirst($route['name']),
        'request' => [
            'method' => $method,
            'header' => [],
            'url' => "{{base_url}}{$postmanPath}",  // ← FIX: String simple au lieu d'objet
            'description' => $route['description'] ?: "{$method} {$path}",
        ],
        'response' => [],
    ];
    
    // Auth
    if (!isPublic($path)) {
        $req['request']['auth'] = ['type' => 'bearer', 'bearer' => [['key' => 'token', 'value' => '{{jwt_token}}', 'type' => 'string']]];
    }
    
    // Tenant header
    if (needsTenant($path)) {
        $req['request']['header'][] = ['key' => 'X-Tenant-ID', 'value' => '{{tenant_id}}', 'type' => 'text'];
    }
    
    // Body
    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $req['request']['header'][] = ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'];
        $req['request']['body'] = ['mode' => 'raw', 'raw' => bodyExample($route), 'options' => ['raw' => ['language' => 'json']]];
    }
    
    return $req;
}

function isPublic($path) {
    foreach (['/auth/register', '/auth/login', '/auth/verify', '/auth/forgot', '/auth/reset', '/webhooks', '/api/plans/list', '/api/plans/compare'] as $p) {
        if (strpos($path, $p) !== false) return true;
    }
    return false;
}

function needsTenant($path) {
    return strpos($path, '/api/subscription') !== false || strpos($path, '/contact') !== false 
        || strpos($path, '/company') !== false || strpos($path, '/deal') !== false;
}

function bodyExample($route) {
    $p = $route['path'];
    if (strpos($p, '/auth/register') !== false) return json_encode(['email' => 'user@example.com', 'password' => 'SecurePass123!', 'firstname' => 'John', 'lastname' => 'Doe'], JSON_PRETTY_PRINT);
    if (strpos($p, '/auth/login') !== false) return json_encode(['email' => 'user@example.com', 'password' => 'SecurePass123!'], JSON_PRETTY_PRINT);
    if (strpos($p, '/subscribe') !== false) return json_encode(['plan_code' => 'pro_monthly', 'email' => 'user@example.com', 'payment_method_id' => 'pm_card_visa'], JSON_PRETTY_PRINT);
    if (strpos($p, '/contact/edit') !== false) return json_encode(['email' => 'contact@example.com', 'firstname' => 'Jane', 'lastname' => 'Smith'], JSON_PRETTY_PRINT);
    if (strpos($p, '/list') !== false) return json_encode(['pagination' => ['page' => 1, 'limit' => 25], 'filters' => []], JSON_PRETTY_PRINT);
    return json_encode(['data' => 'example'], JSON_PRETTY_PRINT);
}

function vars($baseUrl) {
    return [
        ['key' => 'base_url', 'value' => $baseUrl, 'type' => 'string'],
        ['key' => 'jwt_token', 'value' => '', 'type' => 'string'],
        ['key' => 'refresh_token', 'value' => '', 'type' => 'string'],
        ['key' => 'tenant_id', 'value' => 'tenant_123', 'type' => 'string'],
        ['key' => 'user_id', 'value' => '', 'type' => 'string'],
        ['key' => 'id', 'value' => 'example_id', 'type' => 'string'],
    ];
}
