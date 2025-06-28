<form method="post" action="editar_formato.php?id=<?= urlencode($id) ?>" id="form-editar-formato">
    <div class="mb-3">
        <button type="button" class="btn btn-success btn-sm" id="addRow">Agregar parámetro</button>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
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
                    <th>Color Texto</th>
                    <th>Color Fondo</th>
                    <th>Orden</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="formatTableBody"></tbody>
        </table>
    </div>
    <button type="submit" class="btn btn-primary">Guardar</button>
    <input type="hidden" id="adicional" name="adicional">
</form>
<pre><?php var_dump($adicional); ?></pre>

<script src="base-php/src/examenes/format-builder-edit.js"></script>
<script>
    window.parametrosData = <?= json_encode(json_decode($adicional) ?: []); ?>;
    console.log('parametrosData:', window.parametrosData);
    
</script>