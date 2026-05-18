<?php

class SnapshotSyncService
{
    private $pdo;
    private $hasSnapshotCol = null;
    private $hasEstadoCol = null;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    private function hasSnapshotColumn()
    {
        if ($this->hasSnapshotCol !== null) {
            return $this->hasSnapshotCol;
        }

        try {
            $col = $this->pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'adicional_snapshot'")->fetch(PDO::FETCH_ASSOC);
            $this->hasSnapshotCol = !empty($col);
        } catch (Exception $e) {
            $this->hasSnapshotCol = false;
        }

        return $this->hasSnapshotCol;
    }

    private function hasEstadoColumn()
    {
        if ($this->hasEstadoCol !== null) {
            return $this->hasEstadoCol;
        }

        try {
            $col = $this->pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'estado'")->fetch(PDO::FETCH_ASSOC);
            $this->hasEstadoCol = !empty($col);
        } catch (Exception $e) {
            $this->hasEstadoCol = false;
        }

        return $this->hasEstadoCol;
    }

    public function syncExamSnapshotsPreservingHeaders($idExamen)
    {
        $idExamen = (int)$idExamen;
        if ($idExamen <= 0 || !$this->hasSnapshotColumn()) {
            return 0;
        }

        $whereEstado = '';
        if ($this->hasEstadoColumn()) {
            // Sincronizar automáticamente todos los estados operativos y omitir solo anulados.
            $whereEstado = " AND (re.estado IS NULL OR re.estado = '' OR re.estado <> 'anulado')";
        }

        $sql = "SELECT re.id, re.adicional_snapshot, re.resultados, e.adicional
                FROM resultados_examenes re
                JOIN examenes e ON e.id = re.id_examen
                WHERE re.id_examen = :id_examen" . $whereEstado;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id_examen' => $idExamen]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return 0;
        }

        $detectBefore = function (array $arr, $idx) {
            $idx = (int)$idx;
            if ($idx <= 0) {
                return '__FIRST__';
            }
            for ($j = $idx + 1; $j < count($arr); $j++) {
                $t2 = $arr[$j]['tipo'] ?? '';
                if (in_array($t2, ['Parámetro', 'Campo', 'Texto Largo'], true)) {
                    $n2 = $arr[$j]['nombre'] ?? '';
                    return ($n2 !== '') ? $n2 : '__END__';
                }
            }
            return '__END__';
        };

        $normKey = function ($s) {
            $s = (string)$s;
            $s = trim($s);
            if ($s === '') {
                return '';
            }
            $s = preg_replace('/\s+/u', ' ', $s);
            $s = mb_strtolower($s, 'UTF-8');
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            if ($ascii !== false && $ascii !== null) {
                $s = $ascii;
            }
            $s = preg_replace('/[^a-z0-9 ._-]/', '', $s);
            return $s;
        };

        $isCampoValor = function ($tipo) {
            return in_array((string)$tipo, ['Parámetro', 'Campo', 'Texto Largo'], true);
        };

        $upd = $this->pdo->prepare("UPDATE resultados_examenes SET adicional_snapshot = :snap WHERE id = :id");
        $updated = 0;

        foreach ($rows as $r) {
            $old = $r['adicional_snapshot'] ?? '';
            $base = $r['adicional'] ?? '';
            $resultadosRaw = $r['resultados'] ?? '';

            $oldArr = $old ? json_decode($old, true) : [];
            if (!is_array($oldArr)) {
                $oldArr = [];
            }
            $baseArr = $base ? json_decode($base, true) : [];
            if (!is_array($baseArr)) {
                $baseArr = [];
            }
            $resultadosArr = $resultadosRaw ? json_decode($resultadosRaw, true) : [];
            if (!is_array($resultadosArr)) {
                $resultadosArr = [];
            }

            $resultadosStable = [];
            foreach ($resultadosArr as $k => $v) {
                if (preg_match('/^id_parametro_(.+)$/', (string)$k, $m)) {
                    $resultadosStable[] = trim((string)$m[1]);
                }
            }

            $custom = [];
            foreach ($oldArr as $i => $it) {
                $tipo = $it['tipo'] ?? '';
                if (!in_array($tipo, ['Título', 'Subtítulo'], true)) {
                    continue;
                }
                if (!empty($it['id_parametro'])) {
                    continue;
                }
                $custom[] = [
                    'before' => $detectBefore($oldArr, $i),
                    'item' => $it,
                ];
            }

            foreach ($custom as $c) {
                $before = (string)($c['before'] ?? '__END__');
                $insertAt = null;
                if ($before === '__FIRST__') {
                    $insertAt = 0;
                } elseif ($before !== '__END__' && $before !== '') {
                    foreach ($baseArr as $k => $it2) {
                        $t = $it2['tipo'] ?? '';
                        $n = $it2['nombre'] ?? '';
                        if (in_array($t, ['Parámetro', 'Campo', 'Texto Largo'], true) && $n === $before) {
                            $insertAt = $k;
                            break;
                        }
                    }
                }

                if ($insertAt === null) {
                    $baseArr[] = $c['item'];
                } else {
                    array_splice($baseArr, $insertAt, 0, [$c['item']]);
                }
            }

            $oldByName = [];
            $oldByOrder = [];
            foreach ($oldArr as $itOld) {
                if (!is_array($itOld)) {
                    continue;
                }
                if (!$isCampoValor($itOld['tipo'] ?? '')) {
                    continue;
                }
                $idOld = trim((string)($itOld['id_parametro'] ?? ''));
                if ($idOld === '') {
                    continue;
                }

                $nameOld = trim((string)($itOld['nombre'] ?? ''));
                $nkOld = $normKey($nameOld);
                if ($nkOld !== '' && !isset($oldByName[$nkOld])) {
                    $oldByName[$nkOld] = $idOld;
                }

                $ordenOld = isset($itOld['orden']) ? (string)$itOld['orden'] : '';
                if ($ordenOld !== '' && !isset($oldByOrder[$ordenOld])) {
                    $oldByOrder[$ordenOld] = $idOld;
                }
            }

            $baseParamIdx = [];
            foreach ($baseArr as $idxBase => $itBase) {
                if (!is_array($itBase)) {
                    continue;
                }
                if ($isCampoValor($itBase['tipo'] ?? '')) {
                    $baseParamIdx[] = $idxBase;
                }
            }

            foreach ($baseParamIdx as $idxBase) {
                $itBase = $baseArr[$idxBase];
                $idBase = trim((string)($itBase['id_parametro'] ?? ''));
                $nameBase = trim((string)($itBase['nombre'] ?? ''));
                $nkBase = $normKey($nameBase);
                $ordenBase = isset($itBase['orden']) ? (string)$itBase['orden'] : '';

                $idElegido = $idBase;

                if ($nkBase !== '' && isset($oldByName[$nkBase])) {
                    $idElegido = $oldByName[$nkBase];
                } elseif ($ordenBase !== '' && isset($oldByOrder[$ordenBase])) {
                    $idElegido = $oldByOrder[$ordenBase];
                } elseif (count($baseParamIdx) === 1 && count($resultadosStable) === 1) {
                    $idElegido = $resultadosStable[0];
                }

                if ($idElegido !== '') {
                    $baseArr[$idxBase]['id_parametro'] = $idElegido;
                }
            }

            $upd->execute([
                'snap' => json_encode($baseArr, JSON_UNESCAPED_UNICODE),
                'id' => $r['id'],
            ]);
            $updated++;
        }

        return $updated;
    }
}
