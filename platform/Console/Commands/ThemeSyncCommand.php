<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Platform\Theme\Themes;

class ThemeSyncCommand extends Command
{
    protected $signature = 'uwms:theme:sync';

    protected $description = 'Record the themes found in themes/ and the runtime folder, and publish the active theme\'s assets';

    public function handle(Themes $themes): int
    {
        $themes->sync();

        foreach ($themes->all() as $theme) {
            $this->line(sprintf('  %s %s (%s)', $theme->is_active ? '●' : '○', $theme->slug, $theme->version));
        }

        return self::SUCCESS;
    }
}
