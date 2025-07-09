<?php
$id_cotizacion = isset($_GET['id_cotizacion']) ? intval($_GET['id_cotizacion']) : 0;
?>


<div class="container mt-4">
  <div class="card">
    <div class="card-body">
      <h5 class="card-title mb-4">Agendar cita de toma de muestra</h5>
      <form action="dashboard.php?action=procesar_agenda" method="POST">
        <input type="hidden" name="id_cotizacion" value="<?php echo $id_cotizacion; ?>">

        <div class="mb-3">
          <label for="tipo_toma" class="form-label">Tipo de toma de muestra</label>
          <select name="tipo_toma" id="tipo_toma" class="form-select" required onchange="toggleDireccion()">
            <option value="laboratorio">En laboratorio</option>
            <option value="domicilio">A domicilio</option>
          </select>
        </div>

        <div class="mb-3">
          <label for="fecha_toma" class="form-label">Fecha de toma</label>
          <input type="date" name="fecha_toma" id="fecha_toma" class="form-control" required>
        </div>

        <div class="mb-3">
          <label for="hora_toma" class="form-label">Hora de toma</label>
          <input type="time" name="hora_toma" id="hora_toma" class="form-control" required>
        </div>

        <div class="mb-3" id="direccion_field" style="display:none;">
          <label for="direccion_toma" class="form-label">Dirección para toma a domicilio</label>
          <input type="text" name="direccion_toma" id="direccion_toma" class="form-control">
        </div>

        <button type="submit" class="btn btn-success">Guardar cita</button>
        <a href="javascript:history.back()" class="btn btn-secondary ms-2">Cancelar</a>
      </form>
    </div>
  </div>
</div>

<script>
function toggleDireccion() {
  var tipo = document.getElementById('tipo_toma').value;
  document.getElementById('direccion_field').style.display = (tipo === 'domicilio') ? 'block' : 'none';
}
document.getElementById('tipo_toma').addEventListener('change', toggleDireccion);
window.onload = toggleDireccion;
</script>
