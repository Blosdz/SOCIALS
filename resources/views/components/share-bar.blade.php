<div class="share-bar border-t border-[#1d1f26] pt-3" data-status="collapsed">
    <button class="share-bar__toggle" type="button" aria-label="Compartir esta publicación" aria-expanded="false">
        <span class="material-symbols-outlined text-base">share</span>
        <span>Compartir</span>
        <span class="material-symbols-outlined share-bar__chevron text-sm">expand_more</span>
    </button>
    <ul class="share-bar__menu" aria-label="Opciones para compartir">
        <li>
            <button data-type="social" data-subtype="facebook" title="Compartir en Facebook" aria-label="Compartir en Facebook" :data-url="shareUrl(content)" :data-title="content.title" type="button">
                <svg class="share-bar__brand-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073c0 6.03 4.388 11.034 10.125 11.927v-8.438H7.078v-3.489h3.047V9.398c0-3.025 1.792-4.701 4.533-4.701 1.312 0 2.686.236 2.686.236v2.953h-1.514c-1.491 0-1.955.93-1.955 1.885v2.302h3.328l-.532 3.489h-2.796V24C19.612 23.107 24 18.103 24 12.073Z"/></svg>
            </button>
        </li>
        <li>
            <button data-type="social" data-subtype="whatsapp" title="Compartir en WhatsApp" aria-label="Compartir en WhatsApp" :data-url="shareUrl(content)" :data-title="content.title" type="button">
                <svg class="share-bar__brand-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.52 3.48A11.8 11.8 0 0 0 12.12 0C5.5 0 .12 5.38.12 12c0 2.11.55 4.17 1.6 5.98L0 24l6.18-1.62A11.9 11.9 0 0 0 12.12 24h.01c6.62 0 12-5.38 12-12 0-3.2-1.25-6.22-3.61-8.52ZM12.13 21.96h-.01a9.94 9.94 0 0 1-5.07-1.39l-.36-.21-3.67.96.98-3.58-.23-.37A9.93 9.93 0 0 1 2.25 12c0-5.46 4.44-9.9 9.89-9.9 2.65 0 5.14 1.03 7.01 2.9a9.85 9.85 0 0 1 2.9 7c0 5.46-4.44 9.9-9.92 9.9Zm5.43-7.42c-.3-.15-1.78-.88-2.06-.98-.28-.1-.48-.15-.68.15-.2.3-.78.98-.96 1.18-.18.2-.35.22-.65.07-.3-.15-1.27-.47-2.41-1.49-.89-.79-1.49-1.77-1.67-2.07-.18-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.38-.02-.53-.07-.15-.68-1.63-.93-2.23-.24-.58-.49-.5-.68-.51h-.58c-.2 0-.53.07-.8.38-.28.3-1.06 1.03-1.06 2.5s1.09 2.9 1.24 3.1c.15.2 2.14 3.27 5.18 4.58.73.32 1.3.51 1.75.65.74.24 1.42.21 1.96.13.6-.09 1.78-.73 2.03-1.43.25-.7.25-1.3.18-1.43-.08-.13-.28-.2-.58-.35Z"/></svg>
            </button>
        </li>
        <li>
            <button @click="publishContent(content, 'linkedin')" :disabled="publishing[content.id + '-linkedin']" title="Publicar por API en LinkedIn" aria-label="Publicar por API en LinkedIn" type="button">
                <svg class="share-bar__brand-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.447-2.136 2.94v5.666H9.351V9h3.414v1.561h.049c.476-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286ZM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124ZM3.56 20.452h3.554V9H3.56v11.452ZM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003Z"/></svg>
            </button>
        </li>
        <li>
            <button @click="openTikTokPublisher(content)" :disabled="tiktokLoading" title="Publicar por API en TikTok" aria-label="Publicar por API en TikTok" type="button">
                <span class="share-bar__network-icon text-lg">♪</span>
            </button>
        </li>
    </ul>
    <span class="share-bar__toast" role="status" aria-live="polite"></span>
    <p v-if="publicationMessages[content.id + '-tiktok']" class="mt-2 text-xs text-[#c3c6d0]" role="status">@{{ publicationMessages[content.id + '-tiktok'] }}</p>
</div>
