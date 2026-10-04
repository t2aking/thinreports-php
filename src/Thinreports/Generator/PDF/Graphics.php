<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Generator\PDF;

use InvalidArgumentException;
use Com\Tecnick\Pdf\Tcpdf;

/**
 * @access private
 */
class Graphics
{
    static private array $pdf_image_align = array(
        'left'   => 'L',
        'center' => 'C',
        'right'  => 'R'
    );

    static private array $pdf_image_valign = array(
        'top'    => 'T',
        'middle' => 'M',
        'bottom' => 'B'
    );

    /**
     * @var Tcpdf
     */
    private Tcpdf $pdf;

    /**
     * @var string[]
     */
    private array $image_registry = array();

    /**
     * @param Tcpdf $pdf
     */
    public function __construct(Tcpdf $pdf)
    {
        $this->pdf = $pdf;
    }

    /**
     * @param float|string $x1
     * @param float|string $y1
     * @param float|string $x2
     * @param float|string $y2
     * @param array $attrs {
     *      @option string|null "stroke_width" required
     *      @option string|null "stroke_color" required
     *      @option string "stroke_dash" required
     * }
     */
    public function drawLine(float|string $x1, float|string $y1, float|string $x2, float|string $y2, array $attrs = array()): void
    {
        $style = $this->buildGraphicStyles($attrs);

        if ($style['stroke'] === null) {
            return;
        }

        $this->pdf->page->addContent($this->pdf->graph->getLine(
            (float) $x1, (float) $y1, (float) $x2, (float) $y2, $this->toEngineStyle($style)));
    }

    /**
     * @param float|string $x
     * @param float|string $y
     * @param float|string $width
     * @param float|string $height
     * @param array $attrs {
     *      @option string|null "stroke_width" required
     *      @option string|null "stroke_color" required
     *      @option string "stroke_dash" required
     *      @option string "fill" required
     *      @option float|string "radius" required
     * }
     */
    public function drawRect(float|string $x, float|string $y, float|string $width, float|string $height, array $attrs = array()): void
    {
        $style = $this->buildGraphicStyles($attrs);
        $rendering_flag = $this->buildRenderingFlag($style['stroke'], $style['fill']);

        if ($rendering_flag === '') {
            return;
        }
        $mode = $this->toPaintMode($rendering_flag);
        $engine_style = $this->toEngineStyle($style);
        if (empty($attrs['radius'])) {
            $out = $this->pdf->graph->getBasicRect((float) $x, (float) $y, (float) $width, (float) $height,
                $mode, $engine_style);
        } else {
            $out = $this->pdf->graph->getRoundedRect((float) $x, (float) $y, (float) $width, (float) $height,
                (float) $attrs['radius'], (float) $attrs['radius'], '1111', $mode, $engine_style);
        }
        $this->pdf->page->addContent($out);
    }

    /**
     * @param float|string $cx
     * @param float|string $cy
     * @param float|string $rx
     * @param float|string $ry
     * @param array $attrs {
     *      @option string|null "stroke_width" required
     *      @option string|null "stroke_color" required
     *      @option string "stroke_dash" required
     *      @option string "fill" required
     * }
     */
    public function drawEllipse(float|string $cx, float|string $cy, float|string $rx, float|string $ry, array $attrs = array()): void
    {
        $style = $this->buildGraphicStyles($attrs);
        $rendering_flag = $this->buildRenderingFlag($style['stroke'], $style['fill']);

        if ($rendering_flag === '') {
            return;
        }
        $this->pdf->page->addContent($this->pdf->graph->getEllipse(
            (float) $cx, (float) $cy, (float) $rx, (float) $ry, 0, 0, 360,
            $this->toPaintMode($rendering_flag), $this->toEngineStyle($style)));
    }

    /**
     * @param string $filename
     * @param float|string $x
     * @param float|string $y
     * @param float|string $width
     * @param float|string $height
     * @param array $attrs {
     *      @option string "align" optional default is "left"
     *      @option string "valign" optional default is "top"
     * }
     */
    public function drawImage(string $filename, float|string $x, float|string $y, float|string $width, float|string $height, array $attrs = array()): void
    {
        // These paths are supplied through Thinreports' image API, never HTML markup.
        $data = file_get_contents($filename);
        if ($data === false) {
            throw new \RuntimeException('Unable to read image: ' . $filename);
        }
        $size = getimagesizefromstring($data);
        if ($size === false) {
            throw new InvalidArgumentException('Invalid image: ' . $filename);
        }
        if ($size[2] === IMAGETYPE_XBM) {
            // GD's generic byte decoder cannot read XBM; use its dedicated decoder.
            $bitmap = imagecreatefromxbm($filename);
            if ($bitmap === false) {
                throw new InvalidArgumentException('Invalid XBM image: ' . $filename);
            }
            ob_start();
            try {
                if (!imagepng($bitmap)) {
                    throw new \RuntimeException('Unable to convert XBM image: ' . $filename);
                }
                $data = (string) ob_get_contents();
            } finally {
                ob_end_clean();
            }
        }
        // Preserve fractional point coordinates; the engine's fit helper accepts integer pixels.
        $scale = min((float) $width / $size[0], (float) $height / $size[1]);
        $dim = ['width' => $size[0] * $scale, 'height' => $size[1] * $scale];
        // As before, downsample oversized images to 300 dpi, without upsampling.
        $pixel_width = max(1, (int) round($dim['width'] * 300 / 72));
        $pixel_height = max(1, (int) round($dim['height'] * 300 / 72));
        $resize = $pixel_width * $pixel_height < $size[0] * $size[1];
        $iid = $this->pdf->image->add('@' . $data,
            $resize ? $pixel_width : null, $resize ? $pixel_height : null);
        $position = $this->buildImagePosition($attrs);
        $x += ($width - $dim['width']) * match ($position[0]) { 'C' => 0.5, 'R' => 1, default => 0 };
        $y += ($height - $dim['height']) * match ($position[1]) { 'M' => 0.5, 'B' => 1, default => 0 };
        $page = $this->pdf->page->getPage();
        $this->pdf->page->addContent($this->pdf->image->getSetImage(
            $iid, (float) $x, (float) $y, $dim['width'], $dim['height'], $page['height']));
    }

    /**
     * @param string $base64_string
     * @param float|string $x
     * @param float|string $y
     * @param float|string $width
     * @param float|string $height
     * @param array $attrs {@see self::drawImage()}
     */
    public function drawBase64Image(string $base64_string, float|string $x, float|string $y, float|string $width, float|string $height, array $attrs = array()): void
    {
        $registry_key = md5($base64_string);
        $image_path = $this->getRegisteredImagePath($registry_key);

        if ($image_path === null) {
            $image_path = tempnam(sys_get_temp_dir(), 'thinreports');
            $this->image_registry[$registry_key] = $image_path;
            file_put_contents($image_path, base64_decode($base64_string));
        }

        $this->drawImage($image_path, $x, $y, $width, $height, $attrs);
    }

    public function clearRegisteredImages(): void
    {
        foreach ($this->image_registry as $image_path) {
            unlink($image_path);
        }
        $this->image_registry = [];
    }

    /**
     * @param array $attrs
     * @return array {@example array("stroke" => array("attr" => "value"), "fill" => "fill_color"))
     */
    public function buildGraphicStyles(array $attrs): array
    {
        if (empty($attrs['stroke_width']) || $attrs['stroke_color'] === 'none') {
            $stroke_style = null;
        } else {
            $stroke_color = ColorParser::parse($attrs['stroke_color']);

            $stroke_dash = $this->normalizeStrokeDash($attrs['stroke_dash']);

            $stroke_style = array(
                'width' => $attrs['stroke_width'],
                'color' => $stroke_color,
                'dash'  => $stroke_dash
            );
        }

        if (array_key_exists('fill_color', $attrs) && $attrs['fill_color'] !== 'none') {
            $fill_color = ColorParser::parse($attrs['fill_color']);
        } else {
            $fill_color = null;
        }

        return array('stroke' => $stroke_style, 'fill' => $fill_color);
    }

    private function toPaintMode(string $flag): string
    {
        return match ($flag) { 'DF' => 'B', 'F' => 'f', default => 'S' };
    }

    private function toEngineStyle(array $style): array
    {
        $stroke = $style['stroke'];
        return [
            'lineWidth' => (float) ($stroke['width'] ?? 0),
            'lineColor' => $stroke === null ? 'black' : sprintf('#%02x%02x%02x', ...($stroke['color'] ?? [0, 0, 0])),
            'fillColor' => $style['fill'] === null ? 'black' : sprintf('#%02x%02x%02x', ...$style['fill']),
            'dashArray' => empty($stroke['dash']) ? [] : array_map('floatval', explode(',', $stroke['dash'])),
            'lineCap' => 'butt', 'lineJoin' => 'miter', 'dashPhase' => 0,
        ];
    }

    /**
     * Normalize Thinreports border styles before conversion to engine dash arrays.
     *
     * The patterns for dashed and dotted match the official Thinreports
     * Generator implementation.
     */
    private function normalizeStrokeDash(string $stroke_dash): int|string
    {
        return match ($stroke_dash) {
            'none', 'solid' => 0,
            'dashed' => '2,2',
            'dotted' => '1,2',
            default => throw new InvalidArgumentException(
                sprintf('Unsupported border style: "%s"', $stroke_dash)
            )
        };
    }

    /**
     * @param array|null $stroke
     * @param array|null $fill
     * @return string
     */
    public function buildRenderingFlag(?array $stroke, ?array $fill): string
    {
        $flag = array();

        if ($stroke !== null) {
            $flag[] = 'D';
        }
        if ($fill !== null) {
            $flag[] = 'F';
        }

        return implode('', $flag);
    }

    /**
     * @param string $registry_key
     * @return string|null
     */
    public function getRegisteredImagePath(string $registry_key): ?string
    {
        return $this->image_registry[$registry_key] ?? null;
    }

    /**
     * @param array $attrs
     * @return string
     */
    public function buildImagePosition(array $attrs): string
    {
        $align  = array_key_exists('align', $attrs)  ? $attrs['align']  : 'left';
        $valign = array_key_exists('valign', $attrs) ? $attrs['valign'] : 'top';

        return self::$pdf_image_align[$align] . self::$pdf_image_valign[$valign];
    }
}
