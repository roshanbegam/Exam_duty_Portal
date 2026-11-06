<?php
session_start();

$captcha_string = substr(md5(uniqid(mt_rand(), true)), 0, 6);
$_SESSION['captcha'] = $captcha_string;

if (function_exists('imagecreatetruecolor')) {
    $image = imagecreatetruecolor(150, 50);
    $bg_color = imagecolorallocate($image, 250, 250, 250);
    imagefill($image, 0, 0, $bg_color);
    $text_color = imagecolorallocate($image, 70, 70, 70);

    for ($i = 0; $i < 100; $i++) {
        $dot_color = imagecolorallocate($image, mt_rand(200, 230), mt_rand(200, 230), mt_rand(200, 230));
        imagesetpixel($image, mt_rand(0, 149), mt_rand(0, 49), $dot_color);
    }
    for ($i = 0; $i < 3; $i++) {
        $line_color = imagecolorallocate($image, mt_rand(150, 200), mt_rand(150, 200), mt_rand(150, 200));
        imageline($image, mt_rand(0, 150), mt_rand(0, 50), mt_rand(0, 150), mt_rand(0, 50), $line_color);
    }

    $font_path = __DIR__ . '/arial.ttf';
    if (file_exists($font_path)) {
        imagettftext($image, 24, mt_rand(-8, 8), mt_rand(10, 20), mt_rand(35, 40), $text_color, $font_path, $captcha_string);
    } else {
        imagestring($image, 5, 40, 15, $captcha_string, $text_color);
    }

    header('Content-Type: image/png');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    imagepng($image);
    imagedestroy($image);
} else {
    http_response_code(500);
    echo "GD library not available";
}
