<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports\Page;

class BlankPage
{
    protected $number;
    protected $is_blank = true;
    protected $is_countable = true;

    /**
     * @param int|null $page_number
     * @param bool $countable
     */
    public function __construct(?int $page_number, bool $countable = true)
    {
        $this->number = $page_number;
        $this->is_countable = $countable;
    }

    /**
     * @return bool
     */
    public function isCountable(): bool
    {
        return $this->is_countable;
    }

    /**
     * @return bool
     */
    public function isBlank(): bool
    {
        return $this->is_blank;
    }

    /**
     * @return int
     */
    public function getNo(): int
    {
        return $this->number;
    }
}
