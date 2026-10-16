{{-- "Was this helpful?" (FR-FAQ-05): one answer per visitor and question (kept in the browser), counted on the server. Contract: $faq --}}
<div class="faq-helpful" style="display:flex;gap:var(--space-2, 8px);align-items:center;margin-top:var(--space-3, 12px);font-size:var(--text-sm, 14px)"
     x-data="{ done: localStorage.getItem('faq-helpful-{{ $faq->id }}') !== null,
               send(answer) {
                   if (this.done) return;
                   this.done = true;
                   try { localStorage.setItem('faq-helpful-{{ $faq->id }}', answer) } catch (e) {}
                   fetch(@js(route('faq.helpful', $faq)), { method: 'POST', headers: { 'X-CSRF-TOKEN': @js(csrf_token()), 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ answer }) })
               } }">
    <template x-if="! done">
        <span style="display:flex;gap:var(--space-2, 8px);align-items:center">
            {{ __('faq::faq.helpful.question') }}
            <button type="button" class="button" @click="send('yes')">{{ __('faq::faq.helpful.yes') }}</button>
            <button type="button" class="button" @click="send('no')">{{ __('faq::faq.helpful.no') }}</button>
        </span>
    </template>
    <span x-cloak x-show="done">{{ __('faq::faq.helpful.thanks') }}</span>
</div>
