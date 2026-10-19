# Novedades — Sistema de gestión disciplinaria

Aplicación web para registrar y hacer seguimiento a las novedades disciplinarias de los colaboradores por operación: llamados de atención, procesos disciplinarios, citaciones a descargos y análisis de reincidencia.

## Funcionalidades

- **Operaciones (rol `normal`)**: registro de novedades de su operación, evidencias adjuntas, descarga automática del llamado de atención en Word y gestión del headcount (manual o por Excel).
- **Jurídico (rol `juridico`)**: bandeja de procesos disciplinarios, agendamiento de descargos con contador de 5 días, generación de la citación en Word y cierre del proceso.
- **Administrador (rol `admin`)**: novedades por operación con detalle completo, panel de análisis con gráficas, gestión de usuarios y operaciones.
- Filtros persistentes en la URL, calendarios de rango, paginación y actualizaciones sin recargar la página.

## Requisitos

- PHP 8.1 o superior con las extensiones `pdo_mysql`, `zip`, `fileinfo`, `mbstring` y `gd`
- MySQL 5.7+ o MariaDB 10.4+
- Apache con `mod_rewrite` y `AllowOverride All` (XAMPP lo trae por defecto)

## Instalación con XAMPP

1. Clona o descarga el repositorio dentro de `C:\xampp\htdocs\` (por ejemplo `C:\xampp\htdocs\novedades`).
2. Abre phpMyAdmin, ve a **Importar** y carga `database/si_disciplinario_v2.sql`. El script crea la base de datos, las tablas, una operación inicial y el usuario administrador.
3. Entra a `http://localhost/novedades/`.
4. Inicia sesión con el administrador inicial y **cambia la contraseña de inmediato** desde *Usuarios*:
   - Correo: `admin@empresa.com`
   - Contraseña: `Admin123*`

Las dependencias de PHP (`vendor/`) ya vienen incluidas. Si las actualizas, ejecuta `composer install`.

### Conexión a la base de datos

Por defecto se conecta a `localhost` con el usuario `root` sin contraseña. En un servidor real define estas variables de entorno (por ejemplo con `SetEnv` en la configuración de Apache):

| Variable | Valor por defecto |
|----------|-------------------|
| `DB_HOST` | `localhost` |
| `DB_NAME` | `si_disciplinario_v2` |
| `DB_USER` | `root` |
| `DB_PASS` | *(vacío)* |

## Estructura del proyecto

```
├── app/
│   ├── config/config.php        Sesión, cabeceras de seguridad y conexión PDO
│   └── includes/                Helpers, layout (header), modal de detalle y analítica
├── database/
│   └── si_disciplinario_v2.sql  Esquema completo listo para importar
├── public/                      Único directorio accesible desde el navegador
│   ├── api/                     Endpoints JSON
│   ├── descargas/               Generación de documentos Word y visor de evidencias
│   ├── assets/media/            Recursos estáticos
│   └── *.php                    Páginas de la aplicación
├── storage/                     Bloqueado para acceso web
│   ├── plantillas/              Plantillas Word (.docx) de llamados y citaciones
│   └── evidencias/              Archivos subidos como evidencia
├── vendor/                      Dependencias de Composer
└── .htaccess                    Redirige todo a public/
```

## Plantillas Word

Los documentos se generan con PhpWord a partir de `storage/plantillas/`:

- `PLANTILLA_UNIVERSAL.docx`: llamados de atención. Variables `${NOMBRE}`, `${CEDULA}`, `${ASUNTO}`, `${DESCRIPCION_HECHOS}`, `${FECHA_ACTUAL}`, `${NOMBRE_CREADOR}`, `${OPERACION_CREADOR}` y bloques `${block_<causal>}`.
- `CITACION_DESCARGOS.docx`: citación a descargos. Variables `${NOMBRE}`, `${CEDULA}`, `${CARGO}`, `${FECHA_ACTUAL}`, `${FECHA_DESCARGOS}`, `${CIUDAD}`, `${FIRMANTE}` y el bloque `${hechos}`…`${HECHO}`…`${/hechos}`.

Para cambiar el formato basta con editar el `.docx` en Word conservando las variables.

## Seguridad

- Sesiones con expiración por inactividad (30 min), aviso previo y cookies `HttpOnly`/`SameSite`.
- Token CSRF en todas las acciones, control de acceso por rol en cada página y endpoint.
- Límite de intentos en el inicio de sesión y en los endpoints.
- Evidencias validadas por extensión y contenido, guardadas fuera del directorio público y servidas solo a usuarios autorizados.
- Contraseñas con `password_hash`; los errores internos se registran en el log y no se muestran al usuario.

En producción usa HTTPS y un usuario de base de datos con contraseña y permisos mínimos.
