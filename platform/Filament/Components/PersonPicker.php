<?php

namespace Platform\Filament\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Platform\Assignments\ManualPerson;
use Platform\Contracts\PersonSource;
use Platform\Localization\Languages;
use Platform\Registry\Registry;
use Platform\Scope\UserScopeFilter;
use Platform\Support\MorphMap;

/**
 * Find a person in every person directory (Faculty Members, Administration, Alumni …
 * and manual persons), or add one right there (FR-REL-09, UI/UX Part A §6). The
 * value is a person key: "core.manual-person:12", "faculty-member.member:7".
 *
 *     PersonPicker::make('person')
 *     PersonPicker::resolve($key)   // the person model
 */
class PersonPicker extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->searchable()
            ->getSearchResultsUsing(fn (string $search): array => static::search($search))
            ->getOptionLabelUsing(fn (?string $value): ?string => $value ? static::labelOf($value) : null)
            ->createOptionForm([
                Grid::make(2)->schema([
                    ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                        TextInput::make("name.{$locale}")->label(__('platform::people.name')." ({$language})")->required($isDefault)->maxLength(150),
                        TextInput::make("designation.{$locale}")->label(__('platform::people.designation')." ({$language})")->maxLength(150),
                    ]),
                    TextInput::make('organization.'.app(Languages::class)->default())->label(__('platform::people.organization'))->maxLength(150),
                    TextInput::make('email')->label(__('platform::people.email'))->email()->maxLength(150),
                    TextInput::make('phone')->label(__('platform::people.phone'))->tel()->maxLength(50),
                ]),
            ])
            ->createOptionModalHeading(__('platform::people.add_new'))
            ->createOptionUsing(function (array $data): string {
                $person = ManualPerson::query()->create([
                    'name' => array_filter((array) $data['name']),
                    'designation' => array_filter((array) ($data['designation'] ?? [])) ?: null,
                    'organization' => array_filter((array) ($data['organization'] ?? [])) ?: null,
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                ]);

                return static::keyOf($person);
            });
    }

    /** @return array<string, class-string<Model>> alias => model: registered sources, manual persons always */
    public static function sources(): array
    {
        return collect(app(Registry::class)->all('personSource'))
            ->mapWithKeys(fn (array $entry): array => [MorphMap::alias($entry['model']) => $entry['model']])
            ->put(MorphMap::alias(ManualPerson::class), ManualPerson::class)
            ->all();
    }

    /** @return array<string, string> key => "Name — designation (Source)" */
    public static function search(string $search, int $perSource = 10): array
    {
        $results = [];

        foreach (static::sources() as $alias => $model) {
            $attribute = $model::$titleAttribute ?? 'name';

            $model::query()->withoutGlobalScope(UserScopeFilter::class)
                ->where($attribute, 'like', "%{$search}%")
                ->limit($perSource)->get()
                ->each(function (Model $person) use (&$results): void {
                    $results[static::keyOf($person)] = static::describe($person);
                });
        }

        return $results;
    }

    public static function keyOf(Model $person): string
    {
        return $person->getMorphClass().':'.$person->getKey();
    }

    public static function resolve(?string $key): ?Model
    {
        if (blank($key) || ! str_contains($key, ':')) {
            return null;
        }

        [$alias, $id] = explode(':', $key, 2);
        $model = static::sources()[$alias] ?? Relation::getMorphedModel($alias);

        return $model ? $model::query()->withoutGlobalScope(UserScopeFilter::class)->find($id) : null;
    }

    public static function labelOf(string $key): ?string
    {
        $person = static::resolve($key);

        return $person ? static::describe($person) : null;
    }

    protected static function describe(Model $person): string
    {
        $presenter = $person instanceof PersonSource ? $person->personPresenter() : null;
        $name = $presenter?->name() ?? (string) $person->getAttribute($person::$titleAttribute ?? 'name');
        $designation = $presenter?->designation();
        $source = $person instanceof ManualPerson ? __('platform::people.manual') : Str::headline(class_basename($person));

        return $name.($designation ? " — {$designation}" : '')." ({$source})";
    }
}
