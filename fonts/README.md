# Font assets

Thinreports ships the original four IPA TrueType fonts and the generated files
required by tc-lib-pdf. Consumers do not need `make fonts`, a Composer install
hook, writable vendor directories, or a global `K_PATH_FONTS` constant.

- `*.ttf`: original IPA fonts, under `IPA_Font_License_Agreement_v1.0.txt`.
- `core/*.afm`: Adobe Core 14 metrics from
  https://github.com/tecnickcom/tc-font-mirror/tree/2.4.0/core;
  copyright and redistribution notice in `core/LICENSE`.
- `generated/*.json`: font descriptors and metrics converted with
  `tecnickcom/tc-lib-pdf-font` 4.3.3.
- `generated/*.z`: compressed IPA font data and glyph maps. Keep the `.json`,
  `.z`, and `.ctg.z` files together. The original TTF files are retained above;
  no glyph artwork has been edited.

Conversion and removal of build-machine paths from descriptors are performed by
`tools/build-fonts.php`. The generated files are derived assets, not hand-edited
source. The font metadata generator is LGPL-3.0-or-later; see
`generated/LICENSE.tc-lib-pdf-font`. Original font notices continue to apply.

Maintainers can regenerate the bundled assets without downloading font sources:

```sh
composer install
php tools/build-fonts.php
composer test
```

Commit regenerated assets with any intentional font-source or converter update.
Review the output of representative reports when changing the converter version.
`Thinreports\Generator\PDF\Font::build()` remains available and verifies/registers
the bundled IPA definitions; it no longer writes converted fonts at runtime.
