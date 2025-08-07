<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Item;

use Thinreports\Page\Page;

class BasicItem extends AbstractItem
{
    public const TYPE_NAME = 'basic';

    /**
     * {@inheritdoc}
     */
    public function __construct(Page $parent, array $schema)
    {
        parent::__construct($parent, $schema);

        $this->style = match (true) {
            $this->isImage() => new Style\BasicStyle($schema),
            $this->isText() => new Style\TextStyle($schema),
            default => new Style\GraphicStyle($schema),
        };
    }

    /**
     * @access private
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return $this->isTypeOf('image');
    }

    /**
     * @access private
     *
     * @return bool
     */
    public function isText(): bool
    {
        return $this->isTypeOf('text');
    }

    /**
     * @access private
     *
     * @return bool
     */
    public function isRect(): bool
    {
        return $this->isTypeOf('rect');
    }

    /**
     * @access private
     *
     * @return bool
     */
    public function isEllipse(): bool
    {
        return $this->isTypeOf('ellipse');
    }

    /**
     * @access private
     *
     * @return bool
     */
    public function isLine(): bool
    {
        return $this->isTypeOf('line');
    }

    /**
     * {@inheritdoc}
     */
    public function isTypeOf(string $type_name): bool
    {
        return parent::isTypeOf($type_name) || self::TYPE_NAME === $type_name;
    }

    /**
     * {@inheritdoc}
     */
    public function getBounds(): array
    {
        $schema = $this->getSchema();

        return match (true) {
            $this->isImage() || $this->isRect() || $this->isText() => array(
                'x' => $schema['x'],
                'y' => $schema['y'],
                'width' => $schema['width'],
                'height' => $schema['height']
            ),
            $this->isEllipse() => array(
                'cx' => $schema['cx'],
                'cy' => $schema['cy'],
                'rx' => $schema['rx'],
                'ry' => $schema['ry']
            ),
            $this->isLine() => array(
                'x1' => $schema['x1'],
                'y1' => $schema['y1'],
                'x2' => $schema['x2'],
                'y2' => $schema['y2']
            ),
            default => [],
        };
    }
}
