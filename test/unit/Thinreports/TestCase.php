<?php
namespace Thinreports;

use Symfony\Component\Yaml\Yaml;

class TestCase extends \PHPUnit\Framework\TestCase
{
    static protected $_item_formats = array();

    public function rootDir(): string
    {
        return dirname(__DIR__) . '/';
    }

    public function dataDir(): string
    {
        return $this->rootDir() . '/data';
    }

    public function dataItemFormat($item_name, $format_key = 'default')
    {
        $this->dataLoadItemFormat($item_name);
        return static::$_item_formats[$item_name][$format_key];
    }

    public function dataItemFormatsFor($item_name): array
    {
        $this->dataLoadItemFormat($item_name);

        $formats = array();
        foreach (static::$_item_formats[$item_name] as $key => $format) {
            $formats[$format['id']] = $format;
        }
        return $formats;
    }

    public function dataItemFormats(array $item_name_and_keys): array
    {
        $formats = array();

        foreach ($item_name_and_keys as $item_name_and_key) {
            [$item_name, $format_key] = $item_name_and_key;

            $format = $this->dataItemFormat($item_name, $format_key);
            $formats[$format['id']] = $format;
        }
        return $formats;
    }

    public function dataLayoutFile($name): string
    {
        return $this->dataDir() . '/layouts/' . $name;
    }

    private function dataLoadItemFormat($item_name): void
    {
        if (!array_key_exists($item_name, static::$_item_formats)) {
            $format_file = $this->dataDir() . '/items/' . $item_name . '.yml';
            $formats = Yaml::parse(file_get_contents($format_file));

            static::$_item_formats[$item_name] = $formats;
        }
    }
}
