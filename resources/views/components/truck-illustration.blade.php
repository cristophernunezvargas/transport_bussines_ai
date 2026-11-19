{{-- Side view of a cabover truck with the M.N.S.T wordmark on the trailer. Warm palette via CSS variables so it follows the theme. --}}
<svg viewBox="0 0 600 214" role="img" aria-label="{{ __('Camión de carga') }}" {{ $attributes }}>
    {{-- Trailer --}}
    <rect x="10" y="30" width="372" height="132" rx="8" class="fill-zinc-900 dark:fill-zinc-800" />
    <rect x="10" y="116" width="372" height="12" class="fill-accent" />
    <text x="34" y="96" font-family="'Barlow Condensed', sans-serif" font-size="58" font-weight="600" letter-spacing="2" class="fill-zinc-100">M.N.S.T</text>
    <text x="36" y="148" font-family="'Barlow', sans-serif" font-size="17" font-weight="500" class="fill-zinc-300">Transportes</text>
    {{-- Chassis --}}
    <rect x="10" y="162" width="520" height="12" rx="3" class="fill-zinc-700" />
    {{-- Cab --}}
    <path d="M394 162V80a14 14 0 0 1 14-14h86l38 44v52z" class="fill-accent" />
    <path d="M412 82h62l30 30h-92z" class="fill-zinc-100" />
    <rect x="520" y="130" width="12" height="10" rx="2" class="fill-zinc-100" />
    {{-- Wheels --}}
    <g class="fill-zinc-950 dark:fill-zinc-950">
        <circle cx="86" cy="174" r="24" />
        <circle cx="142" cy="174" r="24" />
        <circle cx="318" cy="174" r="24" />
        <circle cx="470" cy="174" r="24" />
    </g>
    <g class="fill-zinc-300">
        <circle cx="86" cy="174" r="9" />
        <circle cx="142" cy="174" r="9" />
        <circle cx="318" cy="174" r="9" />
        <circle cx="470" cy="174" r="9" />
    </g>
</svg>
