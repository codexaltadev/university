<?php

namespace Modules\Admission\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Modules\ModulePermissions;
use Platform\Slugs\Slug;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Required default data only, no demo content; must be safe to run twice.
 *
 *  - FR-ROLE-04 v1.1: the "Admission Office" (made by Tuition, or here) manages admission.
 *    Given once — when the role has no admission permission yet; an Admin's later change stays.
 *  - FR-ADM-13: the main site's "Admission" page at /admission (Admission blocks, with a sidebar
 *    and an empty "Admission" free menu for its section pages), when there is none yet.
 */
class AdmissionDatabaseSeeder extends Seeder
{
    public const ROLE = 'Admission Office';

    public function run(): void
    {
        $this->role();
        $this->landingPage();
    }

    protected function role(): void
    {
        $role = Role::findOrCreate(self::ROLE, 'web');
        $keys = app(ModulePermissions::class)->keysFor('Admission');
        if ($keys === [] || $role->permissions()->whereIn('name', $keys)->exists()) {
            return;
        }

        $role->givePermissionTo(Permission::query()->where('guard_name', 'web')->whereIn('name', $keys)->get());
    }

    protected function landingPage(): void
    {
        $page = 'Modules\\Page\\Models\\Page';
        if (! class_exists($page) || ! $page::moduleIsActive()) {
            return;
        }

        $taken = Slug::query()->where('sluggable_type', (new $page)->getMorphClass())->where('slug', 'admission')
            ->whereIn('sluggable_id', $page::query()->withTrashed()->withoutGlobalScopes()->ownedBy('site:0')->whereNull('parent_id')->select('id'))
            ->exists();
        if ($taken) {
            return;
        }

        $menuId = null;
        $editor = 'Modules\\Menu\\Support\\MenuEditor';
        if (class_exists($editor)) {
            $menuId = app($editor)->createFree('site:0', 'Admission')->id;   // the "Browse by section" menu, filled by the Admission Office
        }

        $block = fn (string $type, array $data = []): array => ['type' => "admission-{$type}", 'data' => $data];
        $landing = (new $page([
            'title' => ['en' => 'Admission'],
            'template' => 'with-sidebar',
            'sidebar_menu_id' => $menuId,
            'content' => ['en' => [
                $block('circulars', ['running_only' => true, 'limit' => 3]),
                $block('apply', ['buttons' => ['apply', 'info', 'result']]),
                $block('highlights', ['grouped' => true]),
                $block('steps'),
                $block('contacts'),
                $block('subscribe', ['heading' => 'Get admission updates', 'topic' => 'admission']),
            ]],
            'is_active' => true,
        ]))->setOwnerKey('site:0');
        $landing->save();
        $landing->changeSlug('en', 'admission');
    }
}
