<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Item;

use Thinreports\Page\Page;

abstract class AbstractItem
{
    protected $parent;
    protected $schema;

    protected $is_visible;
    protected $is_dynamic;
    protected $style;

    /**
     * @param Page $parent
     * @param array $schema
     */
    public function __construct(Page $parent, array $schema)
    {
        $this->parent = $parent;
        $this->schema = $schema;
        $this->is_visible = $schema['display'] === true;
        $this->is_dynamic = $schema['id'] !== '';
    }

    /**
     * @param bool $visible
     * @return $this
     */
    public function setVisible(bool $visible): AbstractItem
    {
        $this->is_visible = $visible;
        return $this;
    }

    /**
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->is_visible;
    }

    /**
     * @return $this
     */
    public function hide(): AbstractItem
    {
        $this->setVisible(false);
        return $this;
    }

    /**
     * @return $this
     */
    public function show(): AbstractItem
    {
        $this->setVisible(true);
        return $this;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->schema['id'];
    }

    /**
     * @return bool
     */
    public function isDynamic(): bool
    {
        return $this->is_dynamic;
    }

    /**
     * @param string $name
     * @param mixed $style
     * @return $this
     * @throws Thinreports\Exception\StandardException
     * @throws Thinreports\Exception\UnavailableStyleValue
     */
    public function setStyle(string $name, $style): AbstractItem
    {
        $this->style->set($name, $style);
        return $this;
    }

    /**
     * @param array $styles
     * @return $this
     * @throws Thinreports\Exception\StandardException
     * @throws Thinreports\Exception\UnavailableStyleValue
     */
    public function setStyles(array $styles): AbstractItem
    {
        foreach ($styles as $name => $style) {
            $this->setStyle($name, $style);
        }
        return $this;
    }

    /**
     * @return mixed
     * @throws Thinreports\Exception\StandardException
     */
    public function getStyle($name)
    {
        return $this->style->get($name);
    }

    /**
     * @access private
     *
     * @return array
     */
    public function exportStyles(): array
    {
        return $this->style->export();
    }

    public function __clone()
    {
        $this->style = clone $this->style;
    }

    /**
     * @access private
     *
     * @return Page
     */
    public function getParent(): Page
    {
        return $this->parent;
    }

    /**
     * @access private
     *
     * @return array
     */
    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * @access private
     *
     * @param string $type_name
     * @return bool
     */
    public function isTypeOf(string $type_name): bool
    {
        return $this->schema['type'] === $type_name;
    }

    /**
     * @access private
     *
     * @return array
     */
    abstract public function getBounds(): array;
}
