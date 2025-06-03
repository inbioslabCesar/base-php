<?php
// src/helpers/crud.php

function insertarRegistro($tabla, $datos, $pdo) {
    $tablasPermitidas = ['usuarios', 'clientes', 'empresas'];
    if (!in_array($tabla, $tablasPermitidas)) {
        die('Tabla no permitida');
    }

    // Lista blanca de campos permitidos según tu estructura
    $camposPermitidos = [
        'usuarios' => [
            'usuario', 'password', 'nombre', 'apellido', 'dni', 'sexo', 'email', 
            'telefono', 'direccion', 'profesion', 'rol', 'estado', 'fecha_registro'
        ],
        'clientes' => [
            'codigo_cliente', 'nombre', 'apellido', 'edad', 'email', 'password', 
            'telefono', 'direccion', 'dni', 'sexo', 'origen', 'referencia', 'estado', 'fecha_registro'
        ],
        'empresas' => [
            'ruc', 'razon_social', 'nombre_comercial', 'direccion', 'telefono', 'email', 
            'representante', 'password', 'convenio', 'estado', 'fecha_registro'
        ]
    ];

    $datosFiltrados = array_intersect_key($datos, array_flip($camposPermitidos[$tabla]));
    $campos = implode(", ", array_keys($datosFiltrados));
    $placeholders = ":" . implode(", :", array_keys($datosFiltrados));

    $sql = "INSERT INTO $tabla ($campos) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($datosFiltrados);
}

function actualizarRegistro($tabla, $datos, $id, $pdo, $campoId = 'id') {
    $tablasPermitidas = ['usuarios', 'clientes', 'empresas'];
    if (!in_array($tabla, $tablasPermitidas)) {
        die('Tabla no permitida');
    }

    $camposPermitidos = [
        'usuarios' => [
            'usuario', 'password', 'nombre', 'apellido', 'dni', 'sexo', 'email', 
            'telefono', 'direccion', 'profesion', 'rol', 'estado', 'fecha_registro'
        ],
        'clientes' => [
            'codigo_cliente', 'nombre', 'apellido', 'edad', 'email', 'password', 
            'telefono', 'direccion', 'dni', 'sexo', 'origen', 'referencia', 'estado', 'fecha_registro'
        ],
        'empresas' => [
            'ruc', 'razon_social', 'nombre_comercial', 'direccion', 'telefono', 'email', 
            'representante', 'password', 'convenio', 'estado', 'fecha_registro'
        ]
    ];

    $datosFiltrados = array_intersect_key($datos, array_flip($camposPermitidos[$tabla]));
    $sets = [];
    foreach ($datosFiltrados as $campo => $valor) {
        $sets[] = "$campo = :$campo";
    }
    $setsStr = implode(", ", $sets);

    $sql = "UPDATE $tabla SET $setsStr WHERE $campoId = :id";
    $stmt = $pdo->prepare($sql);
    $datosFiltrados['id'] = $id;
    return $stmt->execute($datosFiltrados);
}

function eliminarRegistro($tabla, $id, $pdo, $campoId = 'id') {
    $tablasPermitidas = ['usuarios', 'clientes', 'empresas'];
    if (!in_array($tabla, $tablasPermitidas)) {
        die('Tabla no permitida');
    }

    $sql = "DELETE FROM $tabla WHERE $campoId = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute(['id' => $id]);
}

function obtenerRegistro($tabla, $id, $pdo, $campoId = 'id') {
    $tablasPermitidas = ['usuarios', 'clientes', 'empresas'];
    if (!in_array($tabla, $tablasPermitidas)) {
        die('Tabla no permitida');
    }

    $sql = "SELECT * FROM $tabla WHERE $campoId = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
