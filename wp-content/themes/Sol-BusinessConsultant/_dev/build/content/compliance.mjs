// Compliance wording (SOL_Website_Compliance_Wording_Fixes.docx, 18 Nov 2026).
// SOL is a business consultancy: not an accountant, tax or BAS agent, mortgage or credit broker, and not
// linked to the NDIA or NDIS Commission. build.mjs runs applyCompliance() after the brand rules, then
// complianceProblems() fails the build if any removed claim comes back.
//
// Partner details: fill these in once the written partner agreements and approvals exist. Until then the
// site says "a registered tax agent" / "a licensed finance broker" without naming anyone.
export const PARTNERS = {
  accountant: { name: '', tpb: '' }, // firm name, Tax Practitioners Board registration number
  broker: { name: '', credit: '' }, // business name, "ACL 123456" or "credit representative 123456 of <licensee>"
};

const acct = PARTNERS.accountant.name
  ? `our partner ${PARTNERS.accountant.name} (TPB no. ${PARTNERS.accountant.tpb})`
  : 'a registered tax or BAS agent';
const broker = PARTNERS.broker.name
  ? `our partner ${PARTNERS.broker.name} (${PARTNERS.broker.credit})`
  : 'a licensed finance broker';

/** Footer line (replaces the old copyright line). */
export const FOOTER = 'Copyright {year} SOL Business Consultant Pty Ltd, an independent business consultancy based in Glenroy, Victoria. '
  + 'SOL is not a registered tax agent, BAS agent, accountant, financial adviser, mortgage broker or credit provider. '
  + `Tax, BAS and lodgement services are provided by ${acct}. Finance broking is provided by ${broker}. `
  + 'SOL may receive a referral fee, which we disclose before you proceed. '
  + 'SOL is not affiliated with, endorsed by or acting for the NDIA, the NDIS Quality and Safeguards Commission or any government agency. '
  + 'Information on this site is general in nature.';

/** Standard disclaimer box near the top of every finance-related page. */
export const FINANCE_NOTICE = 'Please note: SOL is a business consultancy, not a registered tax or BAS agent, accountant, financial adviser or credit provider. '
  + `Tax, BAS and payroll lodgements are handled by ${acct}. Loans are arranged only by ${broker}. `
  + 'Each partner is responsible for their own advice and fees. If SOL receives a referral fee, we’ll tell you before you proceed.';

export const NDIS_DISCLAIMER = 'SOL Business Consultant is an independent business consultancy. We are not affiliated with, endorsed by or acting for the NDIA, '
  + 'the NDIS Quality and Safeguards Commission or any government agency, and we can’t guarantee registration or audit outcomes.';

/** Old page address → new one (inc/old-address.php redirects the old address with a 301). */
export const MOVED = {
  'services/bookkeeping-bas-lodgement': 'services/bookkeeping-support',
  'services/business-personal-loan-support': 'services/finance-broker-referrals',
};

const items = (list) => list.map((item) => ({ item }));

/* ---------- the six finance service pages ---------- */
const SERVICES = {
  'services/bookkeeping-bas-lodgement': {
    title: 'Bookkeeping Support',
    yoast: { title: 'Bookkeeping Support for Small Businesses | SOL', description: 'Xero and QuickBooks setup, record organisation and bookkeeping routines, with referrals to registered BAS agents for lodgement.' },
    fields: {
      card_text: 'Xero and QuickBooks setup, record organisation and bookkeeping routines, with referrals to registered BAS agents for lodgement.',
      eyebrow: 'Bookkeeping Support',
      hero_intro: 'Get your records organised and your software set up properly. We help you build simple bookkeeping routines, and refer you to a registered BAS agent when it’s time to prepare and lodge your BAS.',
      hero_highlights: items(['Xero and QuickBooks setup', 'Record organisation', 'Registered BAS agent referrals']),
      overview_heading: 'Organised Records and Simple Bookkeeping Routines',
      features: items([
        'Accounting software setup and training',
        'Record review and organisation guidance',
        'Simple monthly bookkeeping routines and checklists',
        'Referral to a registered BAS or tax agent for BAS preparation and lodgement',
      ]),
      why: items([
        'Reliable routines mean fewer missing records and less stress at reporting time.',
        'We work in the accounting software your business already uses.',
        'BAS preparation, lodgement and tax advice stay with registered BAS and tax agents.',
      ]),
      details_who: items([
        'Businesses that want cleaner bookkeeping routines and better reporting.',
        'Owners who want their records organised before their BAS agent prepares the BAS.',
        'Businesses using Xero, QuickBooks or similar software.',
      ]),
      details_get: items([
        'Accounting software setup and training',
        'Record review and organisation guidance',
        'Simple monthly bookkeeping routines and checklists',
        'Referral to a registered BAS or tax agent for BAS preparation and lodgement',
      ]),
      details_timeline: items([
        'Initial review: within 1 business day.',
        'Catch-up and record organisation timing depends on transaction volume and account access.',
        'BAS preparation and lodgement are done by your registered BAS or tax agent, on their timeline.',
      ]),
      enquiry_heading: 'Start Your Bookkeeping Support Enquiry',
    },
  },
  'services/payroll-services': {
    title: 'Payroll Admin Support',
    yoast: { title: 'Payroll Admin Support | SOL Business Consultant', description: 'Payroll software setup, employee onboarding checklists and record routines, with referrals to registered payroll and BAS agents.' },
    fields: {
      card_text: 'Payroll software setup, employee onboarding checklists and record routines, with referrals to registered payroll and BAS agents.',
      eyebrow: 'Payroll Administration',
      hero_intro: 'Set up payroll the right way from the start. We help you configure your payroll software, organise employee records and onboarding paperwork, and connect you with a registered BAS agent to run pay, PAYG withholding, super and Single Touch Payroll reporting.',
      hero_highlights: items(['Payroll software setup guidance', 'Employee onboarding checklists', 'Registered BAS agent referrals']),
      overview_heading: 'Payroll Set Up the Right Way',
      features: items([
        'Payroll software setup guidance',
        'Employee onboarding and record checklists',
        'Fair Work and record-keeping reminders (general information)',
        'Referral to a registered BAS or payroll agent for pay runs and STP reporting',
      ]),
      why: items([
        'Practical knowledge of Australian payroll record-keeping.',
        'Clear checklists that reduce avoidable payroll errors.',
        'Pay runs and STP reporting stay with a registered BAS or payroll agent.',
      ]),
      details_who: items([
        'Businesses setting up payroll software for the first time.',
        'Employers who want clearer onboarding and employee record processes.',
        'Growing teams that need well-organised employee records.',
      ]),
      details_get: items([
        'Payroll software setup guidance',
        'Employee onboarding and record checklists',
        'Fair Work and record-keeping reminders (general information)',
        'Referral to a registered BAS or payroll agent for pay runs and STP reporting',
      ]),
      details_timeline: items([
        'Payroll scope review: within 1 business day.',
        'Setup time depends on employee numbers and software access.',
        'Pay runs and STP reporting follow your registered agent’s pay calendar.',
      ]),
      enquiry_heading: 'Start Your Payroll Admin Support Enquiry',
    },
  },
  'services/business-personal-loan-support': {
    path: MOVED['services/business-personal-loan-support'],
    title: 'Finance Broker Referrals',
    yoast: { title: 'Business Finance Broker Referrals | SOL Business Consultant', description: 'Get your business records organised, then meet our partner licensed finance broker for business or personal lending.' },
    fields: {
      card_text: 'Organised business records and an introduction to a licensed finance broker, who provides all credit advice.',
      eyebrow: 'Finance Broker Referrals',
      hero_intro: `Looking for business or personal finance? We help you get your business records in order, then introduce you to ${broker}. Your broker gives the credit advice and handles your application. SOL is not a broker or credit provider.`,
      hero_highlights: items(['Organised business records', 'Licensed broker partner', 'Referral fees disclosed']),
      overview_heading: 'Organised Records, Then the Right Licensed Broker',
      features: items([
        'Business record and document checklist',
        'Organised folder of your business records',
        'An introduction to our partner licensed broker',
        'Written disclosure of any referral fee SOL receives',
      ]),
      why: items([
        'Support for sole traders, startups and established businesses.',
        'Clear, complete business records before you meet a broker.',
        'Scope agreed before work begins, with any referral fee disclosed in writing.',
      ]),
      details_who: items([
        'Business owners getting their records in order before speaking with a finance broker.',
        'Sole traders who want their business records organised.',
        'Owners who want an introduction to a licensed finance broker.',
      ]),
      details_get: items([
        'Business record and document checklist',
        'Organised folder of your business records',
        'An introduction to our partner licensed broker',
        'Written disclosure of any referral fee SOL receives',
      ]),
      details_timeline: items([
        'Initial records review: within 1 business day.',
        'Organising your records depends on what you already have.',
        'Credit advice, applications and approval times are handled by your broker.',
      ]),
      enquiry_heading: 'Start Your Finance Broker Referral Enquiry',
    },
  },
  'services/accountancy-partnerships': {
    title: 'Accounting Partners',
    yoast: { title: 'Accounting Partners for Small Businesses | SOL', description: 'We organise your records and introduce you to our partner registered accountant and tax agent for BAS, tax returns and advice.' },
    fields: {
      card_text: 'Records organised by SOL, with BAS, tax returns and tax advice from a registered tax agent.',
      eyebrow: 'Accounting Partners',
      hero_intro: (PARTNERS.accountant.name
        ? `SOL partners with ${PARTNERS.accountant.name}, a registered tax agent (TPB no. ${PARTNERS.accountant.tpb}).`
        : 'SOL works with a registered tax agent.')
        + ' We organise your records and handle the business admin; the registered tax agent prepares and lodges your BAS and tax returns and gives tax advice. You deal with one team, and each service is done by the right professional.',
      hero_highlights: items(['Records organised by SOL', 'BAS and tax by a registered tax agent', 'One point of contact']),
      overview_heading: 'Each Service Done by the Right Professional',
      features: items([
        'Records organised and handed over to our partner accountant',
        'BAS, tax returns and tax advice from our partner registered tax agent',
        'One point of contact for updates in your SOL client portal',
      ]),
      why_heading: 'Why Clients Choose SOL',
      why: items([
        'Registered professionals for every tax and BAS task.',
        'Your records organised before they reach the accountant.',
        'Updates in one place, in your SOL client portal.',
      ]),
      enquiry_heading: 'Start Your Accounting Partners Enquiry',
    },
  },
  'services/accountancy': {
    title: 'Business Finance Guidance',
    yoast: { title: 'Business Finance Guidance | SOL Business Consultant', description: 'Plain-English help with business finance admin, software and record keeping, with referrals to registered accountants and BAS agents.' },
    fields: {
      card_text: 'Finance admin, software and record-keeping guidance',
      eyebrow: 'Business Finance Guidance',
      hero_heading: 'Business Finance Guidance\nfor [Australian Businesses]',
      enquiry_heading: 'Start Your Business Finance Guidance Enquiry',
    },
  },
  'services/company-business-registration': {
    title: 'Business Setup Support',
    yoast: { title: 'Business Setup Support | ASIC and ABN Applications | SOL', description: 'Help preparing your ASIC company and ABN applications, with referrals to registered tax agents for TFN and GST registration.' },
    fields: {
      card_text: 'Help preparing ASIC company and ABN applications, with referrals to registered tax agents for TFN and GST registration.',
      hero_intro: 'Starting a business? We help you prepare your ASIC company or business name application and your ABN application, and give you a checklist of what comes next. For TFN and GST registration, and advice on the right structure, we refer you to a registered tax agent.',
      hero_highlights: items(['ASIC company and business name applications', 'ABN application support', 'Tax agent referrals for TFN and GST']),
      overview_heading: 'Simple, Well-Prepared Business Setup',
      features: items([
        'Pty Ltd company and business name applications prepared clearly with you for ASIC.',
        'ABN application support, with referrals to a registered tax agent for TFN and GST registration.',
        'A clear checklist of what comes next, from first enquiry to open for business.',
      ]),
      details_who: items([
        'Founders setting up a new Australian company or business name.',
        'Existing businesses that need help with ASIC or ABN applications.',
        'Owners who want clear setup steps and business-ready documents.',
      ]),
      details_get: items([
        'Help preparing your ASIC company or business name application.',
        'ABN application support where included in your scope.',
        'Referral to a registered tax agent for TFN and GST registration and structure advice.',
        'A post-registration checklist so you know exactly what to do next.',
      ]),
      enquiry_heading: 'Start Your Business Setup Enquiry',
    },
  },
};
SERVICES['services/bookkeeping-bas-lodgement'].path = MOVED['services/bookkeeping-bas-lodgement'];

/* ---------- exact text swaps anywhere on the site (labels, menu, pricing, inclusions, products) ---------- */
const SWAP = {
  // Service names (menus, footer, inclusions, home sections, contact form choices).
  'Bookkeeping & BAS Lodgement': 'Bookkeeping Support',
  'Business & Personal Loan Support': 'Finance Broker Referrals',
  'Business & Personal Loans': 'Finance Broker Referrals',
  'Accountancy Partnerships': 'Accounting Partners',
  'Payroll Services': 'Payroll Admin Support',
  'Company & Business Registration': 'Business Setup Support',
  'Accounting Consultancy': 'Business Finance Guidance',
  'Accountancy': 'Business Finance Guidance',
  // Page addresses.
  '/services/bookkeeping-bas-lodgement/': '/services/bookkeeping-support/',
  '/services/business-personal-loan-support/': '/services/finance-broker-referrals/',
  // Mega menu descriptions.
  'ASIC, ABN, TFN & GST setup': 'ASIC and ABN applications',
  'Xero, QuickBooks & BAS': 'Xero and QuickBooks support',
  'Xero, MYOB, QuickBooks & BAS': 'Xero and QuickBooks support',
  'PAYG, super & STP support': 'Payroll software and records',
  'Loan packs & broker links': 'Funding document packs',
  'CPA network & growth planning': 'Accountant referrals',
  // Pricing cards and products.
  'BAS/GST preparation support': 'GST record organisation (BAS lodged by a registered agent)',
  'Payroll/STP process support': 'Payroll records and software support',
  'Tax planning and annual return referrals': 'Referrals to registered tax agents for tax returns',
  'Tax planning referrals where required': 'Referrals to registered tax agents where required',
  'NDIS financial reporting': 'NDIS financial record organisation',
  'Bookkeeping, BAS and GST preparation, and financial reporting. Billed monthly.': 'Bookkeeping support, GST record organisation and financial reporting. BAS is lodged by a registered BAS agent. Billed monthly.',
  'Everything in Monthly Finance Support, plus payroll and STP processing. Billed monthly.': 'Everything in Monthly Finance Support, plus payroll records and software support. Pay runs and STP reporting by a registered agent. Billed monthly.',
  'Everything in Finance Operations Support, plus NDIS financial reporting. Billed monthly.': 'Everything in Finance Operations Support, plus NDIS financial record organisation. Billed monthly.',
  // Service inclusions page.
  'BAS preparation & lodgement (quarterly)': 'Records organised for your registered BAS agent (quarterly)',
  'BAS preparation (ready to lodge)': 'Organised records handed to your BAS agent',
  'PAYG withholding calculation & lodgement': 'Payroll software checks (PAYG withholding lodged by your registered agent)',
  'STP (Single Touch Payroll) lodgement': 'STP reporting handled by your registered BAS or payroll agent',
  'End-of-year PAYG summary & year-end processing': 'Year-end payroll record checklist',
  'Quarterly STP lodgement confirmation': 'Quarterly payroll record checklist',
  'Annual PAYG & super reporting': 'Annual payroll record summary for your agent',
  'Tax File Number (TFN) registration': 'Referral to a registered tax agent for TFN registration',
  'ASIC company registration + documentation': 'ASIC company application preparation + documentation',
  // Accounting pages (shared "who / what you get" lists).
  'Businesses that need bookkeeping, payroll, BAS coordination or finance process guidance.': 'Businesses that need bookkeeping or payroll admin, or finance process guidance.',
  'Bookkeeping, payroll, BAS or accounting partner coordination, as scoped.': 'Bookkeeping, payroll admin or accounting partner coordination, as scoped.',
  // Business Finance Guidance page.
  'BAS & Tax Guidance': 'Understanding your obligations',
  'Introductions to CPA partners for tax returns': 'Referrals to registered tax agents for tax returns',
  'Business structure, ABN and company registration advice': 'General information on business structures',
  'Accounting Partner Network': 'Accountant Referrals',
  'We introduce you to trusted CPA and registered accountant partners for compliant, professional service.': 'We refer you to registered accountants and tax agents for compliant, professional service.',
  // Home page.
  'NDIS-aligned processes': 'Built around the NDIS Practice Standards',
  'Bookkeeping, BAS & Payroll': 'Bookkeeping & Payroll Admin',
  'Bookkeeping, BAS, payroll, or reporting support': 'Bookkeeping, payroll admin or reporting support',
  'Ideal if you need cleaner books, payroll processing, BAS preparation, reconciliations and reporting.': 'Ideal if you need cleaner books, payroll admin, records ready for your BAS agent, reconciliations and reporting.',
  'Company setup, bookkeeping and BAS, payroll, accounting and admin support, so the day-to-day runs smoothly.': 'Business setup, bookkeeping support, payroll admin and accountant referrals, so the day-to-day runs smoothly.',
  // Service page template trust badge.
  'Specialist NDIS service planning': 'Independent business consultancy',
  // Data Privacy & Security.
  'Sensitive client data (documents, invoices, personal information) is encrypted at rest on our secure servers. Only authorised staff can decrypt and access.': 'Sensitive client data (documents, invoices, personal information) is kept on secure, access-controlled systems. Only authorised staff can access it.',
  'We provide free credit monitoring or other support as appropriate. We also implement changes to prevent recurrence.': 'We tell affected people what happened and what they can do to protect themselves, and we make changes to prevent it happening again.',
  // Training: a "was" price must be a genuine former price.
  'Complete the full NDIS Support Coordinator pathway, from Foundation to Advanced, and take your career further. AUD $1,097 (was $1,297).': 'Complete the full NDIS Support Coordinator pathway, from Foundation to Advanced, and take your career further. AUD $1,097.',
};

/* ---------- substring fixes inside longer text (legal pages, blog) ---------- */
const PARTS = [
  // Refund policy and Terms: ASIC doesn't handle consumer disputes.
  [/Consumer Affairs Victoria \(1300 558 181\) or ASIC/g, 'Consumer Affairs Victoria (1300 558 181) or the ACCC'],
  // Terms: Services Provided.
  ['Accountancy &amp; Bookkeeping', 'Bookkeeping support and referrals to registered accountants'],
  ['Accountancy & Bookkeeping', 'Bookkeeping support and referrals to registered accountants'],
  ['company registration and loan support', 'business setup support and finance broker referrals'],
  ['Accountancy Setup:', 'Bookkeeping Setup:'],
  // Terms: accounting clauses.
  ['Accounting services: Review deliverables with your accountant; tax laws change frequently.', 'Bookkeeping support: Your registered accountant or BAS agent is responsible for lodgements and tax advice.'],
  ['Tax &amp; Accounting: Based on current law; external accountant review recommended.', 'Tax &amp; Accounting: SOL does not provide tax, BAS or accounting services; these are provided by registered tax and BAS agents.'],
  ['Tax & Accounting: Based on current law; external accountant review recommended.', 'Tax & Accounting: SOL does not provide tax, BAS or accounting services; these are provided by registered tax and BAS agents.'],
];

const swapText = (t) => {
  if (typeof t !== 'string') return t;
  if (Object.prototype.hasOwnProperty.call(SWAP, t)) return SWAP[t];
  let out = t;
  for (const [from, to] of PARTS) out = typeof from === 'string' ? out.split(from).join(to) : out.replace(from, to);
  return out;
};
const deep = (v, fn) => {
  if (typeof v === 'string') return fn(v);
  if (Array.isArray(v)) return v.map((x) => deep(x, fn));
  if (v && typeof v === 'object') { const o = {}; for (const [k, x] of Object.entries(v)) o[k] = deep(x, fn); return o; }
  return v;
};
const page = (seed, path) => seed.pages.find((p) => p.path === path);
const sections = (item) => (item && item.fields && item.fields.page_sections) || [];

export function applyCompliance(seed) {
  let n = 0;
  const count = (fn) => (v) => { const r = fn(v); if (r !== v) n++; return r; };

  // 1. Service pages: new names, copy and the disclaimer box.
  for (const s of seed.services) {
    const fix = SERVICES[s.path];
    if (!fix) continue;
    s.title = fix.title;
    s.yoast = { ...s.yoast, ...fix.yoast };
    Object.assign(s.fields, fix.fields);
    if (fix.path) s.path = fix.path;
    s.fields.page_sections = [{ acf_fc_layout: 'notice', style: 'info', text: FINANCE_NOTICE }, ...sections(s)];
    n++;
  }
  // NDIS Registration names the NDIA.
  const ndis = seed.services.find((s) => s.path === 'services/ndis-registration');
  if (ndis) {
    ndis.fields.hero_disclaimer = NDIS_DISCLAIMER;
    for (const sec of sections(ndis)) if (sec.note && /not affiliated/.test(sec.note)) sec.note = NDIS_DISCLAIMER;
    n++;
  }

  // 2. Text swaps everywhere.
  for (const g of ['pages', 'services', 'blocks', 'posts', 'options', 'products', 'courses']) seed[g] = deep(seed[g], count(swapText));

  // 3. Footer, home page logos, NDIS pricing card.
  seed.options.footer_copyright = FOOTER;
  for (const home of [page(seed, 'home'), page(seed, 'home-new')]) {
    for (const sec of sections(home)) {
      if (sec.acf_fc_layout === 'logo_strip') {
        sec.eyebrow = 'Software';
        sec.heading = 'Software we work with';
        sec.intro = 'We set up and support the business software our clients use every day. Product names are shown for context only and do not imply endorsement or partnership.';
        sec.logos = [{ name: 'Xero', image: '' }, { name: 'QuickBooks', image: '' }, { name: 'Easy|Compliance', image: '' }];
      }
      if (Array.isArray(sec.stats)) {
        for (const st of sec.stats) {
          if (st.value === '12+' && /business services/i.test(st.label)) st.value = '15';
          if (st.value === '24h' && /response/i.test(st.label)) { st.value = '1 day'; st.label = 'Business-day response'; }
        }
      }
    }
  }
  const dropItem = (v) => {
    if (Array.isArray(v)) return v.filter((x) => !(x && x.item === 'ABN / company setup')).map(dropItem);
    if (v && typeof v === 'object') { const o = {}; for (const [k, x] of Object.entries(v)) o[k] = dropItem(x); return o; }
    return v;
  };
  seed.pages = dropItem(seed.pages);
  seed.blocks = dropItem(seed.blocks);

  // 4. Terms and Conditions: the footer disclaimer as a clause.
  const terms = page(seed, 'terms-conditions');
  for (const sec of sections(terms)) {
    if (!Array.isArray(sec.sections)) continue;
    const reg = sec.sections.find((x) => /Compliance & Regulatory/.test(x.title));
    if (reg && !/not a registered tax agent/.test(reg.content)) {
      reg.content += `<p>Our role: ${FOOTER.replace('Copyright {year} ', '')}</p>`;
    }
  }

  // 5. BAS blog post: general information, recategorised, with the disclaimer.
  const bas = seed.posts.find((p) => p.path === 'bas-lodgement-australian-businesses');
  if (bas) {
    bas.category = 'Business finance';
    if (!/registered BAS or tax agent\.<\/p>/.test(bas.content)) {
      bas.content += `\n\n<!-- wp:paragraph -->\n<p><strong>${FINANCE_NOTICE}</strong></p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Your BAS must be lodged by you or a registered BAS or tax agent.</p>\n<!-- /wp:paragraph -->`;
    }
  }
  return n;
}

/* ---------- claims that must not come back ---------- */
const BANNED = [
  'BAS Lodgement', 'Monthly or quarterly BAS', 'BAS-ready books', 'BAS preparation coordination', 'Xero and QuickBooks experts',
  'Payroll & Tax Process Support', 'Weekly, fortnightly or monthly pay runs', 'credit guidance', 'Application guidance',
  'Application document preparation', 'Loan-ready', 'lender introductions', 'CPA Advisory', 'Qualified CPA partners', 'tax planning',
  'BAS & Tax Guidance', 'Introductions to CPA partners', 'handles ASIC, ABN, TFN', 'ABN, TFN and GST registration',
  'Sole trader, partnership or company advice', 'Advice on the right business structure', 'Specialist NDIS service planning',
  'Accountancy & Bookkeeping', 'Loan packs', 'CPA network', 'free credit monitoring', 'encrypted at rest', '181) or ASIC',
  'NDIS-aligned processes', '(was $', 'ABN / company setup', 'STP (Single Touch Payroll) lodgement', 'BAS preparation & lodgement',
];

/** Removed claims still present (blog posts may discuss BAS lodgement as general information). */
export function complianceProblems(seed) {
  const out = [];
  const scan = (v, where) => {
    if (typeof v === 'string') {
      for (const b of BANNED) if (v.toLowerCase().includes(b.toLowerCase())) out.push(`${where}: "${b}"`);
    } else if (Array.isArray(v)) v.forEach((x, i) => scan(x, `${where}[${i}]`));
    else if (v && typeof v === 'object') for (const [k, x] of Object.entries(v)) scan(x, `${where}.${k}`);
  };
  for (const g of ['pages', 'services', 'blocks', 'options', 'products', 'courses']) scan(seed[g], g);
  return out;
}
