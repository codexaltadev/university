{{-- Footer block "Newsletter" (FR-NLT-01). Contract: $block, $context, $line --}}
<div class="site-footer__newsletter">
    <p style="margin:0 0 var(--space-2, 8px)">{{ $line }}</p>
    <livewire:newsletter.subscribe source="footer" :compact="true" />
</div>
