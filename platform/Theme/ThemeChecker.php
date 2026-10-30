<?php

namespace Platform\Theme;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Checks a theme folder before install or activation (FR-THEME-02, Theme Guide §2, §13):
 * the manifest and required views, and no PHP business logic (T-1, BRL-THEME-02) —
 * PHP files only as Blade views and lang arrays, and no @php / queries in Blade.
 */
class ThemeChecker
{
    /** Patterns a theme file must not contain (T-1). */
    public const FORBIDDEN = [
        '/@php\b/' => '@php block',
        '/<\?(php|=)?/' => 'raw PHP tag',
        '/\bDB::/' => 'database query',
        '/::(query|where|find|all|create|first|get)\s*\(/' => 'model call',
        '/\b(eval|exec|shell_exec|system|passthru|proc_open|popen|file_put_contents|unlink)\s*\(/' => 'forbidden function',
        '/\b(App\\\\Models|Modules\\\\)/' => 'class reference',
        '/<x-[\w.:-]+\s[^>]*@(js|json)\(/' => '@js / @json in a component tag (left as text: pass :attr="$value")',
    ];

    /** @return list<string> errors; empty = the theme may be installed */
    public function check(string $directory): array
    {
        try {
            $manifest = ThemeManifest::fromDirectory($directory);
        } catch (RuntimeException $e) {
            return [$e->getMessage()];
        }

        $errors = $manifest->validate();

        foreach (File::allFiles($directory) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());

            if (preg_match('#(^|/)(node_modules|\.git)/#', $relative)) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $isBlade = Str::endsWith($relative, '.blade.php') && Str::startsWith($relative, 'views/');
            $isLang = Str::startsWith($relative, 'lang/');

            if (! $isBlade && ! $isLang) {
                $errors[] = "PHP file not allowed in a theme: {$relative}";

                continue;
            }

            $code = (string) file_get_contents($file->getPathname());

            if ($isLang) {
                // A lang file is "<?php return [ … ];" and nothing else
                $code = preg_replace('/^<\?php\s*/', '', $code, 1);
            }

            foreach (self::FORBIDDEN as $pattern => $what) {
                if (preg_match($pattern, $code)) {
                    $errors[] = "Not allowed in a theme ({$what}): {$relative}";
                }
            }
        }

        return $errors;
    }
}
