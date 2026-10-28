// SEO copy layer: content/seo-copy.json maps original wording -> rewritten wording.
// The build swaps every matching text value in the seed, so a rebuild from the training site keeps
// the rewrites. Text that changes upstream simply stays original until it gets a new entry
// (`npm run copy:export` lists everything without one).
import { readFileSync, existsSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
export const COPY_FILE = join(HERE, 'content', 'seo-copy.json');

// Keys that never hold marketing copy.
const NON_TEXT = new Set([
  'acf_fc_layout', 'url', 'icon', 'card_icon', 'image', 'hero_image', 'style', 'background', 'align', 'columns', 'card_style',
  'section_id', 'target', 'form_variant', 'form_type', 'service_type', 'source', 'value', 'sku', 'block', 'path', 'template',
  'date', 'category', 'featured_image', 'anchor_id', 'price', 'pricing_code', 'label', 'badge', 'tag', 'select_label',
  'form_shortcode', 'head_code', 'phone', 'phone_link', 'email', 'whatsapp_url', 'address', 'abn', 'website_label',
  'business_hours', 'app_url', 'payment_page_url', 'notification_email', 'company_name', 'cookie_link', 'name', 'logo',
  'thumbnail', 'level', 'duration', 'product_id', 'post_type', 'nav_items', 'mega_menu', 'portal_links',
]);
// Official lists (NDIS registration groups) keep their exact wording.
const SKIP_LAYOUTS = new Set(['directory']);
// Legal documents: only their search title/description are rewritten.
export const LEGAL = new Set(['privacy-policy', 'terms-conditions', 'refunds-cancellation-policy']);

const isCopy = (s) => typeof s === 'string' && /[a-z]/i.test(s) && s.trim().split(/\s+/).length >= 2
  && !/^(img:|https?:|\/|#|woo:|mailto:|tel:)/.test(s.trim());

function walk(v, key, fn, where) {
  if (typeof v === 'string') return NON_TEXT.has(key) || !isCopy(v) ? v : fn(v, where);
  if (Array.isArray(v)) return v.map((x) => walk(x, key, fn, where));
  if (v && typeof v === 'object') {
    if (SKIP_LAYOUTS.has(v.acf_fc_layout)) return v;
    const o = {};
    for (const [k, val] of Object.entries(v)) o[k] = NON_TEXT.has(k) ? val : walk(val, k, fn, where);
    return o;
  }
  return v;
}

/** Run fn(text, where) over every copy string in the seed; returns the new seed. */
export function mapSeedText(seed, fn) {
  const s = { ...seed };
  for (const g of ['pages', 'services', 'blocks', 'posts']) {
    s[g] = (seed[g] || []).map((it) => {
      const where = `${it.post_type}:${it.path}`;
      const out = { ...it };
      if (it.yoast) out.yoast = walk(it.yoast, 'yoast', fn, where);
      if (LEGAL.has(it.path)) return out;
      out.fields = walk(it.fields, 'fields', fn, where);
      if (it.excerpt) out.excerpt = walk(it.excerpt, 'excerpt', fn, where);
      if (it.content) out.content = walk(it.content, 'content', fn, where);
      if (it.featured_alt) out.featured_alt = walk(it.featured_alt, 'featured_alt', fn, where);
      return out;
    });
  }
  s.products = (seed.products || []).map((p) => walk(p, 'product', fn, `product:${p.sku}`));
  s.courses = (seed.courses || []).map((c) => ({ ...c, fields: walk(c.fields, 'fields', fn, `course:${c.product_id}`) }));
  s.options = walk(seed.options, 'options', fn, 'options');
  return s;
}

export function loadCopy() {
  return existsSync(COPY_FILE) ? JSON.parse(readFileSync(COPY_FILE, 'utf8')) : {};
}

/** Apply the copy file. Returns { seed, applied, unused }. */
export function applyCopy(seed) {
  const map = loadCopy();
  const used = new Set();
  let applied = 0;
  const out = mapSeedText(seed, (t) => {
    if (Object.prototype.hasOwnProperty.call(map, t)) { used.add(t); applied++; return map[t]; }
    return t;
  });
  return { seed: out, applied, unused: Object.keys(map).filter((k) => !used.has(k)) };
}
