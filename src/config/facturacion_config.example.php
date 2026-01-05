<?php
// Configuración de la API de facturación externa (EJEMPLO)
// Copia este archivo a `facturacion_config.php` y coloca tus credenciales reales.

return [
    'base_url' => 'http://127.0.0.1:8000',
    'auth' => [
        'username' => 'admin@sistema-sunat.com',
        'password' => 'CAMBIAR_ESTA_PASSWORD',
    ],
    'defaults' => [
        'company_id' => 1,
        'branch_id' => 1,
        'metodo_envio' => 'individual',
        'modo_impuestos' => 'exonerado',
        'pdf_format' => '80mm',
        'porcentaje_igv' => 0,
        'tip_afe_igv' => '20',
        'unidad' => 'NIU',
    ],
    'routes' => [
        'login' => '/api/auth/login',
        'create_boleta' => '/api/v1/boletas',
        'create_factura' => '/api/v1/invoices',
        'send_boleta' => '/api/v1/boletas/{id}/send-sunat',
        'send_factura' => '/api/v1/invoices/{id}/send-sunat',
        'get_boleta' => '/api/v1/boletas/{id}',
        'get_factura' => '/api/v1/invoices/{id}',
        'consulta_boleta' => '/api/v1/consulta-cpe/boleta/{id}',
        'consulta_factura' => '/api/v1/consulta-cpe/factura/{id}',
        'consulta_boleta_v2' => '/api/v1/consulta-cpe-v2/boleta/{id}',
        'consulta_factura_v2' => '/api/v1/consulta-cpe-v2/factura/{id}/con-cdr',
        'download_pdf_boleta' => '/api/v1/boletas/{id}/download-pdf',
        'download_pdf_factura' => '/api/v1/invoices/{id}/download-pdf',
        'generate_pdf_boleta' => '/api/v1/boletas/{id}/generate-pdf',
        'generate_pdf_factura' => '/api/v1/invoices/{id}/generate-pdf',
        'download_xml_boleta' => '/api/v1/boletas/{id}/download-xml',
        'download_xml_factura' => '/api/v1/invoices/{id}/download-xml',
        'download_cdr_boleta' => '/api/v1/boletas/{id}/download-cdr',
        'download_cdr_factura' => '/api/v1/invoices/{id}/download-cdr',
        'status_boleta' => '/api/v1/boletas/{id}/status',
        'status_factura' => '/api/v1/invoices/{id}/status',
    ],
    'token_ttl_seconds' => 3600,
];
