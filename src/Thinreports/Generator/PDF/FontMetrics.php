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
}
