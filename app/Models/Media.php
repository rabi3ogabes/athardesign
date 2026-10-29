<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use App\Core\Image;

final class Media
{
    /** @return array{0:array,1:int} */
    public static function paginate(int $page, int $perPage = 40, string $search = ''): array
    {
        $q = Db::from('media')->order('created_at', false)->limit($perPage)->offset(($page - 1) * $perPage);
        if ($search !== '') {
            $s = str_replace(['%', '*', ',', '(', ')'], '', $search);
            $q->ilike('filename', '*' . $s . '*');
        }
        return $q->getWithCount();
    }

    public static function find(string $id): ?array
    {
        if (!preg_match('/^[0-9a-f\-]{36}$/', $id)) return null;
        return Db::from('media')->eq('id', $id)->first();
    }

    public static function store(array $file, array $alt = ['ar' => '', 'en' => '']): array
    {
        $info = Image::store($file, 'media');
        $row = Db::from('media')->insert([
            'filename' => $info['filename'],
            'path' => $info['path'],
            'url' => $info['url'],
            'mime' => $info['mime'],
            'size' => $info['size'],
            'width' => $info['width'],
            'height' => $info['height'],
            'alt' => $alt,
        ])[0] ?? [];
        $row['thumb'] = $info['thumb'];
        return $row;
    }

    public static function update(string $id, array $data): void
    {
        Db::from('media')->eq('id', $id)->update($data);
    }

    public static function delete(string $id): void
    {
        $m = self::find($id);
        if (!$m) return;
        Image::delete($m['path']);
        Db::from('media')->eq('id', $id)->delete();
    }

    public static function count(): int
    {
        return Db::from('media')->count();
    }
}
