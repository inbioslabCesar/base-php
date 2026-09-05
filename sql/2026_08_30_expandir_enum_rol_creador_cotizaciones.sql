-- Amplia los valores permitidos de rol_creador en cotizaciones para soportar
-- origen real de portales (empresa/convenio/cliente/servicio/engineer).

ALTER TABLE cotizaciones
MODIFY COLUMN rol_creador ENUM(
    'cliente',
    'recepcionista',
    'laboratorista',
    'admin',
    'empresa',
    'convenio',
    'servicio',
    'engineer'
) NOT NULL;
