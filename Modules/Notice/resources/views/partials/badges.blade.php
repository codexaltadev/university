{{-- Pinned / urgent / new / archived marks of a notice (FR-NOT-02/07). Contract: $notice --}}
@if ($notice->is_pinned)<span class="badge badge-primary">{{ __('notice::notice.fields.is_pinned') }}</span>@endif
@if ($notice->is_urgent)<span class="badge badge--danger" style="background:var(--color-danger, #DC2626);color:#fff">{{ __('notice::notice.fields.is_urgent') }}</span>@endif
@if ($notice->isNew())<span class="badge badge--success">{{ __('notice::notice.new') }}</span>@endif
@unless ($notice->isOnBoard())<span class="badge badge--muted">{{ __('notice::notice.archived') }}</span>@endunless
