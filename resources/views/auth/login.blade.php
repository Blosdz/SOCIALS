@extends('app')

@section('title', 'Iniciar sesión · FIREFLYONE')
@section('body-class', 'bg-[#0b0c10] font-body text-[#f8fafc] selection:bg-[#f97316]/20 selection:text-[#f97316]')

@section('head')
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;500;600;700&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700&display=swap" rel="stylesheet">
@endsection

@section('content')
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
                                <span class="ml-auto h-1.5 w-1.5 rounded-full bg-[#f97316]" aria-hidden="true"></span>
                            </div>
                            <div>
                                <h1 id="login-title" class="font-headline text-3xl font-semibold tracking-tight text-[#f8fafc]">Iniciar Sesión</h1>
                                <p class="mt-1 font-body text-xs text-[#94a3b8] sm:text-sm">Ingresa con tus credenciales de asesor certificado</p>
                            </div>
                        </header>
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
@endsection
