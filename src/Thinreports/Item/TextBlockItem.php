<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Item;

use Thinreports\Exception\StandardException;
use Thinreports\Page\Page;
use Thinreports\Item\Style\TextStyle;
use Thinreports\Exception;

class TextBlockItem extends AbstractBlockItem
{
    public const TYPE_NAME = 'text-block';

    private ?bool $format_enabled = null;
    private ?AbstractItem $reference_item = null;
    private TextFormatter $formatter;

    /**
     * {@inheritdoc}
     */
    public function __construct(Page $parent, array $schema)
    {
        parent::__construct($parent, $schema);

        $this->style = new TextStyle($schema['style']);
        $this->formatter = new TextFormatter($schema['format']);

        $this->format_enabled = $this->hasFormatSettings();

        parent::setValue($schema['value']);

        if ($this->hasReference()) {
            $this->reference_item = $parent->item($schema['reference-id']);
        }
    }

    /**
     * {@inheritdoc}
     * @throws Exception\StandardException
     */
    public function setValue(mixed $value): AbstractBlockItem
    {
        if ($this->hasReference()) {
            throw new Exception\StandardException('Readonly Item', $this->getId(),
                "It can't be overwritten, because it has references to the other.");
        }

        parent::setValue($value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(): ?string
    {
        if ($this->hasReference()) {
            return $this->reference_item->getValue();
        }

        return parent::getValue();
    }

    /**
     * @param bool $enable
     * @return $this
     * @throws StandardException
     */
    public function setFormatEnabled(bool $enable): AbstractBlockItem
    {
        if ($enable) {
            if ($this->isMultiple()) {
                throw new Exception\StandardException('Not Formattable',
                    $this->getId(), 'It is multiple-line Text Block.');
            }
            if (!$this->hasFormatSettings()) {
                throw new Exception\StandardException('Not Formattable',
                    $this->getId(), 'It has no formatting configuration.');
            }
        }
        $this->format_enabled = $enable;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFormatEnabled(): bool
    {
        return $this->format_enabled;
    }

    /**
     * @access private
     *
     * @return mixed
     */
    public function getRealValue(): mixed
    {
        if ($this->isFormatEnabled()) {
            return $this->formatter->format($this->getValue());
        }

        return $this->getValue();
    }

    /**
     * @access private
     *
     * @return bool
     */
    public function isMultiple(): bool
    {
        return $this->schema['multiple-line'] === true;
    }

    /**
     * @access private
     *
     * @return bool
     */
    public function hasFormatSettings(): bool
    {
        $text_format = $this->schema['format'];
        return $text_format['type'] !== '' || $text_format['base'] !== '';
    }

    /**
     * @access private
     *
     * @return bool
     */
    public function hasReference(): bool
    {
        return $this->schema['reference-id'] !== '';
    }
}
