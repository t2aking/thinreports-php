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
    }

    /** Thinreports truncates without adding an ellipsis. */
    protected function getTextCellTruncationMarkerOrdArr(): array
    {
        return [];
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
            $start = $line['pos'];
            $end = $start + $line['chars'];
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
            $result[] = $this->hardWrappedLine($ordarr, $start, $end - $start, $line['septype']);
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
