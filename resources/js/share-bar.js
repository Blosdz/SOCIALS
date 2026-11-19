function urlWithUtm(baseUrl, source) {
    const url = new URL(baseUrl, window.location.href);
    url.searchParams.set('utm_source', source);
    url.searchParams.set('utm_medium', 'social');
    url.searchParams.set('utm_campaign', 'adviser_share');

    return url.toString();
}

function showToast(container, message) {
    const toast = container.querySelector('.share-bar__toast');

    if (!toast) return;

    toast.textContent = message;
    toast.classList.add('is-visible');
    window.clearTimeout(toast._timer);
    toast._timer = window.setTimeout(() => toast.classList.remove('is-visible'), 3200);
}

async function copyLink(container, url, message) {
    try {
        await navigator.clipboard.writeText(url);
        showToast(container, message);
    } catch {
        showToast(container, 'No se pudo copiar. Inténtalo desde una página HTTPS.');
    }
}

async function handleShare(button, container) {
    const subtype = button.dataset.subtype;
    const title = button.dataset.title || document.title;
    const url = urlWithUtm(button.dataset.url, subtype === 'copylink' ? 'copylink' : subtype);
    const encodedUrl = encodeURIComponent(url);
    const encodedTitle = encodeURIComponent(title);
    let shareUrl;

    switch (subtype) {
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
            break;
        case 'whatsapp':
            shareUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(`${title} ${url}`)}`;
            break;
        case 'linkedin':
            shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`;
            break;
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`;
            break;
        case 'telegram':
            shareUrl = `https://t.me/share/url?url=${encodedUrl}&text=${encodedTitle}`;
            break;
        case 'mail':
            window.location.href = `mailto:?subject=${encodedTitle}&body=${encodedUrl}`;
            showToast(container, 'Abriendo tu aplicación de correo.');
            return;
        case 'instagram':
        case 'tiktok':
            if (navigator.share) {
                try {
                    await navigator.share({ title, url });
                    showToast(container, `Enlace listo para compartir en ${subtype === 'instagram' ? 'Instagram' : 'TikTok'}.`);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        showToast(container, 'No se pudo abrir el menú para compartir.');
                    }
                }
                return;
            }
            await copyLink(container, url, `Enlace copiado. Pégalo al compartir tu publicación en ${subtype === 'instagram' ? 'Instagram' : 'TikTok'}.`);
            return;
        case 'copylink':
            await copyLink(container, url, '¡Enlace copiado!');
            return;
        default:
            return;
    }

    window.open(shareUrl, '_blank', 'width=620,height=560,noopener,noreferrer');
    showToast(container, `Abriendo ${button.title.replace('Compartir en ', '')}.`);
}

document.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target : event.target.parentElement;
    const container = target?.closest('.share-bar');

    if (!container) return;

    const shareButton = target.closest('[data-type="social"]');

    if (shareButton) {
        event.preventDefault();
        handleShare(shareButton, container);
        return;
    }

    const toggle = target.closest('.share-bar__toggle');

    if (toggle) {
        const expanded = container.dataset.status !== 'expanded';
        container.dataset.status = expanded ? 'expanded' : 'collapsed';
        toggle.setAttribute('aria-expanded', String(expanded));
    }
});
