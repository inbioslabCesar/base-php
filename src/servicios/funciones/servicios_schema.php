<?php

if (!function_exists('servicios_normalizar_codigo')) {
    function servicios_normalizar_codigo(string $valor): string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return '';
        }

        $valor = function_exists('mb_strtoupper') ? mb_strtoupper($valor, 'UTF-8') : strtoupper($valor);
        $valor = preg_replace('/[^A-Z0-9_-]+/', '_', $valor) ?? $valor;
        $valor = trim($valor, '_');

        return $valor;
    }
}

if (!function_exists('servicios_asegurar_tabla')) {
    function servicios_asegurar_tabla(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS servicios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(150) NOT NULL,
            codigo VARCHAR(80) NOT NULL,
            responsable VARCHAR(150) NULL,
            telefono VARCHAR(50) NULL,
            email VARCHAR(190) NOT NULL,
            password VARCHAR(255) NOT NULL,
            estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_servicios_codigo (codigo),
            UNIQUE KEY uq_servicios_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sql);

        $sqlRelacion = "CREATE TABLE IF NOT EXISTS servicio_cliente (
            id INT AUTO_INCREMENT PRIMARY KEY,
            servicio_id INT NOT NULL,
            cliente_id INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_servicio_cliente (servicio_id, cliente_id),
            KEY idx_sc_servicio (servicio_id),
            KEY idx_sc_cliente (cliente_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sqlRelacion);

        $sqlAuditoria = "CREATE TABLE IF NOT EXISTS servicio_descargas_auditoria (
            id INT AUTO_INCREMENT PRIMARY KEY,
            servicio_id INT NOT NULL,
            cotizacion_id INT NOT NULL,
            cliente_id INT NULL,
            usuario_email VARCHAR(190) NULL,
            ip VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_sda_servicio (servicio_id),
            KEY idx_sda_cotizacion (cotizacion_id),
            KEY idx_sda_cliente (cliente_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sqlAuditoria);

        $sqlProfesionales = "CREATE TABLE IF NOT EXISTS profesionales_solicitantes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombres VARCHAR(120) NOT NULL,
            apellidos VARCHAR(120) NULL,
            tipo_profesional VARCHAR(80) NOT NULL,
            numero_documento VARCHAR(30) NULL,
            registro_profesional VARCHAR(80) NULL,
            telefono VARCHAR(50) NULL,
            email VARCHAR(190) NULL,
            estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_ps_estado (estado),
            KEY idx_ps_nombre (nombres, apellidos)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sqlProfesionales);

        $sqlServicioProfesional = "CREATE TABLE IF NOT EXISTS servicio_profesional (
            id INT AUTO_INCREMENT PRIMARY KEY,
            servicio_id INT NOT NULL,
            profesional_id INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_servicio_profesional (servicio_id, profesional_id),
            KEY idx_sp_servicio (servicio_id),
            KEY idx_sp_profesional (profesional_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sqlServicioProfesional);

        $tablaCotizacionesExiste = (bool)$pdo->query("SHOW TABLES LIKE 'cotizaciones'")->fetchColumn();
        if ($tablaCotizacionesExiste) {
            $colProfIdExiste = (bool)$pdo->query("SHOW COLUMNS FROM cotizaciones LIKE 'profesional_solicitante_id'")->fetchColumn();
            if (!$colProfIdExiste) {
                $pdo->exec("ALTER TABLE cotizaciones ADD COLUMN profesional_solicitante_id INT NULL");
            }

            $colProfNombreExiste = (bool)$pdo->query("SHOW COLUMNS FROM cotizaciones LIKE 'profesional_solicitante_nombre'")->fetchColumn();
            if (!$colProfNombreExiste) {
                $pdo->exec("ALTER TABLE cotizaciones ADD COLUMN profesional_solicitante_nombre VARCHAR(190) NULL AFTER profesional_solicitante_id");
            }

            $colProfTipoExiste = (bool)$pdo->query("SHOW COLUMNS FROM cotizaciones LIKE 'profesional_solicitante_tipo'")->fetchColumn();
            if (!$colProfTipoExiste) {
                $pdo->exec("ALTER TABLE cotizaciones ADD COLUMN profesional_solicitante_tipo VARCHAR(80) NULL AFTER profesional_solicitante_nombre");
            }

            $colProfRegistroExiste = (bool)$pdo->query("SHOW COLUMNS FROM cotizaciones LIKE 'profesional_solicitante_registro'")->fetchColumn();
            if (!$colProfRegistroExiste) {
                $pdo->exec("ALTER TABLE cotizaciones ADD COLUMN profesional_solicitante_registro VARCHAR(80) NULL AFTER profesional_solicitante_tipo");
            }

            $idxProfExiste = (bool)$pdo->query("SHOW INDEX FROM cotizaciones WHERE Key_name = 'idx_cotizaciones_profesional_solicitante'")->fetchColumn();
            if (!$idxProfExiste) {
                $pdo->exec("ALTER TABLE cotizaciones ADD INDEX idx_cotizaciones_profesional_solicitante (profesional_solicitante_id)");
            }
        }
    }
}
