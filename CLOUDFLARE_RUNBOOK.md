# Cloudflare Deployment Runbook — SistemaDeTickets

Configuración paso a paso para exponer el portal de tickets a internet de forma segura.

**Asunción:** dominio `tickets.bacrocorp.com` (o el que uses) ya está en Cloudflare.
Reemplaza ese hostname por el real en cada paso.

---

## 0. Requisitos previos

- Branch `security/phase0-cleanup` mergeado a `dev` y desplegado al servidor.
- Servidor de producción accesible vía Traefik en puerto 8006 (HTTP) o el que tengas configurado.
- Cuenta Cloudflare con acceso al dominio.
- Acceso RDP / consola al servidor para configurar firewall del SO.

> ⚠️ **NO continúes sin completar el Bloque B del checklist (backend Rust)** si tu modelo
> de amenaza incluye atacantes con LAN access o que puedan descubrir tu IP de origen.
> El frontend está blindado, pero el backend en `0.0.0.0:3000` con `CorsLayer::permissive()`
> sigue siendo una superficie significativa.

---

## 1. DNS

1. Cloudflare → tu dominio → DNS → Records.
2. Crea / verifica el registro:
   - Type: `A`
   - Name: `tickets` (o el subdominio elegido)
   - IPv4: la IP pública de tu servidor
   - Proxy status: **Proxied** (nube naranja). Esto es crítico — sin proxy no hay WAF.
   - TTL: Auto
3. **No** crees un A record para el puerto 3000 ni para ningún otro servicio interno.
4. Verifica con `dig tickets.bacrocorp.com` que la IP que devuelve Cloudflare NO es tu IP real
   (debe ser una IP del rango 104.x / 172.x de Cloudflare).

---

## 2. SSL / TLS

1. SSL/TLS → Overview → SSL/TLS encryption mode: **Full (strict)**.
2. SSL/TLS → Edge Certificates:
   - **Always Use HTTPS:** ON
   - **Automatic HTTPS Rewrites:** ON
   - **Minimum TLS Version:** 1.2
   - **TLS 1.3:** ON
   - **Opportunistic Encryption:** ON
3. SSL/TLS → Origin Server → Create Certificate:
   - Hostnames: `tickets.bacrocorp.com`, `*.bacrocorp.com` (si necesitas wildcard)
   - Validity: 15 years
   - Descarga el certificado y la llave; instálalos en Traefik (vía `acme.json` o `tls.certificates`).
4. **Resultado esperado:** `https://tickets.bacrocorp.com` carga sin errores SSL, y `http://...`
   redirige a `https://...`.

---

## 3. Firewall del origen (CRÍTICO — requiere RDP al servidor)

Tu servidor expone hoy el puerto 8006 al mundo. Después de poner el DNS detrás de Cloudflare,
debes **rechazar todo lo que no venga de IPs de Cloudflare** y **bloquear el puerto 3000**
del Rust API a cualquier interfaz que no sea loopback.

### Opción A: Windows Firewall (nuestro caso)

```powershell
# Bloquear puerto 3000 al exterior, dejar solo loopback y red Docker
New-NetFirewallRule -DisplayName "Block 3000 external" `
    -Direction Inbound -Protocol TCP -LocalPort 3000 `
    -Action Block -RemoteAddress Any

New-NetFirewallRule -DisplayName "Allow 3000 docker" `
    -Direction Inbound -Protocol TCP -LocalPort 3000 `
    -Action Allow -RemoteAddress 127.0.0.1, 172.16.0.0/12

# Restringir 8006 solo a IPs de Cloudflare (lista actualizada en cloudflare.com/ips-v4)
$cfIPs = @(
    "173.245.48.0/20", "103.21.244.0/22", "103.22.200.0/22",
    "103.31.4.0/22",   "141.101.64.0/18", "108.162.192.0/18",
    "190.93.240.0/20", "188.114.96.0/20", "197.234.240.0/22",
    "198.41.128.0/17", "162.158.0.0/15",  "104.16.0.0/13",
    "104.24.0.0/14",   "172.64.0.0/13",   "131.0.72.0/22"
)

# Primero borrar regla previa abierta a Any si existe
Get-NetFirewallRule -DisplayName "Tickets HTTP*" -ErrorAction SilentlyContinue | Remove-NetFirewallRule

New-NetFirewallRule -DisplayName "Tickets HTTP CF only" `
    -Direction Inbound -Protocol TCP -LocalPort 8006 `
    -Action Allow -RemoteAddress $cfIPs

New-NetFirewallRule -DisplayName "Tickets HTTP block all others" `
    -Direction Inbound -Protocol TCP -LocalPort 8006 `
    -Action Block -RemoteAddress Any
```

### Verificación

Desde tu laptop (fuera de Cloudflare):
```bash
curl -v http://<IP_PUBLICA_DEL_SERVIDOR>:8006   # debe colgar / timeout
curl -v http://<IP_PUBLICA_DEL_SERVIDOR>:3000   # debe colgar / timeout
curl -v https://tickets.bacrocorp.com           # debe responder
```

> Si tu IP de origen aún se filtra (vía SSL Cert transparency, censys.io, etc.), un atacante
> que la descubra **no podrá** llegar al puerto 8006 ni al 3000.

---

## 4. WAF — Managed Rules

1. Security → WAF → Managed rules → Cloudflare Managed Ruleset: **Enable**.
   - Action: `Block` (default)
   - Sensitivity: `High`
2. OWASP Core Ruleset: **Enable**.
   - Action: `Block`
   - Paranoia level: `PL2` (empezar). Si genera falsos positivos, bajar a `PL1`.
3. Security → WAF → Custom rules → Create rule:

### Regla 1: bloquear archivos eliminados (belt-and-braces)

```
Field: URI Path
Operator: matches regex
Value: ^/(abc|upload|Login_Procesar|new[%20\s]4|t1|PruebaTabla|PruebaAsignar1|demopruebatab|FORMSGRES|MenuTablero2|MT2|LoginMant|LoginTicket)\.(php|html?)$
Action: Block
```

### Regla 2: bloquear PHPMailer / vendor / config directos

```
Field: URI Path
Operator: matches regex
Value: ^/(PHPMailer|vendor|node_modules|config|auth_check|auth_check_api|api_client|roles|login_rate_limit)\.php$|^/(PHPMailer|vendor|node_modules)/
Action: Block
```

### Regla 3: bloquear UA's vacíos en POST

```
Expression: (http.request.method eq "POST" and http.user_agent eq "")
Action: Block
```

### Regla 4 (opcional): bloquear países fuera de operación

Si tu negocio es solo MX:
```
Expression: (ip.geoip.country ne "MX" and ip.geoip.country ne "US")
Action: Managed Challenge
```
US lo dejas para empleados viajando + workers de Cloudflare. Si quieres ser más estricto,
solo `MX`.

---

## 5. Rate Limiting

Security → WAF → Rate limiting rules → Create rule.

### Regla A: protección de login (CRÍTICA)

```
Name: Loginti rate limit
Expression: (http.request.uri.path eq "/Loginti.php" and http.request.method eq "POST")
Characteristics: IP address
Period: 1 minute
Requests: 5
Action: Block
Block duration: 10 minutes
```

### Regla B: protección de creación de tickets pública

```
Name: Public ticket forms rate limit
Expression: (http.request.uri.path matches "^/(FormTic|FormTic1|FormSG|FormSoporte|FormServVehi)\.php$" and http.request.method eq "POST")
Characteristics: IP address
Period: 1 minute
Requests: 10
Action: Block
Block duration: 10 minutes
```

### Regla C: protección global

```
Name: Global per-IP throttle
Expression: (true)
Characteristics: IP address
Period: 1 minute
Requests: 200
Action: Managed Challenge
```

---

## 6. Bot Fight Mode

Security → Bots → Bot Fight Mode: **ON**.

Si tienes plan Pro o superior:
- **Super Bot Fight Mode:** ON, set `Definitely Automated → Block`, `Likely Automated → Managed Challenge`.

---

## 7. Page Rules / Cache Rules

Cache → Configuration → Cache Rules:

### Regla A: NO cachear PHP

```
If: URI Path ends with ".php"
Then:
  Cache eligibility: Bypass cache
  Edge TTL: Disable
```

### Regla B: cachear estáticos agresivamente

```
If: URI Path matches "\.(jpg|jpeg|png|gif|ico|svg|webp|css|js|woff2?|pdf)$"
Then:
  Cache eligibility: Eligible for cache
  Edge TTL: 1 month
  Browser TTL: 1 week
```

---

## 8. Cloudflare Access (Zero Trust) — opcional pero MUY recomendado

Esto coloca un SSO de Google/Microsoft DELANTE de tu portal. Solo empleados con
correo `@bacrocorp.com` podrán siquiera ver el formulario de login.

1. Zero Trust dashboard → Access → Applications → Add an application → Self-hosted.
2. Application name: `BacrocorpTickets`
3. Session duration: `8 hours`
4. Application domain: `tickets.bacrocorp.com`
5. **Identity providers:**
   - Configura primero el IdP en Settings → Authentication. Si BacroCorp usa Google
     Workspace, agrega Google. Si Microsoft 365, agrega Azure AD / Entra ID.
6. Policies → Add a policy:
   - Name: `Empleados Bacrocorp`
   - Action: `Allow`
   - Include: `Emails ending in @bacrocorp.com`
7. **Bypass rules** — ahora muy reducidas (política nueva: TODA página requiere login).

   Solo permite bypass para el login mismo y assets estáticos:

   - Add policy → Action: `Bypass`
   - Include: `Everyone`
   - Path matches:
     ```
     /Loginti.php          (necesario para que CF Access no se ponga DELANTE del login interno)
     /Logo.png
     /Logo2.png
     /favicon.ico
     /*.css
     /*.js
     /*.woff2
     /*.png
     /*.jpg
     /*.jpeg
     /*.gif
     /*.svg
     /*.webp
     ```

   **NOTA importante:** las páginas que antes eran públicas (`FormTic.php`, `FormSG.php`,
   `FormSoporte.php`, `FormServVehi.php`, `FirmarLiberacion.php`, `FirmarSolicitudBaja.php`)
   ahora requieren sesión PHP. Si pones CF Access encima, los empleados harán DOBLE login:
   primero SSO de Google/Microsoft (CF Access), después usuario+contraseña en `Loginti.php`.
   Esa redundancia es la postura más segura. Si quieres evitar la doble autenticación, deja
   solo la PHP (sin CF Access) — pero entonces el rate-limit de Cloudflare se vuelve más
   crítico.

8. Resultado: cualquier intento de cargar `/IniSoport.php`, `/Asignarticket.php`,
   `/FormTic.php`, etc. fuerza primero SSO de Cloudflare Access y después login PHP.
   Esto bloquea ~99% de los ataques automatizados aunque el código tuviera más bugs.

---

## 9. Logging / Observability

Logs → Logpush (requiere plan Pro/Business/Enterprise) o usa el plan gratuito con dashboards:

- Analytics & Logs → Security → review diario de:
  - WAF Events (qué bloqueó)
  - Rate Limiting Events
  - Bot Score distribution
- Configura **Notifications** (Account home → Notifications):
  - Tipo: `Security Events Alert` para `WAF (firewall)` events.
  - Destino: email del equipo de seguridad.

---

## 10. HSTS (etapa final, irreversible-ish)

`Strict-Transport-Security` ya está set por nginx con `max-age=300` (5 min) — bajo riesgo.

Plan de incremento:

| Semana | Acción | Edita en |
|---|---|---|
| 0 (ahora) | `max-age=300` (5 min) | `nginx/nginx.conf` |
| 1 | Verifica logs limpios → `max-age=86400` (1 día) | `nginx/nginx.conf` |
| 2 | `max-age=2592000` (30 días) | `nginx/nginx.conf` |
| 4 | `max-age=63072000; includeSubDomains` | `nginx/nginx.conf` |
| 8 | `max-age=63072000; includeSubDomains; preload` y submit a hstspreload.org | después de validar TODOS los subdominios HTTPS-only |

**No saltes etapas.** Una vez en preload, salir toma meses y rompe acceso a usuarios viejos
si pierdes HTTPS aunque sea brevemente.

---

## 11. Verificación post-deploy

Lista de smoke tests. Marca cada uno:

```
[ ]  curl -v http://<IP_ORIGEN>:8006        → timeout (firewall bloquea)
[ ]  curl -v http://<IP_ORIGEN>:3000        → timeout
[ ]  curl -I https://tickets.bacrocorp.com  → 200, headers HSTS+CSP+X-Frame
[ ]  curl https://tickets.bacrocorp.com/abc.php           → 410 Gone
[ ]  curl https://tickets.bacrocorp.com/Login_Procesar.php → 410 / 404
[ ]  curl https://tickets.bacrocorp.com/.env              → 404
[ ]  curl https://tickets.bacrocorp.com/uploads/test.php  → 403 / 404
[ ]  curl https://tickets.bacrocorp.com/PHPMailer/        → 404
[ ]  curl https://tickets.bacrocorp.com/config.php        → 404
[ ]  curl https://tickets.bacrocorp.com/auth_check.php    → 404
[ ]  Asignarticket.php sin sesión → redirect a Loginti
[ ]  Loginti.php POST con creds inválidas x6 → bloqueo (rate-limit)
[ ]  FormTic.php carga sin login (público)
[ ]  Login válido, IniSoport carga
[ ]  IniSoport sin permisos admin no muestra botones admin (si aplica)
[ ]  observabilityreview: Cloudflare Security tab muestra eventos
```

---

## 12. Rollback

Si algo se rompe en producción:

1. Cloudflare → DNS → registro `tickets` → Proxy status → cambia a **DNS only** (nube gris).
   Esto saca a Cloudflare del camino. Sigue funcionando con la IP de origen
   (siempre que firewall permita 8006 desde fuera; reabrir temporalmente con
   `Get-NetFirewallRule -DisplayName "Tickets HTTP block all others" | Remove-NetFirewallRule`).
2. En el repo: `git revert <commit-de-merge>` y push para revertir cambios PHP.
3. Restaurar firewall original.
4. Postmortem antes de reintentar.

---

## Estado de tareas pendientes que NO cubre este runbook

- [x] ~~Backend Rust — fallback de JWT, CORS, body limit~~ — **HECHO** (Wave 3).
- [ ] **Backend Rust — JWT middleware en endpoints internos** (`/personal`, `/contactos`,
      `/auth/profile`, `/auth/verify`, `/snipeit/resguardo/:id`). Aún reachable sin auth.
      Mitigado a nivel red por el firewall del paso 3 + el binding del proceso.
- [ ] **`JWT_SECRET` en `.env` de prod** — DEBE ser un valor real de 64+ chars random.
      Generar con `openssl rand -hex 64` y poner en el `.env` ANTES de re-deployar.
      La API ahora rechaza arrancar con placeholders (`CAMBIA_ESTE_SECRET_EN_PRODUCCION`,
      `dev_secret_key`, etc.).
- [ ] **`ALLOWED_ORIGINS` en `.env` de prod** — debe contener la URL pública del PHP, p.ej.
      `ALLOWED_ORIGINS=https://tickets.bacrocorp.com`. Sin esto, la API solo acepta
      `http://localhost:8006` y los XHR de producción fallarán.
- [ ] **Rotar credenciales** filtradas (Larome03, SA Administrador1*, JWT Snipe-IT).
      Aunque no se rotan hoy, cualquier sospecha de fuga obliga a rotación inmediata.
- [ ] **Mover `.env` local fuera de `Downloads/`** del dev — riesgo de fuga vía sync/AV.
