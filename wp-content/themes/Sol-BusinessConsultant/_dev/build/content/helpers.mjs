import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { applyLiveUpdates } from './live-updates.mjs';

// Training-site content (source-data.json) plus changes made on the live site since the zip.
export const D = applyLiveUpdates(JSON.parse(readFileSync(fileURLToPath(new URL('../source-data.json', import.meta.url)), 'utf8')));
export const DATA = D._data;

// The training app keeps the LMS, student dashboard and client portal after checkout.
export const APP = 'https://training.solbusinessconsultant.com.au';
export const app = (path) => APP + path;

export const L = (title, url, target = '') => ({ title, url, target });
export const ext = (title, url) => ({ title, url, target: '_blank' });
export const btn = (title, url, style = 'primary') => ({ link: L(title, url), style });
export const img = (p) => `img:${p}`; // file in assets/images, added to the Media Library on import
export const ic = (v) => (v && typeof v === 'object' ? v.icon : v || '');
export const items = (arr) => (arr || []).map((item) => ({ item }));
export const ref = (type, slug) => `ref:${type}:${slug}`; // post reference, resolved on import
export const yesNo = (v) => (v === true ? 'yes' : v === false ? 'no' : String(v));

// Checkout tokens, resolved by the theme when the page renders:
//   woo:sku:SKU  -> adds the product to the cart and opens checkout
//   woo:id:123   -> same, by product ID
export const buy = (sku) => `woo:sku:${sku}`;
export const buyId = (id) => `woo:id:${id}`;

// Existing WooCommerce products on solbusinessconsultant.com.au
export const WOO = { level1: 3255, level2: 495, level3: 499, servicePayment: 3481 };

// Training-site routes that live elsewhere on WordPress.
const WP_ROUTES = {
  '/services/marketing': '/services/social-media-marketing/',
  '/services/support-coordination-training': '/services/ndis-support-coordination-training/',
  '/refund-policy': '/refunds-cancellation-policy/',
  '/terms-and-conditions': '/terms-conditions/',
  '/training-courses': '/training-2/',
  '/service-payment': '/sol-service-payment-form/',
};
// Routes that stay on the training app.
const APP_ROUTES = ['/client-portal', '/student-dashboard', '/portal', '/payment-history', '/verify-certificate', '/ndis-registration-flow', '/login', '/register', '/plans', '/proposals'];

/** Translate a training-site URL into its WordPress (or app) equivalent. */
export function route(u) {
  if (!u || typeof u !== 'string') return u;
  if (/^(https?:|mailto:|tel:|#|woo:)/.test(u)) return u;
  const m = u.match(/^([^?#]*)([?#].*)?$/);
  let path = m[1] || '/';
  const rest = m[2] || '';
  if (APP_ROUTES.some((r) => path === r || path.startsWith(r + '/'))) return APP + u;
  const key = path.replace(/\/$/, '') || '/';
  if (WP_ROUTES[key]) {
    // Query strings meant for the React payment page don't apply on WordPress.
    return WP_ROUTES[key] + (rest.startsWith('#') ? rest : '');
  }
  if (path !== '/' && !path.endsWith('/')) path += '/';
  return path + rest;
}

export const formatAud = (n) => new Intl.NumberFormat('en-AU', { style: 'currency', currency: 'AUD' }).format(n);

// Aliases kept for the content files; URLs are routed for WordPress in build.mjs.
export const R = L;
export const rbtn = btn;
