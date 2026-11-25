import { createApp } from 'vue';

const whatsappRoot = document.querySelector('#whatsapp-app');

if (whatsappRoot) {
    createApp({
        data() {
            return {
                adviser: null,
                signingOut: false,
                conversations: [],
                selected: null,
                messages: [],
                draft: '',
                loading: true,
                busy: false,
                error: '',
                notice: '',
                poller: null,
            };
        },
        computed: {
            initials() {
                return (this.adviser?.name ?? 'Asesor').split(/\s+/).filter(Boolean).slice(0, 2)
                    .map((part) => part[0].toUpperCase()).join('');
            },
            canReply() {
                return !!this.selected?.assigned_adviser_id
                    && this.selected.assigned_adviser_id === this.adviser?.id
                    && Date.now() - Date.parse(this.selected.last_inbound_at) < 24 * 60 * 60 * 1000;
            },
        },
        async mounted() {
            if (!this.token()) {
                window.location.replace('/login');
                return;
            }

            try {
                this.adviser = JSON.parse(localStorage.getItem('adviser') ?? sessionStorage.getItem('adviser'));
            } catch {
                window.location.replace('/login');
                return;
            }

            await this.loadConversations();
            this.poller = window.setInterval(async () => {
                if (document.hidden || this.busy) return;
                await this.loadConversations();
                if (this.selected) await this.loadMessages(this.selected.id);
            }, 10000);
        },
        beforeUnmount() {
            window.clearInterval(this.poller);
        },
        methods: {
            token() {
                return localStorage.getItem('adviser_token') ?? sessionStorage.getItem('adviser_token');
            },
            async request(path, options = {}) {
                const response = await fetch(path, {
                    ...options,
                    headers: {
                        Accept: 'application/json',
                        Authorization: `Bearer ${this.token()}`,
                        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                    },
                });

                if (response.status === 401) {
                    window.location.replace('/login');
                    throw new Error('Sesión vencida.');
                }

                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || 'No se pudo completar la solicitud.');

                return payload;
            },
            async loadConversations() {
                this.loading = true;
                try {
                    const payload = await this.request('/api/whatsapp/conversations');
                    this.conversations = payload.data || [];
                    if (this.selected) {
                        this.selected = this.conversations.find((item) => item.id === this.selected.id) || null;
                        if (!this.selected) this.messages = [];
                    }
                    this.error = '';
                } catch (error) {
                    this.error = error.message;
                } finally {
                    this.loading = false;
                }
            },
            async selectConversation(conversation) {
                this.selected = conversation;
                this.messages = [];
                this.notice = '';
                await this.loadMessages(conversation.id);
            },
            async loadMessages(id) {
                try {
                    const payload = await this.request(`/api/whatsapp/conversations/${id}`);
                    if (this.selected?.id !== id) return;
                    this.selected = payload.data;
                    this.messages = payload.messages || [];
                } catch (error) {
                    this.error = error.message;
                }
            },
            async sendMessage() {
                if (!this.canReply || !this.draft || this.busy) return;
                this.busy = true;
                this.error = '';
                this.notice = '';
                try {
                    await this.request(`/api/whatsapp/conversations/${this.selected.id}/messages`, {
                        method: 'POST',
                        body: JSON.stringify({ body: this.draft }),
                    });
                    this.draft = '';
                    await this.loadMessages(this.selected.id);
                    await this.loadConversations();
                } catch (error) {
                    this.error = error.message;
                } finally {
                    this.busy = false;
                }
            },
            formatTime(value) {
                return value ? new Date(value).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' }) : '';
            },
            statusLabel(message) {
                if (message.direction === 'incoming') return 'Recibido';
                return ({ submitting: 'Enviando', accepted: 'Aceptado', sent: 'Enviado', delivered: 'Entregado', read: 'Leído', failed: 'Falló', unknown: 'Sin confirmar' })[message.status] || message.status;
            },
            async signOut() {
                this.signingOut = true;
                try {
                    await this.request('/api/auth/logout', { method: 'POST' });
                } catch {
                    // The local session is cleared even if the server is unavailable.
                } finally {
                    localStorage.removeItem('adviser_token');
                    localStorage.removeItem('adviser');
                    sessionStorage.removeItem('adviser_token');
                    sessionStorage.removeItem('adviser');
                    window.location.assign('/login');
                }
            },
        },
    }).mount(whatsappRoot);
}
