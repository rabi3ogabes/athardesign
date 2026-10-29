<?php
declare(strict_types=1);

namespace App\Core;

/** Upload handling: validation, resizing with GD, WebP conversion, thumbnails. */
final class Image
{
    private const MAX_BYTES = 12 * 1024 * 1024;
    private const MAX_EDGE = 2200;
    private const THUMB_EDGE = 560;

    /**
     * @return array{path:string,url:string,filename:string,mime:string,size:int,width:int|null,height:int|null,thumb:string}
     */
    public static function store(array $file, string $folder = 'media'): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(t('media.err_upload'));
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new \RuntimeException(t('media.err_size'));
        }
        $tmp = $file['tmp_name'];
        if (!is_uploaded_file($tmp)) {
            throw new \RuntimeException(t('media.err_upload'));
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/svg+xml' => 'svg', 'image/avif' => 'avif'];
        if (!isset($allowed[$mime])) {
            throw new \RuntimeException(t('media.err_type'));
        }
        $ext = $allowed[$mime];
        if ($ext === 'svg') {
            $svg = (string)file_get_contents($tmp);
            if (preg_match('/<script|on[a-z]+\s*=|javascript:|<foreignObject|<iframe|xlink:href\s*=\s*["\']\s*(?!#|data:image)/i', $svg)) {
                throw new \RuntimeException(t('media.err_svg'));
            }
        }

        $dir = UPLOADS_PATH . '/' . $folder . '/' . date('Y') . '/' . date('m');
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            throw new \RuntimeException('Uploads folder is not writable: public/uploads');
        }
        $base = pathinfo((string)($file['name'] ?? 'image'), PATHINFO_FILENAME);
        $base = mb_substr(slugify($base), 0, 60);
        $name = $base . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
        $rel = 'uploads/' . $folder . '/' . date('Y') . '/' . date('m') . '/';

        $width = $height = null;
        $thumbRel = '';
        $final = $name . '.' . $ext;

        if (in_array($ext, ['jpg', 'png', 'webp', 'avif'], true) && function_exists('imagecreatefromstring')) {
            $src = @imagecreatefromstring((string)file_get_contents($tmp));
            if ($src !== false) {
                $w = imagesx($src); $h = imagesy($src);
                // honour EXIF orientation for JPEG
                if ($ext === 'jpg' && function_exists('exif_read_data')) {
                    $exif = @exif_read_data($tmp);
                    $o = (int)($exif['Orientation'] ?? 1);
                    if ($o === 3) $src = imagerotate($src, 180, 0);
                    elseif ($o === 6) $src = imagerotate($src, -90, 0);
                    elseif ($o === 8) $src = imagerotate($src, 90, 0);
                    $w = imagesx($src); $h = imagesy($src);
                }
                $hasAlpha = in_array($ext, ['png', 'webp', 'avif'], true);
                $main = self::resize($src, $w, $h, self::MAX_EDGE, $hasAlpha);
                $thumb = self::resize($src, $w, $h, self::THUMB_EDGE, $hasAlpha);
                $useWebp = function_exists('imagewebp');
                $final = $name . ($useWebp ? '.webp' : '.' . ($ext === 'avif' ? 'png' : $ext));
                self::save($main, $dir . '/' . $final, $useWebp ? 'webp' : $ext, 84);
                $thumbName = $name . '-thumb.' . ($useWebp ? 'webp' : ($ext === 'avif' ? 'png' : $ext));
                self::save($thumb, $dir . '/' . $thumbName, $useWebp ? 'webp' : $ext, 76);
                $thumbRel = $rel . $thumbName;
                $width = imagesx($main); $height = imagesy($main);
                $mime = $useWebp ? 'image/webp' : $mime;
                imagedestroy($src); imagedestroy($main); imagedestroy($thumb);
            } else {
                move_uploaded_file($tmp, $dir . '/' . $final);
            }
        } else {
            move_uploaded_file($tmp, $dir . '/' . $final);
            if ($ext === 'gif') {
                $info = @getimagesize($dir . '/' . $final);
                if ($info) { $width = $info[0]; $height = $info[1]; }
            }
        }
        $path = $rel . $final;
        return [
            'path' => $path,
            'url' => '/' . $path,
            'filename' => $final,
            'mime' => $mime,
            'size' => (int)@filesize($dir . '/' . $final),
            'width' => $width,
            'height' => $height,
            'thumb' => $thumbRel !== '' ? '/' . $thumbRel : '/' . $path,
        ];
    }

    private static function resize(\GdImage $src, int $w, int $h, int $maxEdge, bool $alpha): \GdImage
    {
        $scale = min(1, $maxEdge / max($w, $h));
        $nw = max(1, (int)round($w * $scale)); $nh = max(1, (int)round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        if ($alpha) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        return $dst;
    }

    private static function save(\GdImage $img, string $file, string $format, int $quality): void
    {
        match ($format) {
            'webp' => imagewebp($img, $file, $quality),
            'png' => imagepng($img, $file, 7),
            'gif' => imagegif($img, $file),
            default => imagejpeg($img, $file, $quality),
        };
    }

    public static function delete(string $path): void
    {
        $path = ltrim($path, '/');
        if (!str_starts_with($path, 'uploads/')) return;
        $abs = PUBLIC_PATH . '/' . $path;
        if (is_file($abs)) @unlink($abs);
        $thumb = preg_replace('/(\.[a-z]+)$/i', '-thumb$1', $abs);
        if ($thumb && is_file($thumb)) @unlink($thumb);
    }

    public static function thumbOf(string $url): string
    {
        if (!str_contains($url, '/uploads/')) return $url;
        $t = preg_replace('/(\.[a-z]+)$/i', '-thumb$1', $url);
        $abs = PUBLIC_PATH . '/' . ltrim((string)parse_url((string)$t, PHP_URL_PATH), '/');
        return is_file($abs) ? (string)$t : $url;
    }
}
