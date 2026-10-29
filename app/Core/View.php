<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    /** Render app/Views/{template}.php with $data extracted. */
    public static function make(string $template, array $data = []): string
    {
        $file = APP_PATH . '/Views/' . str_replace(['..', '\\'], ['', '/'], $template) . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('View not found: ' . $template);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string)ob_get_clean();
    }

    /** Render a template inside a layout. The template output is available as $content. */
    public static function render(string $template, array $data, string $layout): string
    {
        $data['content'] = self::make($template, $data);
        return self::make($layout, $data);
    }
}
