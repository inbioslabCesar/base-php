-- MIGRACION DE RUTAS DE IMAGENES DE EMPRESA A uploads/empresa
-- Fecha: 2026-05-20
-- Autor: GitHub Copilot
-- Descripcion: Actualiza y normaliza rutas de imagenes en config_empresa

-- 1) Normalizar separadores de ruta (casos con backslash de Windows)
UPDATE config_empresa
SET
	logo = REPLACE(logo, '\\\\', '/'),
	firma = REPLACE(firma, '\\\\', '/'),
	imagenes_carrusel = REPLACE(imagenes_carrusel, '\\\\', '/'),
	imagenes_institucionales = REPLACE(imagenes_institucionales, '\\\\', '/')
WHERE
	logo LIKE '%\\\\%'
	OR firma LIKE '%\\\\%'
	OR imagenes_carrusel LIKE '%\\\\%'
	OR imagenes_institucionales LIKE '%\\\\%';

UPDATE config_empresa
SET
	logo = REPLACE(logo, '\\', '/'),
	firma = REPLACE(firma, '\\', '/'),
	imagenes_carrusel = REPLACE(imagenes_carrusel, '\\', '/'),
	imagenes_institucionales = REPLACE(imagenes_institucionales, '\\', '/')
WHERE
	logo LIKE '%\\%'
	OR firma LIKE '%\\%'
	OR imagenes_carrusel LIKE '%\\%'
	OR imagenes_institucionales LIKE '%\\%';

-- 2) Migrar rutas legacy images/empresa -> uploads/empresa
UPDATE config_empresa 
SET imagenes_carrusel = REPLACE(imagenes_carrusel, 'images/empresa/carrusel/', 'uploads/empresa/carrusel/')
WHERE imagenes_carrusel LIKE '%images/empresa/carrusel/%';

-- 3) Migrar rutas legacy institucionales
UPDATE config_empresa 
SET imagenes_institucionales = REPLACE(imagenes_institucionales, 'images/empresa/institucional/', 'uploads/empresa/institucional/')
WHERE imagenes_institucionales LIKE '%images/empresa/institucional/%';

-- 4) Migrar logo y firma legacy
UPDATE config_empresa 
SET logo = REPLACE(logo, 'images/empresa/', 'uploads/empresa/')
WHERE logo LIKE 'images/empresa/%';

UPDATE config_empresa 
SET firma = REPLACE(firma, 'images/empresa/', 'uploads/empresa/')
WHERE firma LIKE 'images/empresa/%';

-- 5) Limpieza de prefijos redundantes causados por concatenaciones previas
UPDATE config_empresa
SET
	logo = REPLACE(logo, 'src/../', ''),
	firma = REPLACE(firma, 'src/../', ''),
	imagenes_carrusel = REPLACE(imagenes_carrusel, 'src/../', ''),
	imagenes_institucionales = REPLACE(imagenes_institucionales, 'src/../', '')
WHERE
	logo LIKE '%src/../%'
	OR firma LIKE '%src/../%'
	OR imagenes_carrusel LIKE '%src/../%'
	OR imagenes_institucionales LIKE '%src/../%';

-- Fin de migracion
