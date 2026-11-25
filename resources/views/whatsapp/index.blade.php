@extends('app')

@section('title', 'WhatsApp · FIREFLYONE')
@section('theme-color', '#0c0d10')
@section('body-class', 'bg-[#0c0d10] font-sans text-[#dedfe4] antialiased')

@section('head')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
@endsection

@section('content')
<div id="whatsapp-app" class="min-h-[calc(100vh-2.5rem)] pt-14 md:pl-60 md:pt-0">
    <x-feed-nav active="whatsapp" />

    <main class="page-enter mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="mb-6 border-b border-[#20222a] pb-5">
            <h1 class="text-2xl font-semibold text-[#f0f2f5]">WhatsApp</h1>
            <p class="mt-1 text-sm text-[#7a7e8b]">Responde desde los números oficiales de la empresa.</p>
        </header>

        <p v-if="error" class="mb-4 rounded-lg border border-red-900/70 bg-red-950/40 px-4 py-3 text-sm text-red-300" role="alert">@{{ error }}</p>
        <p v-if="notice" class="mb-4 rounded-lg border border-emerald-900/70 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-300" role="status">@{{ notice }}</p>

        <div class="grid min-h-[65vh] overflow-hidden rounded-xl border border-[#25272e] bg-[#141519] md:grid-cols-[minmax(15rem,19rem)_minmax(0,1fr)]">
            <section class="border-b border-[#25272e] md:border-b-0 md:border-r" aria-label="Conversaciones">
                <div class="flex items-center justify-between border-b border-[#25272e] px-4 py-3">
                    <h2 class="text-sm font-semibold text-white">Bandeja</h2>
                    <button @click="loadConversations" :disabled="loading" class="rounded-md p-1 text-[#9296a2] hover:bg-[#25272e] hover:text-white disabled:opacity-50" type="button" aria-label="Actualizar conversaciones">
                        <span class="material-symbols-outlined text-lg">refresh</span>
                    </button>
                </div>
                <p v-if="loading && !conversations.length" class="px-4 py-6 text-sm text-[#8d919d]">Cargando conversaciones…</p>
                <p v-else-if="!conversations.length" class="px-4 py-6 text-sm text-[#8d919d]">Aún no hay conversaciones. Revisa tu número en <a href="{{ route('integrations', [], false) }}" class="text-[#f58220] underline">Integraciones</a>.</p>
                <ul v-else class="max-h-[65vh] overflow-y-auto">
                    <li v-for="conversation in conversations" :key="conversation.id">
                        <button @click="selectConversation(conversation)" :class="selected?.id === conversation.id ? 'bg-[#211e19] text-white' : 'text-[#c3c6d0] hover:bg-[#1d1f25]'" class="w-full border-b border-[#25272e] px-4 py-3 text-left" type="button">
                            <span class="block truncate text-sm font-medium">@{{ conversation.customer_name || conversation.customer_phone }}</span>
                            <span class="mt-1 block truncate text-xs text-[#9296a2]">@{{ conversation.customer_phone }} · @{{ conversation.business_phone || 'Número empresarial' }}</span>
                            <span class="mt-1 block text-[11px] text-emerald-300">Tu número empresarial</span>
                        </button>
                    </li>
                </ul>
            </section>

            <section class="flex min-h-[30rem] min-w-0 flex-col" aria-label="Mensajes">
                <div v-if="!selected" class="flex flex-1 items-center justify-center px-6 text-center text-sm text-[#8d919d]">Selecciona una conversación para leerla.</div>
                <template v-else>
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#25272e] px-4 py-3">
                        <div class="min-w-0">
                            <h2 class="truncate text-sm font-semibold text-white">@{{ selected.customer_name || selected.customer_phone }}</h2>
                            <p class="text-xs text-[#9296a2]">@{{ selected.customer_phone }} · Empresa: @{{ selected.business_phone || selected.phone_number_id }}</p>
                        </div>
                        <span class="text-xs text-emerald-300">Número conectado a tu cuenta</span>
                    </div>

                    <div class="flex-1 space-y-3 overflow-y-auto bg-[#101114] px-4 py-5" aria-live="polite">
                        <p v-if="!messages.length" class="text-center text-sm text-[#8d919d]">No hay mensajes para mostrar.</p>
                        <div v-for="message in messages" :key="message.id" :class="message.direction === 'outgoing' ? 'ml-auto bg-[#243829]' : 'mr-auto bg-[#24262d]'" class="max-w-[85%] rounded-xl px-4 py-3 sm:max-w-[70%]">
                            <p class="whitespace-pre-wrap break-words text-sm text-[#f0f2f5]">@{{ message.body || 'Mensaje no textual' }}</p>
                            <p class="mt-1 text-right text-[10px] text-[#a0a4ae]">@{{ formatTime(message.occurred_at) }} · @{{ statusLabel(message) }}</p>
                        </div>
                    </div>

                    <form @submit.prevent="sendMessage" class="border-t border-[#25272e] p-4">
                        <p v-if="selected.assigned_adviser_id && !canReply" class="mb-3 text-xs text-amber-300">La ventana de 24 horas terminó. Se necesita una plantilla aprobada para continuar.</p>
                        <div class="flex items-end gap-2">
                            <label class="sr-only" for="whatsapp-reply">Respuesta</label>
                            <textarea id="whatsapp-reply" v-model.trim="draft" :disabled="!canReply || busy" maxlength="4096" rows="2" class="min-h-12 flex-1 resize-y rounded-lg border border-[#30323c] bg-[#0f1013] px-3 py-2 text-sm text-white focus:border-[#f58220] focus:outline-none disabled:opacity-50" placeholder="Escribe una respuesta"></textarea>
                            <button :disabled="!canReply || !draft || busy" class="rounded-lg bg-[#f58220] px-4 py-3 text-xs font-semibold text-black disabled:cursor-not-allowed disabled:opacity-50" type="submit">@{{ busy ? 'Enviando…' : 'Enviar' }}</button>
                        </div>
                    </form>
                </template>
            </section>
        </div>
    </main>
</div>
@endsection
