<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Item;

abstract class AbstractBlockItem extends AbstractItem
{
    private mixed $value = '';

    /**
     * @param mixed $value
     * @return $this
     */
    public function setValue(mixed $value): AbstractBlockItem
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        $value = $this->getValue();
        return $value === null || $value === '';
    }

    /**
     * @return bool
     */
    public function isPresent(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function getBounds(): array
    {
        return array(
            'x' => $this->schema['x'],
            'y' => $this->schema['y'],
            'width' => $this->schema['width'],
            'height' => $this->schema['height']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isTypeOf(string $type_name): bool
    {
        return $type_name === 'block' || parent::isTypeOf($type_name);
    }
}
