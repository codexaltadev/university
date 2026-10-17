<?php

namespace Modules\Csr\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Modules\Csr\Models\Activity;
use Modules\Csr\Support\Categories;
use Platform\Filament\Components\MediaPicker;
use Platform\Taxonomy\Term;
use UnitEnum;

/**
 * Services › CSR categories' pictures (FR-CSR-01): the terms themselves (name, description, parent)
 * are kept in Site › Taxonomy (csr.category); here each gets its picture from the Media Library.
 *
 * @property-read Schema $form
 */
class CsrCategories extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Services';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament-panels::pages.page';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('csr::csr.category_pictures');
    }

    public function getTitle(): string
    {
        return __('csr::csr.category_pictures');
    }

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->can('create', Activity::class);
    }

    public function mount(): void
    {
        $categories = app(Categories::class);
        $this->form->fill(collect($categories->all())->mapWithKeys(fn (Term $t): array => ['term_'.$t->id => $categories->image($t)?->id])->all());
    }

    public function form(Schema $schema): Schema
    {
        $terms = app(Categories::class)->all();

        return $schema->statePath('data')->components([
            Section::make()->schema($terms->isEmpty()
                ? [Text::make(__('csr::csr.no_categories'))]
                : [Grid::make(2)->schema($terms->map(fn (Term $t) => MediaPicker::make('term_'.$t->id)->label(($t->parent_id ? '— ' : '').$t->name)->image()->storeIds())->all())]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('save')->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))->action(fn () => $this->save())];
    }

    public function save(): void
    {
        $categories = app(Categories::class);
        $state = $this->form->getState();
        foreach ($categories->all() as $term) {
            $categories->setImage($term, ($id = $state['term_'.$term->id] ?? null) ? (int) $id : null);
        }
        Notification::make()->success()->title(__('csr::csr.saved'))->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedSchema::make('form')]);
    }
}
