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
Cada registro incluye el desglose en días, horas y minutos (jornada de 8 horas)
más un `texto` legible:

```json
{
  "data": [
    {
      "gestion": 2025,
      "dias_disponibles": 8.74,
      "dias": 8,
      "horas": 5,
      "minutos": 55,
      "texto": "8 días, 5 horas y 55 minutos"
    }
  ],
  "meta": {
    "total_dias_disponibles": 8.74,
    "total_dias": 8,
    "total_horas": 5,
    "total_minutos": 55,
    "total_texto": "8 días, 5 horas y 55 minutos"
  }
}
```

### Consultar compensaciones

```http
GET /empleados/{empleado}/compensaciones
```

Incluye solamente saldos con estado `disponible` y
`meta.total_horas_disponibles`, con desglose en horas y minutos:

```json
{
  "data": [
    {
      "gestion": 2025,
      "cantidad_horas": 5.5,
      "horas": 5,
      "minutos": 30,
      "texto": "5 horas y 30 minutos",
      "fecha_registro": "2025-03-01"
    }
  ],
  "meta": {
    "total_horas_disponibles": 5.5,
    "total_horas": 5,
    "total_minutos": 30,
    "total_texto": "5 horas y 30 minutos"
  }
}
```

### Consultar solicitudes de vacaciones

```http
GET /empleados/{empleado}/solicitudes-vacaciones
```

Cada solicitud incluye `dias_solicitados` más su desglose
(`dias`, `horas`, `minutos`, `texto`).

### Consultar solicitudes de compensaciones

```http
GET /empleados/{empleado}/solicitudes-compensaciones
```

Cada solicitud incluye `horas_solicitadas` más su desglose
(`horas`, `minutos`, `texto`).

La API es de solo lectura. La recepción del webhook y la gestión de la
conversación se realizan en el servicio NestJS.
