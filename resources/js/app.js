import { createApp, markRaw } from 'vue';
import { Device } from '@twilio/voice-sdk';
import './share-bar.js';
import './whatsapp.js';

async function voiceToken(adviserToken) {
    const response = await fetch('/api/twilio/token', {
        headers: { Accept: 'application/json', Authorization: `Bearer ${adviserToken}` },
    });
    const payload = await response.json();
    if (response.status === 401) {
        window.location.replace('/login');
        throw new Error('Sesión vencida.');
    }
    if (!response.ok) throw new Error(payload.message || 'No se pudo preparar el teléfono del navegador.');

    return payload.token;
}

async function startBrowserCall(component, clientPhone, recordingConsent) {
    const adviserToken = component.token();
    const token = await voiceToken(adviserToken);
    const device = markRaw(new Device(token));
    component.voiceDevice = device;
    let callId = null;

    device.on('error', (error) => {
        component.callError = error.message || 'Error de conexión de Twilio.';
    });
    device.on('tokenWillExpire', async () => {
        try {
            await device.updateToken(await voiceToken(adviserToken));
        } catch (error) {
            component.callError = error.message || 'No se pudo renovar la sesión de voz.';
        }
    });

    try {
        const response = await fetch('/api/twilio/calls', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                Authorization: `Bearer ${adviserToken}`,
            },
            body: JSON.stringify({ client_phone: clientPhone, recording_consent: recordingConsent }),
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(Object.values(payload.errors || {})[0]?.[0] || payload.message || 'No se pudo iniciar la llamada.');
        callId = payload.call.id;

        component.callStatus = 'Conectando el micrófono…';
        const call = markRaw(await device.connect({ params: { CallId: String(callId) } }));
        component.activeCall = call;
        component.callStatus = 'Conectado a Twilio. Esperando al cliente…';
        const finish = () => {
            if (component.activeCall === call) finishBrowserCall(component);
        };
        call.on('disconnect', finish);
        call.on('cancel', finish);
        call.on('reject', finish);
        call.on('error', (error) => {
            component.callError = error.message || 'La llamada se interrumpió.';
            finish();
        });
    } catch (error) {
        device.destroy();
        if (callId) {
            try {
                await fetch(`/api/twilio/calls/${callId}/cancel`, {
                    method: 'POST',
                    headers: { Accept: 'application/json', Authorization: `Bearer ${adviserToken}` },
                });
            } catch {
                // The original connection error is shown to the adviser.
            }
        }
        component.voiceDevice = null;
        component.callStatus = '';
        throw error;
    }
}

function finishBrowserCall(component) {
    component.activeCall = null;
    component.voiceDevice?.destroy();
    component.voiceDevice = null;
    component.callNotice = '';
    component.callStatus = 'Llamada finalizada';
    if (typeof component.loadCalls === 'function') component.loadCalls();
}

function hangupBrowserCall(component) {
    component.activeCall?.disconnect();
    component.voiceDevice?.disconnectAll();
    finishBrowserCall(component);
}

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

async function signOut() {
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
}

const feedRoot = document.querySelector('#feed-app');

if (feedRoot) {
    createApp({
        data() {
            return {
                adviser: null,
                contents: [],
                viewMode: 'list',
                loading: true,
                signingOut: false,
                error: '',
                publishing: {},
                publicationMessages: {},
                publicationResult: null,
                tiktokLoading: false,
                tiktokSubmitting: false,
                tiktokCreator: null,
                tiktokPost: null,
                tiktokError: '',
                tiktokPollTimer: null,
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
            canSubmitTikTokPost() {
                const post = this.tiktokPost;
                return !!post && !!post.title && !!post.privacy_level && !!post.terms_accepted
                    && (!post.commercial_disclosure || post.brand_organic || post.brand_content)
                    && !(post.brand_content && post.privacy_level === 'SELF_ONLY');
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
                if (provider !== 'linkedin') return;

                const key = `${content.id}-linkedin`;
                if (this.publishing[key]) return;
                this.publishing[key] = true;

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
                    this.publicationResult = {
                        status: response.ok ? 'success' : payload.code === 'duplicate_post' ? 'info' : 'error',
                        title: response.ok ? 'Publicado en LinkedIn' : payload.code === 'duplicate_post' ? 'Contenido duplicado' : 'No se pudo publicar',
                        contentTitle: content.title,
                        message: payload.message || (response.ok ? 'El contenido se publicó en LinkedIn.' : 'No se pudo publicar el contenido.'),
                    };
                } catch (error) {
                    this.publicationResult = {
                        status: 'error',
                        title: 'No se pudo publicar',
                        contentTitle: content.title,
                        message: error.message || 'No se pudo conectar con el servidor.',
                    };
                } finally {
                    this.publishing[key] = false;
                    this.$nextTick(() => this.$refs.publicationResultClose?.focus());
                }
            },
            closePublicationResult() {
                this.publicationResult = null;
            },
            showTikTokResult(status, title, contentTitle, message) {
                this.publicationResult = { status, title, provider: 'TikTok', contentTitle, message };
                this.$nextTick(() => this.$refs.publicationResultClose?.focus());
            },
            tiktokPrivacyLabel(option) {
                return {
                    PUBLIC_TO_EVERYONE: 'Todos',
                    MUTUAL_FOLLOW_FRIENDS: 'Amigos',
                    FOLLOWER_OF_CREATOR: 'Seguidores',
                    SELF_ONLY: 'Solo yo',
                }[option] || option;
            },
            async openTikTokPublisher(content) {
                const key = `${content.id}-tiktok`;
                this.publicationMessages[key] = '';

                if (!content.images?.length) {
                    this.publicationMessages[key] = 'TikTok necesita al menos una imagen para esta publicación.';
                    return;
                }

                this.tiktokLoading = true;
                try {
                    const token = localStorage.getItem('adviser_token') ?? sessionStorage.getItem('adviser_token');
                    const response = await fetch('/api/tiktok/creator-info', {
                        headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
                    });
                    const payload = await response.json();
                    if (response.status === 401) {
                        window.location.replace('/login');
                        return;
                    }
                    if (!response.ok) throw new Error(payload.message || 'No se pudo leer la cuenta de TikTok.');
                    this.tiktokCreator = payload.data;
                    this.tiktokPost = {
                        content,
                        title: (content.title || '').slice(0, 90),
                        description: (content.description || '').slice(0, 4000),
                        privacy_level: '',
                        allow_comment: false,
                        commercial_disclosure: false,
                        brand_organic: false,
                        brand_content: false,
                        terms_accepted: false,
                    };
                    this.tiktokError = '';
                } catch (error) {
                    this.publicationMessages[key] = error.message || 'No se pudo preparar TikTok.';
                } finally {
                    this.tiktokLoading = false;
                }
            },
            closeTikTokPublisher() {
                if (!this.tiktokSubmitting) this.tiktokPost = null;
            },
            async submitTikTokPost() {
                if (!this.canSubmitTikTokPost || this.tiktokSubmitting) return;
                const post = this.tiktokPost;
                const key = `${post.content.id}-tiktok`;
                this.tiktokSubmitting = true;
                this.tiktokError = '';

                try {
                    const token = localStorage.getItem('adviser_token') ?? sessionStorage.getItem('adviser_token');
                    const response = await fetch(`/api/marketing-contents/${post.content.id}/publish`, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            Authorization: `Bearer ${token}`,
                        },
                        body: JSON.stringify({
                            provider: 'tiktok',
                            title: post.title,
                            description: post.description,
                            privacy_level: post.privacy_level,
                            allow_comment: post.allow_comment,
                            commercial_disclosure: post.commercial_disclosure,
                            brand_organic: post.commercial_disclosure && post.brand_organic,
                            brand_content: post.commercial_disclosure && post.brand_content,
                            terms_accepted: post.terms_accepted,
                        }),
                    });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(Object.values(payload.errors || {})[0]?.[0] || payload.message || 'TikTok rechazó la publicación.');
                    this.publicationMessages[key] = payload.message || 'TikTok está procesando la publicación.';
                    this.tiktokPost = null;
                    this.showTikTokResult('info', 'Publicación en proceso', post.content.title, this.publicationMessages[key]);
                    if (payload.publication_id) this.pollTikTokPublication(post.content.id, payload.publication_id);
                } catch (error) {
                    this.tiktokError = error.message || 'No se pudo publicar en TikTok.';
                } finally {
                    this.tiktokSubmitting = false;
                }
            },
            async pollTikTokPublication(contentId, publicationId, attempt = 0) {
                const key = `${contentId}-tiktok`;
                const contentTitle = this.contents.find((content) => content.id === contentId)?.title || 'Contenido';
                try {
                    const token = localStorage.getItem('adviser_token') ?? sessionStorage.getItem('adviser_token');
                    const response = await fetch(`/api/social-publications/${publicationId}/status`, {
                        headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
                    });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'No se pudo consultar el estado.');
                    if (payload.status === 'published') {
                        this.publicationMessages[key] = payload.message || 'Publicado en TikTok.';
                        this.showTikTokResult('success', 'Publicado en TikTok', contentTitle, this.publicationMessages[key]);
                        return;
                    }
                    if (payload.status === 'failed') {
                        this.publicationMessages[key] = payload.message || 'TikTok no pudo publicar este contenido.';
                        this.showTikTokResult('error', 'No se pudo publicar en TikTok', contentTitle, this.publicationMessages[key]);
                        return;
                    }
                    this.publicationMessages[key] = 'TikTok está procesando la publicación.';
                } catch (error) {
                    this.publicationMessages[key] = error.message || 'No se pudo consultar TikTok.';
                    this.showTikTokResult('error', 'No se pudo consultar TikTok', contentTitle, this.publicationMessages[key]);
                    return;
                }
                if (attempt < 11) {
                    this.tiktokPollTimer = window.setTimeout(() => this.pollTikTokPublication(contentId, publicationId, attempt + 1), 5000);
                } else {
                    this.publicationMessages[key] = 'TikTok sigue procesando la publicación. Revisa tu cuenta más tarde.';
                    this.showTikTokResult('info', 'Publicación en proceso', contentTitle, this.publicationMessages[key]);
                }
            },
            signOut,
        },
        beforeUnmount() {
            window.clearTimeout(this.tiktokPollTimer);
        },
    }).mount(feedRoot);
}

const integrationsRoot = document.querySelector('#integrations-app');

if (integrationsRoot) {
    createApp({
        data() {
            return {
                adviser: null,
                accounts: {},
                loading: true,
                signingOut: false,
                connecting: '',
                disconnecting: '',
                error: '',
                notice: '',
                waLoading: true,
                waConfigured: false,
                waConnected: [],
                waAvailable: [],
                waSelected: '',
                waBusy: false,
                waError: '',
                waNotice: '',
                calls: [],
                callForm: { client_phone: '', recording_consent: false },
                startingCall: false,
                callError: '',
                callNotice: '',
                callStatus: '',
                voiceDevice: null,
                activeCall: null,
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
            if (!this.token()) {
                window.location.replace('/login');
                return;
            }

            const storedAdviser = localStorage.getItem('adviser') ?? sessionStorage.getItem('adviser');

            try {
                this.adviser = storedAdviser ? JSON.parse(storedAdviser) : null;
            } catch {
                this.adviser = null;
            }

            const params = new URLSearchParams(window.location.search);
            if (params.has('connected')) this.notice = `${params.get('connected')} conectado correctamente.`;
            if (params.has('connection_error')) this.error = `No se pudo conectar ${params.get('connection_error')}. Revisa la configuración de la aplicación.`;

            await Promise.all([this.loadAccounts(), this.loadCalls(), this.loadWhatsappNumbers()]);
        },
        methods: {
            signOut,
            token() {
                return localStorage.getItem('adviser_token') ?? sessionStorage.getItem('adviser_token');
            },
            async loadWhatsappNumbers() {
                this.waLoading = true;
                try {
                    const response = await fetch('/api/whatsapp/numbers', {
                        headers: { Accept: 'application/json', Authorization: `Bearer ${this.token()}` },
                    });
                    if (response.status === 401) {
                        window.location.replace('/login');
                        return;
                    }
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'No se pudieron cargar los números de WhatsApp.');
                    this.waConfigured = payload.configured;
                    this.waConnected = payload.connected || [];
                    this.waAvailable = payload.available || [];
                    if (!this.waAvailable.some((item) => item.phone_number_id === this.waSelected)) this.waSelected = '';
                    this.waError = '';
                } catch (error) {
                    this.waError = error.message || 'No se pudo consultar WhatsApp.';
                } finally {
                    this.waLoading = false;
                }
            },
            async connectWhatsappNumber() {
                if (!this.waSelected || this.waBusy) return;
                this.waBusy = true;
                this.waError = '';
                this.waNotice = '';
                try {
                    const response = await fetch('/api/whatsapp/numbers', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            Authorization: `Bearer ${this.token()}`,
                        },
                        body: JSON.stringify({ phone_number_id: this.waSelected }),
                    });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'No se pudo conectar el número.');
                    this.waNotice = payload.message;
                    await this.loadWhatsappNumbers();
                } catch (error) {
                    this.waError = error.message || 'No se pudo conectar el número.';
                } finally {
                    this.waBusy = false;
                }
            },
            async disconnectWhatsappNumber(phoneNumberId) {
                if (this.waBusy) return;
                this.waBusy = true;
                this.waError = '';
                this.waNotice = '';
                try {
                    const response = await fetch(`/api/whatsapp/numbers/${encodeURIComponent(phoneNumberId)}`, {
                        method: 'DELETE',
                        headers: { Accept: 'application/json', Authorization: `Bearer ${this.token()}` },
                    });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'No se pudo desconectar el número.');
                    this.waNotice = payload.message;
                    await this.loadWhatsappNumbers();
                } catch (error) {
                    this.waError = error.message || 'No se pudo desconectar el número.';
                } finally {
                    this.waBusy = false;
                }
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
                if (this.startingCall || this.activeCall) return;
                this.startingCall = true;
                this.callError = '';
                this.callNotice = '';
                try {
                    await startBrowserCall(this, this.callForm.client_phone, this.callForm.recording_consent);
                    this.callNotice = 'Micrófono conectado. Esperando al cliente.';
                    this.callForm.client_phone = '';
                    this.callForm.recording_consent = false;
                    await this.loadCalls();
                } catch (error) {
                    this.callError = error.message || 'No se pudo iniciar la llamada.';
                } finally {
                    this.startingCall = false;
                }
            },
            hangupCall() {
                hangupBrowserCall(this);
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

const contactsRoot = document.querySelector('#contacts-app');

if (contactsRoot) {
    createApp({
        data() {
            return {
                adviser: null,
                signingOut: false,
                customers: [],
                loading: true,
                listError: '',
                customerForm: { name: '', dni: '', email: '', phone: '', extension: '' },
                showCustomerModal: false,
                saving: false,
                formError: '',
                formNotice: '',
                selectedCustomer: null,
                recordingConsent: false,
                calling: false,
                callError: '',
                callNotice: '',
                callStatus: '',
                voiceDevice: null,
                activeCall: null,
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
        mounted() {
            if (!this.token()) {
                window.location.replace('/login');
                return;
            }

            const storedAdviser = localStorage.getItem('adviser') ?? sessionStorage.getItem('adviser');

            try {
                this.adviser = storedAdviser ? JSON.parse(storedAdviser) : null;
            } catch {
                this.adviser = null;
            }

            this.loadCustomers();
        },
        methods: {
            signOut,
            token() {
                return localStorage.getItem('adviser_token') ?? sessionStorage.getItem('adviser_token');
            },
            openCustomerModal() {
                this.formError = '';
                this.formNotice = '';
                this.showCustomerModal = true;
                this.$nextTick(() => this.$refs.customerNameInput?.focus());
            },
            closeCustomerModal() {
                if (this.saving) return;

                this.showCustomerModal = false;
                this.formError = '';
                this.$nextTick(() => this.$refs.addCustomerButton?.focus());
            },
            async loadCustomers() {
                this.loading = true;
                this.listError = '';

                try {
                    const response = await fetch('/api/customers', {
                        headers: { Accept: 'application/json', Authorization: `Bearer ${this.token()}` },
                    });
                    if (response.status === 401) {
                        window.location.replace('/login');
                        return;
                    }

                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'No se pudieron cargar los clientes.');
                    this.customers = payload.data || [];
                } catch (error) {
                    this.listError = error.message || 'No se pudo conectar con el servidor.';
                } finally {
                    this.loading = false;
                }
            },
            async createCustomer() {
                this.saving = true;
                this.formError = '';
                this.formNotice = '';

                try {
                    const response = await fetch('/api/customers', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            Authorization: `Bearer ${this.token()}`,
                        },
                        body: JSON.stringify({
                            ...this.customerForm,
                            extension: this.customerForm.extension || null,
                        }),
                    });
                    if (response.status === 401) {
                        window.location.replace('/login');
                        return;
                    }

                    const payload = await response.json();
                    if (!response.ok) {
                        throw new Error(Object.values(payload.errors || {})[0]?.[0] || payload.message || 'No se pudo guardar el cliente.');
                    }

                    this.customers.push(payload.data);
                    this.customers.sort((first, second) => first.name.localeCompare(second.name));
                    this.customerForm = { name: '', dni: '', email: '', phone: '', extension: '' };
                    this.showCustomerModal = false;
                    this.formNotice = 'Cliente guardado.';
                    this.$nextTick(() => this.$refs.addCustomerButton?.focus());
                } catch (error) {
                    this.formError = error.message || 'No se pudo conectar con el servidor.';
                } finally {
                    this.saving = false;
                }
            },
            selectCustomer(customer) {
                this.selectedCustomer = customer;
                this.callError = '';
                this.callNotice = '';
            },
            async startCall() {
                if (!this.selectedCustomer || this.calling || this.activeCall) return;

                this.calling = true;
                this.callError = '';
                this.callNotice = '';

                try {
                    await startBrowserCall(this, this.selectedCustomer.phone, this.recordingConsent);
                    this.callNotice = 'Micrófono conectado. Esperando al cliente.';
                    this.recordingConsent = false;
                } catch (error) {
                    this.callError = error.message || 'No se pudo conectar con el servidor.';
                } finally {
                    this.calling = false;
                }
            },
            hangupCall() {
                hangupBrowserCall(this);
            },
        },
    }).mount(contactsRoot);
}
