# Sol-Business Consultant child theme

The WordPress marketing site in the **SOL Training Academy** design (training.solbusinessconsultant.com.au), built with **Secure Custom Fields** instead of Elementor.

**Flow:** visitor lands on WordPress → reads services/training → clicks Buy → **WooCommerce checkout** → thank-you page sends them to the **training site** (LMS, student dashboard, client portal). The training site creates the enrolment from the WooCommerce order webhook, as before.

## Go live

1. Install and activate **Secure Custom Fields** (Plugins → Add New).
2. Make sure this child theme is active (parent: Hello Elementor).
3. **Appearance → SOL Setup**: check the page table, then **Run setup**.
4. WooCommerce → Settings: turn on taxes (10% GST). Package prices are ex-GST.
5. When happy, deactivate Elementor, Elementor Pro, Header Footer Elementor and their add-ons. Pages not converted (e.g. policy pages you keep) still need Elementor until they are rebuilt.

**Undo:** SOL Setup → *Restore Elementor templates* switches every converted page back. Elementor data is never deleted.

## What setup does

| | |
|---|---|
| Existing pages (home, about, contact, services/…, training-2, policies) | Switched to the *SOL Page Builder* / *SOL Service Page* template and filled with the training-site sections. Same URLs. |
| Missing pages (FAQ, pricing, NDIS packages, package finder, quizzes, …) | Created. |
| Service pages | Children of `/services/` using *SOL Service Page*. `support-coordination-training` → `ndis-support-coordination-training`, `marketing` → `social-media-marketing`. |
| WooCommerce | 16 hidden package products created by SKU (NDIS START/CERTIFIED/COMPLETE deposit + full, training team/individual, automation, finance, marketing). Course details (outcomes, curriculum) added to training products #3255, #495, #499. |
| Header / footer | SOL header (mega menu) and footer on **every** page, including cart, checkout and account. Elementor theme-builder headers are switched off. |
| Site settings | SOL Site → logo, top bar, menu, mega menu, footer columns, contact details, training-site address, service payment page. |
| SEO | Yoast title/description filled only where empty. |

Running setup again only fills in what's missing; tick *Overwrite* to reset.

## Editing

- **Pages:** edit the page → *Page Sections* (26 section types: hero, cards, pricing, comparison table, FAQ, steps, contact form, quiz, NDIS package finder, course levels…). Drag to reorder.
- **Buy buttons:** in any link field use `woo:sku:SOL-NDIS-START-FULL` (or `woo:id:3255`) to add a product and open checkout; `woo:product:3255` opens the product page.
- **Shared sections** (pricing tabs, contact, FAQ…): SOL Site → Reusable Sections.
- **Headings:** one line per row; `[words]` turn amber. **Icons:** any [Lucide](https://lucide.dev/icons) name.
- **Forms:** submissions under SOL Site → Enquiries, emailed to the address in SOL Site settings.

Every field, repeater and data type: [`_dev/FIELD-REFERENCE.md`](_dev/FIELD-REFERENCE.md).

## Files

```
functions.php            loads inc/ (Child Theme Configurator block kept at the top)
header.php, footer.php   SOL header/footer for every page
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

`npm run build` checks every value against the field definitions (and every `woo:sku:` against the product list) before writing `acf-json`, `seed/site.json` and `FIELD-REFERENCE.md`. Then run SOL Setup with *Overwrite* ticked.
