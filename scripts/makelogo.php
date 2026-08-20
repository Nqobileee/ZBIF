<?php
$src = imagecreatefrompng(__DIR__ . '/../public/assets/img/zbif-logo.png');
if (!$src) {
    fwrite(STDERR, "fail load\n");
    exit(1);
}
$w = imagesx($src);
$h = imagesy($src);
$dst = imagecreatetruecolor($w, $h);
imagealphablending($dst, false);
imagesavealpha($dst, true);
$transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
imagefilledrectangle($dst, 0, 0, $w, $h, $transparent);
imagealphablending($dst, true);
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($src, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        if ($r < 30 && $g < 30 && $b < 30) {
            continue;
        }
        $col = imagecolorallocate($dst, $r, $g, $b);
        imagesetpixel($dst, $x, $y, $col);
    }
}
imagealphablending($dst, false);
imagesavealpha($dst, true);
imagepng($dst, __DIR__ . '/../public/assets/img/zbif-logo-mark.png');
echo "wrote mark\n";
