<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\View;

class Template
{
    public static function render(string $template, array $variables = []): string
    {
        extract($variables, flags: EXTR_SKIP);
        ob_start();
        include dirname(__DIR__, levels: 2) . '/templates/' . $template;

        return ob_get_clean();
    }

    /** Rendert ein Template und packt es in das Seitenlayout. */
    public static function page(string $template, array $variables = [], string $title = '', string $activeNavigation = ''): string
    {
        return self::render(template: 'layout.html.php', variables: [
            'content' => self::render(template: $template, variables: $variables),
            'title' => $title,
            'activeNavigation' => $activeNavigation,
        ]);
    }
}
