// Función para obtener parámetros de la URL
function getParameterByName(name) {
    const url = window.location.href;
    name = name.replace(/[\[\]]/g, '\\$&');
    const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
    const results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

const id = getParameterByName('id');

$(document).ready(function() {
    $.ajax({
        url: 'descarga-pdf.php',
        method: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(data) {
            // Mostrar datos del paciente
            $('#datos-paciente').html(
                `<strong>Nombre:</strong> ${data.paciente.nombre}   
                 <strong>Edad:</strong> ${data.paciente.edad}   
                 <strong>Sexo:</strong> ${data.paciente.sexo}`
            );
            $('#fecha-reporte').text(data.paciente.fecha);
            $('#id-paciente').text(data.paciente.id);

            // Llenar la tabla de resultados
            let filas = '';
            data.resultados.forEach(function(r) {
                filas += `<tr>
                  <td>${r.prueba}</td>
                  <td>${r.metodologia}</td>
                  <td>${r.resultado}</td>
                  <td>${r.unidades}</td>
                  <td>${r.referencia}</td>
                </tr>`;
            });
            $('#tabla-resultados').html(filas);
        }
    });

    // Descarga el reporte como PDF
    $('#btn-descargar-pdf').click(function() {
        var element = document.getElementById('reporte-pdf');
        var opt = {
            margin:       0.5,
            filename:     'reporte-resultados.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    });
});
