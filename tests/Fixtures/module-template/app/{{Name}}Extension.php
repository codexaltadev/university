<?php

namespace Modules\{{Name}};

use Modules\{{Name}}\Filament\{{Name}}Plugin;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

final class {{Name}}Extension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin({{Name}}Plugin::class);
    }
}
