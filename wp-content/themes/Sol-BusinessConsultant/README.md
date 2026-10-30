# Sol-Business Consultant theme

The WordPress marketing site in the **SOL Training Academy** design (training.solbusinessconsultant.com.au), built with **Secure Custom Fields** instead of Elementor.

**Flow:** visitor lands on WordPress → reads services/training → clicks Buy → **WooCommerce checkout** → thank-you page sends them to the **training site** (LMS, student dashboard, client portal). The training site creates the enrolment from the WooCommerce order webhook, as before.

## Go live

1. Install and activate **Secure Custom Fields** (Plugins → Add New).
2. Activate this theme (Appearance → Themes). It is a standalone theme: no parent theme needed.
3. **Appearance → SOL Setup**: check the page table, then **Run setup**.
4. WooCommerce → Settings: turn on taxes (10% GST). Package prices are ex-GST.
5. When happy, deactivate Elementor, Elementor Pro, Header Footer Elementor and their add-ons. Pages not converted (e.g. policy pages you keep) still need Elementor until they are rebuilt.

**Undo:** SOL Setup → *Restore Elementor templates* switches every converted page back. Elementor data is never deleted.

## What setup does

| | |
|---|---|
| Existing pages (home, about, contact, services/…, training-2, policies) | Switched to the *SOL Page Builder* / *SOL Service Page* template and filled with the training-site sections. Same URLs. |
| Missing pages (FAQ, pricing, NDIS packages, package finder, quizzes, …) | Created. |
| Service pages | Children of `/services/` using *SOL Service Page* (AI & Machine Learning stays at `/ai-machine-learning/`). Includes the WordPress-only services: Admin & Back-Office Support, Offshore Outsourcing & Staff Solutions, AI & Machine Learning (text from the old Elementor pages). `support-coordination-training` → `ndis-support-coordination-training`, `marketing` → `social-media-marketing`. |
| WooCommerce | 16 hidden package products created by SKU (NDIS START/CERTIFIED/COMPLETE deposit + full, training team/individual, automation, finance, marketing). Course details (outcomes, curriculum) added to training products #3255, #495, #499. |
| Header / footer | SOL header (mega menu) and footer on **every** page, including cart, checkout and account. Elementor theme-builder headers are switched off. |
| Site settings | SOL Site → logo, top bar, menu, mega menu, footer columns, contact details, training-site address, service payment page. |
| SEO | Yoast title/description filled only where empty. |

Running setup again only fills in what's missing; tick *Overwrite* to reset everything.

## Theme updates after a git pull

No page-by-page work is needed. After `git pull`, the **first time an administrator opens the dashboard** the theme compares its content with what is on the site and updates it:

- Pages, service pages, reusable sections, blog posts, packages, course details and site settings that changed in the theme are **updated**.
- New pages or sections in the theme are **created**.
- Anything edited by hand in WordPress since it was last applied is **kept** and listed in a notice. To overwrite it, tick it under *Appearance → SOL Setup → Theme updates* and press **Apply theme updates**.
- Yoast titles/descriptions are only replaced where they still hold the theme's earlier text.
- The previous version of every updated item is saved. **Undo last update** on the same screen puts it back (and pauses automatic updates until you apply again). Pages also get a WordPress revision.

Same thing from the command line: `wp sol update` (add `--force-all` to overwrite hand edits too). To switch the automatic run off: `add_filter( 'sol_auto_apply_theme_updates', '__return_false' );`

How it knows: `npm run build` gives every item a fingerprint in `seed/site.json`; WordPress stores the fingerprint it applied plus a hash of the saved values, so it can tell "theme changed" from "someone edited it".

## Courses, cart and checkout

- **Course products** (WooCommerce products with *Course Details* filled in) use the training-site course page: level badge, curriculum, outcomes and a sticky Enrol card. **Enrol Now** adds the course and opens checkout.
- **Shop and category pages** list products as course cards. Other products (packages) use the same card and page frame.
- **Cart and checkout** get a branded header with steps (Cart → Checkout → Start learning), the non-accredited training notice when a course is in the cart, and styling for both the block and classic WooCommerce checkout.
- **Header cart icon** shows the number of items.
- Products without a featured image use the *Course Details → Thumbnail* image, also in cart, checkout and emails.
- Templates: `woocommerce/single-product.php`, `woocommerce/archive-product.php`; styles: `assets/css/sol-shop.css`.

## Editing

- **Pages:** edit the page → *Page Sections* (26 section types: hero, cards, pricing, comparison table, FAQ, steps, contact form, quiz, NDIS package finder, course levels…). Drag to reorder.
- **Buy buttons:** in any link field use `woo:sku:SOL-NDIS-START-FULL` (or `woo:id:3255`) to add a product and open checkout; `woo:product:3255` opens the product page.
- **Shared sections** (pricing tabs, contact, FAQ…): SOL Site → Reusable Sections.
- **Headings:** one line per row; `[words]` turn amber. **Icons:** any [Lucide](https://lucide.dev/icons) name.
- **Forms:** submissions under SOL Site → Enquiries, emailed to the address in SOL Site settings.
- **Main menu:** Appearance → Menus → *SOL Main Menu* (location "Main menu (SOL header)"). It is created from SOL Site → Header the first time an admin opens the dashboard on v2.6.0. Three levels make the Services mega menu: *Services* → category (left-hand tab) → links. Two levels make a normal dropdown. Each menu item has **Icon**, **Short description** and **Badge** fields. A category's own link is used for its "View all" link. The dark "Get started" box comes from SOL Site → Header → Mega menu promo box. SOL Setup → *Rebuild main menu* resets the menu to the theme default. If no menu is assigned to the location, the header falls back to SOL Site → Header.
- **Cart:** a floating tab on the left edge (low on the left on phones) shows the item count, slides out to show the total on hover, and opens a cart drawer from the left with the items, subtotal, View cart and Checkout. It is hidden on the cart and checkout pages. To switch it off: `add_filter( 'sol_show_floating_cart', '__return_false' );`
- **Photos:** `assets/images/photos/` (free Unsplash photos, https://unsplash.com/license). Which page uses which photo, and each photo's alt text, is set in `_dev/build/content/photos.mjs`. New file names are picked up by the next theme update; to swap one photo for your own, change it in the Page Sections image field (that page then keeps your choice).
- **Section look:** two light sections in a row get a divider line, grey sections have a dot texture, dark sections become a band with a curved top, and sections fade up as they scroll into view (not for visitors who turn off motion). This all comes from the section backgrounds (white / chalk / ink), so pick backgrounds that alternate.

Every field, repeater and data type: [`_dev/FIELD-REFERENCE.md`](_dev/FIELD-REFERENCE.md).

## Files

```
functions.php            loads inc/
header.php, footer.php   SOL header/footer for every page
page.php, index.php      default pages (cart, checkout, account, unconverted pages), archives, search
404.php                  page not found
page-templates/          SOL Page Builder, SOL Service Page
template-parts/sections/ one PHP file per section type
single.php, home.php     blog post + blog index
inc/                     helpers, setup, renderer, forms, WooCommerce, SOL Setup screen
acf-json/                SCF field groups (generated)
seed/site.json           content applied by SOL Setup (generated)
assets/                  CSS, JS, images
_dev/                    build tools (not served; .htaccess denies access)
```

## Rebuilding from the training site

When the training site's content changes:

```bash
cd wp-content/themes/Sol-BusinessConsultant/_dev/build
npm install
SOL_REACT_SRC=/path/to/sol-training-academy-main/src npm run extract
npm run build
```

`npm run build` checks every value against the field definitions (and every `woo:sku:` against the product list) before writing `acf-json`, `seed/site.json` and `FIELD-REFERENCE.md`. Commit and push; the sites pick the changes up on their next dashboard visit (see *Theme updates* above).

## SEO wording

The site copy is rewritten for SEO and to be unique from the training site (search engines treat copied text as duplicate content). The rewrites live in `_dev/build/content/seo-copy.json` (original sentence → new sentence) and `npm run build` swaps them in, so a rebuild from the training site keeps them.

- **Rewritten:** headings, intros, descriptions, FAQs, benefits, calls to action, image alt text, blog posts, product descriptions, course summaries, and every page's Yoast title (60 characters or fewer) and description (160 or fewer).
- **Kept word for word on purpose:** official NDIS registration group names, prices and figures, menu labels, product names, course module and lesson names (they match the LMS), quiz questions, comparison table cells, and the commitments on the legal, privacy, service inclusions, support, accessibility and complaints pages.
- **New or changed text:** after each build, anything still in its original wording is listed in `_dev/COPY-TODO.json`. To reword it, add `"original text": "new text"` to `seo-copy.json` and build again.

## Social sharing and search tags

Each page has its own 1200×630 share card in `assets/images/share/`: the page's photo, the SOL logo and the page title. Links shared in Slack, Facebook, LinkedIn, WhatsApp, Teams or X show this card (code: `inc/seo.php`).

- **With Yoast SEO (live site):** Yoast keeps titles, descriptions, canonical links, sitemaps and schema. The theme replaces only Yoast's share image with the card, unless you choose an image yourself in a page's Yoast **Social** tab. It also sets the locale to Australian English and adds the address, phone, email, hours, ABN and service area to Yoast's organisation schema. Service pages also get a `Service` schema item.
- **Without an SEO plugin:** the theme prints the meta description, Open Graph and Twitter tags, the business and website schema, and `BlogPosting` schema on posts. It also tells Google not to index search results or 404 pages.
- **New page cards:** run `python _dev/build/share-cards.py` after adding a page to the seed. Pages without a card use their featured image (JPG or PNG), or `default.jpg`.
- **After an update:** Slack and Facebook cache previews. Use the [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/) or LinkedIn Post Inspector to refresh a link. Slack refreshes on its own within about a day.

## AI chat assistant

The **SOL Assistant** chat sits in the bottom-right corner on every page. It's the same assistant as the training site: Groq's `openai/gpt-oss-120b` model with the SOL concierge instructions, rewritten to link to this site's own pages.

- **Switch it on:** add a Groq API key in **SOL Site → AI Chat** (it tests the key before saving) (free from [console.groq.com](https://console.groq.com/keys); the training site's key works too). The chat stays hidden until a key is set. For extra safety, put the key in `wp-config.php` instead: `define( 'SOL_GROQ_API_KEY', 'gsk_…' );`
- **Settings there:** the assistant name, greeting, quick-question buttons (one per line), model, and **Extra knowledge**, a box for facts or rules to add, such as current offers or answers to common questions.
- **What it knows:** SOL's services, every service page (it reads them from the site, so new service pages are included automatically), the key pages (booking, contact, pricing, quiz, payment form, policies), phone, email and hours, and how course purchase and portal access work.
- **Safety:** the key stays on the server (`/wp-json/sol/v1/chat` calls Groq), only this site can use the endpoint, and each visitor is limited to 12 messages per 5 minutes and 80 per day, with 2,000 per day for the whole site (change with the `sol_chat_limits` filter). It never asks for card, bank or password details.
- **Files:** `inc/chat.php`, `assets/js/sol-chat.js`, `assets/css/sol-chat.css`. The conversation is kept for the browser tab's session, so it survives page changes.

## Accessibility toolbar

An accessibility tab sits on the left edge of every page, just under the cart tab (just above it on phones). It opens a panel with:

- **Text size** from 80% to 150% (− and + buttons).
- **Grayscale**, **High Contrast**, **Negative Contrast** (black background, white text, yellow links) and **Light Background** (white background, black text, blue links). Only one of the three contrast modes is on at a time; grayscale can be combined with any of them.
- **Links Underline** and **Read Aloud** (uses the browser's built-in voice; click or select any text to hear it, and keyboard focus is read out too).
- **Reset all settings.**

Choices are saved in the visitor's browser and reapplied before the page appears, so there's no flicker. Turn it off in **SOL Site → Widgets & Forms → Accessibility toolbar**. Files: `inc/a11y.php`, `assets/js/sol-a11y.js`, `assets/css/sol-a11y.css`.

The **Secure Payment / Contact Us** side buttons come from the Sticky Buttons plugin; `assets/css/sol-sticky.css` restyles them to match the site (the plugin still controls links, icons and labels).
