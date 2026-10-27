// Course details (stored on the existing WooCommerce training products) and blog posts.
import { D, L, img, items, DATA, WOO, buyId } from './helpers.mjs';
const { blogPosts } = DATA.blogPosts;

const THUMBS = { blue: 'courses/level-1-foundation-learning.webp', amber: 'courses/level-2-professional-coordination.webp', purple: 'courses/level-3-advanced-mentoring.webp' };
const PRODUCT_IDS = [WOO.level1, WOO.level2, WOO.level3];

export const courses = D.TrainingCourses.LEVELS.map((c, i) => ({
  product_id: PRODUCT_IDS[i], title: c.title,
  fields: {
    level: c.level, badge: c.badge, color: c.color, popular: !!c.popular, price: c.price, duration: c.duration, total_topics: c.totalTopics,
    description: c.description, outcomes: items(c.outcomes),
    curriculum: c.curriculum.map((m) => ({ title: m.title, topics: items(m.topics) })),
    thumbnail: img(THUMBS[c.color]),
    enrol_link: L('Enrol Now', buyId(PRODUCT_IDS[i])),
    buy_link: L('Course details', `woo:product:${PRODUCT_IDS[i]}`),
  },
  seo: { seo_title: `${c.title} | SOL Training Academy`, seo_description: c.description },
}));


const MONTHS = { January: '01', February: '02', March: '03', April: '04', May: '05', June: '06', July: '07', August: '08', September: '09', October: '10', November: '11', December: '12' };
const toDate = (label) => {
  const [m, y] = String(label || '').split(' ');
  return MONTHS[m] && y ? `${y}-${MONTHS[m]}-01 09:00:00` : '';
};
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

export const posts = blogPosts.map((p) => ({
  slug: p.slug, title: p.title, excerpt: p.excerpt, date: toDate(p.date), category: p.category,
  featured_image: img(p.image.replace('/Images/', '')), featured_alt: p.imageAlt,
  content: (p.body || []).map((para) => `<!-- wp:paragraph -->\n<p>${esc(para)}</p>\n<!-- /wp:paragraph -->`).join('\n\n'),
  fields: {
    post_author_label: p.author || 'SOL Business Team', post_date_label: p.date || '', post_read_time: p.readTime || '',
    post_takeaways: items(p.keyTakeaways || p.takeaways || []),
    post_sections: (p.sections || []).map((s) => ({ heading: s.heading, content: s.content || '', bullets: items(s.bullets || []) })),
  },
  seo: { seo_title: `${p.title} | SOL Business Consultant`, seo_description: p.excerpt },
  featured: !!p.featured,
}));
