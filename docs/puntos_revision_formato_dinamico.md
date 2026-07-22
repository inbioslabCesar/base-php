# Puntos de revision: funcionalidades del formato dinamico

Este documento indica donde ver y probar cada funcionalidad implementada.

## 1) Builder de examenes
Abrir:
- [src/examenes/form_examen.php](src/examenes/form_examen.php)

Aqui se revisa:
- Activar o desactivar el modo v2.
- Agregar titulos y subtitulos.
- Definir columnas dinamicas.
- Definir filas dinamicas.
- Ver la vista previa del builder.
- Ver opciones como lista desplegable en la vista previa.

## 2) Lista desplegable de parametros
Abrir:
- [src/examenes/format-builder.js](src/examenes/format-builder.js)

Aqui se revisa:
- El campo de opciones del parametro.
- La vista previa con select real.
- La primera opcion seleccionada por defecto en la vista previa.
- El ejemplo visual derivado de la primera referencia.

## 3) Captura de resultados
Abrir:
- [src/resultados/vistas/ExamCardView.php](src/resultados/vistas/ExamCardView.php)

Aqui se revisa:
- Render del examen en captura.
- Select real cuando el parametro tiene opciones.
- Referencias por edad y sexo.
- Fallback al primer valor referencial.
- Persistencia de titulos y subtitulos.

## 4) Validacion en tiempo real
Abrir:
- [src/resultados/recursos/validacion-realtime.js](src/resultados/recursos/validacion-realtime.js)

Aqui se revisa:
- Marca visual valida o invalida.
- Uso de edad y sexo del paciente.
- Aplicacion de la referencia correcta segun el contexto.

## 5) PDF de resultados
Abrir:
- [src/resultados/resultados_pdf_html.php](src/resultados/resultados_pdf_html.php)
- [src/resultados/resultados_pdf_datos.php](src/resultados/resultados_pdf_datos.php)

Aqui se revisa:
- Titulo y subtitulo impresos correctamente.
- Alineacion y colores.
- Referencias por edad y sexo en el reporte.
- Consistencia entre captura y PDF.

## 6) Prueba funcional sugerida
Usar la guia:
- [docs/prueba_funcional_formato_dinamico.md](docs/prueba_funcional_formato_dinamico.md)

Orden recomendado de prueba:
1. Builder de examen.
2. Captura de resultados.
3. PDF de resultados.
4. Reapertura del examen guardado.
5. Verificacion de un examen legacy.

## Resultado esperado
Si todo esta correcto, deberias ver:
- dropdowns en el builder y en captura,
- primera opcion por defecto,
- referencias ajustadas por edad y sexo,
- titulos y subtitulos persistidos,
- PDF alineado,
- sin regresion en el flujo legacy.
