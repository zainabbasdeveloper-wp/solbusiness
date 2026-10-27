// Reusable sections (sol_block). Each one mirrors a React landing component
// that appears on more than one page.
import { D, DATA, L, R, btn, img, ic, items, ref, yesNo, buy, formatAud } from './helpers.mjs';
import { SKU_BY_NAME } from './products.mjs';

const { NDIS_PACKAGES } = DATA.ndisRegistrationPricing;


// Packages with a fixed price get a WooCommerce "Buy" button; the rest go to enquiry.
const pkgCard = (p, { buyable = true } = {}) => {
  const sku = buyable && /\d/.test(p.price) ? SKU_BY_NAME[p.name] : null;
  return {
    icon: ic(p.icon), category: p.category || '', name: p.name, tag: p.tag || '', badge: p.badge || (p.popular ? 'Most Popular' : ''),
    highlight: !!(p.highlight || p.popular), price: p.price, period: p.period || '', price_note: p.gst || (/\d/.test(p.price) && !p.period ? '+GST' : ''),
    tagline: p.tagline || p.desc || '', features: items(p.features), extra_lines: [],
    cta: sku ? L(p.period ? 'Buy Now' : (p.cta || 'Buy Now'), buy(sku)) : (p.cta ? L(p.cta, p.href || '/book-consultation') : null),
    cta_2: sku && p.href ? L('More details', p.href) : null,
  };
};

export const ndisPackageCards = (cta = 'Find My Package', href = '/get-started') => Object.values(NDIS_PACKAGES).map((p) => ({
  icon: '', category: '', name: p.name, tag: p.pathway, badge: p.tier === 2 ? 'Most Popular' : '', highlight: p.tier === 2,
  price: formatAud(p.priceExGst).replace('.00', ''), period: '', price_note: '+GST', tagline: p.bestFor,
  features: items(p.features), extra_lines: [], cta: R(cta, href),
}));

const P = D.PricingSection;
const C = D.ConsultingPackagesSection;
const Cmp = D.ServiceComparisonTable;

export const blocks = [
  {
    slug: 'pricing-tabs',
    title: 'Pricing - all services (tabs)',
    sections: [{
      acf_fc_layout: 'pricing', section_id: 'pricing', eyebrow: 'Transparent Pricing', background: 'chalk', align: 'center',
      heading: 'Simple, Upfront Pricing',
      intro: 'No hidden fees. Compare packages across all Sol services and choose the level of support that fits your business.',
      columns: '3',
      tabs: P.TABS.map((t) => ({
        label: t.label, icon: ic(t.icon),
        packages: t.id === 'registration' ? ndisPackageCards()
          : (t.id === 'training' ? P.TRAINING_PACKAGES : t.id === 'software' ? P.SOFTWARE_PACKAGES : P.ACCOUNTANCY_PACKAGES).map(pkgCard),
        note: P.PACKAGE_MAP[t.id].note,
      })),
      bottom_cta: { heading: 'Not sure which package is right for you?', text: "Book a free 30-minute consultation - we'll assess your needs and recommend the best fit.", link: L('Book Free Consultation', '/book-consultation') },
    }],
  },
  {
    slug: 'consulting-packages',
    title: 'Business consulting packages',
    sections: [{
      acf_fc_layout: 'pricing', section_id: 'consulting-packages', eyebrow: 'Business Consulting Packages', background: 'white', align: 'center',
      heading: 'The Right Package for Every Stage of Growth',
      intro: 'Transparent pricing. No surprises. Every package is backed by our team of industry specialists.',
      columns: '3',
      tabs: [{ label: 'Packages', icon: '', packages: C.PACKAGES.map((p) => ({ ...pkgCard(p), price_note: '' })), note: '' }],
      bottom_cta: { heading: 'Not sure which package is right for you?', text: "Book a free 30-minute consultation and we'll map the perfect plan.", link: L('Book Free Consultation', '/book-consultation') },
    }],
  },
  {
    slug: 'marketing-packages',
    title: 'Marketing packages (summary)',
    sections: [{
      acf_fc_layout: 'pricing', section_id: 'marketing', eyebrow: 'Digital Marketing', background: 'chalk', align: 'center',
      heading: 'Marketing Packages Built for Measurable Growth',
      intro: 'From social media to paid ads, we manage the digital channels that support your growth goals.',
      columns: '3',
      tabs: [{ label: 'Packages', icon: '', packages: D.MarketingPackagesSection.PACKAGES.map((p) => ({ ...pkgCard({ ...p, cta: 'Get Started', href: '/marketing-packages' }), price_note: '' })), note: '' }],
      bottom_cta: { heading: '', text: '', link: L('View all marketing services & custom packages', '/marketing-packages') },
    }],
  },
  {
    slug: 'about-sol',
    title: 'About SOL',
    sections: [
      {
        acf_fc_layout: 'text_image', section_id: 'about', eyebrow: 'About Sol', background: 'white', align: 'left',
        heading: 'The Foundation\nof Your Growth', intro: '',
        content: '<p>SOL Business Consultant supports Australian businesses with NDIS registration guidance, compliance preparation, bookkeeping, digital systems, websites, and growth services.</p><p>With Easy Compliance in our ecosystem, we help providers organise documents, prepare operational evidence, and build workflows that are easier to maintain. We focus on practical systems, clear advice, and responsible claims.</p>',
        image: img('about/trainer-student-discussion.webp'), image_alt: 'Consultant guiding a client through business planning on a laptop', image_position: 'left', image_badge: '',
        stats: D.AboutSection.focusAreas.map((f) => ({ value: f.value, label: f.label })), checklist: [], buttons: [], callout: { icon: '', title: '', text: '' },
      },
      {
        acf_fc_layout: 'card_grid', section_id: '', eyebrow: '', background: 'white', align: 'left', heading: '', intro: '',
        source: 'manual', columns: '4', card_style: 'plain',
        cards: D.AboutSection.values.map((v) => ({ icon: '', tag: v.num, title: v.title, text: v.desc })), buttons: [], note: '',
      },
    ],
  },
  {
    slug: 'compliance',
    title: 'Easy Compliance',
    sections: [
      {
        acf_fc_layout: 'text_image', section_id: 'compliance', eyebrow: 'Powered by Easy Compliance', background: 'chalk', align: 'left',
        heading: 'NDIS Compliance Made Effortless',
        intro: 'Easy Compliance is a service, not just software. Our team works with you every step - from scattered documents toward a clear audit-readiness plan.',
        content: '<h3>Audits Are Stressful. They Don\'t Have to Be.</h3>',
        image: img('services/compliance-document-review.webp'), image_alt: 'Professionals reviewing compliance documents in a modern office', image_position: 'right', image_badge: 'Document Review Workflow Active',
        stats: [], checklist: [], buttons: [{ link: { title: 'Open Platform - sol.easycompliance.org', url: 'https://sol.easycompliance.org', target: '_blank' }, style: 'outline' }],
        callout: { icon: '', title: '', text: '' },
      },
      {
        acf_fc_layout: 'steps', section_id: '', eyebrow: '', background: 'chalk', align: 'center', heading: 'The Compliance Lifecycle', intro: 'From first call to a clear audit-readiness plan.',
        style: 'cards', columns: '3',
        groups: [{ title: '', icon: '', steps: D.ComplianceSection.steps.map((s, i) => ({ anchor_id: s.id, label: `STEP ${String(i + 1).padStart(2, '0')}`, icon: ic(s.icon), title: s.title, text: s.desc })) }],
        image: '', buttons: [btn('Get Audit-Ready Now', '/#contact')], note: '',
      },
    ],
  },
  {
    slug: 'ndis-roadmap',
    title: 'NDIS registration roadmap + packages',
    sections: [
      {
        acf_fc_layout: 'steps', section_id: 'ndis', eyebrow: 'NDIS Registration Specialists', background: 'white', align: 'left',
        heading: 'Your Path to NDIS\nRegistration, Simplified',
        intro: 'We help sole traders, small providers, and allied health professionals navigate the NDIS Commission audit with confidence.',
        style: 'timeline', columns: '2',
        groups: [{ title: '', icon: '', steps: D.NDISSection.roadmap.map((s) => ({ anchor_id: s.id, label: s.week, icon: ic(s.icon), title: s.title, text: s.desc })) }],
        image: img('services/ndis-registration-documents.webp'), buttons: [btn('Find My NDIS Package', '/get-started'), btn('NDIS Registration Service', '/services/ndis-registration', 'outline')], note: '',
      },
      {
        acf_fc_layout: 'pricing', section_id: '', eyebrow: '', background: 'white', align: 'center', heading: 'Simple, Transparent Pricing',
        intro: 'Choose the level of support that matches your needs.', columns: '3',
        tabs: [{ label: 'NDIS', icon: '', packages: ndisPackageCards(), note: "*Prices exclude GST. The approved quality auditor's fee is separate. Your package is determined by the highest registration group selected. Registration outcomes are not guaranteed." }],
        bottom_cta: { heading: '', text: '', link: null },
      },
    ],
  },
  {
    slug: 'service-trust',
    title: 'Service standards',
    sections: [
      {
        acf_fc_layout: 'card_grid', section_id: '', eyebrow: 'Service Standards', background: 'chalk', align: 'left',
        heading: 'Professional support from enquiry to delivery',
        intro: 'SOL services are built around clear communication, scoped deliverables, secure payments, and ongoing client portal visibility.',
        source: 'manual', columns: '2', card_style: 'feature',
        cards: D.ServiceTrustSection.standards.map((s) => ({ icon: ic(s.icon), title: s.title, text: s.desc })),
        buttons: [btn('View Service Inclusions', '/service-inclusions', 'outline'), btn('After Payment Guide', '/after-payment', 'outline')], note: '',
      },
      { acf_fc_layout: 'checklist', section_id: '', eyebrow: '', background: 'chalk', align: 'left', heading: '', intro: '', style: 'pills', columns: '3', items: items(D.ServiceTrustSection.proofPoints), buttons: [] },
      {
        acf_fc_layout: 'cta_banner', section_id: '', style: 'dark', eyebrow: '', heading: 'Need help choosing the right service?',
        text: 'Book a consultation and SOL will confirm the correct package or next step before you pay.', buttons: [btn('Book Consultation', '/book-consultation')], list: [], image: '', note: '',
      },
    ],
  },
  {
    slug: 'service-journey',
    title: 'Client journey',
    sections: [{
      acf_fc_layout: 'steps', section_id: '', eyebrow: 'Client Journey', background: 'white', align: 'center',
      heading: 'What happens after a client chooses a service',
      intro: 'A simple professional path from enquiry to payment, portal access, document review, delivery, and support.',
      style: 'cards', columns: '3',
      groups: [{ title: '', icon: '', steps: D.ServiceJourneySection.steps.map((s) => ({ anchor_id: '', label: '', icon: ic(s.icon), title: s.title, text: s.desc })) }],
      image: '', buttons: [btn('Explore Business Services', '/services/'), btn('Browse Training Courses', '/training-courses', 'outline'), btn('Book a Consultation', '/book-consultation', 'outline')], note: '',
    }],
  },
  {
    slug: 'general-faq',
    title: 'General FAQ',
    sections: [{
      acf_fc_layout: 'faq', section_id: 'faq', eyebrow: 'FAQ', background: 'white', align: 'center', heading: 'Frequently Asked Questions', intro: '',
      items: D.FAQSection.faqs.map((f) => ({ question: f.q, answer: f.a })), schema: true, cta: { heading: '', text: '', buttons: [] },
    }],
  },
  {
    slug: 'training-faq',
    title: 'Support coordination training FAQ',
    sections: [{
      acf_fc_layout: 'faq', section_id: 'training-faq', eyebrow: 'FAQ', background: 'chalk', align: 'center',
      heading: 'Common Questions About the Training',
      intro: 'Everything you need to know before enrolling — from curriculum structure to delivery formats and certification.',
      items: D.SupportCoordinationFAQ.FAQS.map((f) => ({ question: f.q, answer: f.a })), schema: false,
      cta: { heading: 'Still have questions?', text: 'Our team is happy to walk you through the right package for your organisation.', buttons: [btn('Email Us', 'mailto:info@solbusinessconsultant.com.au', 'outline'), btn('+61 460 003 494', 'tel:+61460003494', 'outline')] },
    }],
  },
  {
    slug: 'contact',
    title: 'Contact form',
    sections: [{
      acf_fc_layout: 'contact', section_id: 'contact', eyebrow: 'Get In Touch', background: 'ink', align: 'left',
      heading: 'Ready to Build\nYour Foundation?',
      intro: "Book a free, no-obligation consultation. We'll discuss your situation and create a clear path forward.",
      show_details: true,
      buttons: [{ link: { title: 'WhatsApp Us', url: D.ContactSection.WHATSAPP_LINK, target: '_blank' }, style: 'primary' }, btn('Start Online Enquiry', '/get-started', 'light')],
      form_type: 'builtin', form_variant: 'contact', form_shortcode: '',
      select_label: 'Service of interest',
      select_options: D.BookConsultation.SERVICE_OPTIONS.map(([value, label]) => ({ value, label })),
      consent_text: 'Optional: I agree to receive SOL Business Consultant marketing updates and service follow-up emails. This is separate from submitting this enquiry, and I can unsubscribe at any time.',
      privacy_text: "By submitting this form you consent to SOL Business Consultant collecting and handling your details in line with our Privacy Policy. We'll respond within 24 hours - no pressure, no obligations.",
      success_message: 'We have received your enquiry. A member of the SOL team will contact you within one business day.',
    }],
  },
  {
    slug: 'service-comparison',
    title: 'Compare core pathways',
    sections: [{
      acf_fc_layout: 'comparison_table', section_id: 'compare', eyebrow: 'Compare Core Pathways', background: 'white', align: 'center',
      heading: 'Choose the Right Path for Your Business',
      intro: 'Not sure where to start? Compare three common pathways side by side, then choose the service that fits your current goal.',
      first_column_label: 'Features',
      columns: Cmp.SERVICES.map((s) => ({ name: s.name, tagline: s.tagline, badge: s.popular ? 'Most Popular' : '', price: '', link: L('Get Started', s.href), highlight: !!s.popular })),
      groups: Cmp.FEATURES.map((g) => ({
        category: g.category,
        rows: g.items.map((it) => ({ label: it.label, values: Cmp.SERVICES.map((s) => yesNo(s.features[it.label])).join(' | ') })),
      })),
      footer_label: 'Ready to get started?', note: '',
    }],
  },
];

export { pkgCard };
