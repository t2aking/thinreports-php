<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Item;

use Thinreports\Page\Page;
use Thinreports\Item\Style;

class BasicItem extends AbstractItem
{
    const TYPE_NAME = 'basic';

    /**
     * {@inheritdoc}
     */
    public function __construct(Page $parent, array $schema)
    {
        parent::__construct($parent, $schema);

        switch (true) {
            case $this->isImage():
                $this->style = new Style\BasicStyle($schema);
                break;
            case $this->isText():
                $this->style = new Style\TextStyle($schema);
                break;
            default:
                $this->style = new Style\GraphicStyle($schema);
                break;
        }
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

        switch (true) {
            case $this->isImage() || $this->isRect() || $this->isText():
                return array(
                    'x'      => $schema['x'],
                    'y'      => $schema['y'],
                    'width'  => $schema['width'],
                    'height' => $schema['height']
                );
            case $this->isEllipse():
                return array(
                    'cx' => $schema['cx'],
                    'cy' => $schema['cy'],
                    'rx' => $schema['rx'],
                    'ry' => $schema['ry']
                );
            case $this->isLine():
                return array(
                    'x1' => $schema['x1'],
                    'y1' => $schema['y1'],
                    'x2' => $schema['x2'],
                    'y2' => $schema['y2']
                );
            default:
                return [];
        }
    }
}
