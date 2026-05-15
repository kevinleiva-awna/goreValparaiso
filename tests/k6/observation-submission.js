// Load test del portal publico GORE Valparaiso.
//
// Simula 50 VUs durante 2 minutos golpeando endpoints publicos. NO incluye
// el POST de observacion porque requiere sesion autenticada — eso se prueba
// con admin-listing.js en otro escenario.
//
// Uso local:
//   k6 run -e BASE_URL=http://localhost:8000 tests/k6/observation-submission.js
//
// Targets:
//   - p95 < 300ms en lectura publica
//   - 0% errores 5xx
//   - error rate < 1% (no esperamos rate limits aqui)

import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 25 },
    { duration: '1m', target: 50 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    'http_req_duration{type:read}': ['p(95)<300'],
    http_req_failed: ['rate<0.01'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const CONSULTATION_SLUG = __ENV.SLUG || 'prot-valparaiso-2026';

export default function () {
  // 1. Home
  const home = http.get(`${BASE_URL}/`, { tags: { type: 'read' } });
  check(home, { 'home 200': (r) => r.status === 200 });

  // 2. Listado de consultas
  const list = http.get(`${BASE_URL}/consultas`, { tags: { type: 'read' } });
  check(list, { 'list 200': (r) => r.status === 200 });

  // 3. Ficha de la consulta seedeada
  const detail = http.get(`${BASE_URL}/consultas/${CONSULTATION_SLUG}`, {
    tags: { type: 'read' },
  });
  check(detail, {
    'detail 200': (r) => r.status === 200,
    'detail has title': (r) => r.body.includes('PROT'),
  });

  sleep(Math.random() * 2 + 1);
}
