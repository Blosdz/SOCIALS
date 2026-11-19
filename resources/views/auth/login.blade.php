<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b0c10">
    <title>Iniciar sesión · FIREFLYONE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;500;600;700&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col justify-between bg-[#0b0c10] font-body text-[#f8fafc] selection:bg-[#f97316]/20 selection:text-[#f97316]">
    <main id="login-app" class="flex w-full flex-grow items-center justify-center bg-[#0b0c10] p-5 sm:p-6">
        <div class="mx-auto flex w-full max-w-7xl flex-col px-1 py-4 sm:px-4">
            <div class="grid grid-cols-1 items-center gap-8 lg:grid-cols-12 lg:gap-12">
                <div class="flex w-full justify-center lg:col-span-12">
                    <section class="relative w-full max-w-md overflow-hidden rounded-2xl border border-[#262938] bg-[#14161f] p-7 shadow-2xl ring-1 ring-white/5 sm:p-9" aria-labelledby="login-title">
                        <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-transparent via-[#f97316] to-transparent"></div>

                        <header class="mb-6 space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-lg font-bold text-[#f97316]">&lt;//&gt;</span>
                                <span class="font-headline text-2xl font-bold tracking-tight text-[#f8fafc]">FIREFLYONE</span>
                                <span class="rounded-full border border-[#4d2d1d] bg-[#2a1c14] px-2 py-0.5 font-body text-[10px] font-bold uppercase tracking-widest text-[#f97316]">Adviso PRO</span>
                                <span class="ml-auto h-1.5 w-1.5 rounded-full bg-[#f97316]" aria-hidden="true"></span>
                            </div>
                            <div>
                                <h1 id="login-title" class="font-headline text-3xl font-semibold tracking-tight text-[#f8fafc]">Iniciar Sesión</h1>
                                <p class="mt-1 font-body text-xs text-[#94a3b8] sm:text-sm">Ingresa con tus credenciales de asesor certificado</p>
                            </div>
                        </header>

                        <div class="space-y-3">
                            <button @click="showComingSoon('Facebook Business')" class="flex w-full items-center justify-center gap-3 rounded-lg border border-[#2c3040] bg-[#1a1d26] px-4 py-2.5 text-xs font-semibold text-[#f8fafc] shadow-sm transition-all duration-200 hover:border-[#3b4156] hover:bg-[#222633] active:scale-[0.99]" type="button">
                                <svg class="h-4 w-4 fill-[#1877F2]" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                <span>Continuar con Facebook Business</span>
                            </button>
                            <button @click="showComingSoon('Google Workspace')" class="flex w-full items-center justify-center gap-3 rounded-lg border border-[#2c3040] bg-[#1a1d26] px-4 py-2.5 text-xs font-semibold text-[#f8fafc] shadow-sm transition-all duration-200 hover:border-[#3b4156] hover:bg-[#222633] active:scale-[0.99]" type="button">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true"><path d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17z" fill="#4285F4"/><path d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24z" fill="#34A853"/><path d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.14-1.55.38-2.27V6.58H1.25C.45 8.16 0 9.97 0 12s.45 3.84 1.25 5.42l4.03-3.15z" fill="#FBBC05"/><path d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z" fill="#EA4335"/></svg>
                                <span>Continuar con Google Workspace</span>
                            </button>
                        </div>

                        <div class="relative my-6 flex items-center justify-center">
                            <div class="h-px w-full bg-[#262936]"></div>
                            <span class="absolute bg-[#14161f] px-3 text-center font-body text-[11px] font-medium uppercase tracking-wider text-[#94a3b8]">o continúa con tu correo profesional</span>
                        </div>

                        <form class="space-y-4" @submit.prevent="login" novalidate>
                            <div class="space-y-1.5">
                                <label class="block font-body text-xs font-semibold uppercase tracking-wider text-[#cbd5e1]" for="corporate-email">Correo Electrónico Corporativo</label>
                                <div class="relative rounded-lg border border-[#2c3040] bg-[#1a1d26] transition-all focus-within:border-[#f97316] focus-within:ring-1 focus-within:ring-[#f97316]">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#f97316]"><span class="material-symbols-outlined text-[18px]">domain</span></div>
                                    <input v-model.trim="email" autocomplete="username" class="w-full rounded-lg bg-transparent py-2.5 pl-10 pr-3.5 font-body text-sm text-[#f8fafc] placeholder:text-[#64748b] focus:outline-none" id="corporate-email" placeholder="asesor@firmafinanciera.com" required type="email">
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block font-body text-xs font-semibold uppercase tracking-wider text-[#cbd5e1]" for="corporate-password">Contraseña</label>
                                <div class="relative rounded-lg border border-[#2c3040] bg-[#1a1d26] transition-all focus-within:border-[#f97316] focus-within:ring-1 focus-within:ring-[#f97316]">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#f97316]"><span class="material-symbols-outlined text-[18px]">lock</span></div>
                                    <input v-model="password" autocomplete="current-password" class="w-full rounded-lg bg-transparent py-2.5 pl-10 pr-10 font-body text-sm tracking-wider text-[#f8fafc] placeholder:text-[#64748b] focus:outline-none" id="corporate-password" placeholder="••••••••••••" required :type="showPassword ? 'text' : 'password'">
                                    <button @click="showPassword = !showPassword" :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'" class="absolute inset-y-0 right-0 flex items-center pr-3 text-[#94a3b8] transition-colors hover:text-[#f8fafc] focus:outline-none" type="button"><span class="material-symbols-outlined text-[18px]">@{{ showPassword ? 'visibility_off' : 'visibility' }}</span></button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-1">
                                <label class="flex cursor-pointer select-none items-center gap-2">
                                    <input v-model="rememberDevice" class="h-4 w-4 cursor-pointer rounded border-[#2c3040] bg-[#1a1d26] accent-[#ea580c]" type="checkbox">
                                    <span class="font-body text-xs text-[#94a3b8]">Recordar este equipo</span>
                                </label>
                                <a @click.prevent="showComingSoon('Recuperación de contraseña')" class="font-body text-xs font-semibold text-[#f97316] transition-all hover:text-[#ea580c] hover:underline" href="#">¿Olvidaste tu contraseña?</a>
                            </div>

                            <p v-if="error" class="rounded-lg border border-red-900/70 bg-red-950/40 px-3 py-2 text-xs text-red-300" role="alert">@{{ error }}</p>
                            <p v-if="notice" class="rounded-lg border border-[#4d2d1d] bg-[#2a1c14]/60 px-3 py-2 text-xs text-[#fdba74]" role="status">@{{ notice }}</p>
                            <p v-if="success" class="rounded-lg border border-emerald-900/70 bg-emerald-950/40 px-3 py-2 text-xs text-emerald-300" role="status">@{{ success }}</p>

                            <button :disabled="loading" class="group mt-2 flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg bg-[#ea580c] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-[#ea580c]/25 transition-all duration-200 hover:bg-[#f97316] hover:shadow-[#f97316]/35 active:scale-[0.99] disabled:cursor-wait disabled:opacity-70" type="submit">
                                <span>@{{ loading ? 'Ingresando…' : 'Ingresar al Portal de Noticias' }}</span>
                                <span v-if="!loading" class="material-symbols-outlined text-[18px] transition-transform duration-200 group-hover:translate-x-1">arrow_forward</span>
                            </button>
                        </form>

                        <div class="mt-6 flex items-center justify-center gap-2 border-t border-[#1f222e] pt-4 text-[11px] text-[#94a3b8]">
                            <span class="material-symbols-outlined shrink-0 text-[15px] text-emerald-400">encrypted</span>
                            <span>Acceso protegido para asesores FIREFLYONE</span>
                        </div>
                        <div class="mt-4 border-t border-[#1f222e] pt-4 text-center">
                            <p class="font-body text-xs text-[#94a3b8]">¿Aún no tienes cuenta institucional?
                                <a @click.prevent="showComingSoon('Registro de asesores')" class="ml-1 font-semibold text-[#f97316] hover:text-[#ea580c] hover:underline" href="#">Solicita tu demo o registro</a>
                            </p>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>
    <footer class="w-full border-t border-[#1c1e27] bg-[#0b0c10] px-6 py-6 text-center font-body text-xs text-[#94a3b8]">
        <p>FIREFLYONE · Plataforma de comunicación para asesores</p>
    </footer>
</body>
</html>
