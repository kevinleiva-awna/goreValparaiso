// Load test del backoffice GORE Valparaiso (listado de observaciones).
//
// Simula 10 VUs autenticados como funcionario, golpeando el listado de
// observaciones. Requiere que las credenciales seedeadas existan en BD.
//
// Uso local:
//   k6 run -e BASE_URL=http://localhost:8000 \
//          -e EMAIL=claudio@gorevalparaiso.cl \
//          -e PASSWORD=password \
//          tests/k6/admin-listing.js
//
// Targets:
//   - p95 < 500ms en listado con eager loading
//   - 0% errores 5xx

import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 10,
  duration: '1m',
  thresholds: {
    http_req_duration: ['p(95)<500'],
    http_req_failed: ['rate<0.01'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const EMAIL = __ENV.EMAIL || 'claudio@gorevalparaiso.cl';
const PASSWORD = __ENV.PASSWORD || 'password';

export function setup() {
  // Obtener CSRF + sesion. Laravel emite token via meta o cookie XSRF-TOKEN.
  const loginPage = http.get(`${BASE_URL}/admin/login`);
  const csrfToken = loginPage.html().find('input[name=_token]').attr('value');

  const login = http.post(`${BASE_URL}/admin/login`, {
    email: EMAIL,
    password: PASSWORD,
    _token: csrfToken,
  });

  check(login, {
    'login redirect 302 o 200': (r) => r.status === 302 || r.status === 200,
  });

  return { cookies: login.cookies };
}

export default function (data) {
  const res = http.get(`${BASE_URL}/admin/observations`, {
    cookies: data.cookies,
  });
  check(res, {
    'listado 200': (r) => r.status === 200,
  });
  sleep(1);
}
