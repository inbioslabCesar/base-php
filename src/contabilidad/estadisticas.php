<?php
// Vista: Estadística de exámenes por mes (cantidad y monto)
$month = isset($_GET['month']) ? trim((string)$_GET['month']) : '';
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}
?>
<div class="container mt-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="mb-0">Estadística de Exámenes</h3>
        <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <label for="monthPick" class="form-label mb-0">Mes</label>
                <input type="month" id="monthPick" class="form-control" value="<?= htmlspecialchars($month) ?>" style="max-width: 220px;">
                <div class="form-check ms-md-2">
                    <input class="form-check-input" type="checkbox" value="1" id="chkTop10">
                    <label class="form-check-label" for="chkTop10">Top 10</label>
                </div>
            </div>
            <div class="d-grid gap-2 d-md-flex">
                <button type="button" class="btn btn-primary w-100 w-md-auto" id="btnCargarEst">Ver</button>
                <button type="button" class="btn btn-success w-100 w-md-auto" id="btnEstXls">Excel</button>
                <button type="button" class="btn btn-danger w-100 w-md-auto" id="btnEstPdf">PDF</button>
                <button type="button" class="btn btn-secondary w-100 w-md-auto" id="btnEstCsv">CSV</button>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="text-muted">Total de exámenes (cantidad)</div>
                    <div class="fs-4 fw-bold" id="estTotalCantidad">—</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="text-muted">Monto total (S/)</div>
                    <div class="fs-4 fw-bold" id="estTotalMonto">—</div>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped" id="tablaEstadisticas">
            <thead class="table-primary">
                <tr>
                    <th>Examen</th>
                    <th class="text-end">Cantidad</th>
                    <th class="text-end">Monto (S/)</th>
                </tr>
            </thead>
            <tbody id="estBody">
                <tr><td colspan="3" class="text-center text-muted py-4">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center gap-2 mt-2" id="estPaginationWrap" style="display:none !important;">
        <div class="d-flex align-items-center gap-2">
            <small class="text-muted">Mostrar</small>
            <select id="estPageSize" class="form-select form-select-sm" style="width:90px;">
                <option value="10" selected>10</option>
                <option value="20">20</option>
                <option value="30">30</option>
            </select>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="estPrev">Anterior</button>
            <small class="text-muted" id="estPageInfo">Página 1 de 1</small>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="estNext">Siguiente</button>
        </div>
    </div>
</div>

<script>
(function() {
    const storageKeyPageSize = 'estadisticas_examenes_page_size';
    let currentPage = 1;
    let rowsCache = [];

    const pageSizeSelect = document.getElementById('estPageSize');
    const pageWrap = document.getElementById('estPaginationWrap');
    const pageInfo = document.getElementById('estPageInfo');
    const btnPrev = document.getElementById('estPrev');
    const btnNext = document.getElementById('estNext');

    try {
        const saved = localStorage.getItem(storageKeyPageSize);
        if (saved && pageSizeSelect.querySelector('option[value="' + saved + '"]')) {
            pageSizeSelect.value = saved;
        }
    } catch (e) {
    }

    function buildExportUrl(format) {
        const month = document.getElementById('monthPick').value;
        const top10 = document.getElementById('chkTop10').checked ? '1' : '0';
        const params = new URLSearchParams();
        params.set('action', 'estadisticas_export');
        params.set('month', month);
        params.set('format', format);
        if (top10 === '1') params.set('top10', '1');
        return 'dashboard.php?' + params.toString();
    }

    function triggerDownload(url) {
        const a = document.createElement('a');
        a.href = url;
        a.target = '_blank';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    function formatMoney(value) {
        const n = Number(value || 0);
        return n.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function setLoading() {
        document.getElementById('estBody').innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Cargando...</td></tr>';
        document.getElementById('estTotalCantidad').textContent = '—';
        document.getElementById('estTotalMonto').textContent = '—';
        if (pageWrap) {
            pageWrap.style.display = 'none';
        }
    }

    function renderPagination() {
        const body = document.getElementById('estBody');
        const pageSize = Math.max(10, parseInt(pageSizeSelect.value || '10', 10));
        const totalRows = rowsCache.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        if (totalRows === 0) {
            body.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Sin datos para este mes</td></tr>';
            pageWrap.style.display = 'none';
            return;
        }

        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;
        const pageRows = rowsCache.slice(start, end);

        body.innerHTML = pageRows.map(r => {
            const examen = (r.examen || '').toString();
            const cantidad = Number(r.cantidad || 0);
            const monto = Number(r.monto || 0);
            return '<tr>' +
                '<td>' + examen.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</td>' +
                '<td class="text-end">' + cantidad.toLocaleString('es-PE') + '</td>' +
                '<td class="text-end">' + formatMoney(monto) + '</td>' +
            '</tr>';
        }).join('');

        pageWrap.style.display = 'flex';
        pageInfo.textContent = 'Página ' + currentPage + ' de ' + totalPages;
        btnPrev.disabled = currentPage <= 1;
        btnNext.disabled = currentPage >= totalPages;
    }

    async function cargar() {
        const month = document.getElementById('monthPick').value;
        if (!month) return;

        const top10 = document.getElementById('chkTop10').checked;

        setLoading();
        try {
            const url = 'dashboard.php?action=estadisticas_api&month=' + encodeURIComponent(month);
            const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const json = await resp.json();

            if (!json || !json.ok) {
                const msg = (json && json.message) ? json.message : 'No se pudo cargar la estadística.';
                document.getElementById('estBody').innerHTML = '<tr><td colspan="3" class="text-center text-danger py-4">' + msg + '</td></tr>';
                return;
            }

            document.getElementById('estTotalCantidad').textContent = (json.total_cantidad ?? 0).toLocaleString('es-PE');
            document.getElementById('estTotalMonto').textContent = formatMoney(json.total_monto ?? 0);

            let rows = Array.isArray(json.data) ? json.data : [];
            if (top10 && rows.length > 10) {
                rows = rows.slice(0, 10);
            }

            rowsCache = rows;
            currentPage = 1;
            renderPagination();
        } catch (e) {
            document.getElementById('estBody').innerHTML = '<tr><td colspan="3" class="text-center text-danger py-4">Error al cargar la estadística</td></tr>';
            if (pageWrap) {
                pageWrap.style.display = 'none';
            }
        }
    }

    pageSizeSelect.addEventListener('change', function() {
        currentPage = 1;
        try {
            localStorage.setItem(storageKeyPageSize, pageSizeSelect.value);
        } catch (e) {
        }
        renderPagination();
    });

    btnPrev.addEventListener('click', function() {
        if (currentPage > 1) {
            currentPage--;
            renderPagination();
        }
    });

    btnNext.addEventListener('click', function() {
        const pageSize = Math.max(10, parseInt(pageSizeSelect.value || '10', 10));
        const totalPages = Math.max(1, Math.ceil(rowsCache.length / pageSize));
        if (currentPage < totalPages) {
            currentPage++;
            renderPagination();
        }
    });

    document.getElementById('btnCargarEst').addEventListener('click', cargar);
    document.getElementById('monthPick').addEventListener('change', cargar);
    document.getElementById('chkTop10').addEventListener('change', cargar);
    document.getElementById('btnEstXls').addEventListener('click', function() {
        triggerDownload(buildExportUrl('xls'));
    });
    document.getElementById('btnEstPdf').addEventListener('click', function() {
        triggerDownload(buildExportUrl('pdf'));
    });
    document.getElementById('btnEstCsv').addEventListener('click', function() {
        triggerDownload(buildExportUrl('csv'));
    });
    cargar();
})();
</script>
