-- Fase 1 SIS / Convenios
-- Persistencia del modo operativo y control del portal público.

ALTER TABLE config_empresa
    ADD COLUMN modo_operativo ENUM('PARTICULAR', 'SIS', 'MIXTO') NOT NULL DEFAULT 'PARTICULAR' AFTER celular,
    ADD COLUMN portal_publico_enable TINYINT(1) NOT NULL DEFAULT 1 AFTER modo_operativo;
