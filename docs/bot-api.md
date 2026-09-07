# API interna para el bot de RR. HH.

Esta API expone de forma autenticada y de solo lectura la información de
RR. HH. que necesita el bot administrado por NestJS. Laravel conserva los datos
y las reglas del negocio, mientras que NestJS recibe el webhook de WhatsApp y
gestiona las conversaciones.

## Autenticación automática

Los endpoints de datos requieren un token de Laravel Sanctum con la capacidad
`bot:read`. NestJS obtiene y renueva este token automáticamente.

Configura en Laravel:

```dotenv
RRHH_BOT_CLIENT_SECRET=secreto-aleatorio-compartido
RRHH_BOT_USER_EMAIL=rrhh-bot@internal.local
```

Crea una sola vez el usuario técnico:

```shell
php artisan app:crear-usuario-bot
```

NestJS debe usar el mismo `RRHH_BOT_CLIENT_SECRET` para solicitar un token:

```http
POST /api/v1/bot/auth/token
X-Bot-Client-Secret: secreto-aleatorio-compartido
Accept: application/json
```

El token emitido tiene la capacidad `bot:read` y vence 24 horas después. NestJS
lo mantiene en memoria y lo renueva automáticamente. Para consultar datos lo
envía como Bearer token:

```http
Authorization: Bearer TOKEN_GENERADO
Accept: application/json
```

El comando `app:crear-token-bot` continúa disponible para diagnóstico o acceso
manual, pero no participa en la renovación automática.

## Endpoints

Todos los endpoints usan el prefijo `/api/v1/bot`.

### Buscar empleado por teléfono

```http
GET /empleados/por-telefono/{telefono}
```

Acepta el número local, el prefijo de Bolivia `591` y el sufijo
`@s.whatsapp.net`. Solo devuelve empleados activos.

### Consultar vacaciones

```http
GET /empleados/{empleado}/vacaciones
```

Incluye los saldos por gestión y `meta.total_dias_disponibles`.

### Consultar compensaciones

```http
GET /empleados/{empleado}/compensaciones
```

Incluye solamente saldos con estado `disponible` y
`meta.total_horas_disponibles`.

### Consultar solicitudes de vacaciones

```http
GET /empleados/{empleado}/solicitudes-vacaciones
```

### Consultar solicitudes de compensaciones

```http
GET /empleados/{empleado}/solicitudes-compensaciones
```

La API es de solo lectura. La recepción del webhook y la gestión de la
conversación se realizan en el servicio NestJS.
