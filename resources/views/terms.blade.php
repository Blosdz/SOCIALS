@extends('app')

@section('title', 'Términos de uso · SOCIALS')
@section('theme-color', '#0c0d10')
@section('body-class', 'bg-[#0c0d10] font-sans text-[#dedfe4] antialiased')

@section('head')
    <meta name="description" content="Condiciones de uso de SOCIALS para asesores y conexiones con redes sociales.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
@endsection

@section('content')
<main class="mx-auto w-full max-w-3xl flex-1 px-5 py-10 sm:px-8 sm:py-14">
    <a class="text-sm text-[#f58220] hover:underline" href="{{ route('login') }}">← Volver a SOCIALS</a>

    <header class="mt-8 border-b border-[#25272e] pb-7">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#f58220]">SOCIALS · FIREFLYONE</p>
        <h1 class="mt-3 text-3xl font-semibold text-white">Términos de uso</h1>
        <p class="mt-3 text-sm leading-6 text-[#9ea2af]">Última actualización: 20 de noviembre de 2026.</p>
    </header>

    <div class="space-y-8 py-8 text-sm leading-7 text-[#c3c6d0]">
        <section>
            <h2 class="text-lg font-semibold text-white">1. Uso de la plataforma</h2>
            <p class="mt-2">SOCIALS es una plataforma de comunicación para asesores. Permite consultar contenidos de un feed, compartir enlaces, conectar cuentas de redes sociales y, cuando las integraciones están disponibles, publicar contenido y responder conversaciones de WhatsApp desde los números empresariales.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">2. Cuenta del asesor</h2>
            <p class="mt-2">Para acceder a las funciones del feed y de publicación necesitas una cuenta de asesor. Debes utilizar tu propia cuenta, mantener tus credenciales en privado y revisar las acciones realizadas desde ella. Si detectas un uso no autorizado, comunícalo al contacto indicado al final de esta página.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">3. Contenido y publicaciones</h2>
            <p class="mt-2">Antes de publicar, revisa el texto, las imágenes y el destino de la publicación. Al elegir publicar en LinkedIn o TikTok, autorizas a SOCIALS a enviar ese contenido a la red seleccionada mediante la cuenta que conectaste. Debes contar con permiso para usar los materiales que publiques y respetar las reglas de la red social correspondiente.</p>
            <p class="mt-2">Los botones de Facebook y WhatsApp para compartir enlaces abren opciones externas; la decisión de completar ese envío se realiza fuera de SOCIALS. La bandeja de WhatsApp permite responder conversaciones directamente desde SOCIALS cuando la integración empresarial está configurada.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">4. Conexiones con terceros</h2>
            <p class="mt-2">Conectar LinkedIn o TikTok requiere autorizar los permisos que muestra cada plataforma. Las funciones de publicación dependen de que esa autorización siga vigente y de la disponibilidad de sus API. Las publicaciones hechas en esas redes quedan sujetas a sus propias condiciones y políticas.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">5. Desconexión y datos</h2>
            <p class="mt-2">Puedes desconectar una red desde <a class="text-[#f58220] underline" href="{{ route('integrations') }}">Integraciones</a>. La desconexión elimina la conexión y los tokens guardados en SOCIALS; no borra publicaciones ya realizadas en la red social ni el historial local de publicaciones. Consulta nuestra <a class="text-[#f58220] underline" href="{{ route('privacy.policy') }}">política de privacidad</a> para conocer el tratamiento de tus datos y cómo solicitar su eliminación.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">6. Contacto</h2>
            @if (config('privacy.contact_email'))
                <p class="mt-2">Para consultas sobre estos términos o sobre tu cuenta, escribe a <a class="text-[#f58220] underline" href="mailto:{{ config('privacy.contact_email') }}">{{ config('privacy.contact_email') }}</a>.</p>
            @else
                <p class="mt-2 text-amber-300">El correo de contacto aún no está configurado. La empresa debe completar este dato antes de presentar esta página a terceros.</p>
            @endif
        </section>
    </div>
</main>
@endsection
