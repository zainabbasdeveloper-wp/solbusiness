// SOL Site Settings (options page).
import { D, L, btn, rbtn, ic, img, route, APP } from './helpers.mjs';
import { LIVE_TEXT } from './live-updates.mjs';

const N = D.Navbar;
const F = D.Footer;
const links = (arr) => arr.map((l) => ({ label: l.label, url: route(l.href) }));

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
    label: c.label, badge: (c.badge || '').replace(/^⭐\s*/, ''),
    items: c.items.map((i) => ({ label: i.label, url: route(i.href), icon: ic(i.icon), desc: i.desc })),
  })),
  mega_promo: { title: 'Not sure where to start?', text: 'Take our readiness quiz and get a personalized plan.', link: L('Free Quiz', '/readiness-quiz') },
  header_buttons: [btn('Find My Plan', '/get-started', 'outline'), btn('Book Consultation', '/book-consultation')],
  portal_links: [{ label: 'Portal', url: route('/client-portal') }, { label: 'Student', url: route('/student-dashboard') }],

  footer_cta_heading: "Let's Build Your|Business Foundation",
  footer_cta_text: 'Start with a free consultation today.',
  footer_cta_link: L('Get Started', '/book-consultation'),
  newsletter_enabled: true,
  newsletter_label: 'Monthly compliance tips',
  newsletter_note: 'By joining, you agree to receive marketing updates. You can unsubscribe at any time.',
  footer_columns: [
    { title: 'Sol Services', links: links(F.solServices) },
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
  notification_email: 'info@solbusinessconsultant.com.au',
  default_success: 'Thanks. The SOL team will contact you within one business day.',
  head_code: '',
};
