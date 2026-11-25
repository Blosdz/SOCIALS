@props(['active' => 'feed'])

<nav class="fixed inset-x-0 top-0 z-40 flex h-14 items-center justify-between gap-3 border-b border-[#20222a] bg-[#0f1013]/95 px-4 backdrop-blur md:hidden" aria-label="Navegación principal">
    <a href="{{ route('feed', [], false) }}" class="flex shrink-0 items-center gap-2 font-semibold text-white">
        <span class="font-mono text-base font-bold text-[#f58220]">&lt;//&gt;</span>
        <span>FIREFLYONE</span>
    </a>
    <div class="flex items-center gap-1">
        <a href="{{ route('feed', [], false) }}" @if ($active === 'feed') aria-current="page" @endif aria-label="Mi Feed" class="flex h-9 w-9 items-center justify-center rounded-lg transition-colors {{ $active === 'feed' ? 'bg-[#1e1c18] text-[#f58220]' : 'text-[#959aa7] hover:bg-[#15161b] hover:text-white' }}">
            <span class="material-symbols-outlined text-xl">home</span>
        </a>
        <a href="{{ route('contacts', [], false) }}" @if ($active === 'contacts') aria-current="page" @endif aria-label="Contactos" class="flex h-9 w-9 items-center justify-center rounded-lg transition-colors {{ $active === 'contacts' ? 'bg-[#1e1c18] text-[#f58220]' : 'text-[#959aa7] hover:bg-[#15161b] hover:text-white' }}">
            <span class="material-symbols-outlined text-xl">contacts</span>
        </a>
        <a href="{{ route('whatsapp', [], false) }}" @if ($active === 'whatsapp') aria-current="page" @endif aria-label="WhatsApp" class="flex h-9 w-9 items-center justify-center rounded-lg transition-colors {{ $active === 'whatsapp' ? 'bg-[#1e1c18] text-[#f58220]' : 'text-[#959aa7] hover:bg-[#15161b] hover:text-white' }}">
            <span class="material-symbols-outlined text-xl">chat</span>
        </a>
        <a href="{{ route('integrations', [], false) }}" @if ($active === 'integrations') aria-current="page" @endif aria-label="Configuración" class="flex h-9 w-9 items-center justify-center rounded-lg transition-colors {{ $active === 'integrations' ? 'bg-[#1e1c18] text-[#f58220]' : 'text-[#959aa7] hover:bg-[#15161b] hover:text-white' }}">
            <span class="material-symbols-outlined text-xl">settings</span>
        </a>
        <button @click="signOut" :disabled="signingOut" aria-label="Cerrar sesión" class="flex h-9 w-9 items-center justify-center rounded-lg text-[#959aa7] hover:bg-[#15161b] hover:text-white disabled:opacity-60" type="button">
            <span class="material-symbols-outlined text-xl">logout</span>
        </button>
    </div>
</nav>

<nav class="fixed bottom-10 left-0 top-0 z-40 hidden w-60 flex-col border-r border-[#20222a] bg-[#0f1013] p-4 md:flex" aria-label="Navegación principal">
    <a href="{{ route('feed', [], false) }}" class="mb-8 flex items-center gap-2 px-2 py-1.5 font-semibold text-white">
        <span class="font-mono text-base font-bold text-[#f58220]">&lt;//&gt;</span>
        <span>FIREFLYONE</span>
    </a>

    <div class="space-y-1">
        <a href="{{ route('feed', [], false) }}" @if ($active === 'feed') aria-current="page" @endif class="flex items-center gap-3 rounded-lg border-l-2 px-3 py-2 text-xs transition-colors {{ $active === 'feed' ? 'border-[#f58220] bg-[#1e1c18] font-medium text-[#f58220]' : 'border-transparent text-[#959aa7] hover:bg-[#15161b] hover:text-white' }}">
            <span class="material-symbols-outlined text-base">home</span><span>Mi Feed</span>
        </a>
        <a href="{{ route('contacts', [], false) }}" @if ($active === 'contacts') aria-current="page" @endif class="flex items-center gap-3 rounded-lg border-l-2 px-3 py-2 text-xs transition-colors {{ $active === 'contacts' ? 'border-[#f58220] bg-[#1e1c18] font-medium text-[#f58220]' : 'border-transparent text-[#959aa7] hover:bg-[#15161b] hover:text-white' }}">
            <span class="material-symbols-outlined text-base">contacts</span><span>Contactos</span>
        </a>
        <a href="{{ route('whatsapp', [], false) }}" @if ($active === 'whatsapp') aria-current="page" @endif class="flex items-center gap-3 rounded-lg border-l-2 px-3 py-2 text-xs transition-colors {{ $active === 'whatsapp' ? 'border-[#f58220] bg-[#1e1c18] font-medium text-[#f58220]' : 'border-transparent text-[#959aa7] hover:bg-[#15161b] hover:text-white' }}">
            <span class="material-symbols-outlined text-base">chat</span><span>WhatsApp</span>
        </a>
        <a href="{{ route('integrations', [], false) }}" @if ($active === 'integrations') aria-current="page" @endif class="flex items-center gap-3 rounded-lg border-l-2 px-3 py-2 text-xs transition-colors {{ $active === 'integrations' ? 'border-[#f58220] bg-[#1e1c18] font-medium text-[#f58220]' : 'border-transparent text-[#959aa7] hover:bg-[#15161b] hover:text-white' }}">
            <span class="material-symbols-outlined text-base text-[#757a87]">settings</span><span>Configuración</span>
        </a>
    </div>

    <div class="mt-auto space-y-3 border-t border-[#1e2027] pt-4">
        <div class="flex min-w-0 items-center gap-2.5">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#363945] bg-[#272933] text-xs font-semibold text-[#f58220]">@{{ initials }}</span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-xs font-medium text-[#e2e4e9]">@{{ adviser?.name || 'Asesor' }}</span>
                <span class="block truncate text-[10px] text-[#6d717f]">@{{ adviser?.email || 'Asesor Certificado' }}</span>
            </span>
        </div>
        <button @click="signOut" :disabled="signingOut" class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-xs text-[#828695] transition hover:bg-[#1b1c22] hover:text-white disabled:opacity-60" type="button">
            <span class="material-symbols-outlined text-base">logout</span>
            <span>@{{ signingOut ? 'Cerrando sesión…' : 'Cerrar sesión' }}</span>
        </button>
    </div>
</nav>
