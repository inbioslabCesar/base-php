-- Agregar campo referencia_personalizada a la tabla cotizaciones
-- Este campo permite modificar la referencia que aparece en el PDF de resultados

ALTER TABLE cotizaciones 
ADD COLUMN referencia_personalizada VARCHAR(100) NULL 
COMMENT 'Referencia personalizada para mostrar en PDF en lugar de empresa/convenio/particular';

-- Comentario explicativo
-- Este campo permite que en el formulario de resultados se pueda especificar
-- una referencia personalizada que aparecerá en el PDF en lugar de mostrar
-- el nombre real de la empresa, convenio o "particular".
-- Útil cuando los convenios no quieren que aparezca su nombre en el documento.