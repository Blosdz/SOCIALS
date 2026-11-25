@extends('app')

@section('title', 'Política de privacidad · SOCIALS')
@section('theme-color', '#0c0d10')
@section('body-class', 'bg-[#0c0d10] font-sans text-[#dedfe4] antialiased')

@section('head')
    <meta name="description" content="Conoce cómo SOCIALS utiliza y protege los datos de las cuentas de redes sociales conectadas.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
@endsection

@section('content')
<main class="mx-auto w-full max-w-3xl flex-1 px-5 py-10 sm:px-8 sm:py-14">
    <a class="text-sm text-[#f58220] hover:underline" href="{{ route('login') }}">← Volver a SOCIALS</a>

    <header class="mt-8 border-b border-[#25272e] pb-7">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#f58220]">SOCIALS · FIREFLYONE</p>
        <h1 class="mt-3 text-3xl font-semibold text-white">Política de privacidad</h1>
        <p class="mt-3 text-sm leading-6 text-[#9ea2af]">Última actualización: 20 de noviembre de 2026.</p>
    </header>

    <div class="space-y-8 py-8 text-sm leading-7 text-[#c3c6d0]">
        <section>
            <h2 class="text-lg font-semibold text-white">1. Alcance</h2>
            <p class="mt-2">Esta política explica cómo SOCIALS trata los datos de los asesores que conectan LinkedIn o TikTok para publicar contenido y, cuando está habilitada, cómo gestiona conversaciones de WhatsApp desde los números empresariales.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">2. Datos que tratamos</h2>
            <p class="mt-2">Al registrar una cuenta de asesor guardamos su nombre y correo electrónico. Al conectar LinkedIn, recibimos el identificador de la cuenta, el nombre visible, los permisos concedidos y el token de acceso; si el proveedor lo entrega, también guardamos un token de renovación y sus fechas de vencimiento. SOCIALS no solicita el correo electrónico de tu perfil de LinkedIn.</p>
            <p class="mt-2">Al conectar TikTok tratamos los datos equivalentes que la cuenta autoriza. También conservamos registros locales de las publicaciones enviadas, como la red social, el estado, el identificador de la publicación y la fecha.</p>
            <p class="mt-2">En la bandeja de WhatsApp guardamos el teléfono y nombre visible del cliente, el número empresarial, los mensajes recibidos y enviados, sus fechas y estados, y el asesor al que está asignado el número. Cada asesor solo puede ver las conversaciones de los números empresariales conectados a su cuenta.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">3. Finalidad y permisos</h2>
            <p class="mt-2">Usamos estos datos para identificar la cuenta conectada, mantener su autorización y publicar el contenido que el asesor decide enviar. Para LinkedIn solicitamos <code class="text-[#f6ae72]">openid</code> y <code class="text-[#f6ae72]">profile</code> para identificar la cuenta, y <code class="text-[#f6ae72]">w_member_social</code> para publicar en nombre del asesor. Para TikTok solicitamos <code class="text-[#f6ae72]">user.info.basic</code> y <code class="text-[#f6ae72]">video.publish</code>.</p>
            <p class="mt-2">Los botones para compartir en Facebook y WhatsApp abren opciones externas para enviar enlaces. La bandeja de WhatsApp es una función distinta: recibe mensajes mediante un webhook y envía las respuestas del asesor a través de la API empresarial de Meta.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">4. Almacenamiento y protección</h2>
            <p class="mt-2">Los tokens de las redes sociales se almacenan cifrados en el servidor y asociados a la cuenta del asesor. Se utilizan desde el servidor para comunicarse con la API de la red correspondiente; no se muestran en el feed ni se envían al navegador como parte de los datos de la cuenta conectada. El acceso a las funciones de conexión y publicación requiere la autenticación del asesor.</p>
            <p class="mt-2">La credencial de WhatsApp se configura en el servidor y no se entrega al navegador. El acceso a la bandeja y al envío de respuestas requiere la autenticación del asesor. Los mensajes enviados se transmiten a Meta para su entrega al cliente.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">5. Redes sociales y publicaciones</h2>
            <p class="mt-2">Cuando eliges publicar, SOCIALS transmite el contenido y los datos necesarios a LinkedIn o TikTok. Cada red trata la información conforme a sus propias políticas. Desconectar una cuenta de SOCIALS no elimina las publicaciones que ya estén visibles en esa red social.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">6. Desconexión y eliminación</h2>
            <p class="mt-2">Puedes desconectar LinkedIn o TikTok en <a class="text-[#f58220] underline" href="{{ route('integrations') }}">Integraciones</a>. Esa acción elimina de SOCIALS la conexión guardada y sus tokens. El historial local de publicaciones no se elimina al desconectar la cuenta.</p>
            <p class="mt-2">Para solicitar la eliminación de otros datos asociados a tu cuenta de asesor, utiliza el correo de contacto indicado abajo e identifica la cuenta afectada. No envíes contraseñas ni tokens en tu solicitud.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">7. Contacto</h2>
            @if (config('privacy.contact_email'))
                <p class="mt-2">Para consultas de privacidad y solicitudes de eliminación de datos, escribe a <a class="text-[#f58220] underline" href="mailto:{{ config('privacy.contact_email') }}">{{ config('privacy.contact_email') }}</a>.</p>
            @else
                <p class="mt-2 text-amber-300">El correo de contacto de privacidad aún no está configurado. La empresa debe completar este dato antes de presentar esta página a LinkedIn.</p>
            @endif
        </section>
    </div>
</main>
@endsection
