# أثر — Athar logo guidelines

The mark is the word **أثر** ("the trace left behind") built in square Kufi on a nine-unit grid.
The three dots of ث are lifted off the letters as three pixels: the single detail that says "web".
Letters are brass, pixels are ivory. The grid unit is the only measure used anywhere in the identity.

## Files (`public/assets/img/`)

| File | Use |
|---|---|
| `logo.svg`, `logo-light.svg` | Header and footer, Arabic (mark on the right). Dark / light themes |
| `logo-en.svg`, `logo-en-light.svg` | Header and footer, English (mark on the left) |
| `logo-full.svg`, `logo-full-light.svg` | Stacked lockup for social, print and the social preview image |
| `logo-mark.svg`, `logo-mark-1024.png` | App-icon tile: mark on an ink rounded square |
| `logo-mark-transparent.svg` | Mark alone, brass + ivory, for dark backgrounds |
| `logo-mark-light.svg` | Mark alone, ink + dark brass, for light backgrounds |
| `logo-mark-ink.svg`, `logo-mark-brass.svg`, `logo-mark-white.svg` | One-colour versions for print, engraving, embroidery |
| `logo-wordmark-amiri.svg` | The calligraphic Amiri wordmark, secondary use only (editorial, signatures) |
| `favicon.svg`, `/favicon.ico`, `apple-touch-icon.png`, `icon-192.png`, `icon-512.png` | Browser and home-screen icons |
| `og-default.jpg` | Default social preview (1200 × 630) |

Masters and the presentation board live in `design/logo/`.

## Colour

| Name | HEX | Use |
|---|---|---|
| Ink | `#0E0D0B` | Backgrounds, tile |
| Brass | `#C9A868` | Letters on ink, Latin line |
| Ivory | `#F1EADC` | Pixels on ink, Arabic tagline on ink |
| Ink (light) | `#15130F` | Letters on ivory and sand backgrounds |
| Brass (dark) | `#A8853F` / `#7A5C25` | Pixels and Latin line on light backgrounds |

Two colours maximum in any application. If only one is available, use the one-colour files; never recolour the pixels alone.

## Clear space and minimum size

- Clear space around the mark and the lockups: one grid unit (the width of one letter stroke, 1/9 of the mark height) on every side. Nothing enters this zone.
- Minimum size: mark 16 px (favicon) or 6 mm in print. Horizontal lockup 120 px wide or 30 mm. Below that, use the mark alone.

## Lockups

- Arabic context: mark on the right, tagline "لتصميم الويب" above the brass rule, "ATHAR WEB DESIGN" below it. The rule ends in a dot on the side of the mark.
- English context: mirrored, mark on the left.
- Stacked: mark centred above the two-line text block, for square formats and the social preview.
- Spacing between mark and text block is 20/72 of the mark height. Do not rebuild the lockups by hand; use the files.

## Backgrounds

Approved: ink, ivory, Doha sand, any photograph darkened to at least 60 % where the mark sits. On brass backgrounds use the ink one-colour version. On busy photography use the tile.

## Do not

- Rotate, skew, outline or add shadows, gradients or glows.
- Change the proportions of the grid or the gap between pixels and letters.
- Put the pixels in a third colour.
- Set the name in another typeface next to the mark; the mark already says أثر.
- Use the Amiri wordmark and the Kufi mark side by side.

## Construction

Nine columns by nine rows, unit = 26 px on a 256 px canvas with an 11 px margin.
Hamza at (9,1); alef column 9 rows 3–7; dots at (6,1), (5,3), (7,3); tooth of ث column 7 rows 5–7;
baseline row 7 columns 4–7; ر drops column 4 rows 7–9 and returns along row 9 columns 1–4.
Counted right to left, top to bottom.

## Open items

- Trademark clearance has not been checked. Run a search in the Qatar trademark register and a reverse-image search before printing at scale.
- Fonts in the lockups are outlined: IBM Plex Sans Arabic (tagline, OFL) and Cormorant (Latin line, OFL). No live text in any file.
