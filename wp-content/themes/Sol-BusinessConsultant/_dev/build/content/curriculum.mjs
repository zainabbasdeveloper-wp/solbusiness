/**
 * Support Coordinator training: the real curriculum (v2.25.2), as set up on the training platform.
 * Level 1: 3 modules, Level 2: 16 modules, Level 3: 6 modules. 25 modules and 272 topics in all.
 * The course cards and product pages read the same list from sol_course_modules() in inc/woocommerce.php.
 */

export const LEVELS = [
  {
    level: 'Level 1', badge: 'Foundation', duration: '4–6 hours', icon: 'book-open',
    modules: [
      ['Introduction to the NDIS', 17],
      ['Person-Centered Support Principles', 22],
      ['Rights and Responsibilities', 13],
    ],
  },
  {
    level: 'Level 2', badge: 'Professional', duration: '8–10 hours', icon: 'users',
    modules: [
      ['Advanced Person-Centered Practice', 17],
      ['Complex Support Needs', 15],
      ['Specialist Communication Approaches', 15],
      ['Advanced Rights and Advocacy', 15],
      ['Positive Behavior Support', 14],
      ['Community Inclusion and Capacity Building', 15],
      ['Professional Practice and Leadership', 15],
      ['NDIS Quality and Safeguards Framework', 16],
      ['Real-World Scenarios', 7],
      ['Assessment Components', 5],
      ['Cultural Competence and Diversity', 3],
      ['Complex Communication Support - Special Requirements', 1],
      ['Practice Leadership Development', 2],
      ['Integration with Qualification Frameworks', 1],
      ['Supplementary Resources', 1],
      ['Practical Tools and Templates', 3],
    ],
  },
  {
    level: 'Level 3', badge: 'Advanced', duration: '12–15 hours', icon: 'award',
    modules: [
      ['Introduction to Level 3 NDIS Support', 14],
      ['Complex Support Needs', 13],
      ['Advanced Person-Centered Practice Level 3', 13],
      ['Medication Management and Health Support', 15],
      ['Leadership in Support Settings', 17],
      ['Resource Materials', 3],
    ],
  },
];

const topics = (l) => l.modules.reduce((n, m) => n + m[1], 0);
export const MODULES = LEVELS.reduce((n, l) => n + l.modules.length, 0); // 25
export const TOPICS = LEVELS.reduce((n, l) => n + topics(l), 0); // 272
const plural = (n, w) => `${n} ${w}${n === 1 ? '' : 's'}`;

/** Exact text replacements (whole strings). */
const SWAP = {
  'The full 12-module online curriculum, self-paced, with 6 months of access.': `The full ${MODULES}-module online curriculum (Levels 1–3), self-paced, with 6 months of access.`,
  'Full 12-module curriculum for up to 10 staff': `Full ${MODULES}-module curriculum (Levels 1–3) for up to 10 staff`,
  'Full 12-module curriculum (up to 10 staff)': `Full ${MODULES}-module curriculum, Levels 1–3 (up to 10 staff)`,
  'Online levels for individual learners and a 12-module curriculum licence for teams.': `Online levels for individual learners and the full ${MODULES}-module curriculum licence for teams.`,
  '12-module curriculum licence for teams': `${MODULES}-module curriculum licence for teams`,
  'Training curriculum (12 modules)': `Training curriculum (${MODULES} modules)`,
  'View All 12 Modules': `View All ${MODULES} Modules`,
  'What are the 12 modules and how long does each take?': 'What modules are in the course and how long does it take?',
};

const swapDeep = (v) => {
  if (typeof v === 'string') return Object.prototype.hasOwnProperty.call(SWAP, v) ? SWAP[v] : v.split('12 modules, 1,500+').join(`${MODULES} modules across 3 levels, 1,500+`).split('12-module NDIS').join(`${MODULES}-module NDIS`);
  if (Array.isArray(v)) return v.map(swapDeep);
  if (v && typeof v === 'object') {
    for (const k of Object.keys(v)) v[k] = swapDeep(v[k]);
  }
  return v;
};

const FAQ_ANSWER = 'The course has three levels and ' + MODULES + ' modules (' + TOPICS + ' topics). '
  + LEVELS.map((l) => `${l.level} ${l.badge} (${plural(l.modules.length, 'module')}, ${topics(l)} topics, about ${l.duration}): `
    + l.modules.map((m) => m[0]).join(', ') + '.').join(' ')
  + ' Each level is self-paced, so you can take it at your own speed.';

/** The "Full Curriculum" section on the Support Coordination Training page: one card per level. */
function curriculumSection(sec) {
  sec.heading = `${MODULES} Modules: The Complete Support Coordination Curriculum`;
  sec.intro = `Three self-paced levels, ${MODULES} modules and ${TOPICS} topics, from NDIS foundations to advanced practice.`;
  sec.columns = '3';
  sec.cards = LEVELS.map((l) => ({
    icon: l.icon,
    tag: `${l.level} · ${l.badge}`,
    title: `${plural(l.modules.length, 'module')} · ${topics(l)} topics`,
    text: `About ${l.duration} of self-paced learning.`,
    list: l.modules.map((m) => ({ item: `${m[0]} (${plural(m[1], 'topic')})` })),
  }));
}

export function applyCurriculum(seed) {
  let n = 0;
  for (const g of ['pages', 'services', 'blocks', 'products']) {
    const before = JSON.stringify(seed[g]);
    seed[g] = swapDeep(seed[g]);
    if (JSON.stringify(seed[g]) !== before) n++;
  }
  const walk = (v) => {
    if (Array.isArray(v)) return v.forEach(walk);
    if (!v || typeof v !== 'object') return;
    if (v.acf_fc_layout === 'card_grid' && v.section_id === 'modules' && Array.isArray(v.cards) && v.cards.some((c) => /^Module \d+/.test(c.tag || ''))) {
      curriculumSection(v);
      n++;
    }
    if (v.question === SWAP['What are the 12 modules and how long does each take?']) {
      v.answer = FAQ_ANSWER;
      n++;
    }
    Object.values(v).forEach(walk);
  };
  for (const g of ['pages', 'services', 'blocks']) walk(seed[g]);
  return n;
}

/** Old module counts still on the site. */
export function curriculumProblems(seed) {
  const out = [];
  const s = JSON.stringify({ pages: seed.pages, services: seed.services, blocks: seed.blocks, products: seed.products });
  for (const bad of ['12 modules', '12-module', '12 Modules', 'Module 12']) {
    if (s.includes(bad)) out.push(bad);
  }
  return out;
}
