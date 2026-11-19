<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

## Integraciones de FireflyOne

Facebook e Instagram se usan únicamente como destinos para compartir enlaces desde cada publicación. No se solicita OAuth ni se publica por sus APIs.

LinkedIn permite conectar la cuenta del asesor y publicar una publicación con enlace. TikTok recibe el contenido compatible como borrador; el asesor debe finalizarlo desde TikTok hasta que la aplicación tenga aprobados los permisos de publicación directa y el dominio multimedia esté verificado.

La página `/integrations` permite conectar LinkedIn/TikTok y solicitar llamadas de Twilio. Para habilitar los proveedores, configura las variables `LINKEDIN_*`, `TIKTOK_*` y `TWILIO_*` de `.env.example`; registra como redirect URIs `https://TU_HOST/oauth/linkedin/callback` y `https://TU_HOST/oauth/tiktok/callback` en sus consolas. TikTok requiere un callback HTTPS público.

Para transcribir llamadas, configura en Twilio una Transcription Configuration con el callback `https://TU_HOST/api/webhooks/twilio/transcription` (POST), y establece su ID en `TWILIO_TRANSCRIPTION_CONFIGURATION_ID`. `TWILIO_WEBHOOK_BASE_URL` debe ser `https://TU_HOST`. La llamada solicita consentimiento antes de grabar. Para conservar transcripciones grandes aunque falle el webhook, configura también un Conversation Configuration en Twilio.

Las publicaciones y llamadas requieren que el usuario tenga una sesión activa. Las credenciales externas se guardan en el servidor; los tokens OAuth se cifran en la base de datos.

---

## About Laravel (default project notes)

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
