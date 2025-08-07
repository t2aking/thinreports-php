<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Thinreports\Item;

class TextFormatter
{
    private array $format;

    public function __construct(array $format)
    {
        $this->format = $format;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function format(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (!empty($this->format['type'])) {
            switch ($this->format['type']) {
                case 'number':
                    $value = $this->applyNumberFormat($value);
                    break;
                case 'datetime':
                    $value = $this->applyDateTimeFormat($value);
                    break;
                case 'padding':
                    $value = $this->applyPaddingFormat($value);
                    break;
            }
        }

        if (!empty($this->format['base'])) {
            $value = $this->applyBaseFormat($value);
        }
        return $value;
    }

    /**
     * @access private
     *
     * @param mixed $value
     * @return mixed
     */
    private function applyNumberFormat(mixed $value): mixed
    {
        if (!is_numeric($value)) {
            return $value;
        }
        $number_format = $this->format['number'];

        $precision = $number_format['precision'] ?: 0;
        $delimiter = $number_format['delimiter'];

        return number_format($value, $precision, '.', $delimiter);
    }

    /**
     * @access private
     *
     * @param mixed $value
     * @return mixed
     */
    private function applyDateTimeFormat(mixed $value): mixed
    {
        $datetime_format = $this->format['datetime'];

        if (empty($datetime_format['format'])) {
            return $value;
        }

        $datetime = date_create($value);

        if ($datetime) {
            return strftime($datetime_format['format'], date_timestamp_get($datetime));
        }

        return $value;
    }

    /**
     * @access private
     *
     * @param mixed $value
     * @return mixed
     */
    private function applyPaddingFormat(mixed $value): mixed
    {
        $padding_format = $this->format['padding'];

        $character = $padding_format['char'] ?? null;
        $direction = $padding_format['direction'];
        $length = (int)$padding_format['length'];

        if ($character === null || $character === '' || $length === 0) {
            return $value;
        }
        if (mb_strlen($value, 'UTF-8') >= $length) {
            return $value;
        }

        return $this->padChars($direction, $value, $character, $length);
    }

    /**
     * @access private
     *
     * @param mixed $value
     * @return mixed
     */
    private function applyBaseFormat(mixed $value): mixed
    {
        $base_format = $this->format['base'];
        $pattern = '/\{value}/';

        if (preg_match($pattern, $base_format)) {
            return preg_replace($pattern, $value, $base_format);
        }

        return $value;
    }

    /**
     * @access private
     *
     * @param string $direction Possible types are "L" or "R"
     * @param string $string
     * @param string $padstr
     * @param integer|string $length
     * @return string
     */
    private function padChars(string $direction, string $string, string $padstr, int|string $length): string
    {
        while (mb_strlen($string, 'UTF-8') < $length) {
            if ($direction === 'L') {
                $string = $padstr . $string;
            } else {
                $string .= $padstr;
            }
        }

        $string_length = mb_strlen($string, 'UTF-8');

        if ($string_length > $length) {
            if ($direction === 'L') {
                $string = mb_substr($string, $string_length - $length, $string_length, 'UTF-8');
            } else {
                $string = mb_substr($string, 0, $length, 'UTF-8');
            }
        }

        return $string;
    }
}
