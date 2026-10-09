{{-- FR-ROLE-09: what this user can really do, and where --}}
<div class="space-y-4 text-sm">
    @if ($super_admin)
        <p class="font-medium">{{ __('user::user.effective.super_admin') }}</p>
    @else
        <div>
            <p class="font-medium mb-1">{{ __('user::user.effective.where') }}</p>
            @if ($scope === null)
                <p>{{ __('user::user.scope.all') }}</p>
            @else
                <ul class="list-disc ps-5">
                    @foreach ($scope as $key)
                        <li>{{ $key }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div>
            <p class="font-medium mb-1">{{ __('user::user.effective.what') }} ({{ $permissions->count() }})</p>
            @forelse ($permissions as $permission)
                <span class="inline-block rounded bg-gray-100 dark:bg-gray-800 px-2 py-0.5 me-1 mb-1">{{ $permission }}</span>
            @empty
                <p>{{ __('user::user.effective.none') }}</p>
            @endforelse
        </div>
    @endif
</div>
