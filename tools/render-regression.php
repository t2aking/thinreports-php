<?php

// A repeatable visual fixture for backend/font updates. Not part of PHPUnit.
require dirname(__DIR__) . '/vendor/autoload.php';

if ($argc !== 2) {
    fwrite(STDERR, "Usage: php tools/render-regression.php /path/to/output.pdf\n");
    exit(2);
}

$doc = new Thinreports\Generator\PDF\Document();
$doc->addBlankPage();
$y = 25;
foreach (['Helvetica', 'Courier New', 'Times New Roman', 'IPAMincho', 'IPAPMincho', 'IPAGothic', 'IPAPGothic'] as $family) {
    foreach ([[], ['bold'], ['italic'], ['underline', 'strikethrough']] as $i => $style) {
        $doc->text->drawTextBox($family[0] === 'I' ? '日本語 ABC 123' : 'Hello ABC 123', 20 + $i * 140, $y, 130, 22,
            ['font_family' => $family, 'font_size' => 14, 'font_style' => $style, 'color' => '#234567']);
    }
    $y += 32;
}
foreach (['truncate', 'fit', 'expand'] as $i => $overflow) {
    $doc->text->drawTextBox("日本語の折り返しを確認します。\n二行目の文章です。\n三行目も表示されます。", 20 + $i * 185, 280, 160, 45,
        ['font_family' => 'IPAGothic', 'font_size' => 16, 'font_style' => [], 'color' => 'black', 'overflow' => $overflow]);
}
foreach (['top','middle','bottom'] as $i => $valign) {
    $doc->graphics->drawRect(20 + $i * 185, 440, 160, 70, ['stroke_width'=>1,'stroke_color'=>'black','stroke_dash'=>'dashed','radius'=>5]);
    $doc->text->drawTextBox("Hello\n日本語", 20 + $i * 185, 440, 160, 70,
        ['font_family'=>'IPAMincho','font_size'=>16,'font_style'=>[], 'color'=>'black','align'=>'center','valign'=>$valign,'line_height'=>1.5]);
}
$doc->text->drawTextBox('Spacing ABC 123',20,540,500,30,['font_family'=>'Helvetica','font_size'=>18,'font_style'=>[],'color'=>'black','letter_spacing'=>2]);
$shape = ['stroke_width' => 1.5, 'stroke_color' => '#234567', 'stroke_dash' => 'dotted', 'fill_color' => '#ddeeff'];
$doc->graphics->drawRect(20, 600, 120, 50, $shape);
$doc->graphics->drawRect(205, 600, 120, 50, $shape + ['radius' => 12]);
$doc->graphics->drawEllipse(450, 625, 60, 25, $shape);
$images = dirname(__DIR__) . '/test/feature/image_rendering/files/';
$doc->graphics->drawImage($images . 'image-block-png.png', 20.5, 690.25, 120.4, 105.9,
    ['align' => 'center', 'valign' => 'middle']);
$doc->graphics->drawImage($images . 'image-block-jpeg.jpg', 205.5, 690.25, 120.4, 105.9,
    ['align' => 'right', 'valign' => 'bottom']);
file_put_contents($argv[1], $doc->render());
