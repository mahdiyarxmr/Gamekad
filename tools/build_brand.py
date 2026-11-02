#!/usr/bin/env python3
"""Build Rokad brand assets and sticker covers from official logos."""
from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageFont

ROOT = Path("/home/user/gamekad-design")
UP = Path("/home/user/uploads")
IMG = ROOT / "assets" / "img"
BRAND = IMG / "brand"
FONT = ROOT / "assets" / "fonts" / "Vazirmatn-Black.ttf"

NAVY = (33, 41, 90, 255)
TEAL = (88, 189, 175, 255)
TEAL_D = (52, 126, 117, 255)
MAGENTA = (224, 25, 91, 255)
ORANGE = (244, 151, 31, 255)
PURPLE = (79, 33, 90, 255)
RED = (169, 1, 1, 255)
MINT = (242, 250, 249, 255)
INK = (41, 40, 39, 255)
WHITE = (255, 255, 255, 255)


def trim(im: Image.Image, pad: int = 0) -> Image.Image:
    im = im.convert("RGBA")
    bbox = im.split()[-1].getbbox()
    if not bbox:
        return im
    im = im.crop(bbox)
    if pad:
        n = Image.new("RGBA", (im.width + pad * 2, im.height + pad * 2), (0, 0, 0, 0))
        n.paste(im, (pad, pad), im)
        return n
    return im


def fit(im: Image.Image, w: int, h: int | None = None) -> Image.Image:
    if h is None:
        h = int(im.height * w / im.width)
    canvas = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    scale = min(w / im.width, h / im.height)
    nw, nh = max(1, int(im.width * scale)), max(1, int(im.height * scale))
    r = im.resize((nw, nh), Image.Resampling.LANCZOS)
    canvas.paste(r, ((w - nw) // 2, (h - nh) // 2), r)
    return canvas


def rounded(size: tuple[int, int], color, radius: int) -> Image.Image:
    im = Image.new("RGBA", size, (0, 0, 0, 0))
    d = ImageDraw.Draw(im)
    d.rounded_rectangle((0, 0, size[0] - 1, size[1] - 1), radius=radius, fill=color)
    return im


def paste(base: Image.Image, layer: Image.Image, xy: tuple[int, int], angle: float = 0) -> None:
    if angle:
        layer = layer.rotate(angle, resample=Image.Resampling.BICUBIC, expand=True)
    x, y = xy
    base.alpha_composite(layer, (int(x), int(y)))


def offset_card(face: Image.Image, shadow_color, dx: int = 14, dy: int = 18) -> Image.Image:
    w, h = face.size
    canvas = Image.new("RGBA", (w + dx + 8, h + dy + 8), (0, 0, 0, 0))
    sh = Image.new("RGBA", face.size, (0, 0, 0, 0))
    sh.paste(shadow_color, (0, 0), face.split()[-1])
    canvas.alpha_composite(sh, (dx, dy))
    canvas.alpha_composite(face, (0, 0))
    return canvas


def font(size: int) -> ImageFont.FreeTypeFont:
    return ImageFont.truetype(str(FONT), size=size)


def label_pill(text: str, fg, bg, fs: int = 42) -> Image.Image:
    f = font(fs)
    dummy = ImageDraw.Draw(Image.new("RGBA", (4, 4)))
    bbox = dummy.textbbox((0, 0), text, font=f, language="fa")
    tw, th = bbox[2] - bbox[0], bbox[3] - bbox[1]
    pad_x, pad_y = 28, 14
    im = rounded((tw + pad_x * 2, th + pad_y * 2), bg, 18)
    d = ImageDraw.Draw(im)
    d.text((pad_x - bbox[0], pad_y - bbox[1]), text, font=f, fill=fg, language="fa")
    return im


def poster(bg, mark: Image.Image, title: str, accent, size=(1400, 900), mark_color="white") -> Image.Image:
    w, h = size
    canvas = Image.new("RGBA", (w, h), bg)
    # decorative rotated tiles
    tile = rounded((220, 160), accent, 28)
    paste(canvas, offset_card(tile, INK, 10, 12), (-40, 40), 12)
    paste(canvas, offset_card(rounded((180, 180), WHITE, 40), accent, 10, 12), (w - 210, -30), -8)
    paste(canvas, offset_card(rounded((160, 110), TEAL, 22), NAVY, 8, 10), (w - 280, h - 180), 6)
    mk = fit(mark, int(w * 0.42), int(h * 0.58))
    paste(canvas, mk, ((w - mk.width) // 2, int(h * 0.08)))
    pill = label_pill(title, WHITE if bg != ORANGE else NAVY, accent if accent != bg else NAVY, 48)
    paste(canvas, offset_card(pill, INK, 8, 10), ((w - pill.width) // 2 - 8, h - 160), -2)
    return canvas.convert("RGB")


def collage(tiles: list[Image.Image], size=(1600, 1100)) -> Image.Image:
    w, h = size
    canvas = Image.new("RGBA", (w, h), MINT)
    # mint wash circles
    d = ImageDraw.Draw(canvas)
    d.ellipse((-200, -120, 520, 520), fill=(88, 189, 175, 40))
    d.ellipse((w - 500, h - 480, w + 80, h + 80), fill=(224, 25, 91, 30))
    layout = [
        (80, 90, -8, 380),
        (520, 40, 6, 340),
        (980, 120, -4, 360),
        (160, 520, 7, 320),
        (620, 480, -6, 380),
        (1080, 560, 5, 300),
    ]
    for (x, y, ang, side), tile in zip(layout, tiles):
        t = fit(tile.convert("RGBA"), side, side)
        # round the tile
        mask = rounded((t.width, t.height), WHITE, 48)
        rounded_t = Image.new("RGBA", t.size, (0, 0, 0, 0))
        rounded_t.paste(t, (0, 0), mask.split()[-1])
        card = offset_card(rounded_t, NAVY, 12, 16)
        paste(canvas, card, (x, y), ang)
    return canvas.convert("RGB")


def main() -> None:
    BRAND.mkdir(parents=True, exist_ok=True)
    (IMG / "icons").mkdir(exist_ok=True)

    mark_teal = trim(Image.open(UP / "image-1.png"))
    wordmark = trim(Image.open(UP / "image-2.png"))
    mark_black = trim(Image.open(UP / "image-4.png"))
    mark_white = trim(Image.open(UP / "image-5.png"))
    icon_navy = Image.open(UP / "image-7.png").convert("RGBA")

    mark_teal.save(BRAND / "mark-teal.png")
    mark_white.save(BRAND / "mark-white.png")
    mark_black.save(BRAND / "mark-black.png")
    wordmark.save(BRAND / "wordmark.png")
    icon_navy.save(BRAND / "icon-navy.png")

    # flatten navy icon onto solid navy (semi-transparent edges)
    emblem = Image.new("RGBA", icon_navy.size, NAVY)
    emblem.alpha_composite(icon_navy)
    emblem_sq = fit(emblem, 1024, 1024)
    emblem_sq.save(IMG / "logo-emblem.png")
    for size, path in [
        (512, IMG / "icons" / "icon-512.png"),
        (192, IMG / "icons" / "icon-192.png"),
        (180, IMG / "icons" / "apple-touch.png"),
    ]:
        fit(emblem, size, size).save(path)

    colors = {
        "orange": UP / "image-1.jpeg",
        "red": UP / "image-8.jpeg",
        "magenta": UP / "image-9.jpeg",
        "teal-dark": UP / "image-10.jpeg",
        "teal": UP / "image-11.jpeg",
        "purple": UP / "image-12.jpeg",
    }
    tiles = []
    for key, src in colors.items():
        im = Image.open(src).convert("RGB").resize((800, 800), Image.Resampling.LANCZOS)
        im.save(BRAND / f"{key}.jpg", quality=88)
        tiles.append(im)

    # posters
    posters = [
        ("cat-mafia.jpg", MAGENTA, NAVY, "مافیا"),
        ("cat-golyapooch.jpg", ORANGE, MAGENTA, "گل یا پوچ"),
        ("cat-chess.jpg", NAVY, TEAL, "شطرنج"),
        ("cat-boardgame.jpg", TEAL_D, ORANGE, "بردگیم"),
        ("cat-werewolf.jpg", PURPLE, TEAL, "گرگینه"),
        ("blog-mafia-classic.jpg", NAVY, MAGENTA, "کلاسیک"),
        ("blog-lastmove.jpg", MAGENTA, ORANGE, "آخرین حرکت"),
        ("blog-golyapooch.jpg", ORANGE, NAVY, "گل یا پوچ"),
    ]
    for name, bg, accent, title in posters:
        poster(bg, mark_white, title, accent).save(IMG / name, quality=90)
        print("wrote", name)

    collage(tiles).save(IMG / "hero-stickers.png", quality=90)
    # about image: wordmark on mint with tiles
    about = Image.new("RGBA", (1400, 900), MINT)
    wm = fit(mark_teal, 520, 680)
    paste(about, wm, (80, 80))
    for i, (tile, pos, ang) in enumerate(
        zip(tiles[:4], [(780, 70), (1040, 280), (760, 430), (1080, 560)], [-7, 8, 5, -4])
    ):
        t = fit(tile.convert("RGBA"), 280, 280)
        mask = rounded((t.width, t.height), WHITE, 40)
        rt = Image.new("RGBA", t.size, (0, 0, 0, 0))
        rt.paste(t, mask=mask.split()[-1])
        paste(about, offset_card(rt, NAVY, 10, 12), pos, ang)
    about.convert("RGB").save(IMG / "hero-cafe.jpg", quality=90)
    print("hero + about ok")


if __name__ == "__main__":
    main()
