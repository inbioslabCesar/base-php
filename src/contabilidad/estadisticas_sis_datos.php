<?php
// Datos del reporte de estadística SIS por código prestacional (sin precios: solo conteos).

if (!function_exists('sis_estadistica_rango')) {
    // Normaliza el rango Y-m-d; por defecto, el mes en curso. Siempre desde <= hasta.
    function sis_estadistica_rango(?string $desde, ?string $hasta): array
    {
        $valida = static function (?string $f): ?string {
            $f = trim((string)$f);
            $d = DateTime::createFromFormat('Y-m-d', $f);

            return ($d instanceof DateTime && $d->format('Y-m-d') === $f) ? $f : null;
        };
        $desde = $valida($desde) ?? date('Y-m-01');
        $hasta = $valida($hasta) ?? date('Y-m-t');
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [$desde, $hasta];
    }
}

if (!function_exists('sis_estadistica_resumen')) {
    /**
     * @return array{disponible: bool, filas: array, total_atenciones: int, total_pacientes: int, total_examenes: int}
     */
    function sis_estadistica_resumen(PDO $pdo, string $desde, string $hasta): array
    {
        $resumen = ['disponible' => false, 'filas' => [], 'total_atenciones' => 0, 'total_pacientes' => 0, 'total_examenes' => 0];
        if (!app_database_has_column($pdo, 'cotizaciones', 'es_sis') || !sis_prestacional_columnas_ok($pdo)) {
            return $resumen;
        }
        $resumen['disponible'] = true;

        $ini = $desde . ' 00:00:00';
        $fin = date('Y-m-d 00:00:00', strtotime($hasta . ' +1 day'));
        $filtro = "c.es_sis = 1 AND c.fecha >= ? AND c.fecha < ? AND (c.estado_pago IS NULL OR c.estado_pago <> 'anulada')";

        $stmt = $pdo->prepare(
            "SELECT c.codigo_prestacional AS codigo,
                    COUNT(*) AS atenciones,
                    COUNT(DISTINCT c.id_cliente) AS pacientes,
                    COALESCE(SUM(ex.examenes), 0) AS examenes
             FROM cotizaciones c
             LEFT JOIN (
                 SELECT cd.id_cotizacion, SUM(COALESCE(cd.cantidad, 1)) AS examenes
                 FROM cotizaciones_detalle cd
                 INNER JOIN cotizaciones c2 ON c2.id = cd.id_cotizacion
                 WHERE c2.es_sis = 1 AND c2.fecha >= ? AND c2.fecha < ?
                 GROUP BY cd.id_cotizacion
             ) ex ON ex.id_cotizacion = c.id
             WHERE {$filtro}
             GROUP BY c.codigo_prestacional"
        );
        $stmt->execute([$ini, $fin, $ini, $fin]);
        $porCodigo = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $porCodigo[(string)($r['codigo'] ?? '')] = $r;
        }

        $stmtTot = $pdo->prepare("SELECT COUNT(DISTINCT c.id_cliente) FROM cotizaciones c WHERE {$filtro}");
        $stmtTot->execute([$ini, $fin]);
        $resumen['total_pacientes'] = (int)$stmtTot->fetchColumn();

        $armar = static function (array $cat, ?array $r): array {
            return $cat + [
                'atenciones' => (int)($r['atenciones'] ?? 0),
                'pacientes' => (int)($r['pacientes'] ?? 0),
                'examenes' => (int)($r['examenes'] ?? 0),
            ];
        };

        // Se listan todos los códigos del catálogo (aunque estén en 0) y los códigos históricos que ya no existen.
        foreach (sis_prestacional_catalogo($pdo, false) as $cat) {
            $cod = (string)$cat['codigo'];
            $resumen['filas'][] = $armar($cat, $porCodigo[$cod] ?? null);
            unset($porCodigo[$cod]);
        }
        foreach ($porCodigo as $cod => $r) {
            $etiqueta = $cod === '' ? 'Sin código' : 'Código ' . $cod;
            $resumen['filas'][] = $armar([
                'codigo' => $cod === '' ? '—' : $cod,
                'nombre' => $etiqueta,
                'descripcion_minsa' => $cod === '' ? 'Atenciones sin edad/fecha de nacimiento del paciente' : null,
                'icono_clase' => 'bi-question-circle',
                'icono_ruta' => null,
                'color' => '#6c757d',
            ], $r);
        }

        foreach ($resumen['filas'] as $f) {
            $resumen['total_atenciones'] += $f['atenciones'];
            $resumen['total_examenes'] += $f['examenes'];
        }

        return $resumen;
    }
}
