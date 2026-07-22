# Prueba funcional: formato dinamico y paridad legacy

Fecha sugerida de ejecucion: 2026-07-19

## Objetivo
Validar en un solo caso real todo lo que se implemento en el flujo de formatos de examenes:
- builder con opciones tipo lista desplegable,
- vista previa con select real,
- primera opcion por defecto,
- referencias por edad y sexo,
- multiples valores referenciales,
- titulos y subtitulos con persistencia,
- alineacion correcta en captura y PDF,
- comportamiento consistente entre formato antiguo y nuevo.

## Datos de prueba
Usar un examen de laboratorio de ejemplo con estos elementos:

- Titulo principal: Hematologia
- Subtitulo: Serie roja
- Parametro 1: Tipo de muestra
  - Opciones: Suero, Plasma, Sangre total
  - Debe mostrarse como desplegable.
  - Si no hay valor guardado, debe quedar seleccionada la primera opcion.
- Parametro 2: Glucosa
  - Referencia 1: 70-110, sexo cualquiera, edad cualquiera
  - Referencia 2: 65-100, sexo femenino, edad 0-17
  - Referencia 3: 75-120, sexo masculino, edad 18-99
- Parametro 3: Observacion libre
  - Debe conservar alineacion, color de fondo y color de texto.

## Pacientes de prueba
Usar al menos estos dos pacientes para forzar la logica de edad y sexo:

1. Paciente A
- Sexo: femenino
- Edad: 12 anos
- Esperado: debe tomar la referencia femenina/0-17 para Glucosa.

2. Paciente B
- Sexo: masculino
- Edad: 35 anos
- Esperado: debe tomar la referencia masculina/18-99 para Glucosa.

Si el paciente no coincide con ningun rango especifico, debe caer al primer valor referencial disponible.

## Caso de prueba 1: Builder nuevo y vista previa
1. Abrir el formulario de creacion o edicion del examen.
2. Crear un titulo con color de fondo visible.
3. Crear un subtitulo.
4. Agregar un parametro con opciones: Suero, Plasma, Sangre total.
5. Agregar al menos dos referencias.
6. Guardar y revisar la vista previa.

Resultado esperado:
- El titulo se guarda.
- El subtitulo se guarda.
- La columna de opciones se ve como select real en la vista previa.
- El select muestra Suero como primera opcion por defecto.
- Las referencias se muestran con su rango de sexo y edad cuando aplica.

## Caso de prueba 2: Captura de resultados
1. Abrir el examen en la pantalla de captura.
2. Verificar que el parametro con opciones se renderiza como lista desplegable.
3. No seleccionar nada manualmente si el formulario inicia vacio.
4. Revisar que la primera opcion quede preseleccionada.
5. Cargar valores para Glucosa con los dos pacientes de prueba.

Resultado esperado:
- La lista desplegable aparece correctamente.
- La primera opcion queda seleccionada por defecto.
- La referencia aplicada coincide con sexo y edad del paciente.
- Si no hay coincidencia exacta, se usa el primer valor referencial.

## Caso de prueba 3: Guardado y reapertura
1. Guardar el examen con titulos, subtitulos, opciones y referencias.
2. Cerrar la edicion.
3. Reabrir el examen.

Resultado esperado:
- El titulo sigue presente.
- El subtitulo sigue presente.
- Las opciones siguen guardadas.
- Las referencias siguen guardadas.
- No aparecen titulos duplicados.

## Caso de prueba 4: PDF de resultados
1. Generar el PDF del examen con un paciente femenino de 12 anos.
2. Generar el PDF del examen con un paciente masculino de 35 anos.

Resultado esperado:
- El PDF respeta alineacion y colores.
- El encabezado del titulo se imprime correctamente.
- La lista de opciones no rompe el maquetado.
- La referencia aplicada en PDF coincide con edad y sexo.
- Los bordes y columnas se ven consistentes con el formato esperado.

## Caso de prueba 5: Paridad legacy
1. Abrir un examen antiguo con el formato clasico.
2. Revisar captura, guardado y PDF.

Resultado esperado:
- El examen legacy sigue funcionando igual.
- No se rompen los campos antiguos.
- La logica de referencias y alineacion permanece estable.

## Criterios de aprobacion
La prueba se considera aprobada si se cumplen todos estos puntos:
- El builder nuevo muestra opciones como dropdown real.
- La primera opcion se selecciona por defecto.
- Edad y sexo discriminan correctamente la referencia.
- Existe fallback al primer valor referencial.
- Titulo y subtitulo se guardan y reaparecen.
- El PDF queda alineado y legible.
- El flujo legacy no cambia su comportamiento.

## Observaciones durante la prueba
Anotar aqui cualquier diferencia visual o funcional encontrada:
- 
- 
- 
