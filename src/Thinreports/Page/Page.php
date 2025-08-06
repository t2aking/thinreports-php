<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Page;

use Thinreports\Exception\StandardException;
use Thinreports\Item\AbstractItem;
use Thinreports\Report;
use Thinreports\Layout;
use Thinreports\Exception;

class Page extends BlankPage
{
    private $report;
    private $layout;
    private $items = array();

    /**
     * @param Report $report
     * @param Layout $layout
     * @param int|null $page_number
     * @param bool $countable
     */
    public function __construct(Report $report, Layout $layout, ?int $page_number, bool $countable = true)
    {
        parent::__construct($page_number, $countable);

        $this->report = $report;
        $this->layout = $layout;
        $this->is_blank = false;
    }

    /**
     * @param string $id
     * @return AbstractItem
     * @throws StandardException
     */
    public function item(string $id): AbstractItem
    {
        if (array_key_exists($id, $this->items)) {
            return $this->items[$id];
        }

        $item = $this->layout->createItem($this, $id);
        $this->items[$id] = $item;

        return $item;
    }

    /**
     * @throws StandardException
     * @see self::item()
     */
    public function __invoke(string $id): AbstractItem
    {
        return $this->item($id);
    }

    /**
     * @param string $id
     * @param mixed $value
     * @throws Exception\StandardException
     */
    public function setItemValue(string $id, $value): void
    {
        $item = $this->item($id);

        if (!$item->isTypeOf('block')) {
            throw new Exception\StandardException('Uneditable Item', $id);
        }
        $item->setValue($value);
    }

    /**
     * @param array $values
     * @throws StandardException
     */
    public function setItemValues(array $values): void
    {
        foreach ($values as $id => $value) {
            $this->setItemValue($id, $value);
        }
    }

    /**
     * @param string $id
     * @return bool
     */
    public function hasItem(string $id): bool
    {
        return $this->layout->hasItemById($id);
    }

    /**
     * @return array
     */
    public function getItemIds(): array
    {
        return array_keys($this->layout->getItemSchemas('with_id'));
    }

    /**
     * @access private
     *
     * @return Report
     */
    public function getReport(): Report
    {
        return $this->report;
    }

    /**
     * @access private
     *
     * @return Layout
     */
    public function getLayout(): Layout
    {
        return $this->layout;
    }

    /**
     * @access private
     *
     * @return AbstractItem[]
     * @throws StandardException
     */
    public function getFinalizedItems(): array
    {
        $items = array();

        foreach ($this->getItemIds() as $id) {
            $items[] = $this->item($id);
        }
        return $items;
    }
}
