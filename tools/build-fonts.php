<?php

// Maintainer-only build. Generated assets are shipped; consumers need no build step.
require dirname(__DIR__) . '/vendor/autoload.php';

use Com\Tecnick\Pdf\Font\Import;

$root = dirname(__DIR__) . '/fonts';
$out = $root . '/generated/';
if (!is_dir($out)) {
    mkdir($out, 0755, true);
}
foreach (array_merge(glob($root . '/core/*.afm'), glob($root . '/*.ttf')) as $source) {
    $encoding = str_ends_with($source, '.afm')
        ? (str_contains($source, 'Symbol') ? 'symbol' : (str_contains($source, 'ZapfDingbats') ? '' : 'cp1252'))
        : '';
    // Import refuses to overwrite descriptors. Regenerate only the known font's assets.
    $name = strtolower(str_replace(['-', 'Bold', 'Oblique', 'Italic'], ['', 'b', 'i', 'i'], pathinfo($source, PATHINFO_FILENAME)));
    foreach (['.json', '.z', '.ctg.z'] as $suffix) {
        if (is_file($out . $name . $suffix)) {
            unlink($out . $name . $suffix);
        }
    }
    $font = new Import($source, $out, '', $encoding);
    // Remove build-machine paths from the portable font descriptor.
    $file = $out . $font->getFontName() . '.json';
    $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    unset($data['input_file'], $data['dir'], $data['datafile']);
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
    echo $font->getFontName(), "\n";
}
