// SOL Site Settings (options page).
import { D, L, btn, rbtn, ic, img, route, APP } from './helpers.mjs';
import { LIVE_TEXT } from './live-updates.mjs';

const N = D.Navbar;
const F = D.Footer;
const links = (arr) => arr.map((l) => ({ label: l.label, url: route(l.href) }));
// Icon, one-line description and "view all" link for each mega menu category.
const MEGA_META = {
  'NDIS Services': { icon: 'shield-check', desc: 'Registration, audits & readiness', url: route('/ndis-packages') },
  Training: { icon: 'graduation-cap', desc: 'Support coordination courses', url: route('/training-courses') },
  'Business Services': { icon: 'briefcase-business', desc: 'Setup, finance & operations', url: '/services/' },
  Marketing: { icon: 'megaphone', desc: 'SEO, ads & growth campaigns', url: route('/marketing-packages') },
  'Outsourcing & AI': { icon: 'bot', desc: 'Remote teams & AI data services', url: '/services/' },
};

export const options = {
  logo: img('sol-logo.jpg'),
  company_name: 'SOL Business Consultant',
  tagline: LIVE_TEXT.footerTagline,
  topbar_text: 'Australian-owned NDIS & business consulting — audit-ready systems, end-to-end support.',
  topbar_link: L('Free Readiness Quiz', '/readiness-quiz'),

  phone: '+61 460 003 494',
  phone_link: '+61460003494',
  email: 'info@solbusinessconsultant.com.au',
  whatsapp_url: D.ContactSection.WHATSAPP_LINK,
  address: 'Glenroy VIC 3046, Australia',
  abn: '20 662 022 522',
  website_label: 'www.solbusinessconsultant.com.au',
  business_hours: 'Monday-Friday: 9:00 AM - 5:00 PM AEST',

  nav_items: [
    ...N.NAV_LINKS.map((l) => ({ label: l.label, url: l.href, type: 'link' })),
    { label: 'Services', url: '/#services', type: 'mega' },
    ...N.NAV_LINKS_AFTER_SERVICES.map((l) => ({ label: l.label, url: l.href, type: 'link' })),
  ],
  mega_menu: N.SERVICE_CATEGORIES.map((c) => ({
    label: c.label, badge: (c.badge || '').replace(/^⭐\s*/, ''), ...(MEGA_META[c.label] || {}),
    items: c.items.map((i) => ({ label: i.label, url: route(i.href), icon: ic(i.icon), desc: i.desc })),
  })).concat([
    // Services that exist only on the WordPress site.
    { label: 'Outsourcing & AI', badge: '', ...MEGA_META['Outsourcing & AI'], items: [
      { label: 'Admin & Back-Office Support', url: '/services/admin-back-office-support/', icon: 'clipboard-list', desc: 'CRM, scheduling & data entry' },
      { label: 'Offshore Outsourcing & Staff', url: '/services/offshore-outsourcing-staff-solutions/', icon: 'users', desc: 'Managed remote teams' },
      { label: 'AI & Machine Learning', url: '/ai-machine-learning/', icon: 'brain-circuit', desc: 'Data labelling & annotation' },
    ] },
  ]),
  mega_promo: { title: 'Not sure where to start?', text: 'Take our readiness quiz and get a personalized plan.', points: ['Takes about 5 minutes', 'Personalised NDIS & business plan', 'No obligation, no sign-up'].join('\n'), link: L('Free Quiz', '/readiness-quiz') },
  header_buttons: [btn('Find My Plan', '/get-started', 'outline'), btn('Book Consultation', '/book-consultation')],
  portal_links: [{ label: 'Portal', url: route('/client-portal') }, { label: 'Student', url: route('/student-dashboard') }],

  footer_cta_heading: "Let's Build Your|Business Foundation",
  footer_cta_text: 'Start with a free consultation today.',
  footer_cta_link: L('Get Started', '/book-consultation'),
  newsletter_enabled: true,
  newsletter_label: 'Monthly compliance tips',
  newsletter_note: 'By joining, you agree to receive marketing updates. You can unsubscribe at any time.',
  footer_columns: [
    { title: 'Sol Services', links: [...links(F.solServices), { label: 'Admin & Back-Office Support', url: '/services/admin-back-office-support/' }, { label: 'Offshore Outsourcing & Staff', url: '/services/offshore-outsourcing-staff-solutions/' }, { label: 'AI & Machine Learning', url: '/ai-machine-learning/' }] },
    { title: 'Easy Compliance', links: links(F.complianceLinks) },
    { title: 'NDIS Registration', links: links(F.ndisLinks) },
    { title: 'Company', links: links(F.companyLinks) },
    { title: 'Portals', links: links(F.portalLinks) },
  ],
  footer_copyright: 'Copyright {year} SOL Business Consultant - NDIS registration & compliance support across Australia. Not affiliated with NDIS Commission.',

  app_url: APP,
  payment_page_url: '/sol-service-payment-form/',
  cookie_enabled: true,
  cookie_text: 'We use essential cookies to keep the website secure and improve your browsing experience.',
  cookie_link: '/privacy-policy',
  floating_whatsapp: true,
  back_to_top: true,
  a11y_enabled: true,
  notification_email: 'info@solbusinessconsultant.com.au',
  default_success: 'Thanks. The SOL team will contact you within one business day.',
  head_code: '',

  chat_enabled: true,
  chat_model: 'openai/gpt-oss-120b',
  chat_name: 'SOL Assistant',
  chat_greeting: "Hi, I'm online and ready to help. Are you looking for a business service, NDIS support, a training course, payment help or access to your Student Portal?",
  chat_questions: [
    'Help me choose a business service',
    'How do I get registered as an NDIS provider?',
    'How do I purchase a training course?',
    'How can I pay for an agreed service?',
  ].join('\n'),
  chat_instructions: '',
};
