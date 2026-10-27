// WooCommerce products the SOL Setup screen creates (matched by SKU, never duplicated).
// Prices are ex-GST; WooCommerce adds GST at checkout when taxes are enabled.
// Products are "hidden" from the shop catalogue but can be bought through the site's buttons.
import { DATA } from './helpers.mjs';

const N = DATA.ndisRegistrationPricing.NDIS_PACKAGES;
const ndis = Object.values(N).flatMap((p) => p.paymentOptions.map((o) => ({
  sku: `SOL-NDIS-${p.id.toUpperCase()}-${o.id === 'full' ? 'FULL' : 'DEPOSIT'}`,
  name: `${p.name} — NDIS Registration (${o.label})`,
  price: Math.round((o.amount / 1.1) * 100) / 100,
  short: `${p.pathway}. ${p.bestFor}. Package total ${p.priceExGst.toLocaleString('en-AU')} + GST. The approved quality auditor's fee is separate.`,
  features: p.features,
  category: 'NDIS Registration',
  pricing_code: o.pricingCode,
})));

const pkg = (sku, name, price, category, short, features = []) => ({ sku, name, price, short, features, category });

export const products = [
  ...ndis,
  pkg('SOL-SCT-INDIVIDUAL', 'Support Coordination Training — Individual Learner', 1200, 'Training Packages', 'Full 12-module online curriculum, self-paced (6 months access).'),
  pkg('SOL-SCT-TEAM', 'Support Coordination Training — Team / Organisation (up to 10 staff)', 3800, 'Training Packages', 'Full curriculum for up to 10 staff, trainer guides and 2 live Q&A sessions.'),
  pkg('SOL-AUTO-STARTER', 'Automation Starter', 2500, 'Software & Automation', 'One automated process built, tested and handed over.'),
  pkg('SOL-AUTO-PRO', 'Automation Pro', 5500, 'Software & Automation', '2-4 automated processes, Easy Compliance setup and 30-day support.'),
  pkg('SOL-FIN-MONTHLY', 'Monthly Finance Support (per month)', 350, 'Finance Support', 'Bookkeeping, BAS/GST preparation support and reporting. Billed monthly.'),
  pkg('SOL-FIN-OPS', 'Finance Operations Support (per month)', 990, 'Finance Support', 'Everything in Monthly Finance Support plus payroll/STP process support. Billed monthly.'),
  pkg('SOL-FIN-NDIS', 'NDIS Financial Pack (per month)', 1800, 'Finance Support', 'Everything in Finance Operations Support plus NDIS financial reporting. Billed monthly.'),
  pkg('SOL-MKT-STARTER', 'Marketing Starter Package (per month)', 499, 'Marketing Packages', 'Social media on 2 platforms, 8 branded posts, basic SEO audit and monthly report.'),
  pkg('SOL-MKT-GROWTH', 'Marketing Growth Package (per month)', 999, 'Marketing Packages', 'Social media on 4 platforms, SEO blogs, Google Ads management and email campaigns.'),
  pkg('SOL-MKT-ENTERPRISE', 'Marketing Enterprise Package (per month)', 2499, 'Marketing Packages', 'All platforms, advanced SEO, Google & Meta Ads, video and PR outreach.'),
];

// Package name -> SKU, used by the pricing sections.
export const SKU_BY_NAME = {
  'Individual Learner': 'SOL-SCT-INDIVIDUAL',
  'Team / Organisation': 'SOL-SCT-TEAM',
  'Automation Starter': 'SOL-AUTO-STARTER',
  'Automation Pro': 'SOL-AUTO-PRO',
  'Monthly Finance Support': 'SOL-FIN-MONTHLY',
  'Finance Operations Support': 'SOL-FIN-OPS',
  'NDIS Financial Pack': 'SOL-FIN-NDIS',
  Starter: 'SOL-MKT-STARTER',
  Growth: 'SOL-MKT-GROWTH',
  Enterprise: 'SOL-MKT-ENTERPRISE',
};
export const ndisSku = (tierId, full = true) => `SOL-NDIS-${tierId.toUpperCase()}-${full ? 'FULL' : 'DEPOSIT'}`;
