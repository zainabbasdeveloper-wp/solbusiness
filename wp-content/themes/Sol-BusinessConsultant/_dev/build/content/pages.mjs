// WordPress pages. Slugs match the React routes so existing links keep working.
import { readFileSync } from 'node:fs';
import { D, L, R, ext, btn, rbtn, img, ic, items, ref, app, yesNo } from './helpers.mjs';
import { DATA, buy, buyId, WOO } from './helpers.mjs';
import { ndisSku, SKU_BY_NAME } from './products.mjs';
const { NDIS_PACKAGES, NDIS_ENTITY_TYPES, NDIS_REGISTRATION_GROUPS, NDIS_OPTIONAL_MODULES } = DATA.ndisRegistrationPricing;

const block = (slug) => ({ acf_fc_layout: 'reusable_block', block: ref('sol_block', slug) });
const header = (eyebrow, heading, text = '', o = {}) => ({ acf_fc_layout: 'page_header', eyebrow, heading, text, info_cards: [], buttons: [], ...o });
const head = (o = {}) => ({ section_id: '', eyebrow: '', background: 'white', align: 'left', heading: '', intro: '', ...o });
const grid = (o) => ({ acf_fc_layout: 'card_grid', ...head(), source: 'manual', columns: '3', card_style: 'bordered', cards: [], buttons: [], note: '', ...o });
const faq = (o) => ({ acf_fc_layout: 'faq', ...head({ align: 'center' }), items: [], schema: false, cta: { heading: '', text: '', buttons: [] }, ...o });
const cta = (o) => ({ acf_fc_layout: 'cta_banner', section_id: '', style: 'dark', eyebrow: '', heading: '', text: '', buttons: [], list: [], image: '', note: '', ...o });
const steps = (o) => ({ acf_fc_layout: 'steps', ...head(), style: 'numbered', columns: '2', groups: [], image: '', buttons: [], note: '', ...o });
const checklist = (o) => ({ acf_fc_layout: 'checklist', ...head(), style: 'checks', columns: '3', items: [], buttons: [], ...o });
const textImage = (o) => ({ acf_fc_layout: 'text_image', ...head(), content: '', image: '', image_alt: '', image_position: 'left', image_badge: '', stats: [], checklist: [], buttons: [], callout: { icon: '', title: '', text: '' }, ...o });
const contact = (o) => ({
  acf_fc_layout: 'contact', ...head(), show_details: true, buttons: [], form_type: 'builtin', form_variant: 'contact', form_shortcode: '',
  select_label: 'Service of interest', select_options: D.BookConsultation.SERVICE_OPTIONS.map(([value, label]) => ({ value, label })),
  consent_text: 'Optional: send me NDIS and business service updates. I can unsubscribe at any time.', privacy_text: '', success_message: '', ...o,
});
const notice = (text, style = 'info') => ({ acf_fc_layout: 'notice', style, text });
const html = (paras) => paras.map((p) => (p.startsWith('<') ? p : `<p>${p}</p>`)).join('');
const ul = (arr) => `<ul>${arr.map((x) => `<li>${x}</li>`).join('')}</ul>`;
const cmpRows = (rows) => rows.map(([label, ...vals]) => ({ label, values: vals.map(yesNo).join(' | ') }));

/* ---------------- Home ---------------- */
const H = D.HeroSection;
const home = {
  slug: 'home', title: 'Home', front_page: true,
  seo: { seo_title: 'SOL Business Consultant | NDIS Registration, Compliance & Business Services', seo_description: 'Australian business and NDIS consulting with practical systems, documentation, and support to move from setup to audit-ready operations.' },
  sections: [
    {
      acf_fc_layout: 'hero', style: 'split', eyebrow: '', heading: 'STRUCTURE.\nCOMPLIANCE.\n[SCALE.]',
      text: 'Australian business and NDIS consulting with practical systems, documentation, and support to move from setup to audit-ready operations.',
      buttons: [btn('Book Free Consultation', '/book-consultation'), btn('Explore Services', '/#services', 'outline')],
      badges: H.trustBadges.map((b) => ({ icon: ic(b.icon), label: b.label })),
      stats: H.stats.map((s) => ({ value: s.value, label: s.label })),
      image: img('services/compliance-document-review.webp'), image_alt: 'Australian compliance consultant reviewing business documentation',
      card_title: 'Audit Ready', card_text: 'Your compliance system is configured and reviewed against your workflow.', disclaimer: '',
    },
    {
      acf_fc_layout: 'logo_strip', ...head({ eyebrow: 'Australian Compliance Context', align: 'center', heading: 'Built Around the Systems Australian Providers Use', intro: 'We help clients navigate common government, accounting, compliance, and business platforms. Platform names are shown for context only and do not imply endorsement or partnership.' }),
      logos: D.LogoCarousel.PLATFORMS.map((p) => ({ name: p.abbr.replace(/\n/g, '|'), image: '' })), animate: true,
      pills: items(['ABN-backed Australian business', 'NDIS-aware service guidance', 'Clear pricing before paid work', 'Privacy-conscious enquiry handling']),
    },
    grid({ section_id: 'services', eyebrow: 'Our Services', heading: 'Comprehensive Solutions for Every Stage of Growth', source: 'services', card_style: 'numbered', buttons: [btn('View All Services', '/services/'), btn('Browse Training Courses', '/training-courses', 'outline'), btn('Book Free Consultation', '/book-consultation', 'outline')] }),
    block('service-comparison'),
    cta({ style: 'image', heading: 'Your One-Stop Business Partner', text: 'From startup to scale - every service connected under one roof', image: img('services/marketing-strategy-team.webp') }),
    steps({
      eyebrow: 'Clear Delivery Path', align: 'center', heading: 'Services and training stay organised from payment to completion',
      intro: 'SOL separates business service delivery from student training, so clients and learners each get the right portal, documents, updates, and support.',
      groups: [
        { title: 'For Business Services', icon: 'briefcase-business', steps: D.ProcessPortalSection.serviceSteps.map((s) => ({ title: s.title, text: s.desc })), buttons: [btn('Explore Business Services', '/services/'), btn('Make a Service Payment', '/service-payment', 'outline')] },
        { title: 'For Training', icon: 'graduation-cap', steps: D.ProcessPortalSection.trainingSteps.map((s) => ({ title: s.title, text: s.desc })), buttons: [btn('Browse Training Courses', '/training-courses'), btn('Compare Training Packages', '/services/support-coordination-training#training-pricing', 'outline')] },
      ],
      buttons: [btn('Book Free Consultation', '/book-consultation'), btn('View All Pricing', '/pricing', 'outline')],
    }),
    grid({
      background: 'chalk', heading: 'Client portal included with service work', intro: 'After a service payment, clients can manage project communication and files from one secure place.',
      columns: '4', card_style: 'feature', cards: D.ProcessPortalSection.clientBenefits.map((b) => ({ icon: ic(b.icon), title: b.title, text: b.desc })),
    }),
    cta({
      heading: 'Already have a quote?', text: 'Make a secure payment for agreed service work. Training enrolments remain separate in the student training system.',
      list: items(D.ProcessPortalSection.trustItems), buttons: [rbtn('Make Service Payment', '/service-payment'), btn('Explore Training', '/training-courses', 'light')],
    }),
    grid({
      background: 'chalk', eyebrow: 'Start With The Right Brief', heading: 'Faster quotes, clearer delivery, fewer back-and-forth emails',
      intro: 'Visitors can start with guided service intake, download preparation resources, or go straight to consultation. Training visitors stay on the student path, while service clients move into the client portal.',
      columns: '2', card_style: 'bordered',
      cards: D.IntakeResourcesSection.intakeCards.map((c) => ({ icon: ic(c.icon), tag: 'Service Intake Path', title: c.title, text: c.desc, link: L('Continue', c.href) })),
      buttons: [btn('Start Service Intake', '/get-started'), btn('Browse Training', '/training-courses', 'outline')],
    }),
    grid({
      background: 'chalk', heading: 'Helpful Resources', columns: '2', card_style: 'feature',
      cards: D.IntakeResourcesSection.resources.map((c) => ({ icon: ic(c.icon), title: c.title, text: c.desc, link: L('Open', c.href) })),
    }),
    cta({
      style: 'light', heading: 'After payment, everything is tracked',
      text: 'Service clients use the client portal for documents, requests, plans, invoices, and deliverables. Training students use the student dashboard for lessons, quizzes, certificates, and resources.',
      buttons: [rbtn('Choose Portal', '/portal', 'outline')],
    }),
    {
      acf_fc_layout: 'resource_download', ...head({ eyebrow: 'Free Tools', heading: 'Start with a checklist or find the right SOL service', intro: 'Capture your next step in a structured way. We keep service enquiries separate from training enrolments and send the right pathway to your inbox.' }),
      resources: D.LeadGrowthToolsSection.RESOURCES.map((r) => ({ key: r.id, title: r.title, text: r.desc, file: '' })),
      consent_text: 'Send me this resource and relevant SOL service updates. I can unsubscribe anytime.', button_label: 'Request Checklist',
    },
    {
      acf_fc_layout: 'quiz', ...head({ background: 'chalk', heading: 'Service selector quiz', intro: 'Answer three questions and get a recommended SOL pathway.' }),
      mode: 'recommend', bullets: [], start_label: 'Find My Service', lead_capture: false,
      consent_text: 'Send me this recommendation and relevant SOL follow-up.',
      questions: D.LeadGrowthToolsSection.QUESTIONS.map((q) => ({ group: '', question: q.label, options: q.options.map((o) => ({ label: o.label, score: 0, value: o.value })) })),
      results: Object.entries(D.LeadGrowthToolsSection.RECOMMENDATIONS).map(([key, r]) => ({ key, min_percent: 0, title: r.title, subtitle: 'Recommended Pathway', description: r.desc, link: L(r.cta, r.href) })),
      note: '',
    },
    grid({
      eyebrow: 'Engagement Examples', heading: 'How SOL typically supports clients and learners',
      intro: 'Practical examples of how our services, training, documents, and portal workflows come together.', card_style: 'tagged',
      cards: D.CaseStudiesPreviewSection.items.map((c) => ({ icon: ic(c.icon), tag: c.label, title: c.title, text: c.desc })),
      buttons: [btn('View Examples', '/case-studies', 'outline')],
    }),
    grid({
      background: 'chalk', eyebrow: 'Featured Offers', heading: 'Current packages, plans, and next-step offers',
      intro: 'Start with the most useful pathways below, or view all current plans.', card_style: 'bordered',
      cards: D.FeaturedOffersSection.fallbackOffers.map((o) => ({ icon: 'sparkles', tag: 'Start here', title: o.title, text: o.service_name, link: L('Open', o.href) })),
      buttons: [rbtn('View All Plans', '/plans', 'outline')],
    }),
    block('service-trust'),
    block('service-journey'),
    block('consulting-packages'),
    block('pricing-tabs'),
    block('compliance'),
    block('ndis-roadmap'),
    block('about-sol'),
    block('marketing-packages'),
    textImage({
      background: 'ink', eyebrow: 'Service Standards', heading: 'Built for Australian Providers and Growing Businesses',
      content: '<p>We do not publish client names, ratings, or outcome statistics unless they are verified and approved. This section now focuses on the service standards clients can expect from SOL Business Consultant.</p>',
      callout: { icon: 'message-square', title: '', text: 'Have a project in mind? Use the enquiry form and we will confirm scope, cost, and likely timing before any paid work begins.' },
    }),
    grid({ background: 'ink', columns: '4', card_style: 'dark', cards: D.TestimonialSection.STANDARDS.map((s) => ({ icon: ic(s.icon), title: s.title, text: s.desc })) }),
    checklist({ background: 'ink', eyebrow: 'Common Support Areas', items: items(D.TestimonialSection.WORK_AREAS) }),
    block('general-faq'),
    block('contact'),
  ],
};

/* ---------------- Simple pages ---------------- */
const about = {
  slug: 'about', title: 'About',
  seo: { seo_title: 'About SOL Business Consultant | Australian NDIS & Business Support', seo_description: 'Learn how SOL Business Consultant supports Australian providers, small businesses, and learners with NDIS registration, compliance, training, digital operations, and finance support.' },
  sections: [
    header('About SOL', 'Australian business, compliance, and NDIS support with practical delivery.', 'SOL Business Consultant Pty Ltd supports Australian providers, small businesses, and learners with NDIS registration guidance, compliance systems, training, digital operations, and finance support.'),
    block('about-sol'), block('compliance'),
  ],
};

const contactPage = {
  slug: 'contact', title: 'Contact',
  seo: { seo_title: 'Contact SOL Business Consultant | Glenroy VIC', seo_description: 'Contact the SOL Business Consultant team in Glenroy, Victoria for NDIS registration guidance, compliance, training, and business support.' },
  sections: [
    header('Contact', 'Speak with the SOL team', '', { info_cards: [
      { icon: 'phone', label: 'Phone', value: '+61 460 003 494', url: 'tel:+61460003494' },
      { icon: 'mail', label: 'Email', value: 'info@solbusinessconsultant.com.au', url: 'mailto:info@solbusinessconsultant.com.au' },
      { icon: 'map-pin', label: 'Location', value: 'Glenroy VIC 3046, Australia', url: '' },
    ] }),
    block('contact'),
  ],
};

const faqPage = {
  slug: 'faq', title: 'FAQ',
  seo: { seo_title: 'FAQ | SOL Business Consultant', seo_description: 'Answers to common questions about NDIS registration, compliance support, training, bookkeeping, business services, and working with SOL.' },
  sections: [header('FAQ', 'Common questions', 'Answers about NDIS registration, support coordination training, compliance support, and working with SOL.'), block('general-faq'), block('training-faq')],
};

const pricing = {
  slug: 'pricing', title: 'Pricing',
  seo: { seo_title: 'Pricing & Packages | SOL Business Consultant', seo_description: 'Compare SOL training, NDIS registration, marketing, and consulting packages. Custom services are quoted after the project scope is confirmed.' },
  sections: [header('Pricing', 'Packages and service options', 'Compare training, NDIS registration, marketing, and consulting support in one place. Custom work is quoted after scope confirmation.'), block('pricing-tabs'), block('consulting-packages'), block('marketing-packages')],
};

const blog = {
  slug: 'blog', title: 'Blog',
  seo: { seo_title: 'Blog | SOL Business Consultant', seo_description: 'Practical SOL guides for NDIS registration, client portals, automation, compliance workflows, websites, marketing, and Australian business operations.' },
  sections: [
    header('Insights', 'Business, NDIS & Automation Blog', 'Practical guides for NDIS registration, client portals, automation, compliance workflows, websites, marketing, and business operations.'),
    { acf_fc_layout: 'post_feed', ...head(), post_type: 'post', count: 24, featured_first: true, show_filters: true, buttons: [btn('View Use Cases', '/use-cases', 'outline')] },
    cta({ style: 'light', heading: 'Need advice for your own workflow?', text: 'Book a consultation and we can map the right NDIS, automation, website, or client portal pathway for your business.', buttons: [btn('Book Consultation', '/book-consultation')] }),
  ],
};

const caseStudies = {
  slug: 'case-studies', title: 'Engagement Examples',
  seo: { seo_title: 'Engagement Examples | SOL Business Consultant', seo_description: 'Practical examples of how SOL supports NDIS registration, training, bookkeeping, compliance, and service delivery workflows.' },
  sections: [
    header('Engagement Examples', 'How We Typically Support Clients', 'These are general examples of SOL Business Consultant service pathways. They are not client case studies, testimonials, or guarantees of outcomes.'),
    grid({ columns: '1', card_style: 'tagged', cards: D.CaseStudies.ENGAGEMENTS.map((e) => ({ icon: ic(e.icon), tag: e.tag, subtitle: e.subtitle, title: e.title, text: e.overview, list: items(e.support), list_title: 'Typical Support', meta: [{ label: 'Expected Deliverable', value: e.outcome }] })) }),
    cta({ heading: 'Want to Discuss Your Situation?', text: 'Book a free consultation and we will map the most suitable pathway for your business before recommending paid work.', buttons: [btn('Get My Free Assessment', '/get-started')] }),
  ],
};

const UC = D.UseCases;
const useCases = {
  slug: 'use-cases', title: 'Use Cases',
  seo: { seo_title: 'Use Cases | SOL Business Consultant', seo_description: 'Professional use cases for SOL client portal, automation, NDIS registration, document workflows, CRM, tickets, and service delivery.' },
  sections: [
    header('Use Cases', 'Practical automation use cases for SOL service delivery', 'See how client portal, CRM, document verification, support tickets, notifications, and service workflows can work together without mixing client services with the student training portal.', { buttons: [rbtn('Make a Service Payment', '/service-payment'), btn('Book Consultation', '/book-consultation', 'outline')] }),
    notice('These are operational examples for service delivery design, not client testimonials or guaranteed outcomes.'),
    checklist({ heading: 'Typical service workflow', style: 'numbered', columns: '1', items: items(UC.WORKFLOW) }),
    checklist({ background: 'ink', eyebrow: 'Connected delivery system', heading: 'One flow from payment to delivery', style: 'pills', columns: '4', items: items(UC.CONNECTED_SYSTEMS) }),
    grid({ columns: '2', card_style: 'tagged', cards: UC.USE_CASES.map((u) => ({ icon: ic(u.icon), tag: u.label, title: u.title, text: u.summary, list: items(u.steps) })) }),
    cta({ heading: 'Want this mapped to your business?', text: 'SOL can map your service flow, client portal needs, document workflow, and admin CRM requirements before development or automation work starts.', buttons: [btn('View Automation Service', '/services/software-automation')] }),
  ],
};

/* ---------------- Marketing packages ---------------- */
const MP = D.MarketingPackages;
const marketingPackages = {
  slug: 'marketing-packages', title: 'Marketing Packages',
  seo: { seo_title: 'Marketing Packages | SOL Business Consultant', seo_description: 'Transparent digital marketing packages, a full service list and optional add-ons for Australian businesses.' },
  sections: [
    {
      acf_fc_layout: 'hero', style: 'dark', eyebrow: 'Digital Marketing', heading: 'Marketing Packages\n& [Services]',
      text: 'Transparent pricing and flexible plans. Choose a package or build a custom plan with our team.',
      buttons: [btn('View Packages', '#packages'), btn('Talk to Us', '#contact-cta', 'light'), { link: { title: 'Buy a Package', url: '#packages', target: '' }, style: 'outline' }],
      badges: [], stats: [['Scoped', 'Campaign Plans'], ['Monthly', 'Performance Review'], ['No Lock-In', 'Flexible Plans'], ['Australia', 'Local Contact']].map(([value, label]) => ({ value, label })),
      image: img('services/marketing-strategy-team.webp'), image_alt: 'Marketing team reviewing digital campaign plans at a laptop', card_title: '', card_text: '', disclaimer: '',
    },
    {
      acf_fc_layout: 'comparison_table', ...head({ section_id: 'packages', eyebrow: 'Compare Plans', align: 'center', heading: 'Feature Comparison', intro: 'All plans include onboarding, setup, and a no lock-in monthly contract.' }),
      first_column_label: 'Feature',
      columns: MP.PACKAGES.map((p) => ({ name: p.name, tagline: p.tagline, badge: p.badge || '', price: `${p.price}${p.period}`, link: L(`Get ${p.name}`, '#contact-cta'), highlight: !!p.dark })),
      groups: [{ category: '', rows: MP.PACKAGES[0].features.map((f, i) => ({ label: f.label, values: MP.PACKAGES.map((p) => yesNo(p.features[i].value)).join(' | ') })) }],
      footer_label: 'Ready to get started?', note: '',
    },
    {
      acf_fc_layout: 'pricing', ...head({ background: 'chalk', eyebrow: 'Choose Your Plan', align: 'center', heading: 'Marketing Packages' }), columns: '3',
      tabs: [{ label: 'Packages', icon: '', packages: MP.PACKAGES.map((p) => ({
        icon: '', category: '', name: p.name, tag: '', badge: p.badge || '', highlight: !!p.dark, price: p.price, period: p.period, price_note: '', tagline: p.tagline,
        features: items(p.features.filter((f) => f.value !== false).map((f) => (typeof f.value === 'string' ? `${f.label}: ${f.value}` : f.label))), extra_lines: [],
        cta: L('Buy Now', buy(SKU_BY_NAME[p.name])), cta_2: L('Ask a Question', '#contact-cta'),
      })), note: '' }],
      bottom_cta: { heading: '', text: '', link: null },
    },
    grid({ eyebrow: 'Full Service List', align: 'center', heading: 'All Marketing Services', columns: '4', cards: MP.SERVICES.map((s) => ({ icon: ic(s.icon), title: s.title, text: s.desc, link: L('Enquire Now', '#contact-cta') })) }),
    grid({ background: 'chalk', eyebrow: 'Enhancements', align: 'center', heading: 'Optional Add-Ons', intro: 'Bolt these on to any plan to supercharge your results.', cards: MP.ADD_ONS.map((a) => ({ icon: 'plus-circle', title: a.name, price: a.price, text: a.desc, link: L('Add to My Plan', '#contact-cta') })) }),
    cta({ section_id: 'contact-cta', heading: 'Ready to Grow Your Business?', text: 'Book a free strategy session. We will review your goals and scope a practical marketing plan before recommending paid work.', buttons: [btn('Call SOL', 'tel:+61460003494'), btn('Send an Enquiry', '/#contact', 'light')] }),
  ],
};

/* ---------------- Training courses ---------------- */
const trainingCourses = {
  slug: 'training-2', title: 'Training',
  seo: { seo_title: 'NDIS Support Coordinator Training Courses | SOL Training Academy', seo_description: "Explore SOL's online NDIS Support Coordinator training levels, course topics, and learning resources." },
  sections: [
    {
      acf_fc_layout: 'hero', style: 'dark', eyebrow: 'Online LMS Training', heading: 'NDIS Support Coordinator\n[Training Courses]',
      text: 'Three structured levels designed to take you from foundational knowledge to advanced practice. Self-paced, online, and built specifically for Australian NDIS professionals.',
      buttons: [btn('View Levels', '#levels'), btn('Ask a Question', '/#contact', 'light')], badges: [],
      stats: D.CourseHero.STATS.map((s) => ({ value: s.value, label: s.label })),
      image: img('hero/academy-training-hero.webp'), image_alt: 'Professional trainer delivering a laptop-based workshop to adult learners', card_title: '', card_text: '', disclaimer: '',
    },
    { acf_fc_layout: 'course_levels', ...head({ section_id: 'levels' }), courses: [`post:${WOO.level1}`, `post:${WOO.level2}`, `post:${WOO.level3}`], disclaimer: 'SOL training certificates record completion of non-accredited professional development. They are not nationally recognised training under the AQF unless expressly delivered by an authorised RTO under its own scope.' },
    grid({ background: 'chalk', eyebrow: 'Our Platform', align: 'center', heading: 'Learning Management System', intro: 'Our renowned LMS is a simple, powerful, and easy-to-use platform where all your courses and resources are stored and accessed.', cards: D.LMSFeatures.FEATURES.map((f) => ({ icon: ic(f.icon), title: f.title, text: f.desc })) }),
    cta({
      eyebrow: 'Best Value', heading: 'Enrol in All 3 Levels & Save', text: 'Complete the full NDIS Support Coordinator pathway from Foundation to Advanced and take your career to the next level. AUD $1,097 (was $1,297).',
      list: items(['Save $200 with the full bundle', 'Lifetime access to all 3 levels']),
      buttons: [{ link: ext('Buy on Website', 'https://www.solbusinessconsultant.com.au/training-2/'), style: 'primary' }, btn('Have questions? Contact Us', '/#contact', 'light')], note: 'Call +61 460 003 494',
    }),
  ],
};

/* ---------------- NDIS packages ---------------- */
const NP = D.NDISPackagesPage;
const ndisPackages = {
  slug: 'ndis-packages', title: 'NDIS Packages',
  seo: { seo_title: 'NDIS Registration Support Packages | SOL Business Consultant', seo_description: "Compare SOL's NDIS registration support packages, inclusions, payment options, and preparation information." },
  sections: [
    header('NDIS Registration Support', 'Clear Pricing. Flexible Packages.', "Choose the right level of support for your NDIS registration journey. Whether you're a small verification-only provider or a complex SIL/SDA organization, we've got a package that fits."),
    {
      acf_fc_layout: 'pricing', ...head({ align: 'center' }), columns: '3',
      tabs: [{ label: 'NDIS', icon: '', packages: Object.values(NDIS_PACKAGES).map((p) => ({
        icon: '', category: p.pathway, name: p.name, tag: `Best for: ${p.bestFor}`, badge: p.tier === 2 ? 'Most Popular' : '', highlight: p.tier === 2,
        price: `$${p.total.toLocaleString('en-AU')}`, period: '', price_note: `AUD inc GST (ex GST: $${p.priceExGst.toLocaleString('en-AU')})`, tagline: '',
        features: items(p.features), extra_lines: p.paymentOptions.map((o) => ({ label: o.label, value: `$${o.amount.toLocaleString('en-AU', { minimumFractionDigits: o.amount % 1 ? 2 : 0 })}` })),
        cta: L('Get Started', '/get-started'), cta_2: L('Learn More', '/services/ndis-registration'),
      })), note: '' }],
      bottom_cta: { heading: '', text: '', link: null },
    },
    checklist({ heading: 'Understanding the Tiers', columns: '1', items: items(['SOL START: Ideal for verification-only providers (Tier 1 registration groups)', 'SOL CERTIFIED: Perfect for core module direct support providers (Tier 2 groups)', 'SOL COMPLETE: Comprehensive support for SIL, SDA, and specialist providers (Tier 3 groups)']) }),
    {
      acf_fc_layout: 'comparison_table', ...head({ background: 'chalk' }), first_column_label: 'Feature',
      columns: ['SOL START', 'SOL CERTIFIED', 'SOL COMPLETE'].map((name, i) => ({ name, tagline: '', badge: i === 1 ? 'Most Popular' : '', price: '', link: null, highlight: i === 1 })),
      groups: NP.comparisonFeatures.map((g) => ({ category: g.category, rows: g.items.map((it) => ({ label: it.label, values: [it.start, it.certified, it.complete].map(yesNo).join(' | ') })) })),
      footer_label: '', note: '',
    },
    faq({ heading: 'Frequently Asked Questions', items: NP.faqs.map((f) => ({ question: f.question, answer: f.answer })), schema: true }),
    cta({ heading: 'Ready to Get Registered?', text: 'Start your NDIS registration journey today. Our team is ready to support your organization through every step.', buttons: [btn('Start Registration', '/get-started'), btn('Talk to Our Team', '/contact', 'light')] }),
  ],
};

/* ---------------- Get started (package finder) ---------------- */
const getStarted = {
  slug: 'get-started', title: 'Get Started',
  seo: { seo_title: 'NDIS Registration Package Finder | SOL Business Consultant', seo_description: 'Choose your organisation type, operating states, and intended NDIS registration groups to find the relevant SOL support package.' },
  sections: [
    header('NDIS Registration Package Finder', 'Find the right registration package', 'Select your entity and intended registration groups. Your package is determined by the highest audit pathway selected.'),
    {
      acf_fc_layout: 'ndis_finder', ...head({ background: 'chalk' }),
      entity_types: NDIS_ENTITY_TYPES.map((e) => ({ value: e.value, label: e.label })),
      states: items(D.GetStarted.STATES),
      groups: NDIS_REGISTRATION_GROUPS.map((g) => ({ code: g.code, name: g.name, tier: g.tier, company_tier: g.companyTier || g.tier, entity_dependent: !!g.entityDependent })),
      modules: NDIS_OPTIONAL_MODULES.map((m) => ({ code: m.code, name: m.name, tier: m.tier, manual_review: !!m.manualReview })),
      packages: Object.values(NDIS_PACKAGES).map((p) => ({
        tier: p.tier, name: p.name, pathway: p.pathway, best_for: p.bestFor, price_ex_gst: p.priceExGst, gst: p.gst, total: p.total, features: items(p.features),
        payment_options: p.paymentOptions.map((o) => ({ label: o.label, amount: o.amount, url: buy(ndisSku(p.id, o.id === 'full')) })),
      })),
      retainer_text: 'Add optional Compliance Retainer — $395 + GST per month ($434.50 inc GST), offered after registration is granted.',
      manual_review_text: 'Your selection includes a platform provider, more than eight certification groups, or multi-state SIL operations. SOL will confirm the scope before accepting payment.',
      fee_note: "The approved quality auditor's fee is separate and paid directly to the auditor. Verification audits are typically around $900-$1,500; certification audits are typically around $3,000-$6,000 plus GST depending on scope, sites and staff.",
      consult_link: L('Talk to an advisor', '/book-consultation'),
    },
  ],
};

/* ---------------- Book consultation ---------------- */
const bookConsultation = {
  slug: 'book-consultation', title: 'Book a Consultation',
  seo: { seo_title: 'Book a Consultation | SOL Business Consultant', seo_description: 'Choose an available consultation time and share your details so the SOL team can review your request and follow up with next steps.' },
  sections: [
    header('Free Consultation', 'Book a Consultation', 'Choose a time and share a few details. The SOL team will review your request and follow up with the next steps.'),
    contact({ background: 'chalk', heading: 'Request a consultation', intro: 'Tell us your preferred day and time (Australia/Melbourne). If you use a booking tool such as Calendly, paste its shortcode in this section.', form_variant: 'consultation', success_message: 'Thanks. Your consultation request has been sent to the SOL team.' }),
  ],
};

/* ---------------- Quizzes ---------------- */
const RQ = D.ReadinessQuiz;
const readinessQuiz = {
  slug: 'readiness-quiz', title: 'NDIS Readiness Quiz',
  seo: { seo_title: 'Am I Ready to Register as an NDIS Provider? | Free Quiz', seo_description: 'Answer 8 quick questions to find out your NDIS registration readiness and get a personalised recommendation.' },
  sections: [{
    acf_fc_layout: 'quiz', ...head({ background: 'chalk', align: 'center', eyebrow: 'Free Assessment Tool', heading: 'Am I Ready to Register\nas an NDIS Provider?', intro: 'Answer 8 quick questions to find out your current readiness level and get a personalised recommendation — takes less than 2 minutes.' }),
    mode: 'score', bullets: items(['8 questions', '2 minutes', 'Instant personalised results', 'Free, no obligation']), start_label: 'Start Assessment', lead_capture: true,
    consent_text: 'Optional: send me NDIS and business service updates. I can unsubscribe at any time.',
    questions: RQ.QUESTIONS.map((q) => ({ group: '', question: q.question, options: q.options.map((o) => ({ label: o.label, score: o.score, value: '' })) })),
    results: [['very_ready', 85], ['ready', 60], ['partially_ready', 35], ['not_ready', 0]].map(([key, min]) => ({ key, min_percent: min, title: RQ.RESULTS[key].title, subtitle: RQ.RESULTS[key].subtitle, description: RQ.RESULTS[key].description, link: L(RQ.RESULTS[key].cta, RQ.RESULTS[key].ctaLink) })),
    note: 'Free consultation — no obligation',
  }],
};

const RC = D.NDISReadinessCalculator;
const readinessCalculator = {
  slug: 'ndis-readiness-calculator', title: 'NDIS Readiness Calculator',
  seo: { seo_title: 'NDIS Registration Readiness Calculator | SOL Business Consultant', seo_description: 'Answer 15 questions across 5 compliance domains and get an instant NDIS registration readiness score.' },
  sections: [{
    acf_fc_layout: 'quiz', ...head({ background: 'chalk', align: 'center', eyebrow: 'Free Compliance Tool', heading: 'NDIS Registration\nReadiness Calculator', intro: 'Answer 15 questions across 5 compliance domains and receive an instant, personalised readiness score with specific recommendations.' }),
    mode: 'score', bullets: items(['15 questions', '5 domains assessed', 'Instant score', 'Free & confidential']), start_label: 'Start Calculator', lead_capture: true,
    consent_text: 'I agree to receive relevant service updates and marketing emails. I can unsubscribe at any time.',
    questions: RC.DOMAINS.flatMap((d) => d.questions.map((q) => ({ group: d.label, question: q.text, options: q.options.map((o) => ({ label: o.label, score: o.score, value: '' })) }))),
    results: [['excellent', 85], ['good', 65], ['fair', 40], ['poor', 0]].map(([key, min]) => ({ key, min_percent: min, title: RC.READINESS_CONFIG[key].label, subtitle: RC.READINESS_CONFIG[key].sublabel, description: RC.READINESS_CONFIG[key].message, link: L(RC.READINESS_CONFIG[key].cta, RC.READINESS_CONFIG[key].ctaHref) })),
    note: 'Free consultation — no obligation. +61 460 003 494 · info@solbusinessconsultant.com.au',
  }],
};

/* ---------------- Trust pages ---------------- */
const DP = D.DataPrivacyAndSecurity;
const dataPrivacy = {
  slug: 'data-privacy-and-security', title: 'Data Privacy & Security',
  seo: { seo_title: 'Data Privacy & Security | SOL Business Consultant', seo_description: 'How SOL protects client data: encryption, backups, access controls, NDIS privacy and breach response.' },
  sections: [
    header('Trust & Security', 'Data Privacy & Security', "Your data is protected by industry-standard encryption, regular backups, and strict access controls. We're transparent about how we handle your information."),
    grid({ eyebrow: 'Security First', align: 'center', heading: 'How We Protect Your Data', intro: 'Multiple layers of security protect your data from collection to storage to access.', columns: '2', card_style: 'feature', cards: DP.SECURITY_MEASURES.map((m) => ({ icon: ic(m.icon), tag: m.status, title: m.title, text: m.desc })) }),
    grid({ background: 'chalk', eyebrow: 'Data Handling', align: 'center', heading: 'How We Handle Your Information', intro: 'Clear practices for collecting, storing, accessing, and retaining your data.', card_style: 'tagged', cards: DP.DATA_PRACTICES.map((p) => ({ title: p.category, list: items(p.items) })) }),
    grid({ eyebrow: 'Compliance', align: 'center', heading: 'NDIS Provider Privacy', intro: "NDIS participant data requires special care. Here's how we handle it.", columns: '1', card_style: 'tagged', cards: DP.NDIS_COMPLIANCE.map((c) => ({ icon: 'shield-check', title: c.title, text: c.desc, list: items(c.items) })) }),
    steps({ background: 'chalk', eyebrow: 'Incident Response', align: 'center', heading: 'If a Data Breach Occurs', intro: "We take data security seriously. Here's exactly what we do if something goes wrong.", columns: '1', groups: [{ title: '', icon: '', steps: DP.BREACH_RESPONSE.map((b) => ({ title: b.step, text: b.desc })) }] }),
    grid({ eyebrow: 'Legal Framework', align: 'center', heading: 'Australian Laws We Follow', intro: 'We comply with all relevant Australian privacy and data protection regulations.', columns: '2', cards: DP.LEGAL_COMPLIANCE.map((l) => ({ icon: 'scale', title: l.title, text: l.desc, link: ext('Learn more →', l.link) })) }),
    faq({ background: 'chalk', eyebrow: 'Your Rights', heading: 'Data Privacy Questions', items: DP.RIGHTS_FAQ.map((f) => ({ question: f.q, answer: f.a })) }),
    cta({ heading: 'Privacy Questions?', text: 'Contact our privacy team if you have data privacy questions, want to request your data, or need to report a concern.', buttons: [btn('Send Email', 'mailto:info@solbusinessconsultant.com.au'), btn('Support & Hours', '/support-and-availability', 'light')] }),
  ],
};

const SI = D.ServiceInclusions;
const serviceInclusions = {
  slug: 'service-inclusions', title: 'Service Inclusions',
  seo: { seo_title: "What's Included in Each Service | SOL Business Consultant", seo_description: 'Exact inclusions, exclusions, timelines, deliverables and support for every SOL service.' },
  sections: [
    header('Clarity & Transparency', "What's Included in Each Service", 'Exact deliverables, timelines, support levels, and what\'s NOT included. No surprises - just clear expectations.'),
    grid({ columns: '3', card_style: 'feature', cards: [
      { icon: 'list-checks', title: 'Every Service Has', text: 'Exact inclusions & exclusions listed below' },
      { icon: 'clock', title: 'Realistic Timelines', text: 'How long the service actually takes' },
      { icon: 'life-buoy', title: 'Support Included', text: "Who helps you & when they're available" },
    ] }),
    {
      acf_fc_layout: 'service_breakdown', ...head({ background: 'chalk', align: 'center', heading: 'Service-by-Service Breakdown', intro: "Click each service to see exactly what's included, timeline, deliverables, and support." }),
      items: SI.SERVICES_BREAKDOWN.map((s) => ({ name: s.name, icon: ic(s.icon), tagline: s.tagline, included: items(s.included), not_included: items(s.notIncluded), timeline: s.timeline, price: s.price, deliverables: items(s.deliverables), support: s.support, link: L('Get a quote for this service', '/book-consultation') })),
    },
    faq({ eyebrow: 'Common Questions', heading: 'About Service Scope & Inclusions', items: SI.COMMON_QUESTIONS.map((f) => ({ question: f.q, answer: f.a })) }),
    cta({ heading: 'Ready to Move Forward?', text: "Now that you know exactly what's included, book a free consultation or request a quote for your service.", buttons: [btn('Book Free Consultation', '/book-consultation'), btn('Request a Quote', '/get-started', 'light')] }),
  ],
};

const SA = D.SupportAndAvailability;
const support = {
  slug: 'support-and-availability', title: 'Support & Availability',
  seo: { seo_title: 'Support & Availability | SOL Business Consultant', seo_description: 'Contact hours, support channels and realistic response times for SOL Business Consultant.' },
  sections: [
    header('Contact & Support', 'Support & Availability', "Clear, honest contact hours and support response times so you know exactly when to reach us and how fast we'll respond.", { info_cards: [
      { icon: 'map-pin', label: 'Location', value: SA.DISPLAY_OFFICE_INFO.location, url: '' },
      { icon: 'globe', label: 'Timezone', value: SA.DISPLAY_OFFICE_INFO.timezone, url: '' },
      { icon: 'clock', label: 'Business Hours', value: SA.DISPLAY_OFFICE_INFO.hours, url: '' },
      { icon: 'calendar-x', label: 'Closed', value: 'Weekends & public holidays', url: '' },
    ] }),
    grid({ eyebrow: 'How to Reach Us', align: 'center', heading: 'Multiple Ways to Connect', intro: 'Choose the best contact method for your enquiry. All channels are monitored during business hours.', cards: SA.DISPLAY_SUPPORT_CHANNELS.map((c) => ({ icon: ic(c.icon), title: c.title, text: c.desc, meta: [{ label: 'Contact', value: c.contact }, { label: 'Hours', value: c.hours }, { label: 'Best For', value: c.details }] })) }),
    grid({ background: 'chalk', eyebrow: 'Our Promise', align: 'center', heading: 'Realistic Response Times', intro: "We set honest expectations. Here's exactly how quickly you can expect to hear from us.", cards: SA.DISPLAY_RESPONSE_TIMES.map((r) => ({ tag: r.time, title: r.tier, text: r.example, meta: [{ label: 'Via', value: r.contact }] })) }),
    grid({ eyebrow: 'Support Levels', align: 'center', heading: 'Support Varies by Package', intro: "Different service tiers include different support levels. Here's what to expect.", cards: SA.DISPLAY_SUPPORT_TIERS.map((t) => ({ tag: t.icon, title: t.name, meta: [{ label: 'Support Channels', value: t.support }, { label: 'Response Time', value: t.response }, { label: 'Availability', value: t.hours }, { label: 'Includes', value: t.includes }] })), note: 'Not sure which tier you have? Check your contract or invoice, or email us at info@solbusinessconsultant.com.au with your service reference.' }),
    faq({ background: 'chalk', eyebrow: 'Common Questions', heading: 'Frequently Asked About Support', items: SA.DISPLAY_FAQ_SUPPORT.map((f) => ({ question: f.q, answer: f.a })) }),
    cta({ heading: 'Ready to Get Started?', text: "Contact us using any of the channels above. Whether you have questions, need a quote, or want to book a consultation, we're here to help.", buttons: [btn('Call Now', 'tel:+61460003494'), btn('Send Email', 'mailto:info@solbusinessconsultant.com.au', 'light'), btn('Book Consultation', '/book-consultation', 'light')] }),
  ],
};

const AP = D.AfterPaymentJourney;
const afterPayment = {
  slug: 'after-payment', title: 'After Payment',
  seo: { seo_title: 'What Happens After You Pay or Enrol | SOL Business Consultant', seo_description: 'How SOL keeps business services and training separate after payment or enrolment.' },
  sections: [
    header('After Payment', 'What happens after you pay or enrol?', 'SOL keeps business services and training separate so clients see service work in the client portal, while students continue learning in the student dashboard.', { buttons: [rbtn('Make Service Payment', '/service-payment'), btn('Explore Training', '/training-courses', 'outline')] }),
    steps({ background: 'chalk', style: 'cards', groups: [
      { title: 'Service clients', icon: 'briefcase-business', steps: AP.serviceJourney.map((s) => ({ icon: ic(s.icon), title: s.title, text: s.desc })), buttons: [btn('Explore Business Services', '/services/'), btn('Make a Service Payment', '/service-payment', 'outline')] },
      { title: 'Training students', icon: 'graduation-cap', steps: AP.trainingJourney.map((s) => ({ icon: ic(s.icon), title: s.title, text: s.desc })), buttons: [btn('Browse Training Courses', '/training-courses'), btn('Compare Training Packages', '/services/support-coordination-training#training-pricing', 'outline')] },
    ] }),
    { acf_fc_layout: 'comparison_table', ...head({ align: 'center', heading: 'Client portal vs student dashboard', intro: 'Use the right portal for the right work.' }), first_column_label: '', columns: [{ name: 'Service clients', tagline: '', badge: '', price: '', link: null, highlight: false }, { name: 'Training students', tagline: '', badge: '', price: '', link: null, highlight: false }], groups: [{ category: '', rows: cmpRows(AP.comparison) }], footer_label: '', note: '' },
    cta({ heading: 'Need help choosing the right path?', text: 'If you are unsure whether you need service support, training, or both, book a consultation and we will point you to the correct portal and next step.', buttons: [btn('Book Consultation', '/book-consultation')] }),
  ],
};

/* ---------------- Legal ---------------- */
const legal = (meta, sections) => ({ acf_fc_layout: 'legal', meta_lines: items(meta), sections });
const privacy = {
  slug: 'privacy-policy', title: 'Privacy Policy',
  seo: { seo_title: 'Privacy Policy | SOL Business Consultant', seo_description: 'How SOL Business Consultant collects, uses and protects personal information under the Privacy Act 1988 (Cth).' },
  sections: [
    header('Legal', 'Privacy Policy'),
    legal(['SOL Business Consultant Pty Ltd (ABN 20 662 022 522)', 'Last updated: 11 July 2026'], [
      { title: 'Introduction', content: html(['SOL Business Consultant Pty Ltd (ABN 20 662 022 522) is committed to protecting your privacy and handling your personal information in accordance with the Privacy Act 1988 (Cth) and the Australian Privacy Principles (APPs).', 'This Privacy Policy explains how we collect, use, disclose and protect your personal information when you visit our website or use our business consulting services.']) },
      { title: '1. Information We Collect', content: ul(['Full name', 'Email address', 'Telephone number', 'Business or company information', 'Information submitted through contact forms', 'Information provided during consultations', 'Website usage information collected through cookies and analytics']) },
      { title: '2. How We Use Your Information', content: ul(['Respond to enquiries', 'Provide consulting services', 'Prepare proposals and quotations', 'Communicate regarding our services', 'Improve our website and customer experience', 'Meet legal and regulatory obligations']) },
      { title: '3. Cookies and Analytics', content: html(['Our website may use cookies to improve your browsing experience. We may also use Google Analytics or similar tools. Users can disable cookies through their browser settings.']) },
      { title: '4. Disclosure of Information', content: html(['We may disclose personal information to trusted service providers, IT providers, hosting providers and professional advisers, or where required by Australian law. We do not sell, rent or trade personal information.']) },
      { title: '5. Overseas Disclosure', content: html(['Some service providers used to operate this website and training portal may store or process data outside Australia, including Stripe for payments, Cloudinary for uploaded files, hosting providers, email providers and AI service providers such as Groq. We take reasonable steps to work with reputable providers and protect personal information disclosed to them.']) },
      { title: '6. Data Security', content: html(['We take reasonable steps to protect personal information against misuse, loss, unauthorised access, modification and disclosure.']) },
      { title: '7. Access and Correction', content: html(['Users may request access to or correction of their personal information by contacting us through the website.']) },
      { title: '8. Changes to This Policy', content: html(['This Privacy Policy may be updated from time to time. The latest version will always be published on this page.']) },
      { title: '9. Contact Us', content: html(['SOL Business Consultant Pty Ltd<br>ABN: 20 662 022 522<br>For privacy enquiries, please use the <a href="/contact">Contact page</a> on the website.']) },
    ]),
  ],
};

const refund = {
  slug: 'refunds-cancellation-policy', title: 'Refunds & Cancellation Policy',
  seo: { seo_title: 'Refund & Cancellation Policy | SOL Business Consultant', seo_description: 'Refund eligibility, alternatives, cancellation steps and your Australian Consumer Law rights.' },
  sections: [
    header('Legal', 'Refund & Cancellation Policy'),
    legal(['SOL Business Consultant Pty Ltd — ABN 20 662 022 522', 'In accordance with the Australian Consumer Law (ACL)'], [
      { title: 'Refund Eligibility', content: '<p><strong>✅ REFUND ELIGIBLE:</strong></p>' + ul(['Service was NOT delivered as promised (major failure)', 'Deliverables do NOT meet agreed specifications', 'Payment was charged in error or duplicated', 'Service was cancelled by us without cause']) + '<p><strong>❌ NON-REFUNDABLE (Digital Goods):</strong></p>' + ul(['NDIS training materials (once access granted)', 'Custom NDIS documents (once generated)', 'Software systems (once deployed)', 'Website hosting (once activated)', 'Consultation services (once delivered)']) },
      { title: 'Alternatives to Refunds', content: "<p>If you've changed your mind, we offer:</p>" + ul(['<strong>Service Credit:</strong> Apply toward future services (valid 12 months)', '<strong>Modified Deliverables:</strong> Up to 2 free revision rounds', '<strong>Deferred Delivery:</strong> Postpone service up to 12 months', '<strong>Partial Refund:</strong> For bundled services not yet commenced']) },
      { title: 'Service-Specific Refunds', content: ul(['<strong>NDIS Registration (SOL START | SOL CERTIFIED | SOL COMPLETE)</strong> — Refund window: 14 days before documents sent', '<strong>Support Coordination Training ($1,200–$3,800)</strong> — Refund window: 7 days before course access granted', '<strong>Website Development ($1,500–$3,500)</strong> — 100% refund before launch | 50% after design approval | Non-refundable after launch', '<strong>Monthly Bookkeeping ($350)</strong> — 100% refund within 30 days of first payment; then non-refundable per month']) },
      { title: 'Refund Processing', content: html(['Timeline: 7–10 business days (Stripe) | 5–7 business days (Bank transfer)', "Once approved, we'll email confirmation and you can track progress via your bank statement."]) },
      { title: 'Cancellation Procedures', content: html(['<strong>How to Cancel:</strong> Email info@solbusinessconsultant.com.au with your name, order ID, and reason']) + '<p><strong>Notice Required:</strong></p>' + ul(['One-time services: Immediate', 'Monthly subscriptions: 7 days', 'Annual subscriptions: 14 days']) + html(['<strong>Work in Progress:</strong> Refund for work not yet commenced; charges apply for completed work']) },
      { title: 'Guaranteed Remedies (Service Failures)', content: ul(['<strong>5+ days late:</strong> 5% service credit', '<strong>10+ days late:</strong> 10% service credit', '<strong>20+ days late:</strong> Full refund or 25% credit', '<strong>Quality failures:</strong> 2 free revisions; 25% credit if no improvement', '<strong>Duplicate charge:</strong> Immediate full refund within 48 hours']) },
      { title: 'Australian Consumer Law Rights', content: html(['You have consumer guarantees including: services provided with due care and skill, goods of acceptable quality, and goods matching description.', '<strong>Digital Goods Exception:</strong> Once accessed, digital goods cannot be returned for refund, but you retain remedies if goods are faulty or unfit for purpose.']) },
      { title: 'Disputes & Escalation', content: ul(['<strong>Step 1:</strong> Contact us within 30 days: info@solbusinessconsultant.com.au', '<strong>Step 2:</strong> We respond within 10 business days', '<strong>Step 3:</strong> Escalate to Consumer Affairs Victoria (1300 558 181) or ASIC if unresolved']) },
      { title: 'How to Request a Refund', content: html(['📧 <a href="mailto:info@solbusinessconsultant.com.au">info@solbusinessconsultant.com.au</a><br>📞 <a href="tel:+61460003494">+61 460 003 494</a><br>📍 Glenroy VIC 3046, Australia<br>ABN: 20 662 022 522', 'Response time: 2 business days. This policy does not limit your rights under Australian Consumer Law.']) },
    ]),
  ],
};

const terms = {
  slug: 'terms-conditions', title: 'Terms & Conditions',
  seo: { seo_title: 'Terms & Conditions | SOL Business Consultant', seo_description: 'Terms and conditions for SOL Business Consultant services and website.' },
  sections: [
    header('Legal', 'Terms & Conditions'),
    legal(['SOL Business Consultant Pty Ltd — ABN 20 662 022 522', 'Last updated: June 2026'], D.TermsAndConditions.SECTIONS.map((s) => ({ title: s.title, content: html(s.content) }))),
  ],
};

const AS = D.AccessibilityStatement;
const accessibility = {
  slug: 'accessibility', title: 'Accessibility Statement',
  seo: { seo_title: 'Accessibility Statement | SOL Business Consultant', seo_description: 'Our commitment to WCAG 2.1 Level AA accessibility and how to give feedback.' },
  sections: [
    header('Accessibility', 'Accessibility Statement', 'SOL Business Consultant is committed to ensuring digital accessibility for people with disabilities. We continuously improve the user experience for everyone and apply relevant accessibility standards.'),
    textImage({ heading: 'Our Commitment', content: html(['We aim to conform to the <strong>Web Content Accessibility Guidelines (WCAG) 2.1 Level AA</strong> as published by the World Wide Web Consortium (W3C). These guidelines explain how to make web content more accessible to people with disabilities, and to all users regardless of device.', 'As a business providing services to NDIS participants, families, and carers, we recognise that accessible design is not just a legal requirement — it is fundamental to serving our community.']) }),
    grid({ background: 'chalk', heading: 'Accessibility Features', columns: '2', card_style: 'feature', cards: [
      { icon: 'type', title: 'Adjustable Text Size', text: "Use your browser's zoom (Ctrl/Cmd + or -) to increase text size up to 200%" },
      { icon: 'contrast', title: 'High Contrast Support', text: 'The site supports operating system high-contrast mode settings' },
      { icon: 'volume-2', title: 'Screen Reader Compatible', text: 'Semantic HTML and ARIA labels support screen readers such as NVDA, JAWS, and VoiceOver' },
      { icon: 'eye', title: 'Keyboard Navigation', text: 'All interactive elements are accessible via Tab, Enter, and arrow keys' },
    ] }),
    { acf_fc_layout: 'comparison_table', ...head({ heading: 'WCAG 2.1 Conformance Status' }), first_column_label: 'Criterion', columns: ['Level', 'Status', 'Note'].map((name) => ({ name, tagline: '', badge: '', price: '', link: null, highlight: false })), groups: [{ category: '', rows: AS.WCAG_ITEMS.map((w) => ({ label: w.criterion, values: [w.level, w.status, w.note].join(' | ') })) }], footer_label: '', note: 'Partial = in progress. Last reviewed: June 2026.' },
    legal([], [
      { title: 'Known Limitations', content: ul(['Some older PDF documents may not be fully accessible. We are working to remediate these.', 'Third-party embedded content (e.g. Stripe payment pages) is outside our direct control.', 'Skip-to-content navigation links are being implemented.']) },
      { title: 'Feedback & Contact', content: html(['If you experience any barriers accessing our content, or if you require information in an alternative format, please contact us:', '<strong>Email:</strong> <a href="mailto:info@solbusinessconsultant.com.au">info@solbusinessconsultant.com.au</a><br><strong>Phone:</strong> <a href="tel:+61460003494">+61 460 003 494</a><br><strong>Post:</strong> SOL Business Consultant, Glenroy VIC 3046', 'We aim to respond to accessibility feedback within 5 business days.']) },
      { title: 'Formal Complaints', content: html(['If you are not satisfied with our response, you may contact the <strong>Australian Human Rights Commission</strong>:', '<strong>Website:</strong> <a href="https://humanrights.gov.au" target="_blank" rel="noopener">humanrights.gov.au</a><br><strong>Phone:</strong> 1300 656 419', 'This statement was last reviewed on 3 June 2026 and applies to the website at www.solbusinessconsultant.com.au.']) },
    ]),
  ],
};

const complaints = {
  slug: 'complaints-feedback', title: 'Complaints & Feedback',
  seo: { seo_title: 'Complaints & Feedback | SOL Business Consultant', seo_description: 'A fair and transparent complaints process in line with the NDIS Practice Standards and Code of Conduct.' },
  sections: [
    header('NDIS Practice Standards', 'Complaints & Feedback', 'As an NDIS service provider, we are committed to a fair and transparent complaints process in accordance with the NDIS Practice Standards and the NDIS Code of Conduct.'),
    steps({ style: 'cards', columns: '3', groups: [{ title: '', icon: '', steps: [
      { icon: 'mail', title: 'Step 1', text: 'Submit your complaint below or via email' },
      { icon: 'clock', title: 'Step 2', text: 'We acknowledge within 2 business days' },
      { icon: 'check-circle', title: 'Step 3', text: 'Resolution provided within 10 business days' },
    ] }] }),
    notice('Your Rights — You have the right to have your complaint dealt with fairly, promptly, and confidentially. If you are unsatisfied with our response, you may contact the NDIS Quality and Safeguards Commission on 1800 035 544 (Monday–Friday 9am–5pm local time).', 'warning'),
    contact({
      background: 'chalk', heading: 'Submit a complaint or feedback', intro: 'All complaints are treated confidentially and in accordance with the Privacy Act 1988.',
      form_variant: 'complaint', select_label: 'Complaint type', select_options: D.ComplaintsFeedback.COMPLAINT_TYPES.map((t) => ({ value: t.value, label: t.label })),
      consent_text: '', success_message: 'Complaint received. Our team will respond within 2 business days. Not satisfied with our response? You may escalate to the NDIS Quality and Safeguards Commission: 1800 035 544.',
    }),
  ],
};

/* ---------------- Hand-offs to the existing app ---------------- */
const servicesHub = {
  slug: 'services', title: 'Services',
  seo: { seo_title: 'Business, NDIS & Digital Services | SOL Business Consultant', seo_description: 'NDIS registration, support coordination training, bookkeeping, payroll, websites, marketing and automation services for Australian businesses.' },
  sections: [
    header('Our Services', 'Comprehensive Solutions for Every Stage of Growth', 'Practical business, compliance and digital services, scoped before any paid work begins.', { buttons: [btn('Book Free Consultation', '/book-consultation'), btn('Make a Secure Payment', '/service-payment', 'outline')] }),
    grid({ source: 'services', card_style: 'numbered' }),
    block('service-comparison'),
    block('service-trust'),
    block('contact'),
  ],
};
const _unusedServicePayment = {
  slug: 'service-payment', title: 'Service Payment',
  seo: { seo_title: 'Make a Service Payment | SOL Business Consultant', seo_noindex: false },
  sections: [
    header('Secure Payment', 'Make a service payment', 'Payments for agreed service work are processed securely by Stripe in the SOL client system. Only pay after SOL has confirmed your scope, service type and amount.', { buttons: [{ link: { title: 'Continue to secure payment', url: app('/service-payment'), target: '' }, style: 'primary' }, btn('Book Consultation First', '/book-consultation', 'outline')] }),
  ],
};

export const pages = [
  home, about, contactPage, faqPage, pricing, blog, caseStudies, useCases, marketingPackages, trainingCourses, ndisPackages,
  getStarted, bookConsultation, readinessQuiz, readinessCalculator, dataPrivacy, serviceInclusions, support, afterPayment,
  privacy, refund, terms, accessibility, complaints, servicesHub,
];
