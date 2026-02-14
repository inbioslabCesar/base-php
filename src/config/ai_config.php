<?php
// Configuración global de AI
// Habilita el uso de Claude Haiku 4.5 para todos los clientes.
// Puedes cambiar provider/model según tu proveedor.
return [
    'enabled' => true,
    'scope' => 'all_clients', // all_clients | per_company | per_user
    'provider' => 'anthropic',
    'model' => 'claude-haiku-4.5',
    // Opcional: límites y timeouts
    'limits' => [
        'max_requests_per_minute' => 120,
        'max_tokens' => 4096,
        'timeout_seconds' => 30,
    ],
    // Opcional: claves/credenciales (recomendado cargar por entorno)
    'auth' => [
        'api_key_env' => 'ANTHROPIC_API_KEY',
    ],
];
