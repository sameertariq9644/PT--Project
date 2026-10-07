// k6 load test for the public site. Run it against production-like hardware,
// through the CDN / nginx cache, never against a laptop:
//
//   k6 run -e BASE_URL=https://staging.example.com tests/load/public.js
//
// Two scenarios run side by side:
//   pages  ramps to ~830 requests/second (50,000 visitors a minute) across the
//          cacheable public pages, with ?ref= and utm_* tags mixed in to prove
//          the cache key ignores them.
//   leads  posts the eligibility form at a realistic rate (2 a second). It
//          sends a filled honeypot, so the endpoint does all of its work
//          (routing, validation, throttle) but stores nothing and sends no mail.
//
// Record the summary k6 prints in README.md under "Load test results".
import http from 'k6/http';
import { check } from 'k6';

const BASE = (__ENV.BASE_URL || 'http://dp-project.test').replace(/\/$/, '');
const PAGES = ['/', '/pathway', '/services', '/about', '/faq', '/contact', '/checkout/roadmap-session'];
const TAGS = ['', '?ref=SANA20', '?ref=SANA20&utm_campaign=npte-reel', '?utm_source=instagram'];

export const options = {
    scenarios: {
        pages: {
            executor: 'ramping-arrival-rate',
            exec: 'pages',
            startRate: 50,
            timeUnit: '1s',
            preAllocatedVUs: 200,
            maxVUs: 2000,
            stages: [
                { target: 830, duration: '2m' },
                { target: 830, duration: '5m' },
                { target: 0, duration: '30s' },
            ],
        },
        leads: {
            executor: 'constant-arrival-rate',
            exec: 'leads',
            rate: 2,
            timeUnit: '1s',
            duration: '7m30s',
            preAllocatedVUs: 10,
            maxVUs: 50,
        },
    },
    thresholds: {
        'http_req_failed{scenario:pages}': ['rate<0.001'],
        'http_req_duration{scenario:pages}': ['p(95)<300', 'p(99)<800'],
        'http_req_duration{scenario:leads}': ['p(95)<500'],
        'checks{scenario:pages}': ['rate>0.999'],
    },
};

export function pages() {
    const path = PAGES[Math.floor(Math.random() * PAGES.length)];
    const tags = TAGS[Math.floor(Math.random() * TAGS.length)];
    const response = http.get(BASE + path + tags, { tags: { name: path } });

    check(response, {
        'status is 200': (r) => r.status === 200,
        'is publicly cacheable': (r) => String(r.headers['Cache-Control']).includes('public'),
        'sets no cookie': (r) => r.headers['Set-Cookie'] === undefined,
    });
}

export function leads() {
    const response = http.post(
        `${BASE}/leads`,
        {
            name: 'Load Test',
            email: `load-${__VU}-${__ITER}@example.com`,
            whatsapp: '+92 300 0000000',
            graduation_year: '2025',
            university: 'Load Test University',
            clinical_experience: 'None yet',
            fccpt: 'Not yet',
            _elapsed: '9000',
            website: 'k6-load-test',
        },
        { headers: { Accept: 'application/json' }, tags: { name: '/leads' } },
    );

    // 429 is the per-IP rate limit doing its job when the test runs from one address.
    check(response, { 'accepted or rate limited': (r) => r.status === 201 || r.status === 429 });
}
