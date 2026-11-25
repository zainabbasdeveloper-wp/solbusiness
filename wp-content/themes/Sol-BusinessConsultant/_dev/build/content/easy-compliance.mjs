/**
 * Easy Compliance gets its own page (v2.26.0): /easy-compliance/ instead of "/#compliance".
 * The page shows the shared Easy Compliance section (the same one as on the home page) and a call to action.
 * Links to "/#compliance" and "/#compliance-…" in menus and the footer point to the new page.
 */

const PAGE = {
  post_type: 'page',
  path: 'easy-compliance',
  title: 'Easy Compliance',
  menu_order: 30,
  front_page: false,
  template: 'page-templates/sol-builder.php',
  yoast: {
    title: 'Easy Compliance for NDIS Providers | SOL Business Consultant',
    description: 'Easy Compliance helps NDIS providers keep policies, registers, staff records and internal audits in one place, with support from the SOL team.',
  },
  fields: {
    page_sections: [
      {
        acf_fc_layout: 'page_header',
        eyebrow: 'Easy Compliance',
        heading: 'NDIS Compliance, Kept in One Place',
        text: 'Policies, registers, staff records and internal audits in one system, set up and supported by the SOL team.',
        info_cards: [],
        buttons: [
          { link: { title: 'Book a Free Consultation', url: '/book-consultation/', target: '' }, style: 'primary' },
          { link: { title: 'Open Easy Compliance', url: 'https://sol.easycompliance.org', target: '_blank' }, style: 'light' },
        ],
      },
      { acf_fc_layout: 'reusable_block', block: 'ref:sol_block:compliance' },
      {
        acf_fc_layout: 'cta_banner',
        section_id: '',
        style: 'soft',
        eyebrow: 'Free consultation',
        heading: 'See Easy Compliance\nWith Your Own Documents',
        text: 'Tell us how you manage compliance today and we will show you how Easy Compliance fits. No pressure, no jargon.',
        buttons: [{ link: { title: 'Book a Free Consultation', url: '/book-consultation/', target: '' }, style: 'primary' }],
        list: [{ item: 'Free 30-minute call' }, { item: 'Reply within one business day' }, { item: 'No obligation' }],
        image: '',
        note: '',
      },
    ],
  },
};

const fix = (url) => {
  const m = /^(?:\/about)?\/#(compliance(?:-[a-z0-9-]+)?)$/.exec(url);
  if (!m) return url;
  return m[1] === 'compliance' ? '/easy-compliance/' : `/easy-compliance/#${m[1]}`;
};

export function applyEasyCompliance(seed) {
  let n = 0;
  if (!seed.pages.some((p) => p.path === PAGE.path)) {
    seed.pages.push(JSON.parse(JSON.stringify(PAGE)));
    n++;
  }
  const walk = (v) => {
    if (Array.isArray(v)) return v.forEach(walk);
    if (!v || typeof v !== 'object') return;
    for (const k of ['url', 'link']) {
      if (typeof v[k] === 'string' && fix(v[k]) !== v[k]) {
        v[k] = fix(v[k]);
        n++;
      }
    }
    Object.values(v).forEach(walk);
  };
  for (const g of ['options', 'pages', 'services', 'blocks', 'posts']) walk(seed[g]);
  return n;
}
