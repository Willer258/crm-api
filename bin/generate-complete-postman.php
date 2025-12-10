#!/usr/bin/env php
<?php

/**
 * Générateur de collection Postman complète pour le CRM API
 * Génère automatiquement tous les endpoints avec scripts d'automation
 */

$baseUrl = 'http://crm-api.test';

// Configuration des scripts automatiques par pattern de route
$testScripts = [
    'auth/register' => <<<'JS'
if (pm.response.code === 201 || pm.response.code === 200) {
    const res = pm.response.json();
    const body = JSON.parse(pm.request.body.raw || '{}');
    if (body.email) pm.collectionVariables.set('user_email', body.email);
    if (res.user && res.user.id) pm.collectionVariables.set('user_id', res.user.id);
    console.log('✅ User registered:', res.user?.email || body.email);
}
JS,
    'auth/login' => <<<'JS'
if (pm.response.code === 200) {
    const res = pm.response.json();
    pm.collectionVariables.set('jwt_token', res.token);
    if (res.refreshToken) pm.collectionVariables.set('refresh_token', res.refreshToken);
    if (res.user) {
        pm.collectionVariables.set('user_id', res.user.id);
        if (res.user.currentWorkspace) {
            pm.collectionVariables.set('workspace_id', res.user.currentWorkspace.id);
            console.log('✅ Logged in - Workspace:', res.user.currentWorkspace.name);
        }
    }
    console.log('✅ JWT Token saved');
}
JS,
    'auth/refresh' => <<<'JS'
if (pm.response.code === 200) {
    const res = pm.response.json();
    pm.collectionVariables.set('jwt_token', res.token);
    console.log('✅ JWT Token refreshed');
}
JS,
    'workspace/create' => <<<'JS'
if (pm.response.code === 201 || pm.response.code === 200) {
    const res = pm.response.json();
    if (res.data && res.data.id) {
        pm.collectionVariables.set('workspace_id', res.data.id);
        console.log('✅ Workspace created:', res.data.name, 'ID:', res.data.id);
    }
}
JS,
    'workspace/list' => <<<'JS'
if (pm.response.code === 200) {
    const res = pm.response.json();
    if (res.data && res.data.length > 0) {
        pm.collectionVariables.set('workspace_id', res.data[0].id);
        console.log('✅ First workspace:', res.data[0].name, 'ID:', res.data[0].id);
    }
}
JS,
    'contact/edit' => <<<'JS'
if (pm.response.code === 200 || pm.response.code === 201) {
    const res = pm.response.json();
    if (res.contact && res.contact.id) {
        pm.collectionVariables.set('contact_id', res.contact.id);
        console.log('✅ Contact saved:', res.contact.firstName, res.contact.lastName, 'ID:', res.contact.id);
    }
}
JS,
    'contact/list' => <<<'JS'
if (pm.response.code === 200) {
    const res = pm.response.json();
    if (res.data && res.data.length > 0) {
        pm.collectionVariables.set('contact_id', res.data[0].id);
        console.log('✅ First contact:', res.data[0].firstName, res.data[0].lastName, 'ID:', res.data[0].id);
    }
}
JS,
    'company/edit' => <<<'JS'
if (pm.response.code === 200 || pm.response.code === 201) {
    const res = pm.response.json();
    if (res.company && res.company.id) {
        pm.collectionVariables.set('company_id', res.company.id);
        console.log('✅ Company saved:', res.company.name, 'ID:', res.company.id);
    }
}
JS,
    'company/list' => <<<'JS'
if (pm.response.code === 200) {
    const res = pm.response.json();
    if (res.data && res.data.length > 0) {
        pm.collectionVariables.set('company_id', res.data[0].id);
        console.log('✅ First company:', res.data[0].name, 'ID:', res.data[0].id);
    }
}
JS,
    'deal/edit' => <<<'JS'
if (pm.response.code === 200 || pm.response.code === 201) {
    const res = pm.response.json();
    if (res.deal && res.deal.id) {
        pm.collectionVariables.set('deal_id', res.deal.id);
        console.log('✅ Deal saved:', res.deal.title, 'ID:', res.deal.id);
    }
}
JS,
    'activity/edit' => <<<'JS'
if (pm.response.code === 200 || pm.response.code === 201) {
    const res = pm.response.json();
    if (res.activity && res.activity.id) {
        pm.collectionVariables.set('activity_id', res.activity.id);
        console.log('✅ Activity saved:', res.activity.title, 'ID:', res.activity.id);
    }
}
JS,
    'activity/list' => <<<'JS'
if (pm.response.code === 200) {
    const res = pm.response.json();
    if (res.data && res.data.length > 0) {
        pm.collectionVariables.set('activity_id', res.data[0].id);
        console.log('✅ First activity ID:', res.data[0].id);
    }
}
JS,
    'pipeline/edit' => <<<'JS'
if (pm.response.code === 200 || pm.response.code === 201) {
    const res = pm.response.json();
    if (res.pipeline && res.pipeline.id) {
        pm.collectionVariables.set('pipeline_id', res.pipeline.id);
        console.log('✅ Pipeline saved:', res.pipeline.name, 'ID:', res.pipeline.id);
    }
}
JS,
    'pipeline/list' => <<<'JS'
if (pm.response.code === 200) {
    const res = pm.response.json();
    if (res.data && res.data.length > 0) {
        pm.collectionVariables.set('pipeline_id', res.data[0].id);
        console.log('✅ First pipeline:', res.data[0].name, 'ID:', res.data[0].id);
    }
}
JS,
    'pipeline/info' => <<<'JS'
if (pm.response.code === 200) {
    const res = pm.response.json();
    if (res.pipeline && res.pipeline.steps && res.pipeline.steps.length > 0) {
        pm.collectionVariables.set('pipeline_step_id', res.pipeline.steps[0].id);
        console.log('✅ First pipeline step:', res.pipeline.steps[0].name, 'ID:', res.pipeline.steps[0].id);
    }
}
JS,
    'pipeline/step/edit' => <<<'JS'
if (pm.response.code === 200 || pm.response.code === 201) {
    const res = pm.response.json();
    if (res.step && res.step.id) {
        pm.collectionVariables.set('pipeline_step_id', res.step.id);
        console.log('✅ Pipeline step saved:', res.step.name, 'ID:', res.step.id);
    }
}
JS,
    'tag/create' => <<<'JS'
if (pm.response.code === 200 || pm.response.code === 201) {
    const res = pm.response.json();
    if (res.tag && res.tag.id) {
        pm.collectionVariables.set('tag_id', res.tag.id);
        console.log('✅ Tag created:', res.tag.name, 'ID:', res.tag.id);
    }
}
JS,
    'tag/list' => <<<'JS'
if (pm.response.code === 200) {
    const res = pm.response.json();
    if (res.data && res.data.length > 0) {
        pm.collectionVariables.set('tag_id', res.data[0].id);
        console.log('✅ First tag:', res.data[0].name, 'ID:', res.data[0].id);
    }
}
JS,
    'note/edit' => <<<'JS'
if (pm.response.code === 200 || pm.response.code === 201) {
    const res = pm.response.json();
    if (res.note && res.note.id) {
        pm.collectionVariables.set('note_id', res.note.id);
        console.log('✅ Note saved ID:', res.note.id);
    }
}
JS,
    'file/upload' => <<<'JS'
if (pm.response.code === 200 || pm.response.code === 201) {
    const res = pm.response.json();
    if (res.file && res.file.id) {
        pm.collectionVariables.set('file_id', res.file.id);
        console.log('✅ File uploaded:', res.file.name, 'ID:', res.file.id);
    }
}
JS,
    'file/uploader' => <<<'JS'
if (pm.response.code === 200) {
    const res = pm.response.json();
    if (res.status === 'finished' && res.id) {
        pm.collectionVariables.set('file_uuid', res.id);
        console.log('✅ Chunked upload finished:', res.filename, 'UUID:', res.id);
    }
}
JS,
    'file/list' => <<<'JS'
if (pm.response.code === 200) {
    const res = pm.response.json();
    if (res.data && res.data.length > 0) {
        pm.collectionVariables.set('file_id', res.data[0].id);
        console.log('✅ First file ID:', res.data[0].id);
    }
}
JS,
];

// Exemples de body par route
$requestBodies = [
    'auth/register' => [
        'email' => 'demo@example.com',
        'password' => 'Demo123456!',
        'firstName' => 'Demo',
        'lastName' => 'User'
    ],
    'auth/login' => [
        'email' => 'demo@example.com',
        'password' => 'Demo123456!'
    ],
    'auth/verify-email' => [
        'token' => 'verification-token-here'
    ],
    'auth/forgot-password' => [
        'email' => 'demo@example.com'
    ],
    'auth/reset-password' => [
        'token' => 'reset-token-here',
        'password' => 'NewPassword123!'
    ],
    'workspace/create' => [
        'name' => 'Mon Workspace',
        'logo' => null
    ],
    'workspace/switch' => [
        'workspaceId' => '{{workspace_id}}'
    ],
    'contact/edit' => [
        'firstName' => 'Jean',
        'lastName' => 'Dupont',
        'email' => 'jean.dupont@example.com',
        'phone' => '+33612345678',
        'company' => '{{company_id}}'
    ],
    'company/edit' => [
        'name' => 'Acme Corp',
        'email' => 'contact@acme.com',
        'phone' => '+33123456789',
        'website' => 'https://acme.com'
    ],
    'deal/edit' => [
        'title' => 'Deal 50K€',
        'value' => 50000,
        'currency' => 'EUR',
        'contact' => '{{contact_id}}',
        'company' => '{{company_id}}',
        'pipeline' => '{{pipeline_id}}',
        'expectedCloseDate' => '2025-12-31'
    ],
    'activity/edit' => [
        'title' => 'Appel de suivi',
        'type' => 'call',
        'contact' => '{{contact_id}}',
        'scheduledAt' => '2025-12-15T14:00:00Z',
        'duration' => 30
    ],
    'pipeline/edit' => [
        'name' => 'Pipeline Ventes',
        'isDefault' => true
    ],
    'pipeline/step/edit' => [
        'name' => 'Qualification',
        'pipeline' => '{{pipeline_id}}',
        'position' => 1
    ],
    'tag/create' => [
        'name' => 'VIP',
        'color' => '#FF0000'
    ],
    'tag/assign' => [
        'tagId' => '{{tag_id}}',
        'entityType' => 'contact',
        'entityId' => '{{contact_id}}'
    ],
    'note/edit' => [
        'content' => 'Note importante sur ce contact',
        'entityType' => 'contact',
        'entityId' => '{{contact_id}}'
    ],
    'contact/list' => [
        'pagination' => [
            'page' => 1,
            'limit' => 25
        ]
    ],
    'company/list' => [
        'pagination' => [
            'page' => 1,
            'limit' => 25
        ]
    ],
];

// Routes qui ne nécessitent pas d'auth
$noAuthRoutes = [
    'auth/register',
    'auth/login',
    'auth/verify-email',
    'auth/forgot-password',
    'auth/reset-password'
];

echo "🚀 Génération de la collection Postman complète...\n\n";

// Lire les routes depuis Symfony
$routesJson = shell_exec('php bin/console debug:router --format=json');
$routes = json_decode($routesJson, true);

// Filtrer les routes publiques (pas /_profiler, etc.)
$apiRoutes = array_filter($routes, function($route) {
    return !str_starts_with($route['path'], '/_');
});

echo "📊 Total routes trouvées: " . count($apiRoutes) . "\n\n";

// Organiser les routes par catégorie
$categories = [];
foreach ($apiRoutes as $route) {
    $path = $route['path'];
    $method = $route['method'];

    // Déterminer la catégorie
    $parts = explode('/', trim($path, '/'));
    $category = ucfirst($parts[0] ?? 'Other');

    if (!isset($categories[$category])) {
        $categories[$category] = [];
    }

    $categories[$category][] = $route;
}

echo "📁 Catégories: " . implode(', ', array_keys($categories)) . "\n\n";

// Trier les catégories par ordre logique
$categoryOrder = ['Auth', 'Workspace', 'Contact', 'Company', 'Deal', 'Activity', 'Pipeline', 'Tag', 'Note', 'File', 'Mail', 'Phone', 'Property', 'Item', 'Subscription', 'Plan', 'Import', 'Export'];
$sortedCategories = [];
foreach ($categoryOrder as $cat) {
    if (isset($categories[$cat])) {
        $sortedCategories[$cat] = $categories[$cat];
        unset($categories[$cat]);
    }
}
// Ajouter les catégories restantes
$sortedCategories = array_merge($sortedCategories, $categories);

// Générer la collection Postman
$collection = [
    'info' => [
        '_postman_id' => 'crm-api-complete-' . time(),
        'name' => 'CRM API - Complete Collection',
        'description' => "Collection Postman complète pour le CRM API\n\n" .
                        "**Base URL:** $baseUrl\n\n" .
                        "**Features:**\n" .
                        "- ✅ Auto-capture JWT token après login\n" .
                        "- ✅ Auto-capture workspace_id\n" .
                        "- ✅ Auto-capture IDs (contact, company, deal, etc.)\n" .
                        "- ✅ " . count($apiRoutes) . " endpoints organisés en " . count($sortedCategories) . " catégories\n" .
                        "- ✅ Scripts automatiques pour tous les endpoints critiques\n" .
                        "- ✅ Exemples de données réalistes\n\n" .
                        "**Quick Start:**\n" .
                        "1. Exécuter 'Auth > Register' (optionnel)\n" .
                        "2. Exécuter 'Auth > Login' → Token et Workspace ID sauvegardés automatiquement\n" .
                        "3. Tous les autres endpoints sont prêts à l'emploi!\n\n" .
                        "Generated: " . date('Y-m-d H:i:s'),
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
    ],
    'auth' => [
        'type' => 'bearer',
        'bearer' => [
            ['key' => 'token', 'value' => '{{jwt_token}}', 'type' => 'string']
        ]
    ],
    'event' => [
        [
            'listen' => 'prerequest',
            'script' => [
                'type' => 'text/javascript',
                'exec' => [
                    '// Global pre-request script',
                    'if (!pm.collectionVariables.get("base_url")) {',
                    '    pm.collectionVariables.set("base_url", "' . $baseUrl . '");',
                    '}'
                ]
            ]
        ],
        [
            'listen' => 'test',
            'script' => [
                'type' => 'text/javascript',
                'exec' => [
                    '// Global test script',
                    'if (pm.response.code >= 400) {',
                    '    console.error("❌ Error", pm.response.code, ":", pm.response.json());',
                    '}'
                ]
            ]
        ]
    ],
    'variable' => [
        ['key' => 'base_url', 'value' => $baseUrl, 'type' => 'string'],
        ['key' => 'jwt_token', 'value' => '', 'type' => 'string'],
        ['key' => 'refresh_token', 'value' => '', 'type' => 'string'],
        ['key' => 'workspace_id', 'value' => '', 'type' => 'string'],
        ['key' => 'user_id', 'value' => '', 'type' => 'string'],
        ['key' => 'user_email', 'value' => '', 'type' => 'string'],
        ['key' => 'contact_id', 'value' => '', 'type' => 'string'],
        ['key' => 'company_id', 'value' => '', 'type' => 'string'],
        ['key' => 'deal_id', 'value' => '', 'type' => 'string'],
        ['key' => 'activity_id', 'value' => '', 'type' => 'string'],
        ['key' => 'pipeline_id', 'value' => '', 'type' => 'string'],
        ['key' => 'pipeline_step_id', 'value' => '', 'type' => 'string'],
        ['key' => 'tag_id', 'value' => '', 'type' => 'string'],
        ['key' => 'note_id', 'value' => '', 'type' => 'string'],
        ['key' => 'file_id', 'value' => '', 'type' => 'string'],
        ['key' => 'file_uuid', 'value' => '', 'type' => 'string'],
        ['key' => 'mail_id', 'value' => '', 'type' => 'string'],
        ['key' => 'phone_id', 'value' => '', 'type' => 'string'],
        ['key' => 'property_id', 'value' => '', 'type' => 'string'],
        ['key' => 'property_model_id', 'value' => '', 'type' => 'string'],
        ['key' => 'item_type_id', 'value' => '', 'type' => 'string'],
        ['key' => 'subscription_id', 'value' => '', 'type' => 'string'],
        ['key' => 'plan_id', 'value' => '', 'type' => 'string'],
    ],
    'item' => []
];

// Générer les items pour chaque catégorie
foreach ($sortedCategories as $category => $routes) {
    echo "📂 Traitement catégorie: $category (" . count($routes) . " routes)\n";

    $categoryItem = [
        'name' => $category,
        'item' => []
    ];

    foreach ($routes as $route) {
        $path = $route['path'];
        $method = strtoupper($route['method']);
        if ($method === 'ANY') $method = 'POST'; // ANY devient POST par défaut

        // Nom de la requête
        $pathClean = trim($path, '/');
        $requestName = str_replace('/', ' ', ucwords(str_replace(['/', '-', '_'], ' ', $pathClean)));

        // Créer la requête
        $request = [
            'name' => $requestName,
            'request' => [
                'method' => $method,
                'header' => [],
                'url' => [
                    'raw' => '{{base_url}}' . $path,
                    'host' => ['{{base_url}}'],
                    'path' => array_filter(explode('/', trim($path, '/')))
                ]
            ],
            'response' => []
        ];

        // Ajouter auth sauf pour les routes publiques
        $routeKey = str_replace('/', '/', trim($path, '/'));
        $needsAuth = true;
        foreach ($noAuthRoutes as $noAuthRoute) {
            if (str_contains($routeKey, $noAuthRoute)) {
                $needsAuth = false;
                break;
            }
        }

        if (!$needsAuth) {
            $request['request']['auth'] = ['type' => 'noauth'];
        } else {
            // Ajouter X-Workspace-Id header
            $request['request']['header'][] = [
                'key' => 'X-Workspace-Id',
                'value' => '{{workspace_id}}',
                'type' => 'text'
            ];
        }

        // Ajouter body pour POST/PUT/PATCH
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            // Chercher un body exemple
            $bodyExample = null;
            foreach ($requestBodies as $bodyPath => $bodyData) {
                if (str_contains($routeKey, $bodyPath)) {
                    $bodyExample = $bodyData;
                    break;
                }
            }

            if ($bodyExample) {
                $request['request']['header'][] = [
                    'key' => 'Content-Type',
                    'value' => 'application/json'
                ];
                $request['request']['body'] = [
                    'mode' => 'raw',
                    'raw' => json_encode($bodyExample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                ];
            }
        }

        // Ajouter test script si applicable
        foreach ($testScripts as $scriptPath => $scriptCode) {
            if (str_contains($routeKey, $scriptPath)) {
                $request['event'] = [[
                    'listen' => 'test',
                    'script' => [
                        'exec' => explode("\n", $scriptCode),
                        'type' => 'text/javascript'
                    ]
                ]];
                break;
            }
        }

        $categoryItem['item'][] = $request;
    }

    $collection['item'][] = $categoryItem;
}

// Sauvegarder la collection
$outputFile = __DIR__ . '/../CRM_API_COMPLETE.postman_collection.json';
file_put_contents($outputFile, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

echo "\n✅ Collection générée: $outputFile\n";
echo "📊 Total endpoints: " . count($apiRoutes) . "\n";
echo "📁 Total catégories: " . count($sortedCategories) . "\n";
echo "🎯 Variables collection: " . count($collection['variable']) . "\n";
echo "🤖 Scripts automatiques: " . count($testScripts) . "\n";
echo "\n🎉 Done!\n";
