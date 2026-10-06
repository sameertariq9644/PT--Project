// Affiliate click tracking for the public pages. The pages themselves are
// cached and cookie-free, so attribution lives in first-party cookies set here:
//   dpt_ref       the affiliate code from ?ref=CODE (last click wins)
//   dpt_campaign  the utm_campaign that came with it
//   dpt_vid       an anonymous visitor id, used to count unique visitors
// The lead and checkout endpoints read dpt_ref / dpt_campaign server-side.
function readCookie(name) {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
}

function writeCookie(name, value, days) {
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';

    document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${days * 86400}; Path=/; SameSite=Lax${secure}`;
}

function clearCookie(name) {
    document.cookie = `${name}=; Max-Age=0; Path=/; SameSite=Lax`;
}

function uuid() {
    if (window.crypto?.randomUUID) return window.crypto.randomUUID();

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
        const random = (Math.random() * 16) | 0;

        return (char === 'x' ? random : (random & 0x3) | 0x8).toString(16);
    });
}

function track(root) {
    const params = new URLSearchParams(window.location.search);
    const ref = (params.get('ref') || '').replace(/[^A-Za-z0-9_-]/g, '').slice(0, 40).toUpperCase();

    if (!ref) return;

    const days = Number(root.dataset.attributionDays) || 30;
    const campaign = (params.get('utm_campaign') || '').slice(0, 80);

    writeCookie('dpt_ref', ref, days);

    if (campaign) {
        writeCookie('dpt_campaign', campaign, days);
    } else {
        clearCookie('dpt_campaign');
    }

    let visitor = readCookie('dpt_vid');

    if (!visitor) {
        visitor = uuid();
    }

    writeCookie('dpt_vid', visitor, 365);

    const payload = JSON.stringify({ ref, campaign: campaign || null, vid: visitor, path: window.location.pathname });
    const body = new Blob([payload], { type: 'application/json' });

    if (!navigator.sendBeacon?.(root.dataset.trackUrl, body)) {
        fetch(root.dataset.trackUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: payload,
            keepalive: true,
        }).catch(() => {});
    }
}

const root = document.querySelector('[data-track]');

if (root) track(root);
