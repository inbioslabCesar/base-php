<?php
// Configuracion de APISPERU para consulta de DNI/RUC.
// Recomendado: definir APISPERU_TOKEN en variables de entorno del servidor.

$token = getenv('APISPERU_TOKEN') ?: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJlbWFpbCI6ImluYmlvc2xhYkBnbWFpbC5jb20ifQ.nniID9A9mORK-Qz1xr5i2B5S2nomx6X5Wc6gU_aeths';

return [
    'base_url' => 'https://dniruc.apisperu.com/api/v1',
    'token' => $token,
    'timeout_seconds' => 8,
];
