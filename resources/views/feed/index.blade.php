<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0c0d10">
    <title>Mi Feed · FIREFLYONE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#0c0d10] font-sans text-[#dedfe4] antialiased selection:bg-[#f58220]/20 selection:text-[#f58220]">
<div id="feed-app" class="mx-auto flex min-h-screen w-full max-w-[1440px] flex-col border-x border-[#1a1c22] bg-[#0f1013]">
    <header class="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-[#20222a] bg-[#0f1013]/95 px-4 backdrop-blur md:hidden">
        <a href="/feed" class="flex items-center gap-2 font-semibold text-white">
            <span class="font-mono text-sm font-bold text-[#f58220]">&lt;//&gt;</span>
            <span>FIREFLYONE</span>
        </a>
        <button @click="signOut" :disabled="signingOut" class="flex items-center gap-1.5 rounded-md px-2 py-1.5 text-xs text-[#9ea2af] hover:bg-[#1b1c22] hover:text-white" type="button">
            <span class="material-symbols-outlined text-base">logout</span>
            <span>Salir</span>
        </button>
    </header>

    <div class="flex min-h-0 flex-1">
        <aside class="hidden w-60 shrink-0 flex-col justify-between border-r border-[#20222a] p-4 md:flex" aria-label="Navegación principal">
            <div>
                <a href="/feed" class="mb-7 flex items-center gap-2 px-2 py-1.5">
                    <span class="font-mono text-base font-bold text-[#f58220]">&lt;//&gt;</span>
                    <span class="font-semibold tracking-tight text-[#e2e4e9]">FIREFLYONE</span>
                </a>
                <nav class="space-y-1">
                    <a href="/feed" class="flex items-center gap-3 rounded-lg border-l-2 border-[#f58220] bg-[#1e1c18] px-3 py-2 text-xs font-medium text-[#f58220]">
                        <span class="material-symbols-outlined text-base">home</span><span>Mi Feed</span>
                    </a>
                    <a href="#guardados" class="flex items-center gap-3 rounded-lg px-3 py-2 text-xs text-[#959aa7] transition hover:bg-[#15161b] hover:text-white">
                        <span class="material-symbols-outlined text-base text-[#757a87]">bookmark</span><span>Guardados</span>
                    </a>
                    <a href="#mas" class="flex items-center gap-3 rounded-lg px-3 py-2 text-xs text-[#959aa7] transition hover:bg-[#15161b] hover:text-white">
                        <span class="material-symbols-outlined text-base text-[#757a87]">more_horiz</span><span>Más opciones</span>
                    </a>
                    <div class="py-2"><div class="border-t border-[#1e2027]"></div></div>
                    <a href="#notificaciones" class="flex items-center justify-between rounded-lg px-3 py-2 text-xs text-[#959aa7] transition hover:bg-[#15161b] hover:text-white">
                        <span class="flex items-center gap-3"><span class="material-symbols-outlined text-base text-[#757a87]">notifications</span><span>Notificaciones</span></span>
                        <span class="flex h-4 w-4 items-center justify-center rounded-full bg-[#242630] text-[10px] font-medium text-[#9ea2af]">0</span>
                    </a>
                    <a href="/integrations" class="flex items-center gap-3 rounded-lg px-3 py-2 text-xs text-[#959aa7] transition hover:bg-[#15161b] hover:text-white">
                        <span class="material-symbols-outlined text-base text-[#757a87]">settings</span><span>Configuración</span>
                    </a>
                </nav>
            </div>

            <div class="space-y-4 border-t border-[#1e2027] pt-4">
                <div class="flex min-w-0 items-center gap-2.5">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full border border-[#363945] bg-[#272933] text-xs font-semibold text-[#f58220]">@{{ initials }}</div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-medium text-[#e2e4e9]">@{{ adviser?.name || 'Asesor' }}</p>
                        <p class="truncate text-[10px] text-[#6d717f]">@{{ adviser?.email || 'Asesor Certificado' }}</p>
                    </div>
                </div>
                <button @click="signOut" :disabled="signingOut" class="flex w-full items-center justify-between rounded-lg px-2 py-2 text-xs text-[#828695] transition hover:bg-[#1b1c22] hover:text-white disabled:opacity-60" type="button">
                    <span class="flex items-center gap-2"><span class="material-symbols-outlined text-base">logout</span>@{{ signingOut ? 'Cerrando sesión…' : 'Cerrar sesión' }}</span>
                    <span class="text-[10px] text-[#5e6372]">↵</span>
                </button>
                <div class="flex items-center justify-between px-1 text-xs text-[#828695]">
                    <span class="text-[11px]">Tema Oscuro</span>
                    <span class="relative flex h-4 w-8 items-center rounded-full bg-[#f58220]" aria-label="Tema oscuro activo"><span class="absolute right-0.5 h-3.5 w-3.5 rounded-full bg-black"></span></span>
                </div>
            </div>
        </aside>

        <main class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-5 overflow-y-auto px-4 py-5 sm:px-6" aria-label="Feed de contenidos">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#f58220]">NewsShare</p>
                    <h1 class="mt-1 text-lg font-semibold text-[#f0f2f5]">Mi Feed</h1>
                </div>
                <div class="flex items-center gap-2 rounded-lg border border-[#25272e] bg-[#16171b] px-3 py-2 text-[11px] text-[#9ea2af]">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    Contenido para compartir
                </div>
            </div>

            <div v-if="loading" class="space-y-4" aria-label="Cargando contenidos">
                <div v-for="n in 2" :key="n" class="animate-pulse space-y-3 rounded-xl border border-[#21232b] bg-[#141519] p-4">
                    <div class="h-9 w-40 rounded bg-[#20222a]"></div>
                    <div class="h-4 w-3/4 rounded bg-[#20222a]"></div>
                    <div class="h-3 w-full rounded bg-[#20222a]"></div>
                    <div class="h-48 rounded-lg bg-[#20222a]"></div>
                </div>
            </div>

            <div v-else-if="error" class="rounded-xl border border-[#3b2525] bg-[#1a1415] p-5 text-sm text-[#e7a2a2]" role="alert">
                <p>@{{ error }}</p>
                <button @click="loadContents" class="mt-3 rounded-md border border-[#483030] px-3 py-1.5 text-xs hover:bg-[#261a1b]" type="button">Intentar de nuevo</button>
            </div>

            <div v-else-if="contents.length === 0" class="rounded-xl border border-[#21232b] bg-[#141519] px-5 py-12 text-center">
                <span class="material-symbols-outlined text-3xl text-[#686d7a]">dynamic_feed</span>
                <p class="mt-3 text-sm font-medium text-[#e2e4e9]">Todavía no hay contenidos</p>
                <p class="mt-1 text-xs text-[#7a7e8b]">Los nuevos contenidos aparecerán aquí.</p>
            </div>

            <article v-for="content in contents" :key="content.id" class="space-y-3 rounded-xl border border-[#21232b] bg-[#141519] p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full border border-[#2f323c] bg-[#20222a] text-[11px] font-bold text-[#f58220]">F</div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <h2 class="text-xs font-semibold text-[#f0f2f5]">FIREFLYONE</h2>
                                <span class="material-symbols-outlined text-sm text-[#f58220]">verified</span>
                            </div>
                            <p class="text-[11px] text-[#6d7280]">Contenido para asesores</p>
                        </div>
                    </div>
                    <span class="rounded-full border border-[#342a20] bg-[#211b15] px-2 py-1 text-[10px] font-medium text-[#d9924f]">Marketing</span>
                </div>

                <div>
                    <h3 class="text-sm font-semibold leading-snug text-[#dedfe4]">@{{ content.title }}</h3>
                    <p class="mt-1.5 whitespace-pre-line text-xs leading-relaxed text-[#9195a1]">@{{ content.description }}</p>
                </div>

                <div v-if="content.images?.length" class="grid gap-2 overflow-hidden rounded-lg" :class="content.images.length > 1 ? 'grid-cols-2' : 'grid-cols-1'">
                    <a v-for="(image, index) in content.images.slice(0, 2)" :key="image" :href="image" target="_blank" rel="noopener noreferrer" class="block overflow-hidden bg-[#0c0d10]" :class="content.images.length > 1 ? 'aspect-[4/3]' : 'aspect-[16/8]'">
                        <img :src="image" :alt="`${content.title} — imagen ${index + 1}`" class="h-full w-full object-cover transition duration-300 hover:scale-[1.02]" loading="lazy">
                    </a>
                </div>

                <a v-if="content.purchase_url" :href="content.purchase_url" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between rounded-lg border border-[#20222a] bg-[#0c0d10] px-3.5 py-3 transition hover:border-[#4a3422] hover:bg-[#121317]">
                    <span class="text-[11px] font-medium text-[#c3c6d0]">Ver información y opciones de compra</span>
                    <span class="material-symbols-outlined text-base text-[#f58220]">arrow_outward</span>
                </a>

                <div class="share-bar border-t border-[#1d1f26] pt-3" data-status="collapsed">
                    <button class="share-bar__toggle" type="button" aria-label="Compartir esta publicación" aria-expanded="false">
                        <span class="material-symbols-outlined text-base">share</span>
                        <span>Compartir</span>
                        <span class="material-symbols-outlined share-bar__chevron text-sm">expand_more</span>
                    </button>
                    <ul class="share-bar__menu" aria-label="Opciones para compartir">
                        <li><button data-type="social" data-subtype="facebook" title="Compartir en Facebook" :data-url="shareUrl(content)" :data-title="content.title" type="button"><svg class="share-bar__brand-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073c0 6.03 4.388 11.034 10.125 11.927v-8.438H7.078v-3.489h3.047V9.398c0-3.025 1.792-4.701 4.533-4.701 1.312 0 2.686.236 2.686.236v2.953h-1.514c-1.491 0-1.955.93-1.955 1.885v2.302h3.328l-.532 3.489h-2.796V24C19.612 23.107 24 18.103 24 12.073Z"/></svg><span class="sr-only">Facebook</span></button></li>
                        <li><button data-type="social" data-subtype="whatsapp" title="Compartir en WhatsApp" :data-url="shareUrl(content)" :data-title="content.title" type="button"><svg class="share-bar__brand-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.52 3.48A11.8 11.8 0 0 0 12.12 0C5.5 0 .12 5.38.12 12c0 2.11.55 4.17 1.6 5.98L0 24l6.18-1.62A11.9 11.9 0 0 0 12.12 24h.01c6.62 0 12-5.38 12-12 0-3.2-1.25-6.22-3.61-8.52ZM12.13 21.96h-.01a9.94 9.94 0 0 1-5.07-1.39l-.36-.21-3.67.96.98-3.58-.23-.37A9.93 9.93 0 0 1 2.25 12c0-5.46 4.44-9.9 9.89-9.9 2.65 0 5.14 1.03 7.01 2.9a9.85 9.85 0 0 1 2.9 7c0 5.46-4.44 9.9-9.92 9.9Zm5.43-7.42c-.3-.15-1.78-.88-2.06-.98-.28-.1-.48-.15-.68.15-.2.3-.78.98-.96 1.18-.18.2-.35.22-.65.07-.3-.15-1.27-.47-2.41-1.49-.89-.79-1.49-1.77-1.67-2.07-.18-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.38-.02-.53-.07-.15-.68-1.63-.93-2.23-.24-.58-.49-.5-.68-.51h-.58c-.2 0-.53.07-.8.38-.28.3-1.06 1.03-1.06 2.5s1.09 2.9 1.24 3.1c.15.2 2.14 3.27 5.18 4.58.73.32 1.3.51 1.75.65.74.24 1.42.21 1.96.13.6-.09 1.78-.73 2.03-1.43.25-.7.25-1.3.18-1.43-.08-.13-.28-.2-.58-.35Z"/></svg><span class="sr-only">WhatsApp</span></button></li>
                        <li><button data-type="social" data-subtype="linkedin" title="Compartir en LinkedIn" :data-url="shareUrl(content)" :data-title="content.title" type="button"><svg class="share-bar__brand-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.447-2.136 2.94v5.666H9.351V9h3.414v1.561h.049c.476-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286ZM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124ZM3.56 20.452h3.554V9H3.56v11.452ZM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003Z"/></svg><span class="sr-only">LinkedIn</span></button></li>
                        <li><button data-type="social" data-subtype="twitter" title="Compartir en X" :data-url="shareUrl(content)" :data-title="content.title" type="button"><span class="share-bar__network-icon text-sm font-semibold">𝕏</span><span class="sr-only">X</span></button></li>
                        <li><button data-type="social" data-subtype="telegram" title="Compartir en Telegram" :data-url="shareUrl(content)" :data-title="content.title" type="button"><span class="material-symbols-outlined">send</span><span class="sr-only">Telegram</span></button></li>
                        <li><button data-type="social" data-subtype="instagram" title="Compartir en Instagram" :data-url="shareUrl(content)" :data-title="content.title" type="button"><svg class="share-bar__brand-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M7.8 2h8.4A5.8 5.8 0 0 1 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8A5.8 5.8 0 0 1 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2Zm0 2A3.8 3.8 0 0 0 4 7.8v8.4A3.8 3.8 0 0 0 7.8 20h8.4a3.8 3.8 0 0 0 3.8-3.8V7.8A3.8 3.8 0 0 0 16.2 4H7.8Zm4.2 3a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm5.25-3.5a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5Z"/></svg><span class="sr-only">Instagram</span></button></li>
                        <li><button data-type="social" data-subtype="tiktok" title="Compartir en TikTok" :data-url="shareUrl(content)" :data-title="content.title" type="button"><span class="share-bar__network-icon text-lg">♪</span><span class="sr-only">TikTok</span></button></li>
                        <li><button data-type="social" data-subtype="mail" title="Compartir por correo" :data-url="shareUrl(content)" :data-title="content.title" type="button"><span class="material-symbols-outlined">mail</span><span class="sr-only">Correo</span></button></li>
                        <li><button data-type="social" data-subtype="copylink" title="Copiar enlace" :data-url="shareUrl(content)" :data-title="content.title" type="button"><span class="material-symbols-outlined">link</span><span class="sr-only">Copiar enlace</span></button></li>
                    </ul>
                    <span class="share-bar__toast" role="status" aria-live="polite"></span>
                </div>

                <div class="mt-3 flex flex-wrap gap-2 border-t border-[#1d1f26] pt-3">
                    <button @click="publishContent(content, 'linkedin')" :disabled="publishing[content.id + '-linkedin']" class="rounded-lg border border-[#26394a] bg-[#141b21] px-3 py-2 text-[11px] font-medium text-[#80b8e8] transition hover:border-[#0a66c2] hover:bg-[#18242d] disabled:opacity-60" type="button">
                        @{{ publishing[content.id + '-linkedin'] ? 'Publicando…' : 'Publicar en LinkedIn' }}
                    </button>
                    <button @click="publishContent(content, 'tiktok')" :disabled="publishing[content.id + '-tiktok']" class="rounded-lg border border-[#35353a] bg-[#17171a] px-3 py-2 text-[11px] font-medium text-[#d7d8dd] transition hover:border-[#595a62] hover:bg-[#202024] disabled:opacity-60" type="button">
                        @{{ publishing[content.id + '-tiktok'] ? 'Enviando…' : 'Enviar borrador a TikTok' }}
                    </button>
                </div>
                <p v-if="publicationMessages[content.id + '-linkedin']" class="mt-2 rounded-md bg-[#10171d] px-3 py-2 text-[11px] text-[#a5c9e8]" role="status">@{{ publicationMessages[content.id + '-linkedin'] }}</p>
                <p v-if="publicationMessages[content.id + '-tiktok']" class="mt-2 rounded-md bg-[#17171a] px-3 py-2 text-[11px] text-[#bfc0c6]" role="status">@{{ publicationMessages[content.id + '-tiktok'] }}</p>
            </article>
        </main>

        <aside class="hidden w-72 shrink-0 space-y-5 overflow-y-auto border-l border-[#20222a] p-4 lg:block" aria-label="Descubrir contenido">
            <section>
                <h2 class="mb-2.5 text-xs font-semibold text-[#f0f2f5]">Temas en Tendencia</h2>
                <div class="flex flex-wrap gap-1.5 text-[11px] text-[#8c91a0]">
                    <a v-for="tag in ['#asesoria', '#proteccion', '#bienestar', '#patrimonio', '#familia']" :key="tag" href="#temas" class="rounded-md border border-[#21232c] bg-[#16171d] px-2 py-1 transition hover:bg-[#20222b] hover:text-white">@{{ tag }}</a>
                </div>
            </section>
            <section>
                <h2 class="mb-2.5 text-xs font-semibold text-[#f0f2f5]">Contenidos recientes</h2>
                <div v-if="contents.length" class="space-y-3">
                    <a v-for="content in contents.slice(0, 3)" :key="content.id" href="#feed" class="block text-xs leading-snug text-[#b8bcc8] transition hover:text-[#f58220]">@{{ content.title }}</a>
                </div>
                <p v-else class="text-[11px] text-[#6d7280]">Los contenidos aparecerán aquí.</p>
            </section>
            <div class="rounded-xl border border-[#25272e] bg-[#16171b] p-3.5">
                <p class="text-[11px] font-semibold text-[#e2e4e9]">Comparte con tus clientes</p>
                <p class="mt-1.5 text-[10px] leading-relaxed text-[#7a7e8b]">Usa los enlaces de cada publicación para conocer las opciones disponibles.</p>
            </div>
        </aside>
    </div>
</div>
</body>
</html>
