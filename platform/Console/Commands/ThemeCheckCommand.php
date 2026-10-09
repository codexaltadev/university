<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Platform\Theme\ThemeChecker;
use Platform\Theme\Themes;

/** Theme Guide §13: structure, required views and no PHP logic (T-1, T-3, T-6). */
class ThemeCheckCommand extends Command
{
    protected $signature = 'uwms:theme:check {slug : Folder under themes/ or an installed theme}';

    protected $description = 'Check a theme before installing or activating it';

    public function handle(Themes $themes, ThemeChecker $checker): int
    {
        $slug = $this->argument('slug');
        $directory = $themes->discover()[$slug] ?? base_path("themes/{$slug}");
        $errors = $checker->check($directory);

        if ($errors === []) {
            $this->components->info("Theme {$slug} passes the checks.");

            return self::SUCCESS;
        }

        foreach ($errors as $error) {
            $this->components->error($error);
        }

        return self::FAILURE;
    }
}
