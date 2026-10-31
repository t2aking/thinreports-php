<?php

namespace Thinreports\Generator\PDF;

use Com\Tecnick\Pdf\Tcpdf;

/** Internal adaptations for Thinreports text semantics. */
class Engine extends Tcpdf
{
    /** Accepts the same arguments as Tcpdf::__construct(). */
    public function __construct(mixed ...$args)
    {
        parent::__construct(...$args);
        // Break only at spaces, as TCPDF 6 did. Unspaced text that is too long is still
        // split in splitLines(), so Japanese without spaces keeps wrapping.
        $this->enableIdeographicBreaks(false);
        // Words wider than the line are split in splitLines() in every overflow mode.
        $this->enableWordBreakFallback(false);
    }

    /** Thinreports truncates without adding an ellipsis. */
    protected function getTextCellTruncationMarkerOrdArr(): array
    {
        return [];
    }

    /**
     * Shrink fitted text below tc-lib-pdf's 4pt minimum when needed, down to 1pt as TCPDF 6
     * did, so long text is not truncated.
     */
    protected function fitTextCellByFontSize(
        array $ordarr,
        float $maxWidth,
        float $maxHeight,
        float $offsetPoints,
        float $lineSpacePoints,
    ): array {
        $fit = parent::fitTextCellByFontSize($ordarr, $maxWidth, $maxHeight, $offsetPoints, $lineSpacePoints);
        if (!$this->textCellLayoutOverflows($fit['layout'], $maxWidth, $maxHeight)) {
            return $fit;
        }
        if ($fit['fontchanged']) {
            // The caller restores one font, so drop the 4pt font before applying ours.
            $this->font->popLastFont();
        }
        $current = $this->font->getCurrentFont();
        $low = 1.0;
        $high = min(4.0, $current['size']);
        if ($high <= $low) {
            return $fit;
        }
        $best = $this->probeTextCellFontSizeFit($ordarr, $low, $maxWidth, $maxHeight, $offsetPoints, $lineSpacePoints);
        if ($best['fits']) {
            for ($i = 0; $i < 12; $i++) {
                $mid = ($low + $high) / 2;
                $probe = $this->probeTextCellFontSizeFit($ordarr, $mid, $maxWidth, $maxHeight, $offsetPoints, $lineSpacePoints);
                if ($probe['fits']) {
                    $low = $mid;
                    $best = $probe;
                } else {
                    $high = $mid;
                }
            }
        }
        $applied = $this->font->cloneFont($this->pon, $current['idx'], null, $best['size']);
        if (!$best['fits']) {
            // Even 1pt overflows: keep only the lines inside the box, as TCPDF 6 did.
            $kept = $this->fitTextCellByTruncation($ordarr, $maxWidth, $maxHeight, $offsetPoints, $lineSpacePoints);
            $best['dim'] = $this->font->getOrdArrDims($kept);
            $best['layout'] = $this->getTextCellLayout(
                $kept, $best['dim'], $maxWidth, $maxWidth, 1.0, $offsetPoints, $lineSpacePoints,
            );
        }
        return ['fontchanged' => true, 'fontout' => $applied['out'], 'dim' => $best['dim'], 'layout' => $best['layout']];
    }

    /**
     * Break lines only at ASCII white space, as TCPDF 6 did: a full-width space (U+3000)
     * and other non-ASCII spaces stay inside the line. Paragraph breaks, soft hyphens and
     * the end-of-text entry are kept.
     */
    protected function removeNoBreakSplits(array $dim): array
    {
        $dim = parent::removeNoBreakSplits($dim);
        $split = array_values(array_filter(
            $dim['split'],
            static fn(array $data): bool => $data['septype'] === 'B' || $data['ord'] < 0x80
                || $data['ord'] === 0xAD || $data['pos'] >= $dim['chars'],
        ));
        if (count($split) !== count($dim['split'])) {
            $dim['split'] = $split;
            $dim['words'] = count($split);
        }
        return $dim;
    }

    /** Split unspaced text (including Japanese) that exceeds the cell width. */
    protected function splitLines(array $ordarr, array $dim, float $pwidth, float $poffset = 0): array
    {
        $lines = parent::splitLines($ordarr, $dim, $pwidth, $poffset);
        $font = $this->font->getCurrentFont();
        $spacing = $font['spacing'] * $font['stretching'];
        $result = [];
        foreach ($lines as $line) {
            $available = $pwidth - ($result === [] ? $poffset : 0);
            if ($line['totwidth'] <= $available + 0.000001 || $line['chars'] < 2) {
                $result[] = $line;
                continue;
            }
            $end = $line['pos'] + $line['chars'];
            // A word wider than the line follows a space: like TCPDF 6, fill the previous
            // line instead of breaking at the space.
            $previous = $result !== [] && end($result)['septype'] !== 'B' ? array_pop($result) : null;
            if ($previous !== null) {
                $available = $pwidth - ($result === [] ? $poffset : 0);
            }
            $first = $start = $previous['pos'] ?? $line['pos'];
            $width = 0.0;
            for ($pos = $start; $pos < $end; $pos++) {
                $char_width = $this->font->getCharWidth($ordarr[$pos]);
                // Match getOrdArrDims(): tracking applies between characters.
                $next_width = $width + $char_width + ($pos > $start ? $spacing : 0);
                if ($pos > $start && $next_width > $available + 0.000001) {
                    $result[] = $this->hardWrappedLine($ordarr, $start, $pos - $start, 'BN');
                    $start = $pos;
                    $next_width = $char_width;
                    $available = $pwidth;
                }
                $width = $next_width;
            }
            if ($start === $first) {
                // No break point was found, so keep the lines as they are.
                if ($previous !== null) {
                    $result[] = $previous;
                }
                $result[] = $line;
                continue;
            }
            // Lay out the rest of the word again with the text after it, so the following
            // words share its line as they did with TCPDF 6.
            $rest = array_slice($ordarr, $start);
            foreach ($this->splitLines($rest, $this->font->getOrdArrDims($rest), $pwidth) as $tail) {
                $tail['pos'] += $start;
                $result[] = $tail;
            }
            return $result;
        }
        return $result;
    }

    private function hardWrappedLine(array $ordarr, int $start, int $length, string $separator): array
    {
        $dim = $this->font->getOrdArrDims(array_slice($ordarr, $start, $length));
        return ['pos' => $start, 'chars' => $length, 'spaces' => $dim['spaces'],
            'septype' => $separator, 'totwidth' => $dim['totwidth'],
            'totspacewidth' => $dim['totspacewidth'], 'words' => $dim['words']];
    }
}
