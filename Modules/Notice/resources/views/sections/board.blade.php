{{-- Homepage section "Notice Board" (FR-NOT-06). Contract: $items (Notice list, pinned first), $section, $settings (count); $context from the page --}}
@if ($items->isNotEmpty())
    @php($base = isset($context) && $context->isMicrosite() ? rtrim((string) app(\Platform\Context\MicrositeResolver::class)->urlFor($context->entity), '/') : '')
    <ul class="notice-board" style="list-style:none;padding:0;display:grid;gap:var(--space-2, 8px)">
        @foreach ($items as $notice)
            <li style="display:flex;gap:var(--space-3, 12px);align-items:baseline;padding:var(--space-2, 8px) 0;border-bottom:1px solid var(--color-border)">
                <time style="flex:none;font-size:.85em;color:var(--color-muted, #6b7280)">{{ $notice->active_at?->translatedFormat('d M') }}</time>
                <span><a href="{{ url($base.'/'.\Modules\Notice\Models\Notice::PATH.'/'.$notice->slug()) }}" @style(['font-weight:700;color:var(--color-danger, #DC2626)' => $notice->is_urgent])>{{ $notice->title }}</a> @include('notice::partials.badges', ['notice' => $notice])</span>
            </li>
        @endforeach
    </ul>
    <p style="margin-top:var(--space-4, 16px)"><a href="{{ url($base.'/'.\Modules\Notice\Models\Notice::PATH) }}" class="button">{{ __('notice::notice.all_notices') }}</a></p>
@endif
