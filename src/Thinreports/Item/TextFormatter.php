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
            return $datetime->format($this->convertStrftimeToDateFormat($datetime_format['format']));
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

    public function convertStrftimeToDateFormat(string $format): string
    {
        $replacements = [
            // 年
            '%Y' => 'Y',    // 4桁の年 (例: 2023)
            '%y' => 'y',    // 2桁の年 (例: 23)
            '%C' => '',     // 世紀 - DateTime::formatには直接対応なし

            // 月
            '%B' => 'F',    // 月名フル (例: January)
            '%b' => 'M',    // 月名省略 (例: Jan)
            '%h' => 'M',    // 月名省略 (%bと同じ)
            '%m' => 'm',    // 月 (01-12)

            // 日
            '%d' => 'd',    // 日 (01-31)
            '%e' => 'j',    // 日 (1-31) - ゼロパディングなし
            '%j' => 'z',    // 年間通算日 (001-366) ※注：DateTime::formatでは0-365

            // 週
            '%A' => 'l',    // 曜日名フル (例: Sunday)
            '%a' => 'D',    // 曜日名省略 (例: Sun)
            '%w' => 'w',    // 曜日番号 (0=日曜日)
            '%u' => 'N',    // 曜日番号 (1=月曜日)
            '%U' => '',     // 年間通算週 (日曜始まり) - DateTime::formatには直接対応なし
            '%W' => 'W',    // 年間通算週 (月曜始まり)
            '%V' => 'W',    // ISO週番号

            // 時間
            '%H' => 'H',    // 時 (00-23)
            '%k' => 'G',    // 時 (0-23) - ゼロパディングなし
            '%I' => 'h',    // 時 (01-12)
            '%l' => 'g',    // 時 (1-12) - ゼロパディングなし
            '%M' => 'i',    // 分 (00-59)
            '%S' => 's',    // 秒 (00-59)
            '%p' => 'A',    // AM/PM
            '%P' => 'a',    // am/pm
            '%r' => 'h:i:s A', // 12時間形式の時刻 (例: 09:05:42 PM)
            '%R' => 'H:i',  // 24時間形式の時分 (例: 21:05)
            '%T' => 'H:i:s', // 24時間形式の時刻 (例: 21:05:42)
            '%X' => 'H:i:s', // 時刻表現 (ロケール依存)

            // 日付
            '%D' => 'm/d/y', // MM/DD/YY形式
            '%F' => 'Y-m-d', // YYYY-MM-DD形式
            '%x' => 'n/j/Y', // 日付表現 (ロケール依存)

            // 日付時刻
            '%c' => 'D M j H:i:s Y', // 完全な日時表現
            '%s' => 'U',    // Unixタイムスタンプ

            // タイムゾーン
            '%z' => 'O',    // タイムゾーンオフセット (+0900)
            '%Z' => 'T',    // タイムゾーン名 (JST)

            // その他
            '%%' => '%',    // パーセント文字そのもの
            '%n' => "\n",   // 改行
            '%t' => "\t",   // タブ

            // 以下は DateTime::format に直接対応するものがない
            '%g' => '',     // ISO年の下2桁
            '%G' => '',     // ISO年
        ];

        return strtr($format, $replacements);
    }
}
