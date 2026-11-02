{{-- Core spacer (core.spacer): empty room of the chosen size. Contract: $items (sm / md / lg / xl) --}}
<div aria-hidden="true" @class(['h-4' => $items === 'sm', 'h-10' => $items === 'md', 'h-20' => $items === 'lg', 'h-32' => $items === 'xl'])></div>
