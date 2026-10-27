// Pulls the literal data arrays/objects out of the React components so the
// WordPress seed uses exactly the same content. Output: source-data.json
// Usage: SOL_REACT_SRC=/path/to/training-app/src npm run extract
const fs = require('fs');
const path = require('path');
const parser = require('@babel/parser');

// Path to the React app's src folder (the training site). Override with SOL_REACT_SRC.
const SRC = path.resolve(process.env.SOL_REACT_SRC || path.join(__dirname, '../../../../../../sol-training-academy-main/src'));
const FILES = [
  'components/landing/HeroSection.jsx', 'components/landing/LogoCarousel.jsx', 'components/landing/ServicesSection.jsx',
  'components/landing/ServiceComparisonTable.jsx', 'components/landing/ProcessPortalSection.jsx',
  'components/landing/IntakeResourcesSection.jsx', 'components/landing/LeadGrowthToolsSection.jsx',
  'components/landing/CaseStudiesPreviewSection.jsx', 'components/landing/FeaturedOffersSection.jsx',
  'components/landing/ServiceTrustSection.jsx', 'components/landing/ServiceJourneySection.jsx',
  'components/landing/ConsultingPackagesSection.jsx', 'components/landing/PricingSection.jsx',
  'components/landing/ComplianceSection.jsx', 'components/landing/NDISSection.jsx', 'components/landing/AboutSection.jsx',
  'components/landing/MarketingPackagesSection.jsx', 'components/landing/TestimonialSection.jsx',
  'components/landing/FAQSection.jsx', 'components/landing/ContactSection.jsx', 'components/landing/Footer.jsx',
  'components/landing/Navbar.jsx', 'components/landing/ServicePagePolishSection.jsx',
  'components/services/SupportCoordinationFAQ.jsx', 'components/training/CourseHero.jsx', 'components/training/LMSFeatures.jsx',
  'pages/AccessibilityStatement.jsx', 'pages/AfterPaymentJourney.jsx', 'pages/BookConsultation.jsx', 'pages/CaseStudies.jsx',
  'pages/ComplaintsFeedback.jsx', 'pages/DataPrivacyAndSecurity.jsx', 'pages/FAQ.jsx', 'pages/MarketingPackages.jsx',
  'pages/NDISReadinessCalculator.jsx', 'pages/ReadinessQuiz.jsx', 'pages/ServiceInclusions.jsx',
  'pages/SupportAndAvailability.jsx', 'pages/TermsAndConditions.jsx', 'pages/TrainingCourses.jsx', 'pages/UseCases.jsx',
  'pages/services/Accountancy.jsx', 'pages/services/MarketingServices.jsx', 'pages/services/NDISRegistration.jsx',
  'pages/services/SoftwareAutomation.jsx', 'pages/services/SupportCoordinationTraining.jsx',
  'pages/services/WebsiteDevelopment.jsx', 'pages/ndis/NDISPackagesPage.jsx', 'pages/GetStarted.jsx',
];

// Some source files were saved with double-encoded UTF-8 ("â€”" instead of "—"). Undo that.
const CP1252 = { 0x20ac: 0x80, 0x201a: 0x82, 0x0192: 0x83, 0x201e: 0x84, 0x2026: 0x85, 0x2020: 0x86, 0x2021: 0x87, 0x02c6: 0x88, 0x2030: 0x89, 0x0160: 0x8a, 0x2039: 0x8b, 0x0152: 0x8c, 0x017d: 0x8e, 0x2018: 0x91, 0x2019: 0x92, 0x201c: 0x93, 0x201d: 0x94, 0x2022: 0x95, 0x2013: 0x96, 0x2014: 0x97, 0x02dc: 0x98, 0x2122: 0x99, 0x0161: 0x9a, 0x203a: 0x9b, 0x0153: 0x9c, 0x017e: 0x9e, 0x0178: 0x9f };
function fixMojibake(s) {
  if (!/[âÃð]/.test(s)) return s;
  return s.replace(/[Â-ô][\u0080-ÿŒ-™]{1,3}/g, (m) => {
    const bytes = [];
    for (const ch of m) {
      const c = ch.codePointAt(0);
      if (c < 0x100) bytes.push(c); else if (CP1252[c]) bytes.push(CP1252[c]); else return m;
    }
    const out = Buffer.from(bytes).toString('utf8');
    return out.includes('�') ? m : out;
  });
}

const kebab = (name) => name.replace(/([a-z0-9])([A-Z])/g, '$1-$2').replace(/([A-Z])([A-Z][a-z])/g, '$1-$2').replace(/([a-zA-Z])(\d)/g, '$1-$2').toLowerCase();

let CODE = '';
function lit(node) {
  if (!node) return null;
  switch (node.type) {
    case 'StringLiteral': return fixMojibake(node.value);
    case 'NumericLiteral': case 'BooleanLiteral': return node.value;
    case 'NullLiteral': return null;
    case 'TemplateLiteral': return fixMojibake(node.quasis.map((q) => q.value.cooked).join(''));
    case 'ArrayExpression': return node.elements.map(lit);
    case 'ObjectExpression': {
      const o = {};
      for (const p of node.properties) {
        if (p.type !== 'ObjectProperty') continue;
        const k = p.key.type === 'Identifier' ? p.key.name : lit(p.key);
        o[k] = lit(p.value);
      }
      return o;
    }
    case 'Identifier': return { $ref: node.name, icon: kebab(node.name) };
    default: return { $expr: CODE.slice(node.start, node.end).slice(0, 80) };
  }
}

// Top-level consts plus consts declared directly inside the default component.
function collect(body, into) {
  for (const st of body) {
    let decl = st;
    if ((st.type === 'ExportNamedDeclaration' || st.type === 'ExportDefaultDeclaration') && st.declaration) decl = st.declaration;
    if (decl.type === 'FunctionDeclaration' && decl.body) collect(decl.body.body, into);
    if (decl.type !== 'VariableDeclaration') continue;
    for (const d of decl.declarations) {
      if (d.id.type !== 'Identifier' || !d.init) continue;
      if (['ArrayExpression', 'ObjectExpression', 'StringLiteral', 'TemplateLiteral'].includes(d.init.type)) into[d.id.name] = lit(d.init);
    }
  }
}

(async () => {
const out = {};
for (const rel of FILES) {
  CODE = fs.readFileSync(path.join(SRC, rel), 'utf8');
  const ast = parser.parse(CODE, { sourceType: 'module', plugins: ['jsx'] });
  const consts = {};
  collect(ast.program.body, consts);
  out[path.basename(rel, '.jsx')] = consts;
}
// Plain data modules are imported as-is.
const { pathToFileURL } = require('url');
out._data = {};
for (const rel of ['data/serviceCatalog.js', 'data/ndisRegistrationPricing.js', 'data/blogPosts.js']) {
  const mod = await import(pathToFileURL(path.join(SRC, rel)).href);
  out._data[path.basename(rel, '.js')] = JSON.parse(JSON.stringify(mod));
}
fs.writeFileSync(path.join(__dirname, 'source-data.json'), JSON.stringify(out, null, 1));
console.log(`Extracted ${Object.keys(out).length - 1} components + 3 data files from ${SRC} -> source-data.json`);
})();
