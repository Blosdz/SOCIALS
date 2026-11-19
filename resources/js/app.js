import { createApp } from 'vue';
import './share-bar.js';

const loginRoot = document.querySelector('#login-app');

if (loginRoot) {
    createApp({
    data() {
        return {
            email: '',
            password: '',
            showPassword: false,
            rememberDevice: true,
            loading: false,
            error: '',
            notice: '',
            success: '',
        };
    },
    methods: {
        async login() {
            this.loading = true;
            this.error = '';
            this.notice = '';
            this.success = '';

            try {
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email: this.email, password: this.password }),
                });
                const data = await response.json();

                if (!response.ok) {
                    this.error = data.errors?.email?.[0]
                        ?? data.errors?.password?.[0]
                        ?? data.message
                        ?? 'No fue posible iniciar sesión. Revisa tus credenciales.';
                    return;
                }

                const storage = this.rememberDevice ? localStorage : sessionStorage;
                localStorage.removeItem('adviser_token');
                localStorage.removeItem('adviser');
                sessionStorage.removeItem('adviser_token');
                sessionStorage.removeItem('adviser');
                storage.setItem('adviser_token', data.token);
                storage.setItem('adviser', JSON.stringify(data.adviser));
                window.location.assign('/feed');
            } catch {
                this.error = 'No pudimos conectar con el servidor. Inténtalo de nuevo.';
            } finally {
                this.loading = false;
            }
        },
        showComingSoon(feature) {
            this.notice = `${feature} estará disponible próximamente.`;
            this.error = '';
            this.success = '';
        },
    },
    }).mount(loginRoot);
}

const feedRoot = document.querySelector('#feed-app');

if (feedRoot) {
    createApp({
        data() {
            return {
                adviser: null,
                contents: [],
                loading: true,
                signingOut: false,
                error: '',
                publishing: {},
                publicationMessages: {},
            };
        },
        computed: {
            initials() {
                return (this.adviser?.name ?? 'Asesor')
                    .split(/\s+/)
                    .filter(Boolean)
                    .slice(0, 2)
                    .map((part) => part[0].toUpperCase())
                    .join('');
            },
        },
        async mounted() {
            const storedAdviser = localStorage.getItem('adviser') ?? sessionStorage.getItem('adviser');
            const token = localStorage.getItem('adviser_token') ?? sessionStorage.getItem('adviser_token');

            if (!token || !storedAdviser) {
                window.location.replace('/login');
                return;
            }

            try {
                this.adviser = JSON.parse(storedAdviser);
            } catch {
                localStorage.removeItem('adviser');
                sessionStorage.removeItem('adviser');
                window.location.replace('/login');
                return;
            }

            await this.loadContents();
        },
        methods: {
            async loadContents() {
                this.loading = true;
                this.error = '';

                try {
                    const token = localStorage.getItem('adviser_token') ?? sessionStorage.getItem('adviser_token');
                    const response = await fetch('/api/marketing-contents', {
                        headers: {
                            Accept: 'application/json',
                            Authorization: `Bearer ${token}`,
                        },
                    });
                    const payload = await response.json();

                    if (response.status === 401) {
                        localStorage.removeItem('adviser_token');
                        localStorage.removeItem('adviser');
                        sessionStorage.removeItem('adviser_token');
                        sessionStorage.removeItem('adviser');
                        window.location.replace('/login');
                        return;
                    }

                    if (!response.ok) {
                        throw new Error(payload.message ?? 'No fue posible cargar las publicaciones.');
                    }

                    this.contents = payload.data ?? [];
                } catch (error) {
                    this.error = error.message ?? 'No fue posible conectar con el servidor.';
                } finally {
                    this.loading = false;
                }
            },
            shareUrl(content) {
                const url = new URL(`/share/${content.id}`, window.location.origin);
                const slug = (value) => (value || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .trim()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-|-$/g, '');

                url.searchParams.set('name', slug(this.adviser?.name) || 'asesor');
                url.searchParams.set('adviser', btoa(String(this.adviser?.id ?? '')));
                url.searchParams.set('idmktcontent', btoa(String(content.id)));
                url.searchParams.set('title', slug(content.title));

                return url.toString();
            },
            async publishContent(content, provider) {
                if (provider === 'tiktok' && !window.confirm('Se enviarán las imágenes a TikTok como borrador. Tendrás que terminar la publicación desde la app de TikTok. ¿Continuar?')) return;

                const key = `${content.id}-${provider}`;
                this.publishing[key] = true;
                this.publicationMessages[key] = '';

                try {
                    const token = localStorage.getItem('adviser_token') ?? sessionStorage.getItem('adviser_token');
                    const response = await fetch(`/api/marketing-contents/${content.id}/publish`, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            Authorization: `Bearer ${token}`,
                        },
                        body: JSON.stringify({ provider }),
                    });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'No se pudo publicar el contenido.');
                    this.publicationMessages[key] = payload.message || 'Contenido enviado.';
                } catch (error) {
                    this.publicationMessages[key] = error.message || 'No se pudo publicar el contenido.';
                } finally {
                    this.publishing[key] = false;
                }
            },
            async signOut() {
                this.signingOut = true;
                const token = localStorage.getItem('adviser_token') ?? sessionStorage.getItem('adviser_token');

                try {
                    if (token) {
                        await fetch('/api/auth/logout', {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                Authorization: `Bearer ${token}`,
                            },
                        });
                    }
                } catch {
                    // Clear the local session even if the server cannot be reached.
                } finally {
                    localStorage.removeItem('adviser_token');
                    localStorage.removeItem('adviser');
                    sessionStorage.removeItem('adviser_token');
                    sessionStorage.removeItem('adviser');
                    window.location.assign('/login');
                }
            },
        },
    }).mount(feedRoot);
}

const integrationsRoot = document.querySelector('#integrations-app');

if (integrationsRoot) {
    createApp({
        data() {
            return {
                accounts: {},
                loading: true,
                connecting: '',
                disconnecting: '',
                error: '',
                notice: '',
                calls: [],
                callForm: { adviser_phone: '', client_phone: '', recording_consent: false },
                startingCall: false,
                callError: '',
                callNotice: '',
            };
        },
        async mounted() {
            if (!this.token()) {
                window.location.replace('/login');
                return;
            }

            const params = new URLSearchParams(window.location.search);
            if (params.has('connected')) this.notice = `${params.get('connected')} conectado correctamente.`;
            if (params.has('connection_error')) this.error = `No se pudo conectar ${params.get('connection_error')}. Revisa la configuración de la aplicación.`;

            await Promise.all([this.loadAccounts(), this.loadCalls()]);
        },
        methods: {
            token() {
                return localStorage.getItem('adviser_token') ?? sessionStorage.getItem('adviser_token');
            },
            async loadAccounts() {
                this.loading = true;
                try {
                    const response = await fetch('/api/social-accounts', {
                        headers: { Accept: 'application/json', Authorization: `Bearer ${this.token()}` },
                    });
                    if (response.status === 401) {
                        window.location.replace('/login');
                        return;
                    }
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'No fue posible cargar las conexiones.');
                    this.accounts = payload.data || {};
                } catch (error) {
                    this.error = error.message || 'No fue posible conectar con el servidor.';
                } finally {
                    this.loading = false;
                }
            },
            async loadCalls() {
                try {
                    const response = await fetch('/api/twilio/calls', {
                        headers: { Accept: 'application/json', Authorization: `Bearer ${this.token()}` },
                    });
                    if (response.status === 401) {
                        window.location.replace('/login');
                        return;
                    }
                    const payload = await response.json();
                    if (response.ok) this.calls = payload.data || [];
                } catch {
                    this.callError = 'No fue posible cargar el historial de llamadas.';
                }
            },
            async startCall() {
                this.startingCall = true;
                this.callError = '';
                this.callNotice = '';
                try {
                    const response = await fetch('/api/twilio/calls', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            Authorization: `Bearer ${this.token()}`,
                        },
                        body: JSON.stringify(this.callForm),
                    });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'No se pudo iniciar la llamada.');
                    this.callNotice = payload.message;
                    this.callForm.client_phone = '';
                    await this.loadCalls();
                } catch (error) {
                    this.callError = error.message || 'No se pudo iniciar la llamada.';
                } finally {
                    this.startingCall = false;
                }
            },
            async connect(provider) {
                this.connecting = provider;
                this.error = '';
                try {
                    const response = await fetch(`/api/social-accounts/${provider}/connect`, {
                        method: 'POST',
                        headers: { Accept: 'application/json', Authorization: `Bearer ${this.token()}` },
                    });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'No fue posible iniciar la conexión.');
                    window.location.assign(payload.authorization_url);
                } catch (error) {
                    this.error = error.message || 'No fue posible iniciar la conexión.';
                    this.connecting = '';
                }
            },
            async disconnect(provider) {
                this.disconnecting = provider;
                this.error = '';
                try {
                    const response = await fetch(`/api/social-accounts/${provider}`, {
                        method: 'DELETE',
                        headers: { Accept: 'application/json', Authorization: `Bearer ${this.token()}` },
                    });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'No fue posible desconectar la cuenta.');
                    delete this.accounts[provider];
                    this.notice = 'Cuenta desconectada.';
                } catch (error) {
                    this.error = error.message || 'No fue posible desconectar la cuenta.';
                } finally {
                    this.disconnecting = '';
                }
            },
        },
    }).mount(integrationsRoot);
}
