<?php
// Render the provider's existing logo as a square favicon without cropping it.
$path=is_file(__DIR__.'/mkfiles/logo.jpg')?__DIR__.'/mkfiles/logo.jpg':'/opt/mk-auth/mkfiles/logo.jpg';
if(!is_file($path)){http_response_code(404);exit;}
$info=@getimagesize($path);
if(!$info||$info[0]>4096||$info[1]>4096||filesize($path)>8388608){http_response_code(404);exit;}
header('Cache-Control: public, max-age=3600');
if(!function_exists('imagecreatefromstring')){
 header('Content-Type: '.$info['mime']);readfile($path);exit;
}
$src=@imagecreatefromstring(file_get_contents($path));
if(!$src){http_response_code(404);exit;}
$size=192;$canvas=imagecreatetruecolor($size,$size);
imagefill($canvas,0,0,imagecolorallocate($canvas,255,255,255));
$scale=min(176/$info[0],176/$info[1]);$w=max(1,(int)round($info[0]*$scale));$h=max(1,(int)round($info[1]*$scale));
imagecopyresampled($canvas,$src,(int)(($size-$w)/2),(int)(($size-$h)/2),0,0,$w,$h,$info[0],$info[1]);
header('Content-Type: image/png');imagepng($canvas);imagedestroy($src);imagedestroy($canvas);
