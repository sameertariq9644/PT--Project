// Affiliate dashboards: the admin "Affiliates" page and the affiliate portal.
// Numbers come from the server (App\Support\AffiliateStats) as JSON:
//   { text, monthly, cur, pv, buckets: [{ lab, clicks, visitors, checkouts, sales, revenue, commission }], rows? }
const METRICS = {
    portal: [
        ['earn', 'Earnings', (r) => r.commission, true],
        ['clicks', 'Clicks', (r) => r.clicks, false],
        ['sales', 'Sales', (r) => r.sales, false],
    ],
    admin: [
        ['sales', 'Sales', (r) => r.sales, false],
        ['revenue', 'Revenue', (r) => r.revenue, true],
        ['clicks', 'Clicks', (r) => r.clicks, false],
    ],
};

const ARROW_UP = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"></path><path d="M6 11l6-6 6 6"></path></svg>';
const ARROW_DOWN = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"></path><path d="M6 13l6 6 6-6"></path></svg>';

const fmt = (n) => Math.round(n).toLocaleString('en-US');
const money = (n) => `$${fmt(n)}`;

function compact(n) {
    if (n >= 1e6) return (n / 1e6).toFixed(n >= 1e7 ? 1 : 2).replace(/\.0+$/, '') + 'M';
    if (n >= 1e3) return Math.round(n / 1e3) + 'k';

    return Number.isInteger(n) ? String(n) : n.toFixed(1);
}

function delta(c, p) {
    const pct = p ? ((c - p) / p) * 100 : 0;

    return { up: pct >= 0, text: (pct >= 0 ? '+' : '−') + Math.abs(pct).toFixed(1) + '%' };
}

function niceMax(m) {
    if (m <= 0) return 1;

    const p = Math.pow(10, Math.floor(Math.log10(m)));
    const n = m / p;
    const s = n <= 1 ? 1 : n <= 2 ? 2 : n <= 2.5 ? 2.5 : n <= 5 ? 5 : 10;

    return s * p;
}

function esc(value) {
    const span = document.createElement('span');
    span.textContent = value ?? '';

    return span.innerHTML;
}

function initDashboard(root) {
    const mode = root.dataset.dashboard;
    const metricDefs = METRICS[mode];
    const state = { range: '30d', metric: metricDefs[0][0], aff: 'all', search: '', data: null };
    const el = (name) => root.querySelector(`[data-el="${name}"]`);
    const set = (name, value) => {
        const node = el(name);

        if (node) node.textContent = value;
    };

    function params() {
        const query = new URLSearchParams({ range: state.range });

        if (state.range === 'custom') {
            query.set('from', el('from').value);
            query.set('to', el('to').value);
        }

        if (mode === 'admin' && state.aff !== 'all') query.set('affiliate', state.aff);

        return query;
    }

    function kpi(label, c, p, format) {
        const d = delta(c, p);

        return `<div class="kpi">
            <span class="kpi__label">${label}</span>
            <span class="kpi__value">${format(c)}</span>
            <div class="kpi__delta">
                <span class="delta delta--${d.up ? 'up' : 'down'}">${d.up ? ARROW_UP : ARROW_DOWN}${d.text}</span>
                <span class="kpi__vs">vs prev.</span>
            </div>
        </div>`;
    }

    function renderChart(buckets, md) {
        const vals = buckets.map(md[2]);
        const nm = niceMax(Math.max(...vals, 1));
        const step = buckets.length > 14 ? 5 : 1;

        el('chart').classList.toggle('chart--dense', buckets.length > 14);
        el('ticks').innerHTML = [nm, nm / 2, 0].map((v) => `<span>${v === 0 ? '0' : compact(v)}</span>`).join('');
        el('bars').innerHTML = buckets.map((r, i) => {
            const h = ((vals[i] / nm) * 100).toFixed(1);
            const tip = md[3] ? money(vals[i]) : `${fmt(vals[i])} ${md[1].toLowerCase()}`;

            return `<div class="chart__col">
                <div class="chart__bar" style="height: ${h}%"></div>
                <div class="chart__tip" style="bottom: calc(${h}% + 10px)">
                    <span class="chart__tip-label">${esc(r.lab)}</span>
                    <span class="chart__tip-value">${tip}</span>
                    <span class="chart__tip-sub">${fmt(r.clicks)} clicks · ${fmt(r.checkouts)} checkouts · ${fmt(r.sales)} sales</span>
                </div>
            </div>`;
        }).join('');
        el('labels').innerHTML = buckets
            .map((r, i) => `<span>${i % step === 0 ? esc(r.lab.replace(/ \d{4}$/, '')) : ''}</span>`)
            .join('');
    }

    function renderRows(list) {
        const needle = state.search.trim().toLowerCase();
        const rows = list.filter((x) => !needle || `${x.name} ${x.code} ${x.channel ?? ''}`.toLowerCase().includes(needle));

        el('rows').innerHTML = rows.map((x) => `<div class="table__row">
                <div class="aff">
                    <div class="aff__avatar">${esc(x.initials)}</div>
                    <div class="aff__text"><span class="aff__name">${esc(x.name)}</span><span class="aff__sub">ref=${esc(x.code)}${x.channel ? ` · ${esc(x.channel)}` : ''}</span></div>
                </div>
                <span class="right">${fmt(x.clicks)}</span>
                <span class="right">${fmt(x.visitors)}</span>
                <span class="right">${fmt(x.checkouts)}</span>
                <span class="right strong">${fmt(x.sales)}</span>
                <span class="right">${((x.sales / Math.max(x.clicks, 1)) * 100).toFixed(1)}%</span>
                <span class="right">${money(x.revenue)}</span>
                <span class="aff-commission">${money(x.commission)}</span>
                <span class="status status--sm status--${x.active ? 'green' : 'grey'}">${x.active ? 'Active' : 'Paused'}</span>
                <a class="aff-view" href="${esc(x.url)}" aria-label="View ${esc(x.name)}">View</a>
            </div>`).join('') || '<div class="table__empty">No affiliates to show. Add one to get started.</div>';
    }

    function render() {
        const R = state.data;

        root.querySelectorAll('[data-range]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.range === state.range)));
        root.querySelectorAll('[data-metric]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.metric === state.metric)));
        el('custom-range').hidden = state.range !== 'custom';

        if (!R) return;

        const { cur, pv } = R;
        const md = metricDefs.find((m) => m[0] === state.metric);
        const selected = el('affiliate')?.selectedOptions[0];

        set('range-text', R.text);

        const kpis = [
            kpi('Link clicks', cur.clicks, pv.clicks, fmt),
            kpi('Unique visitors', cur.visitors, pv.visitors, fmt),
            kpi('Checkouts started', cur.checkouts, pv.checkouts, fmt),
            kpi('Sales confirmed', cur.sales, pv.sales, fmt),
        ];

        if (mode === 'portal') {
            kpis.push(kpi('Your earnings', cur.commission, pv.commission, money));
        } else {
            const pk = (n) => `$${compact(n)}`;

            kpis.push(kpi('Revenue', cur.revenue, pv.revenue, pk));
            kpis.push(kpi('Commission', cur.commission, pv.commission, pk));
        }

        el('kpis').innerHTML = kpis.join('');
        set('chart-title', `${md[1]} over time`);
        set(
            'chart-sub',
            (R.monthly ? 'Monthly' : 'Daily')
                + (mode === 'admin' ? ` · ${state.aff === 'all' ? 'all affiliates' : selected.textContent}` : '')
                + ' · hover a bar for details',
        );
        renderChart(R.buckets, md);

        if (mode === 'admin') {
            renderRows(R.rows);

            const exportLink = el('export');

            if (exportLink) exportLink.href = `${root.dataset.exportUrl}?${params()}`;
        }
    }

    // Ignore a slow response that arrives after a newer request was made.
    let request = 0;

    async function load() {
        const current = ++request;

        render();

        try {
            const response = await fetch(`${root.dataset.statsUrl}?${params()}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok || current !== request) return;

            state.data = await response.json();
            render();
        } catch {
            set('range-text', 'Could not load the numbers. Reload the page to try again.');
        }
    }

    root.querySelectorAll('[data-range]').forEach((button) => {
        button.addEventListener('click', () => {
            state.range = button.dataset.range;
            load();
        });
    });

    root.querySelectorAll('[data-metric]').forEach((button) => {
        button.addEventListener('click', () => {
            state.metric = button.dataset.metric;
            render();
        });
    });

    el('apply')?.addEventListener('click', load);

    el('affiliate')?.addEventListener('change', (event) => {
        state.aff = event.target.value;
        load();
    });

    el('search')?.addEventListener('input', (event) => {
        state.search = event.target.value;
        render();
    });

    load();
}

function initLinkBuilder(root) {
    const page = root.querySelector('[data-el="page"]');
    const campaign = root.querySelector('[data-el="campaign"]');
    const output = root.querySelector('[data-el="link"]');
    const copy = root.querySelector('[data-el="copy"]');

    const update = () => {
        const slug = campaign.value.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

        output.textContent = root.dataset.baseUrl + page.value
            + '?ref=' + root.dataset.code
            + (slug ? `&utm_campaign=${slug}` : '');
        copy.classList.remove('is-copied');
    };

    page.addEventListener('change', update);
    campaign.addEventListener('input', update);
    copy.addEventListener('click', () => {
        navigator.clipboard?.writeText(output.textContent).catch(() => {});
        copy.classList.add('is-copied');
    });

    update();
}

document.querySelectorAll('[data-dashboard]').forEach(initDashboard);
document.querySelectorAll('[data-link-builder]').forEach(initLinkBuilder);
