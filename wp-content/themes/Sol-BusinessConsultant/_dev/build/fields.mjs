// All SCF field groups for the SOL website.
import {
  text, textarea, wysiwyg, image, file, link, url, email, number, bool, select, icon, tab,
  postObject, relationship, repeater, group, flexible, layout, listOf, buttons, sectionHead,
  fieldGroup, onPostType, onOptionsPage, onTemplate,
} from './lib.mjs';

export const TPL_BUILDER = 'page-templates/sol-builder.php';
export const TPL_SERVICE = 'page-templates/sol-service.php';

export const OPTIONS_SLUG = 'sol-site-settings';

const columns = (def = '3') => select('columns', 'Columns', { 1: '1', 2: '2', 3: '3', 4: '4' }, { default_value: def });

/* ------------------------------------------------------------------ */
/* Page builder layouts                                                */
/* ------------------------------------------------------------------ */
export const LAYOUTS = [
  layout('hero', 'Hero', [
    select('style', 'Style', { split: 'Split - white, image right', dark: 'Dark - navy, image right', centered: 'Centered - no image' }, { default_value: 'split' }),
    text('eyebrow', 'Eyebrow'),
    textarea('heading', 'Heading', { rows: 3, instructions: 'One line per row. Wrap words in [square brackets] to colour them amber.' }),
    textarea('text', 'Text'),
    buttons(),
    repeater('badges', 'Trust badges', [icon(), text('label', 'Label')], { layout: 'table', item: 'Badge' }),
    repeater('stats', 'Stats', [text('value', 'Value'), text('label', 'Label')], { layout: 'table', item: 'Stat' }),
    image('image', 'Image'),
    text('image_alt', 'Image alt text'),
    text('card_title', 'Floating card title'),
    textarea('card_text', 'Floating card text', { rows: 2 }),
    textarea('disclaimer', 'Small print', { rows: 2 }),
  ]),
  layout('page_header', 'Page Header', [
    text('eyebrow', 'Eyebrow'),
    textarea('heading', 'Heading', { rows: 2 }),
    textarea('text', 'Text'),
    repeater('info_cards', 'Info cards', [icon(), text('label', 'Label'), text('value', 'Value'), text('url', 'Link (optional)')], { layout: 'table', item: 'Card' }),
    buttons(),
  ]),
  layout('card_grid', 'Card Grid', [
    ...sectionHead(),
    select('source', 'Cards come from', { manual: 'Cards below', services: 'Service pages (children of /services/)' }, { default_value: 'manual' }),
    columns('3'),
    select('card_style', 'Card style', {
      bordered: 'Bordered card', numbered: 'Numbered (01, 02...)', plain: 'Top rule, no box', dark: 'Dark card',
      feature: 'Icon left', tagged: 'Tag + list',
    }, { default_value: 'bordered' }),
    repeater('cards', 'Cards', [
      icon(),
      text('tag', 'Tag / label'),
      text('subtitle', 'Subtitle'),
      text('title', 'Title'),
      textarea('text', 'Text'),
      image('image', 'Image'),
      text('price', 'Price'),
      text('list_title', 'Bullet list heading'),
      listOf('list', 'Bullet list'),
      repeater('meta', 'Detail rows', [text('label', 'Label'), text('value', 'Value')], { layout: 'table', item: 'Row' }),
      link('link', 'Link'),
      link('link_2', 'Second link'),
    ], { item: 'Card' }),
    buttons(),
    textarea('note', 'Note under grid', { rows: 2 }),
  ]),
  layout('steps', 'Steps / Timeline', [
    ...sectionHead(),
    select('style', 'Style', { numbered: 'Numbered list', timeline: 'Timeline', cards: 'Step cards' }, { default_value: 'numbered' }),
    columns('2'),
    repeater('groups', 'Step groups', [
      text('title', 'Group title (optional)'),
      icon(),
      repeater('steps', 'Steps', [
        text('anchor_id', 'Anchor ID'),
        text('label', 'Label (e.g. Week 1, STEP 01)'),
        icon(),
        text('title', 'Title'),
        textarea('text', 'Text'),
      ], { item: 'Step' }),
      buttons(),
    ], { item: 'Group' }),
    image('image', 'Image'),
    buttons(),
    textarea('note', 'Note', { rows: 2 }),
  ]),
  layout('pricing', 'Pricing Packages', [
    ...sectionHead(),
    columns('3'),
    repeater('tabs', 'Package sets (tabs)', [
      text('label', 'Tab label', { instructions: 'Only shown when there is more than one set.' }),
      icon(),
      repeater('packages', 'Packages', [
        icon(),
        text('category', 'Category'),
        text('name', 'Name'),
        text('tag', 'Tag'),
        text('badge', 'Badge (e.g. Most Popular)'),
        bool('highlight', 'Highlight this package'),
        text('price', 'Price'),
        text('period', 'Period (e.g. /mo)'),
        text('price_note', 'Price note (e.g. +GST)'),
        textarea('tagline', 'Tagline', { rows: 2 }),
        listOf('features', 'Features', 'Feature'),
        repeater('extra_lines', 'Payment options / extra lines', [text('label', 'Label'), text('value', 'Value')], { layout: 'table', item: 'Line' }),
        link('cta', 'Button'),
        link('cta_2', 'Second button'),
      ], { item: 'Package' }),
      textarea('note', 'Note under packages', { rows: 2 }),
    ], { item: 'Set' }),
    group('bottom_cta', 'Bottom call to action', [text('heading', 'Heading'), textarea('text', 'Text', { rows: 2 }), link('link', 'Button')]),
  ]),
  layout('comparison_table', 'Comparison Table', [
    ...sectionHead(),
    text('first_column_label', 'First column label', { default_value: 'Features' }),
    repeater('columns', 'Columns', [text('name', 'Name'), text('tagline', 'Tagline'), text('badge', 'Badge'), text('price', 'Price'), link('link', 'Button'), bool('highlight', 'Highlight'), listOf('highlights', 'Headline features (optional)')], { item: 'Column', instructions: 'With headline features filled in, the section shows them as cards and puts the full table behind "Show all features".' }),
    repeater('groups', 'Row groups', [
      text('category', 'Group heading (optional)'),
      repeater('rows', 'Rows', [
        text('label', 'Row label'),
        text('values', 'Values', { instructions: 'One value per column, separated by |. Use yes or no for a tick or dash.' }),
      ], { layout: 'table', item: 'Row' }),
    ], { item: 'Group' }),
    text('footer_label', 'Footer row label', { instructions: 'e.g. Ready to get started? (shows column buttons)' }),
    textarea('note', 'Note', { rows: 2 }),
  ]),
  layout('faq', 'FAQ Accordion', [
    ...sectionHead(),
    repeater('items', 'Questions', [text('question', 'Question'), textarea('answer', 'Answer', { rows: 4 })], { item: 'Question' }),
    bool('schema', 'Output FAQPage schema (SEO)'),
    group('cta', 'Call to action under the list', [text('heading', 'Heading'), textarea('text', 'Text', { rows: 2 }), buttons()]),
  ]),
  layout('text_image', 'Text + Image', [
    ...sectionHead(),
    wysiwyg('content', 'Content'),
    image('image', 'Image'),
    text('image_alt', 'Image alt text'),
    select('image_position', 'Image position', { left: 'Left', right: 'Right' }, { default_value: 'left' }),
    text('image_badge', 'Image badge text'),
    repeater('stats', 'Stats', [text('value', 'Value'), text('label', 'Label')], { layout: 'table', item: 'Stat' }),
    listOf('checklist', 'Checklist'),
    buttons(),
    group('callout', 'Callout box', [icon(), text('title', 'Title'), textarea('text', 'Text', { rows: 3 })], { instructions: 'With no image, the callout shows as a dark box beside the text.' }),
  ]),
  layout('checklist', 'Checklist / Pills', [
    ...sectionHead(),
    select('style', 'Style', { checks: 'Ticks', pills: 'Pills', numbered: 'Numbered' }, { default_value: 'checks' }),
    columns('3'),
    listOf('items', 'Items'),
    buttons(),
  ]),
  layout('cta_banner', 'Call to Action Banner', [
    text('section_id', 'Anchor ID'),
    select('style', 'Style', { dark: 'Dark navy', amber: 'Amber', light: 'Light', image: 'Image with overlay', strip: 'Slim amber strip' }, { default_value: 'dark' }),
    text('eyebrow', 'Eyebrow'),
    textarea('heading', 'Heading', { rows: 2 }),
    textarea('text', 'Text', { rows: 3 }),
    buttons(),
    listOf('list', 'Checklist'),
    image('image', 'Background image'),
    textarea('note', 'Small print', { rows: 2 }),
  ]),
  layout('logo_strip', 'Logo / Platform Strip', [
    ...sectionHead(),
    repeater('logos', 'Logos', [text('name', 'Name', { instructions: 'Use | for a line break' }), image('image', 'Logo image (optional)')], { layout: 'table', item: 'Logo' }),
    bool('animate', 'Scrolling animation', { default_value: 1 }),
    listOf('pills', 'Trust pills'),
  ]),
  layout('contact', 'Contact / Enquiry Form', [
    ...sectionHead(),
    bool('show_details', 'Show phone / email / address', { default_value: 1 }),
    buttons(),
    select('form_type', 'Form', { builtin: 'Built-in SOL form', shortcode: 'Shortcode (Contact Form 7, WPForms, Fluent Forms, Calendly...)', none: 'No form' }, { default_value: 'builtin' }),
    select('form_variant', 'Built-in form variant', { contact: 'Contact / enquiry', consultation: 'Consultation request', complaint: 'Complaint', training: 'Training enquiry' }, { default_value: 'contact' }),
    text('form_shortcode', 'Form shortcode'),
    text('select_label', 'Dropdown label', { default_value: 'Service of interest' }),
    repeater('select_options', 'Dropdown options', [text('value', 'Value'), text('label', 'Label')], { layout: 'table', item: 'Option' }),
    textarea('consent_text', 'Marketing consent checkbox text', { rows: 2 }),
    textarea('privacy_text', 'Privacy note under the form', { rows: 2 }),
    textarea('success_message', 'Success message', { rows: 2 }),
  ]),
  layout('rich_text', 'Rich Text', [
    ...sectionHead(),
    wysiwyg('content', 'Content'),
    select('width', 'Width', { narrow: 'Narrow (reading)', wide: 'Wide' }, { default_value: 'narrow' }),
  ]),
  layout('legal', 'Legal Document', [
    listOf('meta_lines', 'Meta lines (ABN, last updated...)'),
    repeater('sections', 'Sections', [text('title', 'Title'), wysiwyg('content', 'Content', { toolbar: 'basic', media_upload: 0 })], { item: 'Section' }),
  ]),
  layout('service_breakdown', 'Service Inclusions Breakdown', [
    ...sectionHead(),
    repeater('items', 'Services', [
      text('name', 'Service name'), icon(), text('tagline', 'Tagline'),
      listOf('included', "What's included"), listOf('not_included', "What's NOT included"),
      text('timeline', 'Timeline'), text('price', 'Price'),
      listOf('deliverables', 'Deliverables'), textarea('support', 'Support included', { rows: 2 }),
      link('link', 'Button'),
    ], { item: 'Service' }),
  ]),
  layout('quiz', 'Quiz / Assessment', [
    ...sectionHead(),
    select('mode', 'Mode', { score: 'Score (adds option scores)', recommend: 'Recommend (first question picks result by key)' }, { default_value: 'score' }),
    listOf('bullets', 'Intro bullets'),
    text('start_label', 'Start button label', { default_value: 'Start Assessment' }),
    bool('lead_capture', 'Ask for name and email before results'),
    textarea('consent_text', 'Consent text', { rows: 2 }),
    repeater('questions', 'Questions', [
      text('group', 'Group / domain (optional)'),
      text('question', 'Question'),
      repeater('options', 'Answers', [text('label', 'Label'), number('score', 'Score'), text('value', 'Value / result key')], { layout: 'table', item: 'Answer' }),
    ], { item: 'Question' }),
    repeater('results', 'Results', [
      text('key', 'Key'),
      number('min_percent', 'Minimum score %', { instructions: 'Score mode: shown when the score is at or above this.' }),
      text('title', 'Title'),
      text('subtitle', 'Subtitle'),
      textarea('description', 'Description', { rows: 3 }),
      link('link', 'Button'),
    ], { item: 'Result' }),
    textarea('note', 'Note under results', { rows: 2 }),
  ]),
  layout('ndis_finder', 'NDIS Package Finder', [
    ...sectionHead(),
    repeater('entity_types', 'Legal entity types', [text('value', 'Value'), text('label', 'Label')], { layout: 'table', item: 'Entity type' }),
    listOf('states', 'States'),
    repeater('groups', 'Registration groups', [
      text('code', 'Code'), text('name', 'Name'), number('tier', 'Tier'), number('company_tier', 'Tier for companies'),
      bool('entity_dependent', 'Tier depends on entity type'),
    ], { layout: 'table', item: 'Group' }),
    repeater('modules', 'Optional modules', [text('code', 'Code'), text('name', 'Name'), number('tier', 'Tier'), bool('manual_review', 'Needs manual review')], { layout: 'table', item: 'Module' }),
    repeater('packages', 'Packages', [
      number('tier', 'Tier'), text('name', 'Name'), text('pathway', 'Audit pathway'), text('best_for', 'Best for'),
      number('price_ex_gst', 'Price ex GST'), number('gst', 'GST'), number('total', 'Total inc GST'),
      listOf('features', 'Features'),
      repeater('payment_options', 'Payment options', [text('label', 'Label'), number('amount', 'Amount'), text('url', 'Payment link (Stripe / WooCommerce)')], { layout: 'table', item: 'Option' }),
    ], { item: 'Package' }),
    textarea('retainer_text', 'Retainer text', { rows: 2 }),
    textarea('manual_review_text', 'Manual review message', { rows: 2 }),
    textarea('fee_note', 'Auditor fee note', { rows: 3 }),
    link('consult_link', 'Consultation link'),
  ]),
  layout('post_feed', 'Post Feed', [
    ...sectionHead(),
    select('post_type', 'Show', { post: 'Blog posts' }, { default_value: 'post' }),
    number('count', 'How many', { default_value: 9 }),
    bool('featured_first', 'Feature the first item'),
    bool('show_filters', 'Show category filter'),
    buttons(),
  ]),
  layout('course_levels', 'Course Levels', [
    ...sectionHead(),
    relationship('courses', 'Course products', ['product'], { instructions: 'WooCommerce products that have Course Details filled in.' }),
    textarea('disclaimer', 'Disclaimer', { rows: 2 }),
  ]),
  layout('directory', 'Searchable Directory', [
    ...sectionHead(),
    bool('searchable', 'Show search box', { default_value: 1 }),
    repeater('groups', 'Groups', [
      text('title', 'Group title'),
      repeater('items', 'Items', [text('label', 'Label'), text('url', 'Link')], { layout: 'table', item: 'Item' }),
    ], { item: 'Group' }),
    textarea('note', 'Note', { rows: 2 }),
  ]),
  layout('resource_download', 'Resource Request', [
    ...sectionHead(),
    repeater('resources', 'Resources', [text('key', 'Key'), text('title', 'Title'), textarea('text', 'Text', { rows: 2 }), file('file', 'File (optional)')], { item: 'Resource' }),
    textarea('consent_text', 'Consent text', { rows: 2 }),
    text('button_label', 'Button label', { default_value: 'Request Checklist' }),
  ]),
  layout('service_overview', 'Service: Overview & Why SOL', [
    text('eyebrow', 'Eyebrow override'),
  ]),
  layout('service_details', 'Service: Scope, Process, Timeline & FAQs', [
    text('heading', 'Heading override'),
  ]),
  layout('service_enquiry', 'Service: Enquiry Form', [
    text('section_id', 'Anchor ID', { default_value: 'intake-form' }),
    select('form_variant', 'Form variant', { contact: 'Enquiry', training: 'Training enquiry' }, { default_value: 'contact' }),
  ]),
  layout('reusable_block', 'Reusable Section', [
    postObject('block', 'Reusable section', ['sol_block']),
  ]),
  layout('notice', 'Notice Bar', [
    select('style', 'Style', { info: 'Info (light)', warning: 'Warning (amber)', dark: 'Dark' }, { default_value: 'info' }),
    textarea('text', 'Text', { rows: 2 }),
  ]),
];

const pageBuilder = fieldGroup({
  key: 'group_sol_page_builder',
  title: 'Page Sections',
  fields: [flexible('page_sections', 'Page Sections', LAYOUTS, { instructions: 'Build the page from sections. Drag to reorder.' })],
  location: [...onTemplate(TPL_BUILDER, TPL_SERVICE), ...onPostType('sol_block')],
  menu_order: 10,
  hide_on_screen: [],
});

/* ------------------------------------------------------------------ */
/* Post type groups                                                    */
/* ------------------------------------------------------------------ */
const service = fieldGroup({
  key: 'group_sol_service',
  title: 'Service Details',
  menu_order: 0,
  location: onTemplate(TPL_SERVICE),
  hide_on_screen: [],
  fields: [
    tab('Card'),
    icon('card_icon', 'Card icon'),
    textarea('card_text', 'Card description', { rows: 2 }),
    text('service_type', 'Service key', { instructions: 'Used for payment links and enquiries, e.g. ndis_registration' }),
    text('card_url', 'Card link override', { instructions: 'Leave empty to link to this service page.' }),
    bool('hide_in_grid', 'Hide from service grids'),
    tab('Hero'),
    text('eyebrow', 'Eyebrow'),
    textarea('hero_heading', 'Hero heading', { rows: 2, instructions: 'Leave empty to use the title. [brackets] = amber.' }),
    textarea('hero_intro', 'Hero intro'),
    listOf('hero_highlights', 'Highlights'),
    repeater('hero_stats', 'Stats', [text('value', 'Value'), text('label', 'Label')], { layout: 'table', item: 'Stat' }),
    image('hero_image', 'Hero image'),
    buttons('hero_buttons'),
    textarea('hero_disclaimer', 'Small print', { rows: 2 }),
    tab('Overview'),
    text('overview_heading', 'Overview heading'),
    listOf('features', 'Features', 'Feature'),
    text('why_heading', '"Why SOL" heading'),
    textarea('why_intro', '"Why SOL" intro', { rows: 2 }),
    listOf('why', '"Why SOL" points', 'Point'),
    tab('Scope & FAQs'),
    bool('show_details', 'Show scope, process, timeline and FAQs', { default_value: 1 }),
    listOf('details_who', 'Who this is for'),
    listOf('details_get', 'What you receive'),
    listOf('details_process', 'How delivery works'),
    listOf('details_timeline', 'Timeline'),
    repeater('details_faqs', 'FAQs', [text('question', 'Question'), textarea('answer', 'Answer')], { item: 'FAQ' }),
    tab('Enquiry'),
    bool('show_enquiry', 'Show enquiry form', { default_value: 1 }),
    text('enquiry_heading', 'Enquiry heading'),
    textarea('enquiry_text', 'Enquiry text', { rows: 2 }),
  ],
});

const course = fieldGroup({
  key: 'group_sol_course',
  title: 'Course Details (training site layout)',
  location: onPostType('product'),
  position: 'normal',
  menu_order: 30,
  fields: [
    text('level', 'Level (e.g. Level 1)', { wrapper: { width: 25 } }),
    text('badge', 'Badge (e.g. Foundation)', { wrapper: { width: 25 } }),
    select('color', 'Colour', { blue: 'Blue', amber: 'Amber', purple: 'Purple' }, { default_value: 'blue', wrapper: { width: 25 } }),
    bool('popular', 'Most popular', { wrapper: { width: 25 } }),
    number('price', 'Price (AUD)', { wrapper: { width: 33 } }),
    text('duration', 'Duration', { wrapper: { width: 33 } }),
    number('total_topics', 'Total topics', { wrapper: { width: 33 } }),
    textarea('description', 'Description'),
    listOf('outcomes', 'What you will learn', 'Outcome'),
    repeater('curriculum', 'Curriculum', [text('title', 'Module title'), listOf('topics', 'Topics', 'Topic')], { item: 'Module' }),
    image('thumbnail', 'Thumbnail'),
    link('enrol_link', 'Enrol button'),
    link('buy_link', 'Buy button'),
  ],
});

const blogPost = fieldGroup({
  key: 'group_sol_post',
  title: 'Article Details',
  location: onPostType('post'),
  fields: [
    text('post_author_label', 'Author label', { wrapper: { width: 33 } }),
    text('post_date_label', 'Date label (optional)', { wrapper: { width: 33 } }),
    text('post_read_time', 'Read time', { wrapper: { width: 33 } }),
    listOf('post_takeaways', 'Key takeaways'),
    repeater('post_sections', 'Article sections', [text('heading', 'Heading'), textarea('content', 'Content', { rows: 4 }), listOf('bullets', 'Bullets')], { item: 'Section' }),
  ],
});

/* ------------------------------------------------------------------ */
/* Site settings (options page)                                        */
/* ------------------------------------------------------------------ */
const options = fieldGroup({
  key: 'group_sol_options',
  title: 'SOL Site Settings',
  location: onOptionsPage(OPTIONS_SLUG),
  fields: [
    tab('Brand'),
    image('logo', 'Logo'),
    text('company_name', 'Company name'),
    textarea('tagline', 'Footer tagline', { rows: 2 }),
    text('topbar_text', 'Top bar text'),
    link('topbar_link', 'Top bar link'),
    tab('Contact'),
    text('phone', 'Phone (display)'),
    text('phone_link', 'Phone (dial format, e.g. +61460003494)'),
    email('email', 'Email'),
    url('whatsapp_url', 'WhatsApp link'),
    text('address', 'Address'),
    text('abn', 'ABN'),
    text('website_label', 'Website (display)'),
    text('business_hours', 'Business hours'),
    tab('Header'),
    repeater('nav_items', 'Main menu', [
      text('label', 'Label'), text('url', 'Link'),
      select('type', 'Type', { link: 'Link', mega: 'Services mega menu' }, { default_value: 'link' }),
    ], { layout: 'table', item: 'Menu item' }),
    repeater('mega_menu', 'Services mega menu', [
      text('label', 'Column title'), text('badge', 'Badge'),
      repeater('items', 'Links', [text('label', 'Label'), text('url', 'Link'), icon(), text('desc', 'Description')], { layout: 'table', item: 'Link' }),
    ], { item: 'Column' }),
    group('mega_promo', 'Mega menu promo box', [text('title', 'Title'), textarea('text', 'Text', { rows: 2 }), link('link', 'Link')]),
    buttons('header_buttons'),
    repeater('portal_links', 'Portal links', [text('label', 'Label'), text('url', 'Link')], { layout: 'table', item: 'Link' }),
    tab('Footer'),
    text('footer_cta_heading', 'CTA heading', { instructions: 'Use | for a line break' }),
    text('footer_cta_text', 'CTA text'),
    link('footer_cta_link', 'CTA button'),
    bool('newsletter_enabled', 'Show newsletter sign-up', { default_value: 1 }),
    text('newsletter_label', 'Newsletter label'),
    text('newsletter_note', 'Newsletter note'),
    repeater('footer_columns', 'Link columns', [
      text('title', 'Title'),
      repeater('links', 'Links', [text('label', 'Label'), text('url', 'Link')], { layout: 'table', item: 'Link' }),
    ], { item: 'Column' }),
    textarea('footer_copyright', 'Copyright line', { rows: 2, instructions: '{year} is replaced with the current year.' }),
    tab('App & Payments'),
    url('app_url', 'Training site address', { instructions: 'After checkout customers continue here (student dashboard, LMS, client portal).' }),
    text('payment_page_url', 'Service payment page', { instructions: 'Where "Make a Service Payment" buttons go, e.g. /sol-service-payment-form/' }),
    url('lead_webhook_url', 'Forward form submissions to (webhook)', { instructions: 'Optional. Every SOL form submission is also POSTed here as JSON, e.g. the training site CRM or an n8n / Zapier webhook.' }),
    tab('Widgets & Forms'),
    bool('cookie_enabled', 'Cookie banner', { default_value: 1 }),
    textarea('cookie_text', 'Cookie banner text', { rows: 2 }),
    text('cookie_link', 'Cookie banner link'),
    bool('floating_whatsapp', 'Floating WhatsApp button', { default_value: 1 }),
    bool('back_to_top', 'Back-to-top button', { default_value: 1 }),
    email('notification_email', 'Send form submissions to'),
    textarea('default_success', 'Default form success message', { rows: 2 }),
    tab('Tracking'),
    textarea('head_code', 'Code in <head> (analytics, tag manager)', { rows: 4, instructions: 'Only administrators can edit this.' }),
  ],
});

/* ------------------------------------------------------------------ */
export const GROUPS = [pageBuilder, service, course, blogPost, options];

// Map of where each top-level field lives, used by the importer.
export function topLevelKeys() {
  const map = {};
  for (const g of GROUPS) {
    for (const f of g.fields) if (f.type !== 'tab') map[f.name] = f.key;
  }
  return map;
}
