# AGENTS.md

This file provides guidance to Codex (Codex.ai/code) when working with code in this repository.

## Project Overview

Thinreports PHP is a PDF generation library for PHP that uses Thinreports Editor layout files (.tlf). It provides an API for generating PDFs from template layouts created with Thinreports Editor 0.8.x.

## Commands

```bash
# Run all tests (unit and feature)
composer test

# Run a single test file
./vendor/bin/phpunit test/unit/Thinreports/ReportTest.php

# Run a specific test method
./vendor/bin/phpunit --filter testMethodName test/unit/Thinreports/ReportTest.php

# Run static analysis
composer phpstan
```

## Architecture

### Core Components

- **Report** (`src/Thinreports/Report.php`): Main entry point. Manages pages and layouts, coordinates PDF generation via `PDFGenerator::generate()`.

- **Layout** (`src/Thinreports/Layout.php`): Parses .tlf JSON files (Thinreports Editor format). Creates item instances based on schema. Compatible with layout versions >= 0.8.2 and < 1.0.0.

- **Page** (`src/Thinreports/Page/Page.php`): Represents a single PDF page. Provides `item($id)` to access layout items and set values/styles.

### Item Types

Located in `src/Thinreports/Item/`:
- `TextBlockItem` - Dynamic text with formatting
- `ImageBlockItem` - Dynamic images (base64 or file path)
- `PageNumberItem` - Page number display
- `BasicItem` - Static layout elements (text, rect, ellipse, line, image)

Each item has an associated style class in `src/Thinreports/Item/Style/`.

### PDF Generation Flow

1. `Report::generate()` calls `PDFGenerator::generate()`
2. `PDFGenerator` iterates pages and delegates to:
   - `LayoutRenderer` - Renders static layout elements (text, images, shapes)
   - `ItemRenderer` - Renders dynamic items with user-set values
3. Both renderers use `PDF\Document` which wraps TCPDF

### PDF Helpers

Located in `src/Thinreports/Generator/PDF/`:
- `Document` - TCPDF wrapper, page management
- `Graphics` - Shape drawing (rect, ellipse, line, images)
- `Text` - Text box rendering with alignment/formatting
- `Font` - Font family mapping
- `ColorParser` - Hex color to RGB conversion

## Testing

- **Unit tests**: `test/unit/Thinreports/` - Mirror src structure, suffix `*Test.php`
- **Feature tests**: `test/feature/` - End-to-end PDF generation tests, suffix `*Feature.php`
- **Test layouts**: Feature tests use .tlf files in their respective directories

## Layout File Format

.tlf files are JSON containing:
- `version`: Layout format version
- `title`: Report title
- `report`: Paper settings (paper-type, orientation, width, height)
- `items`: Array of item definitions with type, position, dimensions, styles