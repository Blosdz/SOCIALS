@extends('app')

@section('title', 'Mi Feed · FIREFLYONE')
@section('theme-color', '#0c0d10')
@section('body-class', 'bg-[#0c0d10] font-sans text-[#dedfe4] antialiased selection:bg-[#f58220]/20 selection:text-[#f58220]')

@section('head')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
@endsection

@section('content')
<div id="feed-app" class="min-h-[calc(100vh-2.5rem)] bg-[#0f1013] pt-14 md:pl-60 md:pt-0">
    <x-feed-nav active="feed" />
    <div class="mx-auto flex w-full max-w-[1440px] border-x border-[#1a1c22]">
        <main class="page-enter mx-auto flex w-full max-w-6xl flex-1 flex-col gap-5 overflow-y-auto px-4 py-5 sm:px-6" aria-label="Feed de contenidos">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#f58220]">NewsShare</p>
                    <h1 class="mt-1 text-lg font-semibold text-[#f0f2f5]">Mi Feed</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="hidden items-center gap-2 rounded-lg border border-[#25272e] bg-[#16171b] px-3 py-2 text-[11px] text-[#9ea2af] sm:flex">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        Contenido para compartir
                    </span>
                    <div class="flex rounded-lg border border-[#25272e] bg-[#16171b] p-1" role="group" aria-label="Vista de noticias">
                        <button @click="viewMode = 'list'" :aria-pressed="viewMode === 'list'" :class="viewMode === 'list' ? 'bg-[#292b33] text-[#f0f2f5]' : 'text-[#8c91a0] hover:text-white'" class="flex items-center gap-1 rounded-md px-2 py-1 text-xs" type="button" title="Vista de lista">
                            <span class="material-symbols-outlined text-base">view_list</span><span class="sr-only">Lista</span>
                        </button>
                        <button @click="viewMode = 'grid'" :aria-pressed="viewMode === 'grid'" :class="viewMode === 'grid' ? 'bg-[#292b33] text-[#f0f2f5]' : 'text-[#8c91a0] hover:text-white'" class="flex items-center gap-1 rounded-md px-2 py-1 text-xs" type="button" title="Vista de cuadrícula">
                            <span class="material-symbols-outlined text-base">grid_view</span><span class="sr-only">Cuadrícula</span>
                        </button>
                    </div>
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

            <div v-else class="grid gap-4" :class="viewMode === 'grid' ? 'grid-cols-1 lg:grid-cols-2' : 'grid-cols-1'">
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

                <x-share-bar />
            </article>
            </div>
        </main>
    </div>
    <div v-if="publicationResult" class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4" @click.self="closePublicationResult" @keydown.esc="closePublicationResult">
        <section class="w-full max-w-md rounded-xl border border-[#30323c] bg-[#141519] p-6 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="publication-result-title" aria-describedby="publication-result-message">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full" :class="publicationResult.status === 'success' ? 'bg-emerald-950 text-emerald-300' : publicationResult.status === 'info' ? 'bg-amber-950 text-amber-300' : 'bg-red-950 text-red-300'">
                    <span class="material-symbols-outlined">@{{ publicationResult.status === 'success' ? 'check_circle' : publicationResult.status === 'info' ? 'info' : 'error' }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 id="publication-result-title" class="text-lg font-semibold text-white">@{{ publicationResult.title }}</h2>
                    <p class="mt-1 text-xs text-[#8d919d]">@{{ publicationResult.provider || 'LinkedIn' }} · @{{ publicationResult.contentTitle }}</p>
                    <p id="publication-result-message" class="mt-4 text-sm leading-6 text-[#c3c6d0]">@{{ publicationResult.message }}</p>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button ref="publicationResultClose" @click="closePublicationResult" class="rounded-lg bg-[#f58220] px-4 py-2 text-xs font-semibold text-black hover:bg-[#e07116]" type="button">Entendido</button>
            </div>
        </section>
    </div>
    <div v-if="tiktokPost" class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4" @click.self="closeTikTokPublisher">
        <section class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-xl border border-[#30323c] bg-[#141519] p-5 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="tiktok-publish-title">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="tiktok-publish-title" class="text-lg font-semibold text-white">Publicar en TikTok</h2>
                    <p class="mt-1 text-xs text-[#9ea2af]">Cuenta: @{{ tiktokCreator?.creator_nickname || 'TikTok' }}</p>
                </div>
                <button @click="closeTikTokPublisher" :disabled="tiktokSubmitting" aria-label="Cerrar" class="rounded-lg p-1 text-[#9ea2af] hover:bg-[#25272e] hover:text-white disabled:opacity-50" type="button"><span class="material-symbols-outlined">close</span></button>
            </div>
            <p v-if="tiktokError" class="mt-4 rounded-lg bg-red-950/40 px-3 py-2 text-xs text-red-300" role="alert">@{{ tiktokError }}</p>
            <div class="mt-4 rounded-lg border border-[#25272e] bg-[#101114] p-3">
                <p class="text-xs font-medium text-white">Vista previa</p>
                <img v-if="tiktokPost.content.images?.length" :src="tiktokPost.content.images[0]" :alt="tiktokPost.title" class="mt-2 max-h-44 w-full rounded-md object-cover">
                <p class="mt-2 text-xs font-semibold text-white">@{{ tiktokPost.title }}</p>
                <p class="mt-1 whitespace-pre-line text-xs text-[#9ea2af]">@{{ tiktokPost.description }}</p>
            </div>
            <form @submit.prevent="submitTikTokPost" class="mt-4 space-y-4">
                <label class="block text-xs font-medium text-[#c3c6d0]">Título
                    <input v-model.trim="tiktokPost.title" required maxlength="90" class="mt-1.5 w-full rounded-lg border border-[#30323c] bg-[#0f1013] px-3 py-2 text-sm text-white focus:border-[#f58220] focus:outline-none">
                </label>
                <label class="block text-xs font-medium text-[#c3c6d0]">Descripción
                    <textarea v-model.trim="tiktokPost.description" maxlength="4000" rows="3" class="mt-1.5 w-full rounded-lg border border-[#30323c] bg-[#0f1013] px-3 py-2 text-sm text-white focus:border-[#f58220] focus:outline-none"></textarea>
                </label>
                <label class="block text-xs font-medium text-[#c3c6d0]">Privacidad
                    <select v-model="tiktokPost.privacy_level" required class="mt-1.5 w-full rounded-lg border border-[#30323c] bg-[#0f1013] px-3 py-2 text-sm text-white focus:border-[#f58220] focus:outline-none">
                        <option value="" disabled>Selecciona quién puede ver la publicación</option>
                        <option v-for="option in tiktokCreator.privacy_level_options" :key="option" :value="option">@{{ tiktokPrivacyLabel(option) }}</option>
                    </select>
                </label>
                <label class="flex items-center gap-2 text-xs text-[#c3c6d0]">
                    <input v-model="tiktokPost.allow_comment" :disabled="tiktokCreator.comment_disabled" type="checkbox" class="accent-[#f58220]"> Permitir comentarios
                </label>
                <label class="flex items-center gap-2 text-xs text-[#c3c6d0]">
                    <input v-model="tiktokPost.commercial_disclosure" type="checkbox" class="accent-[#f58220]"> Este contenido promociona una marca, producto o servicio
                </label>
                <div v-if="tiktokPost.commercial_disclosure" class="space-y-2 rounded-lg border border-[#30323c] p-3 text-xs text-[#c3c6d0]">
                    <label class="flex items-center gap-2"><input v-model="tiktokPost.brand_organic" type="checkbox" class="accent-[#f58220]"> Mi marca o negocio</label>
                    <label class="flex items-center gap-2"><input v-model="tiktokPost.brand_content" :disabled="tiktokPost.privacy_level === 'SELF_ONLY'" type="checkbox" class="accent-[#f58220]"> Asociación pagada con otra marca</label>
                    <p v-if="tiktokPost.brand_content && tiktokPost.privacy_level === 'SELF_ONLY'" class="text-amber-300">Una asociación pagada no puede tener privacidad «Solo yo».</p>
                    <p v-else-if="tiktokPost.brand_content" class="text-[#9ea2af]">La publicación se etiquetará como asociación pagada.</p>
                    <p v-else-if="tiktokPost.brand_organic" class="text-[#9ea2af]">La publicación se etiquetará como contenido promocional.</p>
                </div>
                <label class="flex items-start gap-2 text-xs leading-relaxed text-[#c3c6d0]">
                    <input v-model="tiktokPost.terms_accepted" required type="checkbox" class="mt-0.5 accent-[#f58220]">
                    <span>Al publicar, acepto la <a href="https://www.tiktok.com/legal/page/global/music-usage-confirmation/en" target="_blank" rel="noopener noreferrer" class="text-[#f58220] underline">confirmación de uso de música</a><span v-if="tiktokPost.brand_content"> y la <a href="https://www.tiktok.com/legal/page/global/bc-policy/en" target="_blank" rel="noopener noreferrer" class="text-[#f58220] underline">política de contenido de marca</a></span> de TikTok.</span>
                </label>
                <button :disabled="!canSubmitTikTokPost || tiktokSubmitting" class="rounded-lg bg-[#f58220] px-4 py-2.5 text-xs font-semibold text-black disabled:cursor-not-allowed disabled:opacity-50" type="submit">@{{ tiktokSubmitting ? 'Publicando…' : 'Publicar por API en TikTok' }}</button>
                <p class="text-xs text-[#8d919d]">TikTok puede tardar unos minutos en procesar la publicación.</p>
            </form>
        </section>
    </div>
</div>
@endsection
