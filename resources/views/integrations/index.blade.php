<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0c0d10">
    <title>Integraciones · FIREFLYONE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#0c0d10] font-sans text-[#dedfe4] antialiased">
<main id="integrations-app" class="mx-auto min-h-screen w-full max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <header class="mb-8 flex items-center justify-between border-b border-[#20222a] pb-5">
        <div>
            <a href="/feed" class="text-xs text-[#969aa7] transition hover:text-white">← Volver al feed</a>
            <h1 class="mt-3 text-2xl font-semibold text-[#f0f2f5]">Conexiones e integraciones</h1>
            <p class="mt-1 text-sm text-[#7a7e8b]">Conecta tus cuentas para preparar la publicación de contenidos.</p>
        </div>
        <a href="/feed" class="hidden items-center gap-2 font-semibold text-white sm:flex"><span class="font-mono text-sm text-[#f58220]">&lt;//&gt;</span> FIREFLYONE</a>
    </header>

    <div v-if="notice" class="mb-5 rounded-lg border border-emerald-900/70 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-300" role="status">@{{ notice }}</div>
    <div v-if="error" class="mb-5 rounded-lg border border-red-900/70 bg-red-950/40 px-4 py-3 text-sm text-red-300" role="alert">@{{ error }}</div>

    <section aria-labelledby="social-title">
        <div class="mb-4">
            <h2 id="social-title" class="text-base font-semibold text-[#e2e4e9]">Publicación social</h2>
            <p class="mt-1 text-xs leading-relaxed text-[#7a7e8b]">Facebook e Instagram permanecen disponibles en la barra para compartir enlaces. La publicación automática se prepara para LinkedIn y TikTok.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <article v-for="provider in ['linkedin', 'tiktok']" :key="provider" class="rounded-xl border border-[#25272e] bg-[#141519] p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#1e2026] text-[#e2e4e9]">
                            <span v-if="provider === 'linkedin'" class="text-sm font-bold text-[#0a66c2]">in</span>
                            <span v-else class="text-xl">♪</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold capitalize text-[#f0f2f5]">@{{ provider }}</h3>
                            <p class="mt-0.5 text-[11px] text-[#777c89]">@{{ accounts[provider]?.display_name || (accounts[provider] ? 'Cuenta conectada' : 'Sin conectar') }}</p>
                        </div>
                    </div>
                    <span v-if="accounts[provider]" class="rounded-full border border-emerald-900/70 bg-emerald-950/40 px-2 py-1 text-[10px] font-medium text-emerald-300">Conectada</span>
                    <span v-else class="rounded-full border border-[#33353e] bg-[#1b1c22] px-2 py-1 text-[10px] font-medium text-[#a3a7b2]">Desconectada</span>
                </div>

                <p v-if="provider === 'linkedin'" class="mt-4 text-xs leading-relaxed text-[#8d919d]">Requiere autorización del asesor y los permisos aprobados para publicar en su perfil.</p>
                <p v-else class="mt-4 text-xs leading-relaxed text-[#8d919d]">La publicación directa requiere aprobación de TikTok y contenido multimedia alojado en un dominio verificado.</p>

                <div class="mt-5 flex gap-2">
                    <button v-if="!accounts[provider]" @click="connect(provider)" :disabled="connecting === provider || loading" class="rounded-lg bg-[#f58220] px-3.5 py-2 text-xs font-semibold text-black transition hover:bg-[#e07116] disabled:cursor-wait disabled:opacity-60" type="button">
                        @{{ connecting === provider ? 'Conectando…' : `Conectar ${provider}` }}
                    </button>
                    <button v-else @click="disconnect(provider)" :disabled="disconnecting === provider" class="rounded-lg border border-[#353741] px-3.5 py-2 text-xs font-medium text-[#c3c6d0] transition hover:border-red-900 hover:text-red-300 disabled:opacity-60" type="button">
                        @{{ disconnecting === provider ? 'Desconectando…' : 'Desconectar' }}
                    </button>
                </div>
            </article>
        </div>
    </section>

    <section class="mt-8" aria-labelledby="twilio-title">
        <div class="rounded-xl border border-[#25272e] bg-[#141519] p-5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#1e2026] text-[#f58220]"><span class="material-symbols-outlined">call</span></div>
                <div>
                    <h2 id="twilio-title" class="text-sm font-semibold text-[#f0f2f5]">Twilio Voice</h2>
                    <p class="mt-0.5 text-[11px] text-[#777c89]">Llamadas y transcripción</p>
                </div>
                <span class="ml-auto rounded-full border border-amber-900/70 bg-amber-950/30 px-2 py-1 text-[10px] font-medium text-amber-300">Pendiente de configuración</span>
            </div>
            <p class="mt-4 text-xs leading-relaxed text-[#8d919d]">La integración requiere credenciales de Twilio, un número emisor y una URL pública HTTPS para recibir callbacks de llamadas y grabaciones. Las grabaciones deben anunciarse y contar con el consentimiento necesario.</p>

            <div v-if="callNotice" class="mt-4 rounded-lg border border-emerald-900/70 bg-emerald-950/40 px-3 py-2 text-xs text-emerald-300" role="status">@{{ callNotice }}</div>
            <div v-if="callError" class="mt-4 rounded-lg border border-red-900/70 bg-red-950/40 px-3 py-2 text-xs text-red-300" role="alert">@{{ callError }}</div>

            <form @submit.prevent="startCall" class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="block text-xs font-medium text-[#c3c6d0]">Tu teléfono (E.164)
                    <input v-model.trim="callForm.adviser_phone" class="mt-1.5 w-full rounded-lg border border-[#30323c] bg-[#0f1013] px-3 py-2.5 text-sm text-white placeholder:text-[#626775] focus:border-[#f58220] focus:outline-none focus:ring-1 focus:ring-[#f58220]" placeholder="+51987654321" pattern="\+[1-9][0-9]{7,14}" required type="tel" autocomplete="tel">
                </label>
                <label class="block text-xs font-medium text-[#c3c6d0]">Teléfono del cliente (E.164)
                    <input v-model.trim="callForm.client_phone" class="mt-1.5 w-full rounded-lg border border-[#30323c] bg-[#0f1013] px-3 py-2.5 text-sm text-white placeholder:text-[#626775] focus:border-[#f58220] focus:outline-none focus:ring-1 focus:ring-[#f58220]" placeholder="+51912345678" pattern="\+[1-9][0-9]{7,14}" required type="tel" autocomplete="tel">
                </label>
                <label class="flex items-start gap-2 text-[11px] leading-relaxed text-[#9296a2] md:col-span-2">
                    <input v-model="callForm.recording_consent" class="mt-0.5 h-4 w-4 rounded border-[#41434c] bg-[#0f1013] accent-[#f58220]" required type="checkbox">
                    Confirmo que todas las personas han sido informadas y han dado su consentimiento para grabar y transcribir la llamada. Twilio también pedirá al cliente que pulse 1 para continuar.
                </label>
                <button :disabled="startingCall" class="w-fit rounded-lg bg-[#f58220] px-4 py-2.5 text-xs font-semibold text-black transition hover:bg-[#e07116] disabled:cursor-wait disabled:opacity-60 md:col-span-2" type="submit">@{{ startingCall ? 'Llamando…' : 'Iniciar llamada grabada' }}</button>
            </form>

            <div v-if="calls.length" class="mt-6 border-t border-[#25272e] pt-4">
                <h3 class="text-xs font-semibold text-[#e2e4e9]">Llamadas recientes</h3>
                <div class="mt-3 space-y-3">
                    <article v-for="call in calls" :key="call.id" class="rounded-lg border border-[#25272e] bg-[#101114] p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2 text-[11px]">
                            <span class="text-[#c3c6d0]">@{{ call.client_phone }}</span>
                            <span class="rounded-full bg-[#20222a] px-2 py-1 text-[#a1a5b0]">@{{ call.status }}</span>
                        </div>
                        <p class="mt-2 text-[10px] text-[#727784]">Transcripción: @{{ call.transcription_status || 'pendiente' }}</p>
                        <p v-if="call.transcript" class="mt-2 whitespace-pre-line text-xs leading-relaxed text-[#9ea2af]">@{{ call.transcript }}</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <footer class="mt-8 border-t border-[#20222a] pt-4 text-[11px] leading-relaxed text-[#686d7a]">
        Los tokens de las redes se guardan cifrados en el servidor y se asocian a tu cuenta de asesor.
    </footer>
</main>
</body>
</html>
