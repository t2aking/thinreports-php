<?php

namespace Thinreports\Generator\PDF;

use Com\Tecnick\Pdf\Font\Stack;

/** Keep Thinreports' font-size-based line pitch independently of glyph bounds. */
class FontMetrics extends Stack
{
    public float $lineHeight = 1.0;

    protected function getFontMetric(int $idx): array
    {
        $font = parent::getFontMetric($idx);
        $height = $font['size'] * $this->lineHeight;
        // Center the original ascent/descent in the line box, as TCPDF 6 did.
        $font['ascent'] = ($height + $font['ascent'] + $font['descent']) / 2;
        $font['descent'] = $font['ascent'] - $height;
        $font['height'] = $height;
        return $font;
    }

    /**
     * Do not keep scaled glyph boxes in the per-size metric cache.
     *
     * Only getCharBBox() reads them, and Thinreports never calls it. For an IPA font they
     * are about 11,000 nested arrays for every font size, which used most of the memory of
     * a Japanese report.
     */
    protected function scaleBBoxMap(array $map, float $wratio, float $cratio): array
    {
        return [];
    }

    /** Scale one glyph box on demand, with the same result as Stack::getCharBBox(). */
    public function getCharBBox(int $ord): array
    {
        $font = $this->getCurrentFont();
        $data = $this->getFont($font['key']);
        $cratio = $font['cratio'];
        $wratio = $cratio * $font['stretching'];
        // As in Stack, the codepoint map is preferred, and a malformed box is skipped.
        foreach ([$data['cbboxu'][$ord] ?? null, $data['cbbox'][$ord] ?? null] as $box) {
            if (!is_array($box) || count($box) !== 4) {
                continue;
            }
            $box = array_values($box);
            return [
                0 => (float) $box[0] * $wratio,
                1 => (float) $box[1] * $cratio,
                2 => (float) $box[2] * $wratio,
                3 => (float) $box[3] * $cratio,
            ];
        }
        return [0.0, 0.0, 0.0, 0.0];
    }
}
