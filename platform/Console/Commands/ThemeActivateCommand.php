<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Platform\Theme\Themes;
use RuntimeException;

class ThemeActivateCommand extends Command
{
    protected $signature = 'uwms:theme:activate {slug}';

    protected $description = 'Make an installed theme the website theme';

    public function handle(Themes $themes): int
    {
        try {
            $themes->activate($this->argument('slug'));
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Activated theme {$this->argument('slug')}.");

        return self::SUCCESS;
    }
}
