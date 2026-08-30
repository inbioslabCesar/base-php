<?php

if (!function_exists('laboratorio_turnos_asegurar_esquema')) {
    function laboratorio_turnos_asegurar_esquema(PDO $pdo): void
    {
        static $aplicado = false;
        if ($aplicado) {
            return;
        }
        $aplicado = true;

        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS laboratorio_turnos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                estado ENUM('abierto','cerrado') NOT NULL DEFAULT 'abierto',
                abierto_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                cerrado_en DATETIME NULL,
                observacion_apertura VARCHAR(255) NULL,
                observacion_cierre VARCHAR(255) NULL,
                creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_turnos_usuario_estado (usuario_id, estado),
                INDEX idx_turnos_abierto_en (abierto_en),
                CONSTRAINT fk_turnos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
            // Compatibilidad en ambientes con restricciones de FK o motor.
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS laboratorio_turnos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    estado ENUM('abierto','cerrado') NOT NULL DEFAULT 'abierto',
                    abierto_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    cerrado_en DATETIME NULL,
                    observacion_apertura VARCHAR(255) NULL,
                    observacion_cierre VARCHAR(255) NULL,
                    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_turnos_usuario_estado (usuario_id, estado),
                    INDEX idx_turnos_abierto_en (abierto_en)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Throwable $e2) {
            }
        }

        try {
            $col = $pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'id_turno'")->fetch(PDO::FETCH_ASSOC);
            if (empty($col)) {
                $pdo->exec("ALTER TABLE resultados_examenes ADD COLUMN id_turno INT NULL AFTER id_laboratorista");
            }
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('laboratorio_turnos_columna_id_turno_existe')) {
    function laboratorio_turnos_columna_id_turno_existe(PDO $pdo): bool
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        try {
            $cache = (bool)$pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'id_turno'")->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $cache = false;
        }
        return $cache;
    }
}

if (!function_exists('laboratorio_turno_abierto_por_usuario')) {
    function laboratorio_turno_abierto_por_usuario(PDO $pdo, int $usuarioId): ?array
    {
        if ($usuarioId <= 0) {
            return null;
        }
        laboratorio_turnos_asegurar_esquema($pdo);
        try {
            $stmt = $pdo->prepare("SELECT * FROM laboratorio_turnos WHERE usuario_id = ? AND estado = 'abierto' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$usuarioId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('laboratorio_turno_abrir')) {
    function laboratorio_turno_abrir(PDO $pdo, int $usuarioId, string $observacion = ''): array
    {
        laboratorio_turnos_asegurar_esquema($pdo);
        if ($usuarioId <= 0) {
            return ['ok' => false, 'msg' => 'Usuario inválido para apertura de turno.'];
        }

        $turnoActual = laboratorio_turno_abierto_por_usuario($pdo, $usuarioId);
        if ($turnoActual) {
            return ['ok' => true, 'msg' => 'Ya tienes un turno abierto.', 'turno' => $turnoActual];
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO laboratorio_turnos (usuario_id, estado, abierto_en, observacion_apertura) VALUES (?, 'abierto', NOW(), ?)");
            $stmt->execute([$usuarioId, trim($observacion) !== '' ? trim($observacion) : null]);
            $idTurno = (int)$pdo->lastInsertId();
            $turno = laboratorio_turno_abierto_por_usuario($pdo, $usuarioId);
            return ['ok' => $idTurno > 0, 'msg' => 'Turno abierto correctamente.', 'turno' => $turno];
        } catch (Throwable $e) {
            return ['ok' => false, 'msg' => 'No se pudo abrir el turno: ' . $e->getMessage()];
        }
    }
}

if (!function_exists('laboratorio_turno_cerrar')) {
    function laboratorio_turno_cerrar(PDO $pdo, int $usuarioId, string $observacion = ''): array
    {
        laboratorio_turnos_asegurar_esquema($pdo);
        if ($usuarioId <= 0) {
            return ['ok' => false, 'msg' => 'Usuario inválido para cierre de turno.'];
        }

        $turnoActual = laboratorio_turno_abierto_por_usuario($pdo, $usuarioId);
        if (!$turnoActual) {
            return ['ok' => true, 'msg' => 'No hay turno abierto para cerrar.'];
        }

        try {
            $stmt = $pdo->prepare("UPDATE laboratorio_turnos
                SET estado = 'cerrado',
                    cerrado_en = NOW(),
                    observacion_cierre = CASE
                        WHEN ? IS NULL OR TRIM(?) = '' THEN observacion_cierre
                        ELSE ?
                    END
                WHERE id = ?");
            $obs = trim($observacion);
            $stmt->execute([$obs !== '' ? $obs : null, $obs, $obs !== '' ? $obs : null, (int)$turnoActual['id']]);
            return ['ok' => true, 'msg' => 'Turno cerrado correctamente.', 'turno' => $turnoActual];
        } catch (Throwable $e) {
            return ['ok' => false, 'msg' => 'No se pudo cerrar el turno: ' . $e->getMessage()];
        }
    }
}
