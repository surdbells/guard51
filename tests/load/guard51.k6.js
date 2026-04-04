/**
 * Guard51 Load Test — k6
 * Run: k6 run tests/load/guard51.k6.js
 * Target: 500 concurrent guards, 50 dispatchers, 20 admins
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'https://api.guard51.com/api/v1';
const errorRate = new Rate('errors');

export const options = {
  stages: [
    { duration: '30s', target: 50 },   // Ramp to 50 users
    { duration: '1m', target: 200 },    // Ramp to 200
    { duration: '2m', target: 500 },    // Peak: 500 concurrent
    { duration: '1m', target: 200 },    // Scale down
    { duration: '30s', target: 0 },     // Cool down
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'],  // 95% under 2s
    http_req_failed: ['rate<0.01'],     // <1% errors
    errors: ['rate<0.05'],              // <5% custom errors
  },
};

// Test credentials
const ADMIN_EMAIL = __ENV.ADMIN_EMAIL || 'admin@shieldforce.demo';
const ADMIN_PASS = __ENV.ADMIN_PASS || 'ShieldForce@2026';

let token = '';

export function setup() {
  const res = http.post(`${BASE_URL}/auth/login`, JSON.stringify({
    email: ADMIN_EMAIL, password: ADMIN_PASS,
  }), { headers: { 'Content-Type': 'application/json' } });
  const body = JSON.parse(res.body);
  return { token: body.data?.tokens?.access_token || '' };
}

export default function (data) {
  const headers = { Authorization: `Bearer ${data.token}`, 'Content-Type': 'application/json' };
  const scenario = Math.random();

  if (scenario < 0.4) {
    // 40% — Guard clock-in/location tracking
    const res = http.get(`${BASE_URL}/dashboard/stats`, { headers });
    check(res, { 'dashboard 200': (r) => r.status === 200 });
    errorRate.add(res.status !== 200);

  } else if (scenario < 0.7) {
    // 30% — List guards/sites/shifts
    const endpoints = ['/guards', '/sites', '/shifts'];
    const ep = endpoints[Math.floor(Math.random() * endpoints.length)];
    const res = http.get(`${BASE_URL}${ep}`, { headers });
    check(res, { [`${ep} 200`]: (r) => r.status === 200 });
    errorRate.add(res.status !== 200);

  } else if (scenario < 0.85) {
    // 15% — Reports/Analytics
    const res = http.get(`${BASE_URL}/analytics/dashboard`, { headers });
    check(res, { 'analytics 200': (r) => r.status === 200 });
    errorRate.add(res.status !== 200);

  } else {
    // 15% — Notifications/Chat
    const res = http.get(`${BASE_URL}/notifications`, { headers });
    check(res, { 'notifications 200': (r) => r.status === 200 });
    errorRate.add(res.status !== 200);
  }

  sleep(Math.random() * 2 + 0.5); // 0.5-2.5s between requests
}
