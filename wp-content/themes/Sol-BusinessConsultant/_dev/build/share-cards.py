"""Build 1200x630 social share cards for every seed page/service/post.

usage: python _dev/build/share-cards.py  (needs Pillow: pip install pillow)
"""
import json, os, sys
from PIL import Image, ImageDraw, ImageFont, ImageFilter

HERE = os.path.dirname(os.path.abspath(__file__))
THEME = os.path.abspath(os.path.join(HERE, '..', '..'))
SCRATCH = os.path.join(HERE, 'fonts')  # Space Grotesk and Inter (SIL Open Font License)
OUT = os.path.join(THEME, 'assets', 'images', 'share')
os.makedirs(OUT, exist_ok=True)
seed = json.load(open(os.path.join(THEME, 'seed', 'site.json'), encoding='utf-8'))

W, H = 1200, 630
INK = (15, 23, 42)
AMBER = (217, 119, 6)
AMBER_SOFT = (254, 243, 199)


def inter(size, weight):
    f = ImageFont.truetype(os.path.join(SCRATCH, 'Inter.ttf'), size)
    axes = f.get_variation_axes()
    vals = []
    for a in axes:
        n = a['name'].decode() if isinstance(a['name'], bytes) else a['name']
        vals.append(weight if n.lower().startswith('weight') else min(max(size, a['minimum']), a['maximum']))
    f.set_variation_by_axes(vals)
    return f


def grotesk(size, weight=700):
    f = ImageFont.truetype(os.path.join(SCRATCH, 'SpaceGrotesk.ttf'), size)
    f.set_variation_by_axes([weight])
    return f


def first_photo(v):
    if isinstance(v, str):
        return v[len('img:photos/'):] if v.startswith('img:photos/') else None
    if isinstance(v, dict):
        v = list(v.values())
    if isinstance(v, list):
        for x in v:
            r = first_photo(x)
            if r:
                return r
    return None


FALLBACK = {
    'about': 'about-consultant-conversation.webp',
    'contact': 'home-one-stop-partner.webp',
    'book-consultation': 'home-one-stop-partner.webp',
    'faq': 'compliance-document-review.webp',
    'pricing': 'marketing-packages-team.webp',
    'ndis-packages': 'ndis-roadmap-support-at-home.webp',
    'get-started': 'ndis-roadmap-support-at-home.webp',
    'readiness-quiz': 'compliance-document-review.webp',
    'ndis-readiness-calculator': 'compliance-document-review.webp',
    'blog': 'blog-bookkeeping-review.webp',
    'case-studies': 'home-one-stop-partner.webp',
    'use-cases': 'blog-client-portal.webp',
    'services': 'home-consultants-team.webp',
    'complaints-feedback': 'about-consultant-conversation.webp',
}
DEFAULT_PHOTO = 'home-consultants-team.webp'

logo = Image.open(os.path.join(THEME, 'assets', 'images', 'sol-logo.jpg')).convert('RGB')


def wrap(draw, text, f, maxw):
    words, lines, cur = text.split(), [], ''
    for w in words:
        t = (cur + ' ' + w).strip()
        if draw.textlength(t, font=f) <= maxw:
            cur = t
        else:
            if cur:
                lines.append(cur)
            cur = w
    if cur:
        lines.append(cur)
    return lines


def card(photo, eyebrow, title, out):
    src = Image.open(os.path.join(THEME, 'assets', 'images', 'photos', photo)).convert('RGB')
    # cover-crop
    s = max(W / src.width, H / src.height)
    src = src.resize((round(src.width * s), round(src.height * s)), Image.LANCZOS)
    l, t = (src.width - W) // 2, (src.height - H) // 2
    img = src.crop((l, t, l + W, t + H))

    # navy wash: strong on the left, light on the right
    grad = Image.new('L', (W, 1))
    for x in range(W):
        p = x / W
        a = 238 if p < 0.38 else max(70, int(238 - (p - 0.38) / 0.62 * 190))
        grad.putpixel((x, 0), a)
    grad = grad.resize((W, H))
    img = Image.composite(Image.new('RGB', (W, H), INK), img, grad)
    # soft bottom shade
    sh = Image.new('L', (1, H))
    for y in range(H):
        sh.putpixel((0, y), int(max(0, (y - H * 0.55) / (H * 0.45)) * 120))
    img = Image.composite(Image.new('RGB', (W, H), INK), img, sh.resize((W, H)))

    d = ImageDraw.Draw(img)
    pad = 72

    # logo chip
    lh = 64
    lg = logo.resize((round(logo.width * lh / logo.height), lh), Image.LANCZOS)
    chip = Image.new('RGB', (lg.width + 32, lh + 24), (255, 255, 255))
    chip.paste(lg, (16, 12))
    mask = Image.new('L', chip.size, 0)
    ImageDraw.Draw(mask).rounded_rectangle((0, 0, chip.width - 1, chip.height - 1), 14, fill=255)
    img.paste(chip, (pad, 60), mask)

    # eyebrow
    ey = inter(19, 700)
    y = 236
    d.rounded_rectangle((pad, y + 8, pad + 34, y + 12), 2, fill=AMBER)
    d.text((pad + 48, y), eyebrow.upper(), font=ey, fill=AMBER_SOFT, spacing=0)

    # title, shrink to fit 3 lines
    maxw = 640
    for size in (64, 58, 52, 46, 42):
        tf = grotesk(size, 700)
        lines = wrap(d, title, tf, maxw)
        if len(lines) <= 3:
            break
    y += 44
    for ln in lines:
        d.text((pad, y), ln, font=tf, fill=(255, 255, 255))
        y += int(size * 1.12)

    # footer
    ft = inter(21, 600)
    fy = H - 72
    d.text((pad, fy), 'solbusinessconsultant.com.au', font=ft, fill=(255, 255, 255))
    tw = d.textlength('solbusinessconsultant.com.au', font=ft)
    d.ellipse((pad + tw + 16, fy + 11, pad + tw + 22, fy + 17), fill=AMBER)
    d.text((pad + tw + 36, fy), 'Australia-wide NDIS & business support', font=inter(21, 500), fill=(203, 213, 225))

    img.save(out, 'JPEG', quality=82, optimize=True, progressive=True)


EYEBROW = {'pages': 'SOL Business Consultant', 'services': 'SOL Services', 'posts': 'SOL Insights'}
TITLES = {'home': 'NDIS Registration & Business Consultants Australia'}
made = []
for lst in ('pages', 'services', 'posts'):
    for it in seed[lst]:
        path = it['path']
        photo = first_photo(it.get('fields')) or first_photo(it.get('featured_image')) or FALLBACK.get(path) or DEFAULT_PHOTO
        key = ('post--' + path) if lst == 'posts' else path.replace('/', '--')
        title = TITLES.get(path, it['title'])
        card(photo, EYEBROW[lst], title, os.path.join(OUT, key + '.jpg'))
        made.append(key)
card(DEFAULT_PHOTO, 'Australian-owned consultancy', 'NDIS Registration, Training & Business Consulting', os.path.join(OUT, 'default.jpg'))
print(len(made) + 1, 'cards')
