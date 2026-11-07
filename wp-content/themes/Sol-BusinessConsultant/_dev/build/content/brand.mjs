// The approved business description and the wording rules that keep the whole site in line with it.
// build.mjs runs brandText() over every page, service, reusable section, post, product and site
// setting after the SEO copy layer, then fails if anything still contradicts the description.

export const COMPANY = {
  legalName: 'SOL Business Consultant Pty Ltd',
  // Approved description (do not shorten or reword without sign-off).
  description: 'SOL Business Consultant Pty Ltd is an independent business consultancy based in Glenroy, Victoria. We help small businesses and disability service providers with company and ABN/GST registration, NDIS registration preparation and compliance documentation, bookkeeping and BAS (Xero and QuickBooks), payroll, websites and digital marketing. We also deliver online NDIS Support Coordinator training. We are not affiliated with the NDIS Commission or any government agency.',
  who: 'an independent business consultancy based in Glenroy, Victoria',
  short: 'Independent business consultancy in Glenroy, Victoria, helping small businesses and disability service providers.',
  disclaimer: 'We are not affiliated with the NDIS Commission or any government agency.',
};

// [pattern, replacement] in order. Keep them specific so ordinary text is never touched.
const RULES = [
  // Bookkeeping software: Xero and QuickBooks only.
  [/QuickBooks, Xero,? (?:and )?MYOB/g, 'Xero and QuickBooks'],
  [/Xero, MYOB,? (and|or|&) QuickBooks/g, 'Xero $1 QuickBooks'],
  [/Xero, MYOB, QuickBooks/g, 'Xero, QuickBooks'],
  [/Xero ?\/ ?MYOB ?\/ ?QuickBooks/g, (m) => (m.includes(' / ') ? 'Xero / QuickBooks' : 'Xero/QuickBooks')],
  [/Xero(,| and| or) MYOB\b/g, 'Xero$1 QuickBooks'],
  [/MYOB(,| and| or) Xero\b/g, 'QuickBooks$1 Xero'],
  // Government disclaimer: the full statement.
  [/not affiliated with the NDIS Quality and Safeguards Commission\./g, 'not affiliated with the NDIS Commission or any government agency.'],
  [/Not affiliated with (?:the )?NDIS Commission\./g, 'Not affiliated with the NDIS Commission or any government agency.'],
  [/do not imply endorsement or partnership\./g, 'do not imply endorsement, partnership or any government affiliation.'],
  [/(is|as) an independent consultancy/g, '$1 an independent business consultancy'],
];

export function brandText(t) {
  if (typeof t !== 'string' || !t) return t;
  let out = t;
  for (const [re, to] of RULES) out = out.replace(re, to);
  return out;
}

/** Text that still contradicts the description (checked after the rules run). */
export function brandProblems(json) {
  const problems = [];
  const s = typeof json === 'string' ? json : JSON.stringify(json);
  const myob = s.match(/.{0,50}MYOB.{0,30}/g);
  if (myob) problems.push(...myob.map((m) => `MYOB: ...${m}...`));
  const old = s.match(/.{0,40}affiliated with the NDIS (?:Quality and Safeguards )?Commission\.(?!.{0,5}or any)/g);
  if (old) problems.push(...old.map((m) => `Disclaimer without "or any government agency": ...${m}`));
  return problems;
}
