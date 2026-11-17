"""High-resolution SOL logo from the 200 px original: adaptive enlargement + edge smoothing.
usage: python hires.py <scale> <out.png>"""
import sys
from PIL import Image, ImageFilter, ImageChops, ImageDraw

SRC = r'C:/Users/ADVISOR KAMALIA/Downloads/sol-business/solbusiness-wp/wp-content/themes/Sol-BusinessConsultant/assets/images/sol-logo.jpg'
k = int(sys.argv[1]); out_path = sys.argv[2]
src = Image.open(SRC).convert('L')

# 1. Enlarge and decide black/white by comparing each spot with its neighbourhood, so the thin
#    stripes of the arrow survive (a single global threshold breaks them into dots).
big = src.resize((src.width * k, src.height * k), Image.BICUBIC).filter(ImageFilter.GaussianBlur(k * 0.25))
loc = big.filter(ImageFilter.BoxBlur(int(k * 1.6)))
adapt = ImageChops.subtract(loc, big, 1, 128).point(lambda v: 0 if v > 126 else 255)
dark = big.point(lambda v: 0 if v < 150 else 255)
light = big.point(lambda v: 0 if v < 110 else 255)
bw = Image.composite(dark, adapt, loc.point(lambda v: 255 if v < 70 else 0))
bw = Image.composite(light, bw, loc.point(lambda v: 255 if v > 205 else 0))

# The thin white outline under the arrow, where it meets the O, is too faint in the original to
# recover: join it into a solid line like the rest of the outline (points measured at 12x).
f = k / 12
ImageDraw.Draw(bw).line([(1236 * f, 739 * f), (1300 * f, 712 * f), (1352 * f, 689 * f)], fill=255, width=max(2, int(round(9 * f))))

# 2. Smooth the edges: remove specks, then blur and re-threshold (rounds off pixel stair-steps).
bw = bw.filter(ImageFilter.MedianFilter(5))
for r in (k * 0.30, k * 0.22):
    bw = bw.filter(ImageFilter.GaussianBlur(r)).point(lambda v: 0 if v < 128 else 255)

# 3. Soft anti-aliased edge, pure white background, small margin.
aa = bw.filter(ImageFilter.GaussianBlur(max(1.0, k / 10)))
pad = int(k * 8)
canvas = Image.new('L', (aa.width + 2 * pad, aa.height + 2 * pad), 255)
canvas.paste(aa, (pad, pad))
canvas.convert('RGB').save(out_path, optimize=True)
print(canvas.size)
