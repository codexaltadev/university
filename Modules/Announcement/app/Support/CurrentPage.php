<?php

namespace Modules\Announcement\Support;

use Illuminate\Http\Request;
use Platform\Context\MicrositeResolver;
use Platform\Registry\Registry;

/**
 * Which page of its site is open (FR-ANW-02): "home", a module website page key ("notice.index",
 * "admission.index" … — the longest matching path wins), or null for any other page.
 */
class CurrentPage
{
    public function __construct(protected Registry $registry, protected MicrositeResolver $resolver) {}

    /** The address inside the site, without language and microsite: "admission/fees". */
    public function path(?Request $request = null): string
    {
        $request ??= request();

        return trim((string) ($this->resolver->resolve('/'.ltrim($request->path(), '/'))['rest'] ?? ''), '/');
    }

    public function key(?Request $request = null): ?string
    {
        $rest = $this->path($request);
        if ($rest === '') {
            return 'home';
        }

        $best = null;
        $length = -1;
        foreach ($this->pages() as $key => $path) {
            $path = trim($path, '/');
            if ($path !== '' && ($rest === $path || str_starts_with($rest, $path.'/')) && strlen($path) > $length) {
                [$best, $length] = [$key, strlen($path)];
            }
        }

        return $best;
    }

    /** @return array<string, string> key => path of every module website page */
    public function pages(): array
    {
        return collect($this->registry->all('websitePage'))->mapWithKeys(fn (array $p): array => [$p['key'] => $p['path']])->all();
    }

    /** @return array<string, string> key => label, for the "pages" choice */
    public function options(): array
    {
        return collect($this->registry->all('websitePage'))->mapWithKeys(fn (array $p): array => [$p['key'] => __($p['label']).' ('.$p['path'].')'])->sort()->all();
    }
}
