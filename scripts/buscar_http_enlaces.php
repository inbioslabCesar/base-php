<?php
// Busca posibles enlaces http:// en toda la BD (columnas de texto)
// Uso: subir a /scripts y abrir en navegador o ejecutar por CLI

require_once __DIR__ . '/../src/conexion/conexion.php';

header('Content-Type: text/html; charset=utf-8');

echo '<h2>Escaneo de contenido mixto (http://) en la base de datos</h2>';
echo '<p><em>Solo se analizan columnas tipo CHAR/VARCHAR/TEXT/MEDIUMTEXT/LONGTEXT/JSON.</em></p>';

try {
    $schema = $pdo->query('SELECT DATABASE()')->fetchColumn();

    $sqlCols = "
        SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = :schema
          AND DATA_TYPE IN ('char','varchar','text','mediumtext','longtext','json')
        ORDER BY TABLE_NAME, ORDINAL_POSITION
    ";
    $stCols = $pdo->prepare($sqlCols);
    $stCols->execute(['schema' => $schema]);
    $cols = $stCols->fetchAll(PDO::FETCH_ASSOC);

    $totalEnlaces = 0;
    foreach ($cols as $c) {
        $table = $c['TABLE_NAME'];
        $col   = $c['COLUMN_NAME'];

        // Consulta segura por columna
        $q = $pdo->query("SELECT COUNT(*) FROM `{$table}` WHERE CAST(`{$col}` AS CHAR) LIKE '%http://%'");
        $count = (int)$q->fetchColumn();
        if ($count > 0) {
            echo '<hr>'; 
            echo '<h4>Tabla: ' . htmlspecialchars($table) . ' — Columna: ' . htmlspecialchars($col) . '</h4>';
            echo '<p>Coincidencias: ' . $count . '</p>';

            $m = $pdo->query("SELECT LEFT(CAST(`{$col}` AS CHAR), 400) AS muestra FROM `{$table}` WHERE CAST(`{$col}` AS CHAR) LIKE '%http://%' LIMIT 20");
            $rows = $m->fetchAll(PDO::FETCH_COLUMN);
            echo '<ul style="font-family:monospace">';
            foreach ($rows as $r) {
                echo '<li>' . htmlspecialchars($r, ENT_QUOTES, 'UTF-8') . '</li>';
            }
            echo '</ul>';
            $totalEnlaces += $count;
        }
    }

    if ($totalEnlaces === 0) {
        echo '<p><strong>No se encontraron enlaces http:// en las columnas analizadas.</strong></p>';
    } else {
        echo '<p><strong>Total estimado de coincidencias:</strong> ' . $totalEnlaces . '</p>';
    }

    echo '<p style="margin-top:24px;color:#666">Sugerencia: Reemplaza http:// por https:// donde aplique. Para campos JSON (por ejemplo configuración de empresa/redes), edítalos desde tu panel o con UPDATEs puntuales.</p>';
} catch (Throwable $e) {
    http_response_code(500);
    echo '<pre>Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
}
