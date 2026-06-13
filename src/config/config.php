<?php
function normalizarEmpresa(string $valor): string
{
    $valor = strtolower(trim($valor));
    if ($valor === '') {
        return '';
    }
    return preg_match('/^[a-z0-9_-]+$/', $valor) ? $valor : '';
}

function rutaConfigEmpresa(string $empresa): ?string
{
    $rutas = [
        __DIR__ . "/empresas/{$empresa}.php",
        __DIR__ . "/../config/empresas/{$empresa}.php",
    ];

    foreach ($rutas as $ruta) {
        if (file_exists($ruta)) {
            return $ruta;
        }
    }

    return null;
}

$empresa = '';

// 1) Prioridad máxima: variable de entorno EMPRESA
$empresaEnv = normalizarEmpresa((string)getenv('EMPRESA'));
if ($empresaEnv !== '') {
    $empresa = $empresaEnv;
}

// 2) Si no hay EMPRESA, resolver por host conocido
if ($empresa === '') {
    $host = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
    $host = preg_replace('/:\\d+$/', '', $host ?? '');
    $host = preg_replace('/^www\./', '', $host ?? '');
    $host = rtrim((string)$host, '.');

    $mapaDominioEmpresa = [
        // Alias explícitos (cuando dominio != slug de empresa)
        'inbioslabstore.com' => 'inbioslab',
        'www.inbioslabstore.com' => 'inbioslab',
        'medditech.es' => 'medditech',
        'www.medditech.es' => 'medditech',
        'centromedicodelima.com' => 'cmdl',
        'www.centromedicodelima.com' => 'cmdl',
        'jeycolab.com' => 'jeycolab',
        'www.jeycolab.com' => 'jeycolab',
        'tecnolababrilatalaya.com' => 'tecnolab',
        'www.tecnolababrilatalaya.com' => 'tecnolab',

        // Entornos locales
        'localhost' => 'desarrollo',
        '127.0.0.1' => 'desarrollo',
        '::1' => 'desarrollo',
        'base-php.test' => 'desarrollo',
        'base-php.local' => 'desarrollo',
        'base-php.localhost' => 'desarrollo',
    ];

    if (isset($mapaDominioEmpresa[$host])) {
        $empresa = normalizarEmpresa($mapaDominioEmpresa[$host]);
    } else {
        foreach ($mapaDominioEmpresa as $dominioBase => $empresaMapeada) {
            if (str_contains($dominioBase, '.') && str_ends_with($host, '.' . $dominioBase)) {
                $empresa = normalizarEmpresa($empresaMapeada);
                break;
            }
        }

        if ($empresa === '') {
            $partesHost = array_values(array_filter(explode('.', $host), static function ($parte) {
                return $parte !== '';
            }));

            $candidatos = [];
            if (count($partesHost) >= 2) {
                $candidatos[] = $partesHost[count($partesHost) - 2];
            }
            if (count($partesHost) >= 1) {
                $candidatos[] = $partesHost[0];
            }

            foreach ($candidatos as $candidato) {
                $empresaCandidata = normalizarEmpresa($candidato);
                if ($empresaCandidata !== '' && rutaConfigEmpresa($empresaCandidata) !== null) {
                    $empresa = $empresaCandidata;
                    break;
                }
            }
        }
    }
}

// 3) Fallback fijo e intuitivo
if ($empresa === '') {
    $empresa = 'desarrollo';
}

$configEmpresaPath = rutaConfigEmpresa($empresa);
if ($configEmpresaPath === null) {
    error_log('No se encontró config de empresa: ' . $empresa);
    die('No se encontró la configuración de la empresa: ' . htmlspecialchars($empresa));
}

require_once $configEmpresaPath;

if (!defined('BASE_URL')) {
    if ($empresa === 'desarrollo') {
        define('BASE_URL', '/base-php/src/');
    } else {
        define('BASE_URL', '/src/');
    }
}