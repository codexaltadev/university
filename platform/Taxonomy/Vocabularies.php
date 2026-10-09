<?php

namespace Platform\Taxonomy;

use Illuminate\Support\Facades\DB;
use Platform\Localization\Languages;
use Platform\Modules\ModuleRegistry;
use Platform\Registry\ExtensionLoader;
use Platform\Registry\Registry;
use RuntimeException;

/**
 * Registered vocabularies (FR-TAX-01): $r->vocabulary('faq.category', label: 'FAQ category',
 * hierarchical: true, multiple: false) in a module's Extension, plus the kernel's
 * shared "Tags" (FR-TAX-03). A missing row is created on first use; admins may rename it.
 */
class Vocabularies
{
    public const SHARED_TAGS = 'shared.tags';

    /** @var array<string, Vocabulary> */
    protected array $resolved = [];

    /** @return array<string, array{key: string, label: string, hierarchical: bool, multiple: bool, module: string}> key => registration (active modules only) */
    public function definitions(): array
    {
        return collect(app(Registry::class)->all('vocabulary'))->keyBy('key')->all();
    }

    public function isRegistered(string $key): bool
    {
        return isset($this->definitions()[$key]);
    }

    /** The vocabulary row, created from its registration when missing. */
    public function find(string $key): Vocabulary
    {
        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        $vocabulary = Vocabulary::query()->where('key', $key)->first();

        if ($vocabulary === null) {
            $definition = $this->definitions()[$key] ?? $this->definitionOfInstalled($key) ?? throw new RuntimeException("Vocabulary [{$key}] is not registered.");
            $vocabulary = Vocabulary::query()->create([
                'key' => $key,
                'module_slug' => $definition['module'] === 'core' ? null : $definition['module'],
                'name' => [app(Languages::class)->default() => $definition['label']],
                'is_hierarchical' => $definition['hierarchical'],
                'allows_multiple' => $definition['multiple'],
            ]);
        }

        return $this->resolved[$key] = $vocabulary;
    }

    /**
     * A module's seeder runs at install, before activation (ADR-06): its vocabularies
     * are not in the registry yet, so read them from its Extension directly.
     *
     * @return array<string, mixed>|null
     */
    protected function definitionOfInstalled(string $key): ?array
    {
        $loader = app(ExtensionLoader::class);

        foreach (app(ModuleRegistry::class)->manifests() as $manifest) {
            $scratch = new Registry;
            $loader->loadOne($manifest, $scratch);

            foreach ($scratch->all('vocabulary') as $entry) {
                if ($entry['key'] === $key) {
                    return $entry;
                }
            }
        }

        return null;
    }

    /** Create rows for every registered vocabulary (Taxonomy screen, module seeders). */
    public function sync(): void
    {
        foreach (array_keys($this->definitions()) as $key) {
            $this->find($key);
        }
    }

    /**
     * Make sure these terms exist (module seeders; safe to run twice). Matched by
     * the name in the default language.
     *
     * @param  list<array{name: array<string, string>, slug?: string, children?: list<array>}>  $terms
     */
    public static function ensureTerms(string $key, array $terms, ?int $parentId = null): void
    {
        $vocabulary = app(static::class)->find($key);
        $default = app(Languages::class)->default();

        foreach ($terms as $data) {
            $name = $data['name'][$default] ?? reset($data['name']);
            $term = Term::withTrashed()
                ->where('vocabulary_id', $vocabulary->id)
                ->where('parent_id', $parentId)
                ->get()
                ->first(fn (Term $t): bool => $t->getTranslation('name', $default, false) === $name);

            if ($term === null) {
                $term = Term::query()->create(['vocabulary_id' => $vocabulary->id, 'parent_id' => $parentId, 'name' => $data['name']]);

                if (isset($data['slug'])) {
                    foreach (app(Languages::class)->codes() as $locale) {
                        $term->changeSlug($locale, $data['slug']);
                    }
                }
            }

            static::ensureTerms($key, $data['children'] ?? [], $term->id);
        }
    }

    /**
     * Term choices of a vocabulary for selects; children indented under their parent.
     *
     * @return array<int, string> id => label
     */
    public function options(string $key, bool $activeOnly = true): array
    {
        $terms = Term::query()->where('vocabulary_id', $this->find($key)->id)
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->ordered()->get();

        $options = [];
        $walk = function (?int $parent, int $depth) use (&$walk, &$options, $terms): void {
            foreach ($terms->where('parent_id', $parent) as $term) {
                $options[$term->id] = str_repeat('— ', $depth).$term->name;
                $walk($term->id, $depth + 1);
            }
        };
        $walk(null, 0);

        // Children of an inactive parent still appear, at the top level
        foreach ($terms->whereNotIn('id', array_keys($options)) as $term) {
            $options[$term->id] = $term->name;
        }

        return $options;
    }

    /** Uninstall with data: the module's vocabularies (and their terms) and its content's links go. */
    public function forgetModule(string $slug, array $types): void
    {
        DB::table('core_termables')->whereIn('termable_type', $types)->delete();

        Vocabulary::query()->where('module_slug', $slug)->get()->each(function (Vocabulary $vocabulary): void {
            Term::withTrashed()->where('vocabulary_id', $vocabulary->id)->orderByDesc('parent_id')->get()->each->forceDelete();
            $vocabulary->delete();
        });

        $this->resolved = [];
    }

    public function flush(): void
    {
        $this->resolved = [];
    }
}
