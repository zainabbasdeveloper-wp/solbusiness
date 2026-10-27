// Small DSL for defining SCF (Secure Custom Fields) field groups in JS.
// Keys are derived from each field's path, so they stay stable between builds.
import { createHash } from 'node:crypto';

const hash = (s) => createHash('sha1').update(s).digest('hex').slice(0, 13);

const base = (type, name, label, o = {}) => ({ type, name, label, ...o });

export const text = (name, label, o) => base('text', name, label, o);
export const textarea = (name, label, o) => base('textarea', name, label, { rows: 3, new_lines: '', ...o });
export const wysiwyg = (name, label, o) => base('wysiwyg', name, label, { tabs: 'all', toolbar: 'full', media_upload: 1, delay: 0, ...o });
export const image = (name, label, o) => base('image', name, label, { return_format: 'id', library: 'all', preview_size: 'medium', ...o });
export const file = (name, label, o) => base('file', name, label, { return_format: 'array', library: 'all', ...o });
export const link = (name, label, o) => base('link', name, label, { return_format: 'array', ...o });
export const url = (name, label, o) => base('url', name, label, o);
export const email = (name, label, o) => base('email', name, label, o);
export const number = (name, label, o) => base('number', name, label, o);
export const bool = (name, label, o) => base('true_false', name, label, { ui: 1, default_value: 0, ...o });
export const select = (name, label, choices, o) => base('select', name, label, {
  choices: Array.isArray(choices) ? Object.fromEntries(choices.map((c) => [c, c])) : choices,
  return_format: 'value', allow_null: 0, multiple: 0, ui: 0, ...o,
});
export const icon = (name = 'icon', label = 'Icon') => text(name, label, {
  instructions: 'Lucide icon name, e.g. shield, graduation-cap, file-check. Browse: https://lucide.dev/icons',
});
export const tab = (label) => ({ type: 'tab', name: '', label, placement: 'top', endpoint: 0 });
export const postObject = (name, label, postTypes, o) => base('post_object', name, label, { post_type: postTypes, return_format: 'id', multiple: 0, allow_null: 1, ui: 1, ...o });
export const relationship = (name, label, postTypes, o) => base('relationship', name, label, { post_type: postTypes, return_format: 'id', filters: ['search'], ...o });
export const repeater = (name, label, sub_fields, o = {}) => base('repeater', name, label, {
  layout: 'block', button_label: `Add ${o.item || 'Row'}`, min: 0, max: 0, pagination: 0, rows_per_page: 20, ...o, sub_fields,
});
export const group = (name, label, sub_fields, o) => base('group', name, label, { layout: 'block', ...o, sub_fields });
export const flexible = (name, label, layouts, o) => base('flexible_content', name, label, { button_label: 'Add Section', ...o, layouts });
export const layout = (name, label, sub_fields) => ({ name, label, sub_fields });

// Shared building blocks
export const listOf = (name, label, itemLabel = 'Item') => repeater(name, label, [text('item', itemLabel)], { layout: 'table', item: itemLabel });
export const buttons = (name = 'buttons') => repeater(name, 'Buttons', [
  link('link', 'Link'),
  select('style', 'Style', { primary: 'Primary (amber)', outline: 'Outline', dark: 'Dark', light: 'Light / on dark' }, { default_value: 'primary' }),
], { layout: 'table', item: 'Button' });
export const sectionHead = () => [
  text('section_id', 'Anchor ID', { instructions: 'Optional. Lets you link to this section, e.g. /#pricing', wrapper: { width: 25 } }),
  text('eyebrow', 'Eyebrow', { wrapper: { width: 25 } }),
  select('background', 'Background', { white: 'White', chalk: 'Light grey', ink: 'Dark navy', amber: 'Amber' }, { default_value: 'white', wrapper: { width: 25 } }),
  select('align', 'Heading alignment', { left: 'Left', center: 'Center' }, { default_value: 'left', wrapper: { width: 25 } }),
  textarea('heading', 'Heading', { rows: 2, instructions: 'New lines become line breaks.' }),
  textarea('intro', 'Intro text', { rows: 3 }),
];

// Finalise a field tree: assign keys, parent references and ACF defaults.
function finalizeField(field, path, parentKey, parentKind) {
  const key = `field_${hash(path)}`;
  const out = {
    key,
    label: field.label,
    name: field.name,
    'aria-label': '',
    type: field.type,
    instructions: '',
    required: 0,
    conditional_logic: 0,
    wrapper: { width: '', class: '', id: '' },
  };
  for (const [k, v] of Object.entries(field)) {
    if (['sub_fields', 'layouts', 'item', 'wrapper'].includes(k)) continue;
    out[k] = v;
  }
  if (field.wrapper) out.wrapper = { ...out.wrapper, ...field.wrapper, width: String(field.wrapper.width ?? '') };
  if (parentKind === 'repeater') out.parent_repeater = parentKey;
  if (parentKind === 'layout') out.parent_layout = parentKey;
  if (field.sub_fields) {
    out.sub_fields = field.sub_fields.map((sf, i) => finalizeField(sf, `${path}/${sf.name || 'tab' + i}`, key, field.type === 'repeater' ? 'repeater' : 'group'));
    if (field.type === 'repeater') {
      const first = out.sub_fields.find((s) => s.type !== 'tab');
      out.collapsed = first ? first.key : '';
    }
  }
  if (field.layouts) {
    out.layouts = {};
    for (const l of field.layouts) {
      const lkey = `layout_${hash(path + '#' + l.name)}`;
      out.layouts[lkey] = {
        key: lkey, name: l.name, label: l.label, display: 'block',
        sub_fields: l.sub_fields.map((sf, i) => finalizeField(sf, `${path}#${l.name}/${sf.name || 'tab' + i}`, lkey, 'layout')),
        min: '', max: '',
      };
    }
  }
  return out;
}

export function fieldGroup({ key, title, fields, location, menu_order = 0, position = 'normal', hide_on_screen = [], description = '' }) {
  return {
    key,
    title,
    fields: fields.map((f, i) => finalizeField(f, `${key}/${f.name || 'tab' + i}`, key, 'group')),
    location,
    menu_order,
    position,
    style: 'default',
    label_placement: 'top',
    instruction_placement: 'label',
    hide_on_screen,
    active: true,
    description,
    show_in_rest: 0,
    modified: 1793000000,
  };
}

export const onPostType = (...types) => types.map((t) => [{ param: 'post_type', operator: '==', value: t }]);
export const onTemplate = (...files) => files.map((f) => [{ param: 'page_template', operator: '==', value: f }]);
export const onOptionsPage = (slug) => [[{ param: 'options_page', operator: '==', value: slug }]];
