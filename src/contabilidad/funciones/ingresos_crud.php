<?php
require_once __DIR__ . '/../../conexion/conexion.php';

function contarIngresos($desde, $hasta, $tipo_paciente, $filtro_convenio, $filtro_empresa, $search) {
    // ...igual que en el API, pero solo cuenta
}

function listarIngresos($start, $length, $desde, $hasta, $tipo_paciente, $filtro_convenio, $filtro_empresa, $search, $orderBy, $orderDir) {
    // ...igual que en el API, pero retorna array de ingresos
}

// Puedes agregar funciones para crear, editar, eliminar ingresos si el flujo lo requiere
