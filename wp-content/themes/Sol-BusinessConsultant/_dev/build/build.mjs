// Builds the child theme's generated files:
//   acf-json/*.json          SCF field groups (Local JSON)
//   seed/site.json           all content for Appearance → SOL Setup
//   _dev/FIELD-REFERENCE.md  every field, repeater and data type
// Usage: npm run build   (run `npm run extract` first if the training site changed)
import { mkdirSync, writeFileSync, rmSync, existsSync, readFileSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { execSync } from 'node:child_process';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { GROUPS, LAYOUTS, OPTIONS_SLUG, TPL_BUILDER, TPL_SERVICE, topLevelKeys } from './fields.mjs';
import { route } from './content/helpers.mjs';
import { options } from './content/options.mjs';
import { blocks } from './content/blocks.mjs';
import { services } from './content/services.mjs';
import { courses, posts } from './content/collections.mjs';
import { pages } from './content/pages.mjs';
import { products } from './content/products.mjs';
import { applyCopy, mapSeedText, loadCopy } from './copy.mjs';
import { applyPhotos, PHOTO_ALT } from './content/photos.mjs';

const HERE = dirname(fileURLToPath(import.meta.url));
const THEME = join(HERE, '..', '..');

// Training-site service slugs that already exist under a different name on WordPress.
const SERVICE_SLUG = { 'support-coordination-training': 'ndis-support-coordination-training', marketing: 'social-media-marketing' };

/* ---------- validation: every seed value must match a field ---------- */
const errors = [];
const byName = (fields) => Object.fromEntries(fields.filter((f) => f.type !== 'tab').map((f) => [f.name, f]));
const LAYOUT_FIELDS = Object.fromEntries(Object.values(GROUPS[0].fields[0].layouts).map((l) => [l.name, byName(l.sub_fields)]));

function checkValue(field, value, where) {
  if (value === null || value === undefined || value === '') return;
  switch (field.type) {
    case 'repeater':
      if (!Array.isArray(value)) return errors.push(`${where}: repeater "${field.name}" needs an array`);
      value.forEach((row, i) => checkRow(byName(field.sub_fields), row, `${where}.${field.name}[${i}]`));
      break;
    case 'group':
      checkRow(byName(field.sub_fields), value, `${where}.${field.name}`);
      break;
    case 'flexible_content':
      if (!Array.isArray(value)) return errors.push(`${where}: flexible "${field.name}" needs an array`);
      value.forEach((row, i) => {
        const lf = LAYOUT_FIELDS[row.acf_fc_layout];
        if (!lf) return errors.push(`${where}[${i}]: unknown layout "${row.acf_fc_layout}"`);
        const { acf_fc_layout, ...rest } = row;
        checkRow(lf, rest, `${where}[${i}]<${acf_fc_layout}>`);
      });
      break;
    case 'link':
      if (typeof value !== 'object' || !('url' in value)) errors.push(`${where}: link "${field.name}" needs {title,url,target}`);
      else if (/^woo:sku:/.test(value.url) && !products.some((p) => `woo:sku:${p.sku}` === value.url)) errors.push(`${where}: unknown product ${value.url}`);
      break;
    case 'select':
      if (!(String(value) in field.choices)) errors.push(`${where}: "${value}" is not a choice of "${field.name}"`);
      break;
    case 'number':
      if (typeof value !== 'number') errors.push(`${where}: "${field.name}" should be a number`);
      break;
    case 'true_false':
      if (typeof value !== 'boolean') errors.push(`${where}: "${field.name}" should be true/false`);
      break;
    case 'image':
      if (typeof value !== 'string' || !value.startsWith('img:')) errors.push(`${where}: image "${field.name}" should be img:path`);
      // The file itself is checked after the photo swap below.
      break;
    case 'relationship':
    case 'post_object':
      break;
    default:
      if (typeof value === 'object' && !Array.isArray(value)) errors.push(`${where}: "${field.name}" (${field.type}) got an object`);
  }
}
function checkRow(fields, row, where) {
  if (!row || typeof row !== 'object') return errors.push(`${where}: expected an object`);
  for (const [k, v] of Object.entries(row)) {
    if (!fields[k]) { errors.push(`${where}: unknown field "${k}"`); continue; }
    checkValue(fields[k], v, where);
  }
}
const groupFields = (key) => byName(GROUPS.find((g) => g.key === key).fields);
const PB = groupFields('group_sol_page_builder');

for (const p of pages) checkValue(PB.page_sections, p.sections, `page:${p.slug}`);
for (const b of blocks) checkValue(PB.page_sections, b.sections, `block:${b.slug}`);
for (const s of services) {
  const { page_sections, ...rest } = s.fields;
  checkRow(groupFields('group_sol_service'), rest, `service:${s.slug}`);
  checkValue(PB.page_sections, page_sections || [], `service:${s.slug}`);
}
for (const c of courses) checkRow(groupFields('group_sol_course'), c.fields, `course:${c.product_id}`);
for (const p of posts) checkRow(groupFields('group_sol_post'), p.fields, `post:${p.slug}`);
checkRow(groupFields('group_sol_options'), options, 'options');

if (errors.length) {
  console.error(`Seed validation failed (${errors.length}):\n  ` + errors.slice(0, 60).join('\n  '));
  process.exit(1);
}

/* ---------- route every URL to its WordPress / training-app address ---------- */
function routeUrls(v) {
  if (Array.isArray(v)) return v.map(routeUrls);
  if (v && typeof v === 'object') {
    const o = {};
    for (const [k, val] of Object.entries(v)) o[k] = k === 'url' && typeof val === 'string' ? route(val) : routeUrls(val);
    return o;
  }
  return v;
}
const yoast = (seo) => (seo && (seo.seo_title || seo.seo_description) ? { title: seo.seo_title || '', description: seo.seo_description || '' } : null);

/* ---------- write field groups ---------- */
const jsonDir = join(THEME, 'acf-json');
rmSync(jsonDir, { recursive: true, force: true });
mkdirSync(jsonDir, { recursive: true });
for (const g of GROUPS) writeFileSync(join(jsonDir, `${g.key}.json`), JSON.stringify(g, null, 4));

/* ---------- write seed ---------- */
mkdirSync(join(THEME, 'seed'), { recursive: true });
let seed = routeUrls({
  version: '2.0.0',
  options_slug: OPTIONS_SLUG,
  field_keys: topLevelKeys(),
  options,
  products,
  blocks: blocks.map((b) => ({ post_type: 'sol_block', path: b.slug, title: b.title, fields: { page_sections: b.sections } })),
  pages: pages.map((p, i) => ({ post_type: 'page', path: p.slug, title: p.title, menu_order: i, front_page: !!p.front_page, template: TPL_BUILDER, yoast: yoast(p.seo), fields: { page_sections: p.sections } })),
  services: services.map((s) => ({
    post_type: 'page', path: s.path || `services/${SERVICE_SLUG[s.slug] || s.slug}`, title: s.title, menu_order: s.menu_order, template: TPL_SERVICE,
    yoast: yoast(s.seo), fields: s.fields,
  })),
  courses: courses.map((c) => ({ product_id: c.product_id, title: c.title, fields: c.fields })),
  posts: posts.map((p) => ({ post_type: 'post', path: p.slug, title: p.title, excerpt: p.excerpt, content: p.content, date: p.date, category: p.category, featured_image: p.featured_image, featured_alt: p.featured_alt, sticky: p.featured, yoast: yoast(p.seo), fields: p.fields })),
});
/* ---------- SEO copy layer (content/seo-copy.json) ---------- */
{
  const todo = [];
  const seen = new Set();
  const res = applyCopy(seed);
  // Anything still in its original wording is listed for rewriting.
  const map = loadCopy();
  const rewritten = new Set(Object.values(map));
  mapSeedText(res.seed, (t, where) => {
    if (!rewritten.has(t) && !seen.has(t)) { seen.add(t); todo.push({ id: createHash('sha1').update(t).digest('hex').slice(0, 8), where, text: t }); }
    return t;
  });
  writeFileSync(join(HERE, '..', 'COPY-TODO.json'), JSON.stringify(todo, null, 1));
  seed = res.seed;
  console.log(`    SEO copy: ${res.applied} texts rewritten, ${todo.length} still original (_dev/COPY-TODO.json)${res.unused.length ? `, ${res.unused.length} unused entries` : ''}`);
}

/* ---------- photos (content/photos.mjs) ---------- */
console.log(`    Photos: ${applyPhotos(seed)} items use the new photography`);
{
  const missing = new Set();
  const scan = (v) => {
    if (typeof v === 'string') { if (v.startsWith('img:') && !existsSync(join(THEME, 'assets', 'images', v.slice(4)))) missing.add(v); }
    else if (Array.isArray(v)) v.forEach(scan);
    else if (v && typeof v === 'object') Object.values(v).forEach(scan);
  };
  scan(seed);
  if (missing.size) { console.error('Missing images: ' + [...missing].join(', ')); process.exit(1); }
}
// Alt text WordPress gives each photo when it is added to the Media Library.
seed.image_alts = Object.fromEntries(Object.entries(PHOTO_ALT).map(([k, v]) => [`photos/${k}.webp`, v]));

/* ---------- fingerprints: let WordPress apply only what changed after a git pull ---------- */
// One hash per item (page, reusable section, post, product, course, site setting). WordPress stores the
// hash it last applied; a different hash in a new theme version means that item needs updating.
// "history" lists each item's hashes from earlier committed versions of this file, so sites set up
// before fingerprints existed can still tell which items changed since.
const sha = (v) => createHash('sha1').update(JSON.stringify(v)).digest('hex').slice(0, 16);
function fingerprints(s) {
  const fp = {};
  for (const g of ['blocks', 'pages', 'services', 'posts']) {
    for (const it of s[g] || []) fp[`${it.post_type}:${it.path}`] = sha({ title: it.title, template: it.template || '', fields: it.fields, content: it.content || '', ...(it.featured_image ? { image: it.featured_image } : {}) });
  }
  for (const p of s.products || []) fp[`product:${p.sku}`] = sha(p);
  for (const c of s.courses || []) fp[`course:${c.product_id}`] = sha(c.fields);
  for (const [k, v] of Object.entries(s.options || {})) fp[`option:${k}`] = sha(v);
  return fp;
}
seed.fingerprints = fingerprints(seed);
seed.history = {};
seed.yoast_history = {};
try {
  const rel = 'wp-content/themes/Sol-BusinessConsultant/seed/site.json';
  const root = execSync('git rev-parse --show-toplevel', { cwd: THEME }).toString().trim();
  const shas = execSync(`git log --format=%H -- ${rel}`, { cwd: root }).toString().split('\n').filter(Boolean);
  for (const c of shas) {
    let old;
    try { old = JSON.parse(execSync(`git show ${c}:${rel}`, { cwd: root, maxBuffer: 64 * 1024 * 1024 }).toString()); } catch { continue; }
    // Earlier SEO texts, so a site only gets its Yoast title replaced if the theme set it.
    for (const g of ['pages', 'services', 'posts']) for (const it of old[g] || []) {
      const k = `${it.post_type}:${it.path}`;
      const cur = [...seed.pages, ...seed.services, ...seed.posts].find((x) => `${x.post_type}:${x.path}` === k);
      if (it.yoast && JSON.stringify(it.yoast) !== JSON.stringify(cur && cur.yoast)) (seed.yoast_history[k] = seed.yoast_history[k] || []).push(it.yoast);
    }
    const ofp = old.fingerprints || fingerprints(old);
    for (const [k, h] of Object.entries(ofp)) {
      if (h === seed.fingerprints[k]) continue;
      (seed.history[k] = seed.history[k] || []).includes(h) || seed.history[k].push(h);
    }
  }
} catch (e) {
  console.warn('  (no git history found; fingerprints written without history)');
}
seed.build = sha(seed.fingerprints);
writeFileSync(join(THEME, 'seed', 'site.json'), JSON.stringify(seed, null, 1));
console.log(`    ${Object.keys(seed.fingerprints).length} fingerprints, ${Object.keys(seed.history).length} items changed since earlier versions: ${Object.keys(seed.history).join(', ')}`);

/* ---------- field reference ---------- */
const TYPE_NOTE = {
  text: 'Text', textarea: 'Text area', wysiwyg: 'Rich text (HTML)', image: 'Image (attachment ID)', file: 'File (array)', link: 'Link {title, url, target}',
  url: 'URL', email: 'Email', number: 'Number', true_false: 'True / False', select: 'Select', post_object: 'Post object (ID)', relationship: 'Relationship (IDs)',
  repeater: 'Repeater', group: 'Group', flexible_content: 'Flexible content',
};
const fmtType = (f) => {
  let t = TYPE_NOTE[f.type] || f.type;
  if (f.type === 'select') t += `: ${Object.keys(f.choices).map((c) => '`' + c + '`').join(', ')}`;
  if (f.post_type) t += ` → ${f.post_type.join(', ')}`;
  return t;
};
function fieldRows(fields, depth = 0) {
  const rows = [];
  for (const f of fields) {
    if (f.type === 'tab') { rows.push(`| ${'&nbsp;'.repeat(depth * 4)}**— ${f.label} tab —** | | |`); continue; }
    rows.push(`| ${'&nbsp;'.repeat(depth * 4)}${depth ? '↳ ' : ''}\`${f.name}\` | ${fmtType(f)} | ${f.label}${f.instructions ? ' — ' + f.instructions.replace(/\|/g, '\\|') : ''} |`);
    if (f.sub_fields) rows.push(...fieldRows(f.sub_fields, depth + 1));
  }
  return rows;
}
const md = [];
md.push('# SOL child theme — SCF Field Reference', '', 'Generated by `_dev/build/build.mjs`. Every field group, repeater, sub-field and data type in the Sol-BusinessConsultant child theme.', '');
md.push('Read values with `get_field()` / `have_rows()` (SCF uses the ACF API). Site settings: `get_field( \'name\', \'option\' )`.', '');
md.push('Link fields accept checkout tokens: `woo:sku:SKU` or `woo:id:123` add the product to the cart and open checkout; `woo:product:123` opens the product page.', '');
md.push('## Contents', '', ...GROUPS.map((g) => `- [${g.title}](#${g.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')})`), '- [Page Sections layouts](#page-sections-layouts)', '- [WooCommerce products created by SOL Setup](#woocommerce-products-created-by-sol-setup)', '');
for (const g of GROUPS) {
  const loc = g.location.map((r) => r.map((c) => `${c.param} = ${c.value}`).join(' and ')).join(' / ');
  md.push(`## ${g.title}`, '', `Key \`${g.key}\` · Shown on: ${loc}`, '');
  if (g.key === 'group_sol_page_builder') {
    md.push('One flexible content field, `page_sections`. Its layouts are listed under [Page Sections layouts](#page-sections-layouts).', '');
    continue;
  }
  md.push('| Field name | Type | Label / notes |', '|---|---|---|', ...fieldRows(g.fields), '');
}
md.push('## Page Sections layouts', '', 'Field: `page_sections` (flexible content). Each row has `acf_fc_layout` set to the layout name below.', '');
for (const l of Object.values(GROUPS[0].fields[0].layouts)) {
  md.push(`### \`${l.name}\` — ${l.label}`, '', '| Field name | Type | Label / notes |', '|---|---|---|', ...fieldRows(l.sub_fields), '');
}
md.push('## WooCommerce products created by SOL Setup', '', 'Prices are ex-GST. Matched by SKU, so running setup again never duplicates them.', '', '| SKU | Product | Price (ex GST) |', '|---|---|---|', ...products.map((p) => `| \`${p.sku}\` | ${p.name} | $${p.price.toLocaleString('en-AU', { minimumFractionDigits: 2 })} |`), '');
md.push('Existing products used as-is: Training Level 1 (#3255), Level 2 (#495), Level 3 (#499), Service Payment (#3481).', '');
writeFileSync(join(HERE, '..', 'FIELD-REFERENCE.md'), md.join('\n'));

console.log(`OK  ${GROUPS.length} field groups, ${LAYOUTS.length} layouts, ${products.length} products`);
console.log(`    ${pages.length} pages, ${services.length} service pages, ${courses.length} course products, ${posts.length} posts, ${blocks.length} reusable sections`);
