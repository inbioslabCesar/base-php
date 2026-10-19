-- Rollback: Cotizador Express (historial, conversion y plantillas)
-- ATENCION: elimina el historial de cotizaciones express y las plantillas guardadas.
-- No afecta `cotizaciones`, pagos, resultados ni la deuda de Contabilidad.
-- El codigo PHP tolera la ausencia de estas tablas (el cotizador sigue funcionando sin historial).

DROP TABLE IF EXISTS cotizaciones_express_items;
DROP TABLE IF EXISTS cotizaciones_express;
DROP TABLE IF EXISTS cotizador_express_plantillas;
