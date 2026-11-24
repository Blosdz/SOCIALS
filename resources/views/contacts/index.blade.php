@extends('app')

@section('title', 'Contactos · FIREFLYONE')
@section('theme-color', '#0c0d10')
@section('body-class', 'bg-[#0c0d10] font-sans text-[#dedfe4] antialiased')

@section('head')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
@endsection

@section('content')
<div id="contacts-app" class="min-h-[calc(100vh-2.5rem)] pt-14 md:pl-60 md:pt-0">
    <x-feed-nav active="contacts" />

    <main class="page-enter mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="mb-7 flex flex-wrap items-center justify-between gap-4 border-b border-[#20222a] pb-5">
            <div>
                <h1 class="text-2xl font-semibold text-[#f0f2f5]">Contactos</h1>
                <p class="mt-1 text-sm text-[#7a7e8b]">Guarda clientes y llama a sus números mediante Twilio.</p>
            </div>
            <button ref="addCustomerButton" @click="openCustomerModal" class="flex items-center gap-1.5 rounded-lg bg-[#f58220] px-4 py-2.5 text-xs font-semibold text-black transition hover:bg-[#e07116]" type="button">
                <span class="material-symbols-outlined text-base">person_add</span>Agregar cliente
            </button>
        </header>

        <p v-if="formNotice" class="mb-5 rounded-lg border border-emerald-900/70 bg-emerald-950/40 px-3 py-2 text-xs text-emerald-300" role="status">@{{ formNotice }}</p>

        <section class="min-w-0 rounded-xl border border-[#25272e] bg-[#141519] p-5" aria-labelledby="customer-list-title">
            <h2 id="customer-list-title" class="text-base font-semibold text-[#f0f2f5]">Clientes</h2>
            <p v-if="loading" class="mt-4 text-sm text-[#8d919d]">Cargando contactos…</p>
            <p v-else-if="listError" class="mt-4 text-sm text-red-300" role="alert">@{{ listError }}</p>
            <p v-else-if="customers.length === 0" class="mt-4 text-sm text-[#8d919d]">Aún no hay clientes registrados.</p>
            <ul v-else class="mt-4 grid gap-3 sm:grid-cols-2">
                <li v-for="customer in customers" :key="customer.id" class="customer-card flex min-w-0 items-center justify-between gap-3 rounded-lg border border-[#25272e] bg-[#101114] p-4">
                    <div class="min-w-0 break-words">
                        <p class="font-medium text-[#f0f2f5]">@{{ customer.name }}</p>
                        <p class="mt-1 text-xs text-[#8d919d]">DNI @{{ customer.dni }} · @{{ customer.email }}</p>
                        <p class="mt-1 text-xs text-[#8d919d]">@{{ customer.phone }}<span v-if="customer.extension"> · Ext. @{{ customer.extension }}</span></p>
                    </div>
                    <button @click="selectCustomer(customer)" :disabled="calling || !!activeCall" :aria-label="`Llamar a ${customer.name}`" class="customer-call-action flex shrink-0 items-center gap-1.5 rounded-lg border border-[#4a3422] px-3 py-2 text-xs font-medium text-[#f58220] hover:bg-[#211b15] disabled:cursor-not-allowed disabled:opacity-50" type="button">
                        <span class="material-symbols-outlined text-base">call</span>Llamar
                    </button>
                </li>
            </ul>
        </section>

        <section v-if="selectedCustomer" class="mt-6 rounded-xl border border-[#25272e] bg-[#141519] p-5" aria-labelledby="call-customer-title">
            <h2 id="call-customer-title" class="text-base font-semibold text-[#f0f2f5]">Llamar a @{{ selectedCustomer.name }}</h2>
            <p class="mt-1 text-xs text-[#8d919d]">Destino: @{{ selectedCustomer.phone }}. La extensión se guarda como referencia; esta llamada usa el número principal.</p>
            <p v-if="callError" class="mt-4 rounded-lg border border-red-900/70 bg-red-950/40 px-3 py-2 text-xs text-red-300" role="alert">@{{ callError }}</p>
            <p v-if="callNotice" class="mt-4 rounded-lg border border-emerald-900/70 bg-emerald-950/40 px-3 py-2 text-xs text-emerald-300" role="status">@{{ callNotice }}</p>
            <p v-if="callStatus" class="mt-4 text-xs text-[#c3c6d0]" role="status">@{{ callStatus }}</p>

            <form @submit.prevent="startCall" class="mt-4 grid gap-4">
                <p class="text-xs text-[#8d919d]">Hablarás desde esta computadora. Permite el acceso al micrófono cuando el navegador lo solicite.</p>
                <label class="flex items-start gap-2 text-xs leading-relaxed text-[#9296a2] sm:col-span-2">
                    <input v-model="recordingConsent" class="mt-0.5 h-4 w-4 rounded border-[#41434c] bg-[#0f1013] accent-[#f58220]" required type="checkbox">
                    Confirmo que las personas han sido informadas y han consentido la grabación y transcripción. Twilio también solicitará confirmación al cliente.
                </label>
                <div class="flex flex-wrap gap-2">
                    <button :disabled="calling || !!activeCall" class="rounded-lg bg-[#f58220] px-4 py-2.5 text-xs font-semibold text-black transition hover:bg-[#e07116] disabled:cursor-wait disabled:opacity-60" type="submit">@{{ calling ? 'Conectando…' : 'Llamar desde la computadora' }}</button>
                    <button v-if="activeCall" @click="hangupCall" class="rounded-lg border border-red-800 px-4 py-2.5 text-xs font-semibold text-red-300 hover:bg-red-950/40" type="button">Colgar</button>
                </div>
            </form>
        </section>
    </main>
    <div v-if="showCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" @click.self="closeCustomerModal" @keydown.esc="closeCustomerModal">
        <section class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-[#30323c] bg-[#141519] p-5 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="new-customer-title" tabindex="-1">
            <div class="flex items-center justify-between gap-4">
                <h2 id="new-customer-title" class="text-base font-semibold text-[#f0f2f5]">Agregar cliente</h2>
                <button @click="closeCustomerModal" :disabled="saving" class="rounded-lg p-1 text-[#8d919d] hover:bg-[#25272e] hover:text-white disabled:opacity-60" type="button" aria-label="Cerrar modal">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <p v-if="formError" class="mt-4 rounded-lg border border-red-900/70 bg-red-950/40 px-3 py-2 text-xs text-red-300" role="alert">@{{ formError }}</p>
            <form @submit.prevent="createCustomer" class="mt-4 space-y-4">
                <label class="block text-xs font-medium text-[#c3c6d0]">Nombre
                    <input ref="customerNameInput" v-model.trim="customerForm.name" class="mt-1.5 w-full rounded-lg border border-[#30323c] bg-[#0f1013] px-3 py-2.5 text-sm text-white focus:border-[#f58220] focus:outline-none" required maxlength="255" autocomplete="name">
                </label>
                <label class="block text-xs font-medium text-[#c3c6d0]">DNI
                    <input v-model.trim="customerForm.dni" class="mt-1.5 w-full rounded-lg border border-[#30323c] bg-[#0f1013] px-3 py-2.5 text-sm text-white focus:border-[#f58220] focus:outline-none" required inputmode="numeric" pattern="[0-9]{8}" maxlength="8">
                </label>
                <label class="block text-xs font-medium text-[#c3c6d0]">Correo electrónico
                    <input v-model.trim="customerForm.email" class="mt-1.5 w-full rounded-lg border border-[#30323c] bg-[#0f1013] px-3 py-2.5 text-sm text-white focus:border-[#f58220] focus:outline-none" required type="email" maxlength="255" autocomplete="email">
                </label>
                <label class="block text-xs font-medium text-[#c3c6d0]">Teléfono (E.164)
                    <input v-model.trim="customerForm.phone" class="mt-1.5 w-full rounded-lg border border-[#30323c] bg-[#0f1013] px-3 py-2.5 text-sm text-white focus:border-[#f58220] focus:outline-none" required type="tel" pattern="\+[1-9][0-9]{7,14}" placeholder="+51912345678" autocomplete="tel">
                </label>
                <label class="block text-xs font-medium text-[#c3c6d0]">Extensión (opcional)
                    <input v-model.trim="customerForm.extension" class="mt-1.5 w-full rounded-lg border border-[#30323c] bg-[#0f1013] px-3 py-2.5 text-sm text-white focus:border-[#f58220] focus:outline-none" inputmode="numeric" pattern="[0-9]{1,10}" maxlength="10">
                </label>
                <button :disabled="saving" class="w-full rounded-lg bg-[#f58220] px-4 py-2.5 text-xs font-semibold text-black transition hover:bg-[#e07116] disabled:cursor-wait disabled:opacity-60" type="submit">@{{ saving ? 'Guardando…' : 'Guardar cliente' }}</button>
            </form>
        </section>
    </div>
</div>
@endsection
