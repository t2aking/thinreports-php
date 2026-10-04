#!/usr/bin/env python3
"""Compare PDF page pixels using the same Poppler renderer and font configuration.

Requires pdftoppm on PATH and Pillow. Use an explicit threshold when comparing
engines; inspect generated difference images before accepting a changed baseline.
"""
import argparse
import json
from pathlib import Path
import subprocess
import tempfile

from PIL import Image, ImageChops


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('before', type=Path)
    parser.add_argument('after', type=Path)
    parser.add_argument('--output', type=Path, required=True)
    parser.add_argument('--dpi', type=int, default=144)
    parser.add_argument('--channel-tolerance', type=int, default=16)
    parser.add_argument('--max-changed-fraction', type=float, default=0)
    args = parser.parse_args()
    if args.dpi <= 0 or not 0 <= args.channel_tolerance <= 255 or not 0 <= args.max_changed_fraction <= 1:
        parser.error('Invalid DPI, channel tolerance, or changed fraction')
    args.output.mkdir(parents=True, exist_ok=True)
    results = []
    with tempfile.TemporaryDirectory(prefix='thinreports-pixels-') as temp:
        root = Path(temp)
        pages = []
        for label, pdf in [('before', args.before), ('after', args.after)]:
            subprocess.run(['pdftoppm', '-r', str(args.dpi), '-png', str(pdf.resolve()), str(root / label)],
                           check=True, capture_output=True)
            pages.append(sorted(root.glob(label + '-*.png'), key=lambda p: int(p.stem.rsplit('-', 1)[1])))
        if len(pages[0]) != len(pages[1]) or not pages[0]:
            raise SystemExit('PDF page counts differ or no pages were rendered')
        for index, (left, right) in enumerate(zip(*pages), 1):
            with Image.open(left) as raw_a, Image.open(right) as raw_b:
                a, b = raw_a.convert('RGB'), raw_b.convert('RGB')
                if a.size != b.size:
                    raise SystemExit(f'Page {index}: dimensions differ')
                diff = ImageChops.difference(a, b)
                channels = diff.split()
                mask = ImageChops.lighter(ImageChops.lighter(channels[0], channels[1]), channels[2])
                mask = mask.point(lambda value: 255 if value > args.channel_tolerance else 0)
                fraction = mask.histogram()[255] / (a.width * a.height)
                overlay = b.copy()
                overlay.paste((255, 0, 0), mask=mask)
                overlay.save(args.output / f'page-{index}-difference.png')
                results.append({'page': index, 'changed_fraction': fraction,
                                'passes': fraction <= args.max_changed_fraction})
    report = {'dpi': args.dpi, 'channel_tolerance': args.channel_tolerance,
              'max_changed_fraction': args.max_changed_fraction, 'pages': results}
    (args.output / 'comparison.json').write_text(json.dumps(report, indent=2) + '\n')
    print(json.dumps(report, indent=2))
    return 0 if all(page['passes'] for page in results) else 1


if __name__ == '__main__':
    raise SystemExit(main())
