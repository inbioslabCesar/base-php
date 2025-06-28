<div class="container mt-4">
  <h2>Diseña tu Formato de Examen</h2>
  <pre>
</pre>

  <form action="dashboard.php?action=crear_examen" method="POST" id="form-examen">
    <div class="mb-3">
        <label for="codigo">Código:</label>
    <input type="text" class="form-control" name="codigo" id="codigo" placeholder="Ej: EXA-0000" required>
    </div>
    <div class="mb-3">
      <label for="nombre_examen" class="form-label">Nombre del Examen</label>
      <input type="text" class="form-control" id="nombre_examen" name="nombre" placeholder="Ej: Examen Completo de Orina" required>
    </div>
    <table class="table table-bordered table-editable" id="formatTable">
      <thead>
        <tr>
          <th>Tipo</th>
          <th>Nombre</th>
          <th>Metodología</th>
          <th>Unidad</th>
          <th>Opciones</th>
          <th>Valor(es) Referencia</th>
          <th>Fórmula</th>
          <th>Negrita</th>
          <th>Color texto</th>
          <th>Color fondo</th>
          <th>Orden</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <!-- Filas dinámicas -->
      </tbody>
    </table>
    <input type="hidden" id="adicional" name="adicional">
    <button id="addRow" class="btn btn-success mb-2" type="button">Agregar Fila</button>
    <h4>Vista Previa en Tiempo Real</h4>
    <div id="preview" class="border p-3"></div>
    <button class="btn btn-primary mt-3" type="submit" id="saveFormat">Guardar Formato</button>
  </form>
</div>
<script src="/base-php/src/examenes/format-builder.js"></script>
