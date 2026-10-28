// Content that changed on the live training site (training.solbusinessconsultant.com.au)
// after the source zip was shared. Checked against the live bundles on 28 Oct 2026.
// Applied on top of source-data.json, so re-running `npm run extract` on the older zip keeps these.
// When extracting from an up-to-date training-site source, these become no-ops.

export function applyLiveUpdates(D) {
  const data = D._data;

  // "Google Adwords" was renamed to "Google Ads Management".
  for (const c of data.serviceCatalog.serviceCards) {
    if (c.slug === 'google-adwords') c.title = 'Google Ads Management';
  }
  if (data.serviceCatalog.detailServices['google-adwords']) data.serviceCatalog.detailServices['google-adwords'].title = 'Google Ads Management';
  for (const l of D.Footer.solServices) {
    if (l.href === '/services/google-adwords') l.label = 'Google Ads';
  }

  // Team training package features.
  const team = D.PricingSection.TRAINING_PACKAGES.find((p) => p.name === 'Team / Organisation');
  if (team) {
    team.features = ['Full 12-module curriculum for up to 10 staff', '1,500+ quiz questions with answers', 'Video scripts & PowerPoint slide decks', 'Trainer guides & facilitator notes', 'Branded participant workbooks', '2 x live Q&A sessions with Sol trainer', 'NDIS Code of Conduct alignment pack', 'Certificates for all completions'];
  }

  // Support Coordination Training hero stat.
  const stat = D.SupportCoordinationTraining.OUTCOMES.find((o) => o.label === 'NDIS Standards Aligned');
  if (stat) { stat.stat = 'NDIS'; stat.label = 'Standards-Focused Training'; }

  // Pathway comparison: four headline features per pathway (full table sits behind "Show all features").
  const highlights = {
    ndis: ['Registration groups and business details', 'Verification or certification audit pathway', 'Branded policies, procedures, and registers', 'Guidance for PRODA and NDIS Commission steps'],
    training: ['12 learning modules', 'NDIS plan implementation and participant-centred practice', 'Scenario assessments and 1,500+ quiz questions', 'Completion certificate after passing assessments'],
    automation: ['Branded policy and document generation', 'Client intake and onboarding workflows', 'Compliance dashboards and document status', 'Easy Compliance setup and staff training'],
  };
  for (const s of D.ServiceComparisonTable.SERVICES) s.highlights = highlights[s.id] || [];

  // Helpful resources: readiness quiz and calculator added after the NDIS checklist.
  const res = D.IntakeResourcesSection.resources;
  if (!res.some((r) => r.href === '/readiness-quiz')) {
    res.splice(1, 0,
      { title: 'NDIS readiness quiz', desc: 'Answer 8 questions for a personalised NDIS readiness recommendation.', href: '/readiness-quiz', icon: 'shield-check' },
      { title: 'NDIS readiness calculator', desc: 'Answer 15 questions across five domains for a readiness score.', href: '/ndis-readiness-calculator', icon: 'calculator' });
  }

  // Accessibility: goals and work in progress, not claimed conformance.
  D.AccessibilityStatement.WCAG_ITEMS = [
    { criterion: '1.1.1 Non-text Content', level: 'A', status: 'Goal', note: 'We aim to provide text alternatives for meaningful images.' },
    { criterion: '1.3.1 Info and Relationships', level: 'A', status: 'Goal', note: 'We aim to use semantic structure to communicate content relationships.' },
    { criterion: '1.4.3 Contrast (Minimum)', level: 'AA', status: 'Goal', note: 'We aim to provide sufficient contrast; this has not been independently assessed.' },
    { criterion: '1.4.4 Resize Text', level: 'AA', status: 'Goal', note: 'We aim to keep content usable when text is resized; behavior may vary.' },
    { criterion: '2.1.1 Keyboard', level: 'A', status: 'Goal', note: 'We aim to support keyboard navigation and continue to improve it.' },
    { criterion: '2.4.1 Bypass Blocks', level: 'A', status: 'Partial', note: 'Skip navigation links are in progress.' },
    { criterion: '2.4.2 Page Titled', level: 'A', status: 'Goal', note: 'We aim to provide descriptive page titles throughout the site.' },
    { criterion: '3.1.1 Language of Page', level: 'A', status: 'Goal', note: 'The document language is set to en-AU; content accessibility is not independently assessed.' },
    { criterion: '4.1.2 Name, Role, Value', level: 'A', status: 'Goal', note: 'We aim to provide clear names, roles, and states for interactive elements.' },
  ];
  D.AccessibilityStatement.FEATURES = [
    { icon: 'type', title: 'Text Resizing', desc: "Use your browser's zoom controls to enlarge text. Layout behavior may vary." },
    { icon: 'contrast', title: 'Contrast Preferences', desc: 'We aim to support contrast preferences; behavior can vary by browser and page.' },
    { icon: 'volume-2', title: 'Screen Reader Support', desc: 'We use semantic HTML and ARIA where appropriate and continue to improve assistive technology support.' },
    { icon: 'eye', title: 'Keyboard Navigation', desc: 'We aim to support keyboard navigation and are continuing to improve it.' },
  ];

  return D;
}

// Text-only changes, used directly by the content files.
export const LIVE_TEXT = {
  footerTagline: 'Business consulting, NDIS registration, and compliance support.',
  featuredOffersIntro: 'Explore current offers and choose a starting pathway for your business.',
  serviceStandardsIntro: 'Explore the practical service standards that guide how we work with Australian providers and businesses.',
  resourceRequestIntro: 'Choose a resource and enter your details to request it.',
  scTrainingIntro: 'Practical NDIS Support Coordinator training with 12 modules, 1,500+ quiz questions, and course content covering NDIS Practice Standards. For individuals, teams, and RTOs.',
  scTrainingSeo: 'NDIS support coordination training for Australian learners and organisations, covering NDIS plans, documentation, participant-centred practice, provider networks, and compliance awareness.',
  homeSeoTitle: 'SOL Business Consultant | NDIS Registration & Compliance Support Australia',
  homeSeoDescription: 'Australian-owned NDIS registration, Easy Compliance, support coordination training, bookkeeping, and business consulting support from Glenroy VIC.',
  readinessHeading: 'Check your NDIS readiness',
  readinessNdisService: 'Choose a quick quiz or a detailed readiness score.',
  readinessNdisPackages: 'Use either existing readiness tool before comparing support packages.',
  accessibilityCommitment: 'We use the <strong>Web Content Accessibility Guidelines (WCAG) 2.1 Level AA</strong> as a reference for ongoing accessibility improvements. This website has not been independently assessed or certified for WCAG conformance.',
};
