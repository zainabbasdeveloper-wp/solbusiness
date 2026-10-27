// Service pages (children of /services/, "SOL Service Page" template). Order matches the home page grid.
import { D, L, R, btn, rbtn, img, ic, items, ref, app } from './helpers.mjs';
import { DATA } from './helpers.mjs';
const { serviceCards, detailServices } = DATA.serviceCatalog;
import { pkgCard } from './blocks.mjs';

const SP = D.ServicePagePolishSection;
const details = (type) => {
  const c = { ...SP.defaults, ...(SP.serviceContent[type] || {}) };
  return {
    show_details: true,
    details_who: items(c.who), details_get: items(c.get), details_process: items(c.process), details_timeline: items(c.timeline),
    details_faqs: c.faqs.map(([question, answer]) => ({ question, answer })),
  };
};
const block = (slug) => ({ acf_fc_layout: 'reusable_block', block: ref('sol_block', slug) });
const payBtn = () => ({ link: { title: 'Make a Service Payment', url: '/service-payment', target: '' }, style: 'outline' });
const enquireBtn = (label = 'Start Your Enquiry') => btn(label, '#intake-form');
const grid = (o) => ({ acf_fc_layout: 'card_grid', section_id: '', eyebrow: '', background: 'chalk', align: 'left', heading: '', intro: '', source: 'manual', columns: '4', card_style: 'bordered', cards: [], buttons: [], note: '', ...o });
const textBlock = (o) => ({ acf_fc_layout: 'text_image', section_id: '', eyebrow: '', background: 'chalk', align: 'left', heading: '', intro: '', content: '', image: '', image_alt: '', image_position: 'left', image_badge: '', stats: [], checklist: [], buttons: [], callout: { icon: '', title: '', text: '' }, ...o });
const features = (arr) => arr.map((f) => ({ icon: ic(f.icon), title: f.title, text: f.desc }));
const card = (slug) => serviceCards.find((c) => c.slug === slug);
// The training site uses short icon keys; these are the real Lucide names.
const ICON = { graduation: 'graduation-cap', credit: 'credit-card', building: 'building-2', briefcase: 'briefcase-business', clipboard: 'clipboard-list', receipt: 'receipt-text', code: 'code-xml' };
const lucide = (k) => ICON[k] || k;

const base = (slug, o) => {
  const c = card(slug) || {};
  return {
    slug, title: c.title || o.title, menu_order: Math.max(0, serviceCards.findIndex((s) => s.slug === slug)),
    fields: { card_icon: lucide(ic(c.icon)), card_text: c.desc || '', card_url: '', hide_in_grid: false, show_enquiry: true, ...o.fields },
    seo: o.seo || {},
  };
};

// Standard services rendered from serviceCatalog.detailServices
const standard = Object.entries(detailServices).map(([slug, s]) => base(slug, {
  title: s.title,
  fields: {
    service_type: s.serviceType, eyebrow: s.eyebrow, hero_heading: '', hero_intro: s.intro, hero_highlights: items(s.highlights),
    hero_image: img(s.heroImage.replace('/Images/', '')), hero_buttons: [enquireBtn(), payBtn(s.serviceType)],
    overview_heading: s.heading, features: items(s.features), why_heading: s.whyHeading,
    why_intro: 'Each service is scoped before paid work begins, with practical deliverables and a clear next step for your business.',
    why: items(s.why), ...details(s.serviceType),
    enquiry_heading: `Start Your ${s.title} Enquiry`,
    enquiry_text: 'Share your details and the SOL team will confirm the right scope, package, and next step for your situation.',
    page_sections: [block('service-trust'), block('service-journey'), { acf_fc_layout: 'service_details', heading: '' }, { acf_fc_layout: 'service_overview', eyebrow: '' }, { acf_fc_layout: 'service_enquiry', section_id: 'intake-form', form_variant: 'contact' }],
  },
  seo: { seo_title: `${s.title} | SOL Business Consultant`, seo_description: s.intro },
}));

const NR = D.NDISRegistration;
const ndis = base('ndis-registration', {
  fields: {
    service_type: 'ndis_registration', eyebrow: 'NDIS Registration', hero_heading: 'Start Your NDIS\nBusiness with\n[Confidence]',
    hero_intro: 'SOL Business Consultant guides new and growing providers through NDIS registration preparation, policy documentation, audit readiness, and operational setup.',
    hero_highlights: items(['Registration pathway review', 'Policy and evidence support', 'Australian provider focus']),
    hero_image: img('services/ndis-registration-documents.webp'),
    hero_buttons: [btn('Find Your Registration Package', '/get-started'), payBtn('ndis_registration')],
    hero_disclaimer: 'SOL Business Consultant is not affiliated with the NDIS Quality and Safeguards Commission. Registration and audit outcomes depend on your business, scope, evidence, auditor assessment, and official decisions.',
    ...details('ndis_registration'),
    page_sections: [
      grid({ eyebrow: 'Why Choose Sol', heading: 'A Practical Partner for Your NDIS Journey', align: 'center', card_style: 'plain', cards: NR.WHY.map((w) => ({ title: w.title, text: w.desc })) }),
      block('service-trust'), block('service-journey'),
      { acf_fc_layout: 'service_details', heading: '' },
      { acf_fc_layout: 'steps', section_id: '', eyebrow: 'Registration Pathway', background: 'white', align: 'center', heading: 'Your Path to NDIS Provider Registration', intro: '', style: 'numbered', columns: '1', groups: [{ title: '', icon: '', steps: NR.PATHWAY_STEPS.map((s) => ({ anchor_id: '', label: s.step, icon: '', title: s.title, text: s.desc })) }], image: '', buttons: [], note: '' },
      {
        acf_fc_layout: 'directory', section_id: 'ndis-services', eyebrow: 'NDIS Service Scope', background: 'chalk', align: 'left',
        heading: 'Explore NDIS Services for Registration Planning',
        intro: 'Use this list to understand which supports your organisation may want to register for. Your selected services affect registration groups, audit pathway, evidence, staffing, and operational requirements.',
        searchable: true,
        groups: NR.NDIS_SERVICE_GROUPS.map((g) => ({ title: g.title, items: g.services.map(([label, slug]) => ({ label, url: `https://directory.solbusinessconsultant.com.au/services/${slug}` })) })),
        note: '',
      },
      { acf_fc_layout: 'cta_banner', section_id: '', style: 'dark', eyebrow: 'Also Available', heading: 'NDIS Support Coordination Training', text: 'Training resources and structured learning pathways for support coordination teams, with individual, team, and enterprise options available.', buttons: [btn('View Training Packages', '/services/support-coordination-training')], list: [], image: '', note: '' },
      grid({
        section_id: 'registration-options', eyebrow: 'Choose Your Path', background: 'white', align: 'center', heading: 'How Would You Like to Register?',
        intro: 'Whether you want full guided support, just need to get your business details in order first, or prefer to apply on your own — we have an option for you.',
        columns: '3', card_style: 'tagged',
        cards: NR.REGISTRATION_PATHS.map((p) => ({ icon: ic(p.icon), tag: p.badge || '', title: p.title, text: `${p.subtitle}\n\n${p.desc}`, list: items(p.highlights), link: L(p.cta, p.ctaHref, p.ctaExternal ? '_blank' : '') })),
        note: 'SOL Business Consultant is not affiliated with the NDIS Quality and Safeguards Commission. Registration outcomes depend on your business structure, scope, evidence, and official auditor assessments.',
      }),
      { acf_fc_layout: 'cta_banner', section_id: 'intake-form', style: 'light', eyebrow: 'Get Started Now', heading: 'Start Your NDIS Registration', text: 'Complete the form below so we can understand your pathway, business details, and documentation needs before recommending next steps. Select your legal entity, operating states and intended registration groups to receive the correct fixed-fee package and payment options.', buttons: [btn('Find Your Registration Package', '/get-started'), btn('Open Package Finder', '/get-started', 'outline')], list: [], image: '', note: '' },
    ],
  },
  seo: { seo_title: 'NDIS Registration Support Australia | SOL Business Consultant', seo_description: 'NDIS registration preparation, policy documentation, audit readiness and operational setup for Australian providers.' },
});

const SC = D.SupportCoordinationTraining;
const training = base('support-coordination-training', {
  fields: {
    service_type: 'support_coordination_training', eyebrow: 'NDIS Training', hero_heading: 'Support Coordination\nTraining That\n[Builds Careers]',
    hero_intro: "Australia's most comprehensive NDIS Support Coordinator training — 12 modules, 1,500+ quiz questions, and full curriculum aligned to NDIS Practice Standards. For individuals, teams, and RTOs.",
    hero_stats: SC.OUTCOMES.map((o) => ({ value: o.stat, label: o.label })),
    hero_image: img('training/professional-workshop-presentation.webp'),
    hero_buttons: [btn('Enrol Now', '#intake-form'), payBtn('support_coordination_training'), btn('View All 12 Modules', '#modules', 'light')],
    ...details('support_coordination_training'),
    enquiry_heading: 'Start Your Training Journey',
    enquiry_text: 'Submit your details below — select your training package and our team will be in touch within 1 business day.',
    page_sections: [
      { acf_fc_layout: 'notice', style: 'warning', text: 'SOL Support Coordination Training is non-accredited professional development unless expressly delivered by an authorised RTO under its own scope. Certificates issued by SOL are completion records, not nationally recognised AQF qualifications.' },
      grid({
        section_id: 'modules', eyebrow: 'Full Curriculum', align: 'center', heading: '12 Modules — Complete SC Training',
        intro: 'Every module includes written content, quiz questions, PowerPoint slide decks, scenario assessments, and facilitator guides.',
        columns: '2', card_style: 'tagged',
        cards: SC.MODULES.map((m) => ({ icon: ic(m.icon), tag: `Module ${m.num}`, title: m.title, text: m.desc, list: items(m.topics) })),
        buttons: [btn('Enquire About Training', '#intake-form')],
      }),
      { acf_fc_layout: 'service_details', heading: '' },
      {
        acf_fc_layout: 'pricing', section_id: 'training-pricing', eyebrow: 'Training Packages', background: 'white', align: 'center',
        heading: 'Flexible Pricing for Every Need', intro: 'Individual learners, teams, and enterprise RTOs — choose the package that fits.', columns: '3',
        tabs: [{ label: 'Training', icon: '', packages: SC.TRAINING_PACKAGES.map((p) => pkgCard({ ...p, href: '#intake-form' })), note: 'All prices exclude GST. Enterprise pricing available on request. Group discounts apply for 10+ staff.' }],
        bottom_cta: { heading: '', text: '', link: null },
      },
      grid({
        eyebrow: "What's Included", background: 'ink', align: 'center', heading: 'Every Package Includes Full Curriculum Materials',
        intro: "Our training materials are built for quality delivery — whether you're an individual learner, a registered provider upskilling your team, or an RTO delivering accredited training.",
        columns: '3', card_style: 'dark',
        cards: [
          { icon: 'play-circle', title: 'Flexible Delivery', text: 'Self-paced online or blended with live Sol trainer sessions. Works for individuals and large teams.' },
          { icon: 'award', title: 'NDIS Practice Standards Aligned', text: 'All content mapped to NDIS Practice Standards and Code of Conduct. Audit-ready from day one.' },
          { icon: 'users', title: 'RTO-Ready Content', text: 'Enterprise package includes full white-label rights and LMS integration support for RTOs.' },
        ],
      }),
      block('training-faq'),
      { acf_fc_layout: 'service_enquiry', section_id: 'intake-form', form_variant: 'training' },
    ],
  },
  seo: { seo_title: 'NDIS Support Coordination Training Australia | SOL', seo_description: '12-module NDIS Support Coordinator training with 1,500+ quiz questions for individuals, teams and RTOs.' },
});

const WD = D.WebsiteDevelopment;
const website = base('website-development', {
  fields: {
    service_type: 'website_development', eyebrow: 'Website Development', hero_heading: 'Professional Websites\nThat [Convert]',
    hero_intro: 'Custom website and app development solutions for Australian businesses that need a modern, responsive, and results-focused digital foundation.',
    hero_image: img('blog/website-project-planning.webp'), hero_buttons: [enquireBtn(), payBtn('website_development')],
    ...details('website_development'),
    enquiry_heading: 'Start Your Website Enquiry', enquiry_text: "Tell us about your project and we'll prepare a tailored proposal within 1 business day.",
    page_sections: [
      grid({ cards: features(WD.LIVE_FEATURES) }),
      textBlock({ heading: 'What We Build', checklist: items(WD.LIVE_SERVICES), callout: { icon: '', title: 'Why Sol for Web Development?', text: "We understand the NDIS industry. Your website isn't just a brochure — it's your intake system, your credibility engine, and your growth tool. We build sites that do all three, connected to your compliance and registration systems." } }),
      block('service-trust'), block('service-journey'),
      { acf_fc_layout: 'service_details', heading: '' },
      { acf_fc_layout: 'service_enquiry', section_id: 'intake-form', form_variant: 'contact' },
    ],
  },
  seo: { seo_title: 'Website Development for NDIS Providers & Businesses | SOL', seo_description: 'Custom website and app development for Australian businesses and NDIS providers.' },
});

const SA = D.SoftwareAutomation;
const software = base('software-automation', {
  fields: {
    service_type: 'software_automation', eyebrow: 'Software & NDIS Automation', hero_heading: 'Automate Your\nNDIS Compliance\n[Intelligently]',
    hero_intro: 'From document generation to compliance dashboards — we build the automation systems that let you focus on participants, not paperwork.',
    hero_image: img('services/automation-dashboard.webp'), hero_buttons: [enquireBtn(), payBtn('software_automation')],
    ...details('software_automation'),
    enquiry_heading: 'Tell Us What You Want to Automate', enquiry_text: "Submit your requirements and we'll prepare a tailored automation solution for your NDIS business.",
    page_sections: [
      grid({ cards: features(SA.FEATURES) }),
      { acf_fc_layout: 'checklist', section_id: '', eyebrow: '', background: 'chalk', align: 'center', heading: 'What We Can Automate For You', intro: '', style: 'checks', columns: '2', items: items(SA.USE_CASES), buttons: [] },
      { acf_fc_layout: 'service_details', heading: '' },
      grid({
        eyebrow: 'Security & Compliance', background: 'ink', align: 'center', heading: 'Your Data is Safe With Us',
        intro: 'Every automation we build is designed with Australian privacy law, NDIS data requirements, and enterprise-grade security at its core.',
        columns: '2', card_style: 'dark',
        cards: [
          { icon: 'shield-check', title: 'Australian Privacy Act Compliant', text: 'All systems we build adhere to the Privacy Act 1988 and the Australian Privacy Principles (APPs), ensuring lawful collection, storage, and use of personal data.' },
          { icon: 'lock', title: 'End-to-End Encryption', text: 'Data in transit and at rest is encrypted using AES-256 and TLS 1.3 standards. No sensitive client or participant data is ever stored unencrypted.' },
          { icon: 'eye', title: 'NDIS Practice Standards Aligned', text: 'Our automation solutions are scoped to meet NDIS Practice Standards around data governance, participant confidentiality, and incident reporting obligations.' },
          { icon: 'server', title: 'Australian-Hosted Data', text: 'Client and participant data remains on Australian soil. We work with locally hosted infrastructure providers to ensure data sovereignty compliance.' },
        ],
      }),
      grid({
        background: 'ink', columns: '3', card_style: 'dark',
        cards: [
          { title: 'Access Control', text: 'Role-based permissions ensure only authorised staff can access sensitive participant records and system functions.' },
          { title: 'Audit Logs', text: 'Every action in our systems is logged with timestamps and user attribution — ready for NDIS audit review at any time.' },
          { title: 'Data Minimisation', text: 'We only collect and store the data strictly necessary for the purpose, in line with APP 3 data minimisation principles.' },
        ],
      }),
      { acf_fc_layout: 'service_enquiry', section_id: 'intake-form', form_variant: 'contact' },
    ],
  },
  seo: { seo_title: 'Software & NDIS Automation Services | SOL Business Consultant', seo_description: 'NDIS document automation, client intake systems, compliance dashboards and Easy Compliance setup.' },
});

const AC = D.Accountancy;
const accountancy = {
  slug: 'accountancy', title: 'Accounting Consultancy', menu_order: 20,
  fields: {
    card_icon: 'calculator', card_text: 'Finance operations support', card_url: '', hide_in_grid: true, show_enquiry: true,
    service_type: 'accountancy', eyebrow: 'Accounting Consultancy', hero_heading: 'Accounting Consultancy\nfor [Australian Businesses]',
    hero_intro: "We're not accountants — we're consultants. We help you understand your financial obligations, set up the right systems, and connect you with the right registered professionals.",
    hero_image: img('services/accounting-consultation.webp'), hero_buttons: [enquireBtn('Start Your Enquiry'), payBtn('accountancy')],
    ...details('accountancy'),
    enquiry_heading: 'Start Your Accounting Consultancy Enquiry', enquiry_text: "Tell us about your situation and we'll guide you or connect you with the right licensed professional.",
    page_sections: [
      grid({ cards: features(AC.FEATURES) }),
      textBlock({
        heading: 'What We Help With', checklist: items(AC.SERVICES),
        callout: { icon: 'info', title: 'Why Sol Business Consultant?', text: 'We are business consultants — not registered accountants or CPAs. We help you navigate your financial landscape, set up the right tools, and connect you with the right licensed professionals. All advice is general in nature and does not constitute financial, tax, or legal advice.' },
      }),
      { acf_fc_layout: 'notice', style: 'warning', text: 'Consultancy Only — We provide guidance and referrals. We are not registered accountants, CPAs, or financial advisers. Always consult a licensed professional for formal advice.' },
      { acf_fc_layout: 'service_details', heading: '' },
      { acf_fc_layout: 'service_enquiry', section_id: 'intake-form', form_variant: 'contact' },
    ],
  },
  seo: { seo_title: 'Accounting Consultancy & Bookkeeping Support Australia | SOL', seo_description: 'Bookkeeping referrals, BAS and tax guidance, and accounting partner connections for Australian businesses.' },
};

const MS = D.MarketingServices;
const marketing = {
  ...base('social-media-marketing', {
    fields: {
      service_type: 'digital_marketing', eyebrow: 'Marketing Services', hero_heading: 'Practical Digital Marketing for [Australian Businesses]',
      hero_intro: 'SEO, paid ads, social media, email, and reporting support designed around clear scope, Australian audiences, and measurable next actions.',
      hero_image: img('services/marketing-strategy-team.webp'),
      hero_buttons: [btn('Get a Free Strategy Call', '/#contact'), btn('View Packages', '#packages', 'light'), payBtn('digital_marketing')],
      ...details('digital_marketing'),
      show_enquiry: false,
      page_sections: [
        { acf_fc_layout: 'checklist', section_id: '', eyebrow: '', background: 'amber', align: 'center', heading: '', intro: '', style: 'pills', columns: '4', items: items(['No lock-in plans', 'Australian contact', 'Monthly reporting', 'Clear campaign scope']), buttons: [] },
        block('service-trust'), block('service-journey'),
        { acf_fc_layout: 'service_details', heading: '' },
        {
          acf_fc_layout: 'pricing', section_id: 'packages', eyebrow: 'Transparent Pricing', background: 'white', align: 'center', heading: 'Marketing Packages',
          intro: 'Packages are scoped before work begins. Ad spend, content volume, and reporting needs are confirmed during onboarding.', columns: '3',
          tabs: [{ label: 'Packages', icon: '', packages: MS.PACKAGES.map((p) => ({ ...pkgCard({ ...p, cta: 'Get Started', href: '/#contact' }), price_note: '' })), note: '' }],
          bottom_cta: { heading: '', text: '', link: null },
        },
        grid({ eyebrow: 'What We Do', background: 'chalk', align: 'center', heading: 'Our Marketing Services', columns: '3', cards: features(MS.SERVICES) }),
        { acf_fc_layout: 'cta_banner', section_id: '', style: 'dark', eyebrow: '', heading: 'Ready to Discuss Your Marketing?', text: 'Book a free strategy call and we will scope the right marketing plan before recommending paid work.', buttons: [btn('Book a Free Strategy Call', '/#contact'), payBtn('digital_marketing')], list: [], image: '', note: '' },
        {
          acf_fc_layout: 'contact', section_id: 'intake-form', eyebrow: 'Get in Touch', background: 'white', align: 'left', heading: 'Enquire About Marketing Support',
          intro: 'Tell us what you need and a SOL team member will respond within one business day.', show_details: true, buttons: [],
          form_type: 'builtin', form_variant: 'contact', form_shortcode: '', select_label: 'Package of Interest',
          select_options: [{ value: 'Starter', label: 'Starter' }, { value: 'Growth', label: 'Growth' }, { value: 'Custom', label: 'Custom / Not sure yet' }],
          consent_text: 'Optional: send me marketing updates and service information. I can unsubscribe at any time.', privacy_text: "We'll respond within 1 business day. No spam.",
          success_message: 'Thanks. A SOL team member will contact you within one business day.',
        },
      ],
    },
  }),
  slug: 'marketing',
};
marketing.seo = { seo_title: 'Digital Marketing Services Australia | SOL Business Consultant', seo_description: 'SEO, paid ads, social media, email and reporting support for Australian businesses.' };

export const services = [ndis, training, ...standard, website, software, marketing, accountancy]
  .sort((a, b) => a.menu_order - b.menu_order);
