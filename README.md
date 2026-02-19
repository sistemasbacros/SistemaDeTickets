# SistemaDeTickets — BacroCorp

Sistema web de gestión de tickets de soporte para BacroCorp. Permite registrar, asignar, dar seguimiento y resolver solicitudes de soporte interno, con notificaciones automáticas por correo electrónico.

---

## Índice

1. [Descripción y objetivos](#descripción-y-objetivos)
2. [Tecnologías utilizadas](#tecnologías-utilizadas)
3. [Arquitectura del sistema](#arquitectura-del-sistema)
4. [Estructura del proyecto](#estructura-del-proyecto)
5. [Requisitos previos](#requisitos-previos)
6. [Variables de entorno](#variables-de-entorno)
7. [Levantamiento del proyecto](#levantamiento-del-proyecto)
   - [Entorno local (desarrollo)](#entorno-local-desarrollo)
   - [Entorno de pruebas](#entorno-de-pruebas)
   - [Entorno de producción](#entorno-de-producción)
8. [CI/CD — Despliegue automático](#cicd--despliegue-automático)
9. [Configuración técnica](#configuración-técnica)
10. [Buenas prácticas](#buenas-prácticas)

---

## Descripción y objetivos

**SistemaDeTickets** centraliza la gestión de solicitudes de soporte técnico y operativo dentro de BacroCorp. Sus objetivos principales son:

- Registrar tickets de soporte con categorización por tipo (TI, mantenimiento, operaciones).
- Asignar tickets a los responsables correspondientes y controlar su estado de resolución.
- Notificar automáticamente a los involucrados mediante correo electrónico (PHPMailer + SMTP).
- Proveer vistas diferenciadas para usuarios finales y personal de soporte.
- Mantener trazabilidad completa del ciclo de vida de cada solicitud.

---

## Tecnologías utilizadas

| Capa | Tecnología | Versión |
|---|---|---|
| Backend | PHP | 8.2 (FPM) |
| Base de datos | Microsoft SQL Server | — (sqlsrv driver) |
| Servidor web | Nginx | Alpine (latest) |
| Contenedorización | Docker + Docker Compose | — |
| Proxy inverso | Traefik | (red externa `traefik_network`) |
| Correo electrónico | PHPMailer | 6.x |
| Frontend | HTML5, CSS3, JavaScript vanilla | — |
| UI / Notificaciones | SweetAlert2, Font Awesome | 6.5.0 |
| CI/CD | GitHub Actions (self-hosted, Windows) | — |

---

## Arquitectura del sistema

```
Traefik (proxy inverso)
        │
        ▼
  Nginx : Alpine ──── archivos estáticos (.css, .js, imágenes)
        │
        │  FastCGI (puerto 9000)
        ▼
  PHP 8.2-FPM ──────── código de aplicación (/var/www/html)
        │
        │  sqlsrv (puerto 1433)
        ▼
  SQL Server (MSSQL)
        ├── BD Comercial  (datos de contactos/pedidos)
        └── BD Ticket     (tickets, asignaciones, estados)
```

**Flujo de solicitud HTTP:**
1. Traefik recibe la petición y la enruta por prefijo `/tickets` o `/Tickets`.
2. Nginx sirve archivos estáticos directamente; reenvía `.php` a PHP-FPM via FastCGI.
3. PHP-FPM ejecuta el archivo `.php` correspondiente y consulta SQL Server con `sqlsrv_*`.
4. La respuesta HTML se devuelve al cliente.

**No existe un framework ni enrutador central.** Cada archivo `.php` en la raíz es una página independiente, accesible directamente por su nombre.

---

## Estructura del proyecto

```
SistemaDeTickets/
├── Loginti.php              # Página de inicio de sesión (CSRF, IP binding)
├── Ticket.php               # Dashboard principal de tickets
├── FormSoporte.php          # Formulario de ticket de soporte + envío de correo
├── FormTic.php              # Formulario de ticket TI (versión 1)
├── FormTic1.php             # Formulario de ticket TI (versión extendida)
├── RevisarTickets.php       # Vista de revisión de tickets (soporte)
├── RevisarT.php             # Vista de revisión de tickets (TI)
├── update_ticket.php        # Handler de actualización de tickets
├── Asignarticulo.php        # Asignación de artículos a tickets
├── Consultadata.php         # Conexión a BD y consultas compartidas
├── TableT.php / TableT1.php # Componentes de renderizado de tablas
├── IniSoport.php            # Inicialización del módulo de soporte
│
├── PHPMailer/               # Librería de correo electrónico
│   └── src/
│       ├── PHPMailer.php
│       ├── SMTP.php
│       └── Exception.php
│
├── nginx/
│   ├── nginx.conf           # Configuración del servidor web
│   └── logs/                # Logs de acceso y error (excluidos de git)
│
├── php/
│   └── custom.ini           # Configuración de PHP (límites, zona horaria)
│
├── M/website-menu-05/       # Assets de plantilla de interfaz (CSS, JS, imágenes)
│
├── .github/workflows/
│   ├── deploy-production.yml # Pipeline de despliegue a producción (rama main)
│   └── deploy-test.yml       # Pipeline de despliegue a pruebas (rama dev)
│
├── Dockerfile               # Imagen PHP 8.2-FPM con drivers sqlsrv
├── docker-compose.yml       # Stack de producción (puerto 8006)
├── docker-compose.test.yml  # Stack de pruebas (puerto 9006)
├── .env.example             # Plantilla de variables de entorno
└── web.config               # Configuración IIS (referencia)
```

---

## Requisitos previos

### Entorno local / servidor de despliegue

| Requisito | Versión mínima |
|---|---|
| Docker Engine | 24.x o superior |
| Docker Compose | v2 (plugin integrado) |
| Git | 2.x |

> El servidor de producción es **Windows Server** con Docker Engine instalado. Los pipelines de CI/CD utilizan runners self-hosted con PowerShell.

### Acceso externo necesario

- Instancia de **SQL Server** accesible desde los contenedores (no se incluye en el compose).
- Credenciales SMTP válidas (Gmail con App Password recomendado).
- Red Docker `traefik_network` creada externamente (solo producción).

---

## Variables de entorno

Copiar `.env.example` como `.env` y completar los valores antes de levantar cualquier entorno. **El archivo `.env` nunca se debe versionar.**

```bash
cp .env.example .env
```

| Variable | Descripción | Ejemplo |
|---|---|---|
| `APP_ENV` | Entorno activo | `production` / `test` |
| `HTTP_PORT` | Puerto expuesto en producción | `8006` |
| `HTTP_PORT_TEST` | Puerto expuesto en pruebas | `9006` |
| `DB_HOST` | Host de SQL Server (producción) | `192.168.1.10` |
| `DB_PORT` | Puerto de SQL Server | `1433` |
| `DB_DATABASE` | Nombre de la base de datos | `Ticket` |
| `DB_USERNAME` | Usuario de SQL Server | `sa` |
| `DB_PASSWORD` | Contraseña de SQL Server | `*****` |
| `DB_HOST_TEST` | Host de SQL Server (pruebas) | `192.168.1.11` |
| `DB_DATABASE_TEST` | Base de datos de pruebas | `TicketTest` |
| `DB_USERNAME_TEST` | Usuario BD pruebas | `sa_test` |
| `DB_PASSWORD_TEST` | Contraseña BD pruebas | `*****` |
| `SMTP_HOST` | Servidor SMTP | `smtp.gmail.com` |
| `SMTP_PORT` | Puerto SMTP | `587` |
| `SMTP_USERNAME` | Cuenta de correo | `notificaciones@empresa.com` |
| `SMTP_PASSWORD` | App Password de Gmail | `*****` |
| `TZ` | Zona horaria | `America/Mexico_City` |

---

## Levantamiento del proyecto

### Entorno local (desarrollo)

Para desarrollo local se utiliza el compose de producción apuntando a una instancia local de SQL Server.

```bash
# 1. Clonar el repositorio
git clone <url-del-repositorio>
cd SistemaDeTickets

# 2. Configurar variables de entorno
cp .env.example .env
# Editar .env con los valores de la instancia local de SQL Server y SMTP

# 3. Construir la imagen PHP con drivers sqlsrv
docker compose -f docker-compose.yml build

# 4. Levantar los contenedores
docker compose -f docker-compose.yml up -d

# 5. Verificar que los contenedores están corriendo
docker compose -f docker-compose.yml ps

# 6. Consultar logs en tiempo real
docker compose -f docker-compose.yml logs -f

# La aplicación estará disponible en: http://localhost:8006
```

**Detener el entorno:**

```bash
docker compose -f docker-compose.yml down
```

---

### Entorno de pruebas

El entorno de pruebas utiliza una base de datos separada y se expone en el puerto `9006`. El build siempre se realiza sin caché para garantizar que se incorpora el código más reciente.

```bash
# 1. Configurar las variables _TEST en el archivo .env
#    (DB_HOST_TEST, DB_DATABASE_TEST, DB_USERNAME_TEST, DB_PASSWORD_TEST, HTTP_PORT_TEST)

# 2. Construir sin caché
docker compose -f docker-compose.test.yml build --no-cache

# 3. Levantar el entorno de pruebas
docker compose -f docker-compose.test.yml up -d

# 4. Verificar estado
docker compose -f docker-compose.test.yml ps

# 5. Consultar logs
docker compose -f docker-compose.test.yml logs -f

# La aplicación de pruebas estará disponible en: http://localhost:9006
```

**Detener el entorno de pruebas:**

```bash
docker compose -f docker-compose.test.yml down
```

---

### Entorno de producción

En producción el despliegue es **automático** mediante GitHub Actions (ver sección CI/CD). Para un despliegue manual en el servidor de producción:

```bash
# En el servidor Windows (PowerShell), desde C:\deploy\SistemaDeTicketsProduccion

# Detener contenedores actuales
docker compose -f docker-compose.yml down

# Reconstruir imagen
docker compose -f docker-compose.yml build

# Levantar en segundo plano
docker compose -f docker-compose.yml up -d

# Verificar estado de los contenedores
docker compose -f docker-compose.yml ps

# Ver logs
docker compose -f docker-compose.yml logs -f

# Limpiar imágenes antiguas
docker image prune -f
```

> **Requisito:** El archivo `.env` debe existir en `C:\deploy\SistemaDeTicketsProduccion\.env` con las credenciales de producción antes de ejecutar cualquier comando.

---

## CI/CD — Despliegue automático

El proyecto utiliza GitHub Actions con runners **self-hosted en Windows Server**.

| Rama | Workflow | Destino | Puerto |
|---|---|---|---|
| `main` | `deploy-production.yml` | `C:\deploy\SistemaDeTicketsProduccion` | `8006` |
| `dev` | `deploy-test.yml` | `C:\deploy\SistemaDeTicketsTest` | `9006` |

**Pasos del pipeline:**

1. Checkout del código fuente.
2. Verificación de que el archivo `.env` existe en el directorio de despliegue.
3. Copia de archivos al directorio de despliegue (excluye `.env` y `nginx/logs`).
4. Verificación de la instalación de Docker y accesibilidad del daemon.
5. Detención de contenedores en ejecución.
6. Build de la imagen Docker.
7. Inicio de los contenedores en modo `detached`.
8. Health check HTTP (5 intentos, 2 segundos entre intentos).
9. Limpieza de imágenes antiguas (`docker image prune -f`).

Los pipelines pueden ejecutarse también de forma manual desde la pestaña **Actions** en GitHub (`workflow_dispatch`).

---

## Configuración técnica

### PHP (`php/custom.ini`)

| Parámetro | Valor |
|---|---|
| `memory_limit` | 256M |
| `max_execution_time` | 300s |
| `upload_max_filesize` | 50M |
| `post_max_size` | 50M |
| `date.timezone` | America/Mexico_City |
| `display_errors` | Off |
| `opcache.enable` | On |

### Nginx (`nginx/nginx.conf`)

| Parámetro | Valor |
|---|---|
| `client_max_body_size` | 50M |
| `fastcgi_read_timeout` | 300s |
| Cache de assets estáticos | 30 días |
| Acceso a `.env`, `.log`, `.sql` | Denegado (404) |
| Acceso a archivos ocultos (`.*`) | Denegado |

### Dockerfile

La imagen base es `php:8.2-fpm`. Durante el build se instalan:

- Drivers de Microsoft ODBC 18 (`msodbcsql18`, `unixodbc-dev`).
- Extensiones PHP: `sqlsrv`, `pdo_sqlsrv`, `pdo`, `mbstring`, `gd`, `zip`, `bcmath`.
- Las extensiones `sqlsrv` y `pdo_sqlsrv` se instalan vía PECL.

### Integración con Traefik

El contenedor `nginx` en producción se conecta a la red externa `traefik_network`. El enrutamiento se configura mediante labels de Docker:

- **Regla:** `PathPrefix('/tickets')` o `PathPrefix('/Tickets')`
- **Middleware:** Strip de prefijo (`/tickets`, `/Tickets`) antes de pasar la petición a Nginx.

Para que el stack de producción funcione, la red `traefik_network` debe existir previamente:

```bash
docker network create traefik_network
```

---

## Buenas prácticas

- **Variables de entorno:** Nunca incluir credenciales directamente en el código PHP. Utilizar siempre las variables de entorno inyectadas por Docker Compose y accederlas con `getenv()`.
- **Ramas:** Usar `dev` para desarrollo y pruebas; solo merges probados van a `main` (producción).
- **Archivo `.env`:** Debe crearse manualmente en el servidor antes del primer despliegue. No se copia automáticamente por el pipeline para evitar sobreescritura accidental.
- **Logs de Nginx:** Monitorizados en `nginx/logs/`. Rotar periódicamente para evitar crecimiento excesivo del disco.
- **Actualizaciones de PHPMailer:** La librería está incluida directamente en el repositorio bajo `/PHPMailer/`. Actualizar manualmente cuando se publiquen parches de seguridad.
- **Archivos de subida:** El directorio `uploads/` está excluido del repositorio. Asegurarse de que exista en el servidor con permisos adecuados para `www-data`.
