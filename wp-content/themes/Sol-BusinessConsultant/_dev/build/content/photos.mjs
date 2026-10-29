// Site photography (v2.8). Free-to-use photos from Unsplash (https://unsplash.com/license), stored in
// assets/images/photos/ with descriptive file names. Each page gets its own photo, so nothing repeats.
//
// PHOTOS[itemKey][oldImage] = new image. itemKey is the seed key: page:<path>, sol_block:<slug>,
// post:<slug>, course:<product id>. "*" applies to every item that has no entry of its own.

const P = (f) => `photos/${f}.webp`;

export const PHOTOS = {
  'page:home': {
    'services/business-consulting-workshop.webp': P('home-consultants-team'),
    'services/marketing-strategy-team.webp': P('home-one-stop-partner'),
  },
  'sol_block:about-sol': { '*': P('about-consultant-conversation') },
  'sol_block:compliance': { '*': P('compliance-document-review') },
  'sol_block:ndis-roadmap': { '*': P('ndis-roadmap-support-at-home') },
  'page:marketing-packages': { '*': P('marketing-packages-team') },
  'page:training-2': { '*': P('training-workshop-room') },

  'page:services/ndis-registration': { '*': P('ndis-registration-support-worker') },
  'page:services/ndis-support-coordination-training': { '*': P('support-coordination-training') },
  'page:services/company-business-registration': { '*': P('company-registration-signing') },
  'page:services/bookkeeping-bas-lodgement': { '*': P('bookkeeping-bas-desk') },
  'page:services/payroll-services': { '*': P('payroll-financial-desk') },
  'page:services/website-development': { '*': P('website-development-wireframes') },
  'page:services/business-personal-loan-support': { '*': P('business-loan-meeting') },
  'page:services/accountancy-partnerships': { '*': P('accountancy-partnership-handshake') },
  'page:services/search-engine-optimization': { '*': P('seo-analytics-dashboard') },
  'page:services/google-adwords': { '*': P('google-ads-campaign') },
  'page:services/social-media-marketing': { '*': P('social-media-marketing') },
  'page:services/software-automation': { '*': P('software-automation-spreadsheet') },
  'page:services/admin-back-office-support': { '*': P('admin-back-office-support') },
  'page:services/offshore-outsourcing-staff-solutions': { '*': P('offshore-remote-team-call') },
  'page:ai-machine-learning': { '*': P('ai-human-and-machine') },
  'page:services/accountancy': { '*': P('accountancy-finance-review') },

  'course:3255': { '*': P('course-level-1-foundations') },
  'course:495': { '*': P('course-level-2-coordination') },
  'course:499': { '*': P('course-level-3-mentoring') },

  'post:ndis-provider-registration-australia': { '*': P('blog-ndis-care-support') },
  'post:bookkeeping-mistakes-small-business': { '*': P('blog-bookkeeping-review') },
  'post:what-is-support-coordination': { '*': P('blog-support-coordination-workshop') },
  'post:website-development-ndis-providers': { '*': P('blog-website-development') },
  'post:automation-transform-ndis-business': { '*': P('blog-automation-analytics') },
  'post:client-portal-use-cases-service-businesses': { '*': P('blog-client-portal') },
  'post:bas-lodgement-australian-businesses': { '*': P('blog-bas-lodgement') },
};

// Alt text for the new photos (used when the picture is first added to the Media Library and for page fields).
export const PHOTO_ALT = {
  'home-consultants-team': 'SOL consultants working through a plan with a client team around a laptop',
  'home-one-stop-partner': 'Client and consultant shaking hands after agreeing a business plan',
  'about-consultant-conversation': 'SOL consultant in a friendly planning conversation with a client',
  'compliance-document-review': 'Consultant reviewing compliance documents and notes beside a laptop',
  'ndis-roadmap-support-at-home': 'Support worker helping an NDIS participant at home',
  'ndis-registration-support-worker': 'Support worker assisting an NDIS participant with an exercise session',
  'marketing-packages-team': 'Marketing team planning a digital campaign together',
  'training-workshop-room': 'Adult learners attending a support coordination training workshop',
  'support-coordination-training': 'Trainer presenting support coordination concepts to a group of learners',
  'company-registration-signing': 'Business owner signing company registration documents',
  'bookkeeping-bas-desk': 'Bookkeeper reconciling accounts with a calculator and notebook',
  'payroll-financial-desk': 'Payroll reports and financial charts on an office desk',
  'website-development-wireframes': 'Designer sketching website wireframes beside a laptop',
  'business-loan-meeting': 'Advisor discussing a business loan application with a client',
  'accountancy-partnership-handshake': 'Accounting partners shaking hands to confirm a partnership',
  'seo-analytics-dashboard': 'Website analytics dashboard showing search traffic trends',
  'google-ads-campaign': 'Marketer managing a Google Ads campaign on a laptop and phone',
  'social-media-marketing': 'Social media content being reviewed on a smartphone',
  'software-automation-spreadsheet': 'Automated business data and spreadsheets on a laptop',
  'admin-back-office-support': 'Back-office administrator managing tasks at a desk',
  'offshore-remote-team-call': 'Remote team members meeting on a video call',
  'ai-human-and-machine': 'Human and robotic hands reaching toward each other, representing AI',
  'accountancy-finance-review': 'Accountant reviewing financial statements with a calculator',
  'course-level-1-foundations': 'Learner starting an online support coordination course on a laptop',
  'course-level-2-coordination': 'Support coordinator working through course modules on a laptop',
  'course-level-3-mentoring': 'Mentor guiding a support coordinator through a case on a laptop',
  'blog-ndis-care-support': 'Care worker smiling with an NDIS participant',
  'blog-bookkeeping-review': 'Small business owner reviewing bookkeeping records',
  'blog-support-coordination-workshop': 'Team planning support coordination tasks with sticky notes',
  'blog-website-development': 'Website code and design files open on a laptop',
  'blog-automation-analytics': 'Business automation reports open on a laptop',
  'blog-client-portal': 'Laptop and phone on a desk, used to access a client portal',
  'blog-bas-lodgement': 'Business owner preparing a BAS lodgement with a calculator',
};

/** Swap img:<old> values inside a seed item for its new photo. */
export function applyPhotos(seed) {
  const lists = { blocks: 'sol_block', pages: 'page', services: 'page', posts: 'post' };
  const swap = (v, map) => {
    if (typeof v === 'string') {
      if (!v.startsWith('img:') || v === 'img:sol-logo.jpg') return v;
      const old = v.slice(4);
      const nu = map[old] || map['*'];
      return nu ? `img:${nu}` : v;
    }
    if (Array.isArray(v)) return v.map((x) => swap(x, map));
    if (v && typeof v === 'object') return Object.fromEntries(Object.entries(v).map(([k, x]) => [k, swap(x, map)]));
    return v;
  };
  const altFor = (img) => PHOTO_ALT[(img || '').replace(/^img:photos\//, '').replace(/\.webp$/, '')];
  // Image alt fields next to a swapped image take the new photo's description.
  const fixAlts = (v) => {
    if (Array.isArray(v)) return v.map(fixAlts);
    if (v && typeof v === 'object') {
      const o = Object.fromEntries(Object.entries(v).map(([k, x]) => [k, fixAlts(x)]));
      for (const [imgKey, altKey] of [['image', 'image_alt'], ['hero_image', 'hero_image_alt']]) {
        if (altKey in o && altFor(o[imgKey])) o[altKey] = altFor(o[imgKey]);
      }
      return o;
    }
    return v;
  };
  let used = 0;
  for (const [list, type] of Object.entries(lists)) {
    for (const it of seed[list] || []) {
      const map = PHOTOS[`${type}:${it.path}`];
      if (!map) continue;
      it.fields = fixAlts(swap(it.fields, map));
      if (it.featured_image) {
        it.featured_image = swap(it.featured_image, map);
        if (altFor(it.featured_image)) it.featured_alt = altFor(it.featured_image);
      }
      used++;
    }
  }
  for (const c of seed.courses || []) {
    const map = PHOTOS[`course:${c.product_id}`];
    if (map) { c.fields = swap(c.fields, map); used++; }
  }
  return used;
}
