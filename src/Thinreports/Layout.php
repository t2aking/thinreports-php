<?php

/*
 * This file is part of the Thinreports PHP package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thinreports;

use Thinreports\Exception\IncompatibleLayout;
use Thinreports\Page\Page;

class Layout
{
    public const FILE_EXT_NAME = 'tlf';
    public const COMPATIBLE_VERSION_RANGE_START = '>= 0.8.2';
    public const COMPATIBLE_VERSION_RANGE_END   = '< 1.0.0';

    /**
     * @param string $filename
     * @return self
     * @throws Exception\StandardException
     */
    public static function loadFile(string $filename): Layout
    {
        if (pathinfo($filename, PATHINFO_EXTENSION) !== self::FILE_EXT_NAME) {
            $filename .= '.' . self::FILE_EXT_NAME;
        }

        if (!file_exists($filename)) {
            throw new Exception\StandardException('Layout File Not Found', $filename);
        }

        return self::loadData(file_get_contents($filename, true));
    }

    /**
     * @param string $data
     * @return self
     * @throws IncompatibleLayout
     */
    public static function loadData(string $data): Layout
    {
        $schema = self::parse($data);
        $identifier = md5($data);

        return new self($schema, $identifier);
    }

    /**
     * @access private
     *
     * @param string $file_content
     * @return array
     * @throws Exception\IncompatibleLayout
     */
    public static function parse(string $file_content): array
    {
        $schema = json_decode($file_content, true);

        if (!self::isCompatible($schema['version'])) {
            $rules = array(
                self::COMPATIBLE_VERSION_RANGE_START,
                self::COMPATIBLE_VERSION_RANGE_END
            );
            throw new Exception\IncompatibleLayout($schema['version'], $rules);
        }

        $items = array();
        foreach ($schema['items'] as $item) {
            if ($item['id'] === '') {
                $items[] = $item;
            } else {
                $items[$item['id']] = $item;
            }
        }
        $schema['items'] = $items;

        return $schema;
    }

    /**
     * @access private
     *
     * @param string $layout_version
     * @return bool
     */
    public static function isCompatible(string $layout_version): bool
    {
        $rules = array(
            self::COMPATIBLE_VERSION_RANGE_START,
            self::COMPATIBLE_VERSION_RANGE_END
        );

        foreach ($rules as $rule) {
            [$operator, $version] = explode(' ', $rule);

            if (!version_compare($layout_version, $version, $operator)) {
                return false;
            }
        }
        return true;
    }

    private array $schema;
    private string $identifier;
    private array $item_schemas;

    /**
     * @param array $schema
     * @param string $identifier
     */
    public function __construct(array $schema, string $identifier)
    {
        $this->schema = $schema;
        $this->identifier = $identifier;
        $this->item_schemas = $this->buildItemSchemas($schema['items']);
    }

    /**
     * @return string
     */
    public function getReportTitle(): string
    {
        return $this->schema['title'];
    }

    /**
     * @return string
     */
    public function getPagePaperType(): string
    {
        return $this->schema['report']['paper-type'];
    }

    /**
     * @return string[]|null
     */
    public function getPageSize(): ?array
    {
        if ($this->isUserPaperType()) {
            return array(
                $this->schema['report']['width'],
                $this->schema['report']['height']
            );
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isPortraitPage(): bool
    {
        return $this->schema['report']['orientation'] === 'portrait';
    }

    /**
     * @return boolean
     */
    public function isUserPaperType()
    {
        return $this->schema['report']['paper-type'] === 'user';
    }

    /**
     * @access private
     *
     * @return string
     */
    public function getLastVersion(): string
    {
        return $this->schema['version'];
    }

    /**
     * @access private
     *
     * @param array $item_schemas
     * @return array array('with_id' => array(), 'without_id' => array())
     */
    public function buildItemSchemas(array $item_schemas): array
    {
        $with_id = array();
        $without_id = array();

        foreach ($item_schemas as $item_schema) {
            $item_id = $item_schema['id'];

            if ($item_id === '') {
                $without_id[] = $item_schema;
            } else {
                $with_id[$item_id] = $item_schema;
            }
        }

        return array(
            'with_id' => $with_id,
            'without_id' => $without_id
        );
    }

    /**
     * @access private
     *
     * @param string $id
     * @return bool
     */
    public function hasItemById(string $id): bool
    {
        return array_key_exists($id, $this->item_schemas['with_id']);
    }

    /**
     * @access private
     *
     * @param Page $owner
     * @param string $id
     * @return Item\AbstractItem
     * @throws Exception\StandardException
     */
    public function createItem(Page $owner, string $id)
    {
        if (!$this->hasItemById($id)) {
            throw new Exception\StandardException('Item Not Found', $id);
        }

        $item_schema = $this->item_schemas['with_id'][$id];

        return match ($item_schema['type']) {
            'text-block' => new Item\TextBlockItem($owner, $item_schema),
            'image-block' => new Item\ImageBlockItem($owner, $item_schema),
            'page-number' => new Item\PageNumberItem($owner, $item_schema),
            default => new Item\BasicItem($owner, $item_schema),
        };
    }

    /**
     * @access private
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
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
     * @param string $filter all|with_id|without_id
     * @return array
     */
    public function getItemSchemas(string $filter = 'all'): array
    {
        return match ($filter) {
            'all' => $this->schema['items'],
            'with_id' => $this->item_schemas['with_id'],
            'without_id' => $this->item_schemas['without_id'],
            default => [],
        };
    }
}
