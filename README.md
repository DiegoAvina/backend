# Backend · API Laravel 13 + panel Filament 5

- **API REST v1:** contenido público (productos, servicios, soluciones, industrias, artículos, categorías y configuración) y recepción de las solicitudes comerciales del frontend Next.js.
- **Panel administrativo** en `/admin`: contenido, CRM, usuarios, roles, configuración y auditoría.

- Referencia de endpoints: [`docs/06-api-reference.md`](../docs/06-api-reference.md)
- Esquema de base de datos: [`docs/07-database-schema.md`](../docs/07-database-schema.md)
- Panel, roles y permisos: [`docs/08-panel-administrativo.md`](../docs/08-panel-administrativo.md)
- Arquitectura y decisiones: [`docs/01-arquitectura.md`](../docs/01-arquitectura.md), [`docs/05-decisiones-tecnicas.md`](../docs/05-decisiones-tecnicas.md)

## Requisitos

| Herramienta | Versión |
|---|---|
| PHP | 8.3 o superior, con `pdo_mysql`, `mbstring`, `intl`, `zip`, `bcmath` (y `pdo_sqlite` para las pruebas) |
| Composer | 2.x |
| MySQL | 8.0 o superior (en local: MySQL 8.4 de Laragon) |

## Instalación local

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

1. **Base de datos.** Crea la base (en Laragon, con MySQL iniciado):

   ```sql
   CREATE DATABASE plataforma_corporativa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

   Si tu usuario o contraseña de MySQL no son `root` sin contraseña, ajusta `DB_USERNAME` y `DB_PASSWORD` en `.env`.

2. **Llave de servicio** (la comparte con el frontend). Genera una y guárdala en `FRONTEND_SERVICE_KEY` dentro de `.env`:

   ```bash
   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

   Pon el mismo valor en `API_SERVICE_KEY` de `frontend/.env.local`, junto con `API_URL=http://localhost:8000`.

3. **Migraciones y contenido inicial:**

   ```bash
   php artisan migrate
   php artisan db:seed
   ```

   Los *seeders* son idempotentes: roles y permisos, claves de configuración (vacías) y contenido inicial. Se pueden volver a ejecutar sin duplicar ni borrar nada.

4. **Imágenes públicas:** `php artisan storage:link`.

5. **Primer usuario del panel** (la contraseña se pide de forma oculta):

   ```bash
   php artisan app:create-admin            # superadministrador
   php artisan app:create-admin --role=sales
   ```

6. **Servidor y colas:**

   ```bash
   php artisan serve      # http://localhost:8000  ·  panel en /admin
   php artisan queue:work # revalidación del frontend y avisos (en otra terminal)
   ```

   En local puedes evitar el *worker* con `QUEUE_CONNECTION=sync`. Comprueba que la API responde: `http://localhost:8000/api/v1/products`.

7. **Revalidación del frontend** (opcional en local): genera un secreto, guárdalo en `FRONTEND_REVALIDATE_SECRET` y en `REVALIDATE_SECRET` de `frontend/.env.local`, y deja `FRONTEND_REVALIDATE_URL=http://localhost:3000/api/revalidate`. Sin esto, los cambios del panel tardan hasta 1 h en verse en el sitio.

## Variables de entorno propias

| Variable | Uso |
|---|---|
| `FRONTEND_SERVICE_KEY` | Llave que debe enviar Next.js en `X-Service-Key`. **Si está vacía, la API rechaza todas las escrituras.** |
| `CORS_ALLOWED_ORIGINS` | Orígenes del navegador que pueden leer la API pública, separados por coma. |
| `API_CONTENT_RATE_LIMIT` | Lecturas por minuto y por IP (300 por defecto). |
| `APP_LOCALE` | `es`: mensajes de validación en español (`lang/es/validation.php`). |
| `FRONTEND_REVALIDATE_URL`, `FRONTEND_REVALIDATE_SECRET` | Webhook firmado a Next.js al publicar o editar contenido. Vacíos = desactivado. |
| `LEAD_NOTIFICATIONS_ENABLED`, `COMMERCIAL_NOTIFICATION_EMAILS` | Avisos por correo al equipo comercial. **Desactivados** hasta contar con SMTP (`MAIL_*`) y autorización. |

Nunca subas `.env` al repositorio. En producción usa `APP_ENV=production` y `APP_DEBUG=false`: con `APP_DEBUG=false` los errores 500 no incluyen ningún detalle técnico.

> **Rotación de llaves.** Para cambiar `FRONTEND_SERVICE_KEY`, actualiza el backend y el frontend a la vez. Si rotas `APP_KEY`, los `ip_hash` previos ya no coincidirán con los nuevos; solo afecta la correlación histórica de abuso.

## Pruebas

```bash
php artisan test                 # SQLite en memoria (no requiere MySQL)
vendor/bin/pint                  # estilo de código
```

Para correr la misma suite contra MySQL, crea primero la base `plataforma_corporativa_test` y luego:

```bash
DB_CONNECTION=mysql DB_DATABASE=plataforma_corporativa_test php artisan test
```

En PowerShell: `$env:DB_CONNECTION='mysql'; $env:DB_DATABASE='plataforma_corporativa_test'; php artisan test`.

Las pruebas usan una llave de servicio ficticia definida en `phpunit.xml`; no requieren credenciales reales.

## Estructura

```
app/
├── Enums/                      estados y catálogos (valores = los de los formularios)
├── Http/
│   ├── Controllers/Api/V1/     controladores delgados
│   ├── Middleware/             VerifyServiceKey
│   ├── Requests/Api/V1/        listados (filtros/orden) y formularios (limpieza y validación)
│   ├── Resources/V1/           forma pública de cada recurso
│   └── Responses/ApiResponse   formato { success, message, data | errors }
├── Filament/                   panel /admin
│   ├── Resources/              contenido, comercial (CRM), usuarios, roles, auditoría
│   ├── Pages/ManageSettings    configuración del sitio
│   ├── Widgets/                dashboard (KPI, gráficas, tablas)
│   └── Support/                campos, acciones y bases compartidas
├── Models/                     + Concerns/ (Publishable, LeadSubmission, TracksCommercialStatus, NormalizesSeo)
├── Policies/                   una por modelo (ContentPolicy, CommercialPolicy…)
├── Observers/                  auditoría, historial de estados, revalidación, limpieza de imágenes
├── Jobs/                       RevalidateFrontendContent (webhook firmado)
├── Events/ Listeners/ Notifications/   aviso comercial (desactivado por defecto)
├── Console/Commands/           app:create-admin
└── Services/                   Leads (intake, seguimiento), Audit, Settings, Dashboard
database/
├── migrations/
├── factories/
└── seeders/                    roles y permisos, configuración, contenido + data/*.json
lang/es/validation.php
routes/api.php                  /api/v1
tests/Feature/{Admin,Api,Database,Integrations,Models}, tests/Unit
```

`CLAUDE.md` y `AGENTS.md` contienen guías para asistentes de IA (Laravel Boost); no afectan la aplicación.
