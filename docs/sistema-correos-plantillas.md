# Sistema de Correos Electrónicos y Plantillas (Ecosistema Mail)

En Labs Backend, la comunicación con clientes y usuarios mediante correo electrónico está estandarizada bajo los componentes oficiales de Laravel Mail y Markdown Mailables.

---

## 🏗️ Arquitectura de Plantillas y Componentes

Todos los correos del sistema (Facturas, Presupuestos, Mantenimientos, Proyectos y Notificaciones de Sistema) comparten la estructura de diseño corporativo ubicada en:
- `resources/views/vendor/mail/html/message.blade.php`: Contenedor principal del mensaje.
- `resources/views/vendor/mail/html/header.blade.php`: Cabecera corporativa con el logotipo.
- `resources/views/vendor/mail/html/layout.blade.php`: Estructura HTML responsive base.
- `resources/views/vendor/mail/html/themes/default.css`: Estilos en línea procesados para compatibilidad con clientes de email.

---

## 🖼️ Incrustación de Logotipo mediante CID (Inline Attachment)

### Problema Resuelto
1. **Entorno protegido por VPN / Firewall (HTTP 403):** El entorno Labs cuenta con restricción de acceso por IP/VPN (*"Acceso Restringido - VPN Requerida"*). Si una plantilla de correo intenta enlazar una imagen alojada en Labs mediante `<img src="https://labs.../img/logo.png">`, los clientes de correo (como Gmail, Outlook o Thunderbird) y sus proxies de descarga reciben un error `403 Forbidden`, impidiendo mostrar la imagen.
2. **Dependencia de servidores externos (HTTP 404):** Apuntar a URLs externas (como la web pública principal) puede provocar roturas de enlaces (errores `404 Not Found`) si las rutas de los archivos cambian o no coinciden.
3. **Bloqueo de esquemas Base64 Data URI:** Los principales proveedores de correo (especialmente Gmail y Outlook) bloquean deliberadamente etiquetas `<img src="data:image/png;base64,...">` en el cuerpo del mensaje por motivos de seguridad y prevención de phishing.

### Solución Implementada: CID (Content-ID Inline Attachment)
Se utiliza el mecanismo estándar MIME RFC 2387 soportado nativamente por Laravel (`$message->embed()`):

1. **Compartición Global del Objeto `$message`:**
   En `app/Providers/AppServiceProvider.php`, se registra un `View::composer` en el método `boot()`:
   ```php
   \Illuminate\Support\Facades\View::composer(['mail::*', 'emails.*', 'vendor.mail.*'], function ($view) {
       if (isset($view->getData()['message'])) {
           \Illuminate\Support\Facades\View::share('message', $view->getData()['message']);
       }
   });
   ```
   Esto asegura que el objeto `Illuminate\Mail\Message` esté accesible en todo el árbol de componentes Blade del correo, incluyendo subcomponentes anónimos como `<x-mail::header>`.

2. **Renderizado en la Cabecera (`header.blade.php`):**
   ```blade
   @props(['url'])
   <tr>
   <td class="header">
   <a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
   @php
       $logoPath = public_path('img/logo.png');
       if (!file_exists($logoPath)) {
           $logoPath = public_path('logo-icono.png');
       }
   @endphp
   @if (isset($message) && file_exists($logoPath))
       {{-- Incrustar logotipo mediante CID (adjunto inline) compatible con clientes de correo --}}
       <img src="{{ $message->embed($logoPath) }}" class="logo" alt="{{ config('app.name') }}" style="width: auto; max-width: 250px; height: auto; max-height: 50px; border: none; display: block;" />
   @elseif (file_exists($logoPath))
       <img src="{{ config('app.url') }}/img/logo.png" class="logo" alt="{{ config('app.name') }}" style="width: auto; max-width: 250px; height: auto; max-height: 50px; border: none; display: block;" />
   @else
       {{ config('app.name') }}
   @endif
   </a>
   </td>
   </tr>
   ```

### Ventajas Técnicas
- **Autocontenido:** El logotipo viaja como parte del propio mensaje MIME (`Content-Disposition: inline`).
- **Compatibilidad 100%:** Se visualiza correctamente en Gmail (web y móvil), Microsoft Outlook, Apple Mail, Thunderbird y clientes corporativos.
- **Resiliencia:** No depende de conectividad HTTP a servidores externos ni se ve bloqueado por reglas de cortafuegos o VPNs.
