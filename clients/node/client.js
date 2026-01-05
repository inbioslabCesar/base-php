// Simple Node client (Node 18+) to create, send, check status and download URL
// Set env vars: FACT_API_BASE_URL, FACT_API_USER, FACT_API_PASSWORD, FACT_COMPANY_ID, FACT_BRANCH_ID, FACT_SEND_METHOD, FACT_TAX_MODE, FACT_PDF_FORMAT

const CFG = {
  BASE_URL: process.env.FACT_API_BASE_URL || 'http://127.0.0.1:8000',
  USER: process.env.FACT_API_USER || 'admin@sistema-sunat.com',
  PASSWORD: process.env.FACT_API_PASSWORD || 'Admin123!@#',
  COMPANY_ID: Number(process.env.FACT_COMPANY_ID || 1),
  BRANCH_ID: Number(process.env.FACT_BRANCH_ID || 1),
  SEND_METHOD: process.env.FACT_SEND_METHOD || 'individual',
  TAX_MODE: process.env.FACT_TAX_MODE || 'exonerado',
  PDF_FORMAT: process.env.FACT_PDF_FORMAT || '80mm',
};

async function httpJson(method, url, token, body) {
  const headers = { Accept: 'application/json' };
  if (token) headers['Authorization'] = 'Bearer ' + token;
  if (body != null) headers['Content-Type'] = 'application/json';
  const res = await fetch(url, { method, headers, body: body != null ? JSON.stringify(body) : undefined });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(json.message || `HTTP ${res.status}`);
  }
  return json;
}

async function login() {
  const url = CFG.BASE_URL.replace(/\/$/, '') + '/api/auth/login';
  const data = { email: CFG.USER, username: CFG.USER, password: CFG.PASSWORD };
  const res = await httpJson('POST', url, null, data);
  return res.access_token || (res.data && res.data.access_token);
}

function buildPayload(opts = {}) {
  const gravado = CFG.TAX_MODE === 'gravado';
  const porc = gravado ? 18 : 0;
  const afe = gravado ? '10' : '20';
  const serie = gravado ? 'F001' : 'B001';
  return {
    company_id: CFG.COMPANY_ID,
    branch_id: CFG.BRANCH_ID,
    metodo_envio: CFG.SEND_METHOD,
    serie: opts.serie || serie,
    fecha_emision: new Date().toISOString().slice(0, 10),
    client: {
      tipo_documento: opts.tipo_documento || '1',
      numero_documento: opts.numero_documento || '12345678',
      razon_social: opts.razon_social || 'Cliente Demo',
    },
    detalles: [
      {
        codigo: opts.codigo || 'ITEM-001',
        descripcion: opts.descripcion || 'Servicio Demo',
        unidad: 'NIU',
        cantidad: 1,
        mto_valor_unitario: Number(opts.valor_unitario || 50.0),
        porcentaje_igv: porc,
        tip_afe_igv: afe,
      },
    ],
  };
}

async function run() {
  const token = await login();
  const base = CFG.BASE_URL.replace(/\/$/, '');
  const gravado = CFG.TAX_MODE === 'gravado';
  const tipo = gravado ? 'invoices' : 'boletas';
  // Crear
  const payload = buildPayload();
  const created = await httpJson('POST', `${base}/api/v1/${tipo}`, token, payload);
  const id = created.data?.id || created.id;
  if (!id) throw new Error('No remote id');
  console.log(`Created ${tipo} id=${id}`);
  // Enviar SUNAT
  const send = await httpJson('POST', `${base}/api/v1/${tipo}/${id}/send-sunat`, token, null);
  const estado = String(send.estado_sunat || send.sunat_status || send.status || 'enviado').toLowerCase();
  console.log('Send status=', estado);
  // Estado fallback GET
  const got = await httpJson('GET', `${base}/api/v1/${tipo}/${id}`, token, null);
  const estado2 = String(got.estado_sunat || got.sunat_status || got.status || estado).toLowerCase();
  console.log('Current status=', estado2);
  // PDF URL
  const pdf = `${base}/api/v1/${tipo}/${id}/download-pdf?format=${encodeURIComponent(CFG.PDF_FORMAT)}`;
  console.log('PDF:', pdf);
}

run().catch((e) => {
  console.error('Error:', e.message);
  process.exit(1);
});
