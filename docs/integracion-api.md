# API de integración para aplicaciones externas

API de solo lectura para que otra aplicación consuma datos de RR. HH. Es independiente de la API del bot: usa su propio secreto, su propio usuario técnico y la habilidad `integracion:read`. Un token del bot no sirve aquí y viceversa.

## Autenticación

```http
POST /api/v1/integracion/auth/token
X-Integracion-Client-Secret: <RRHH_INTEGRACION_CLIENT_SECRET>
```

Respuesta:

```json
{ "data": { "access_token": "...", "token_type": "Bearer", "expires_at": "2026-10-15T12:00:00.000000Z" } }
```

El token expira a las 24 horas. Se envía en cada petición como `Authorization: Bearer <access_token>`. El endpoint de token admite 6 peticiones por minuto y el resto 60 por minuto.

## Endpoints

Base: `/api/v1/integracion`. Los empleados eliminados (borrado lógico) no se incluyen en ningún listado.

### Empleados

```http
GET /empleados
GET /empleados/{id}
```

Parámetros de `/empleados`: `estado` (`1` activos, `0` inactivos; sin valor devuelve todos) y `per_page` (1 a 100, por defecto 50). Está paginado (`data`, `links`, `meta`).

```json
{
  "id": 1,
  "nombre_completo": "Ana Perez",
  "correo_electronico": "ana@example.com",
  "telefono": "70000001",
  "nro_item": "145",
  "activo": true
}
```

`nro_item` es el del contrato vigente; es `null` si el empleado no tiene contrato vigente.

### Feriados

```http
GET /feriados?gestion=2026
```

Parámetros opcionales: `gestion` (año) y `estado` (`1` activos, `0` inactivos). Sin paginar, ordenado por fecha.

```json
{ "id": 1, "gestion": 2026, "nombre": "Dia del Trabajo", "fecha": "2026-05-01", "activo": true }
```

### Solicitudes

```http
GET /solicitudes-vacaciones
GET /solicitudes-compensaciones
```

Parámetros opcionales: `empleado_id`, `estado`, `desde` y `hasta` (formato `YYYY-MM-DD`; filtran por `fecha_inicio` en vacaciones y por `fecha_compensacion` en compensaciones) y `per_page`. Están paginadas y ordenadas de la más reciente a la más antigua.

Vacaciones:

```json
{
  "id": 10,
  "empleado": { "id": 1, "nombre_completo": "Ana Perez" },
  "fecha_inicio": "2026-04-06",
  "fecha_fin": "2026-04-08",
  "dias_solicitados": 3.5,
  "dias": 3,
  "horas": 4,
  "minutos": 0,
  "texto": "3 días y 4 horas",
  "estado": "aprobado"
}
```

Compensaciones: igual, con `fecha_compensacion`, `horas_solicitadas`, `horas`, `minutos` y `texto`. El día equivale a 8 horas.

## Ejemplos de uso

En los ejemplos, `https://rrhh.example.com` es la URL del sistema y `SECRETO` el valor de `RRHH_INTEGRACION_CLIENT_SECRET`.

### curl

```bash
# 1. Obtener el token
TOKEN=$(curl -s -X POST https://rrhh.example.com/api/v1/integracion/auth/token \
  -H "X-Integracion-Client-Secret: SECRETO" \
  -H "Accept: application/json" | jq -r '.data.access_token')

# 2. Empleados activos (50 por página)
curl -s "https://rrhh.example.com/api/v1/integracion/empleados?estado=1" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"

# 3. Un empleado
curl -s https://rrhh.example.com/api/v1/integracion/empleados/1 \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"

# 4. Feriados de la gestión 2026
curl -s "https://rrhh.example.com/api/v1/integracion/feriados?gestion=2026" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"

# 5. Vacaciones solicitadas en abril de 2026
curl -s "https://rrhh.example.com/api/v1/integracion/solicitudes-vacaciones?desde=2026-04-01&hasta=2026-04-30" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"

# 6. Compensaciones de un empleado
curl -s "https://rrhh.example.com/api/v1/integracion/solicitudes-compensaciones?empleado_id=1" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"
```

Conviene enviar siempre `Accept: application/json`; sin esa cabecera, los errores de validación o de autenticación pueden devolverse como redirección en vez de JSON.

### JavaScript (Node 18+ o navegador)

```js
const BASE = 'https://rrhh.example.com/api/v1/integracion';

async function obtenerToken() {
  const res = await fetch(`${BASE}/auth/token`, {
    method: 'POST',
    headers: { 'X-Integracion-Client-Secret': process.env.RRHH_SECRET, Accept: 'application/json' },
  });
  if (!res.ok) throw new Error(`Token: ${res.status}`);
  return (await res.json()).data.access_token;
}

// Recorre todas las páginas de un listado.
async function* paginar(token, ruta, params = {}) {
  let url = `${BASE}/${ruta}?${new URLSearchParams({ per_page: 100, ...params })}`;

  while (url) {
    const res = await fetch(url, { headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' } });
    if (!res.ok) throw new Error(`${ruta}: ${res.status}`);

    const cuerpo = await res.json();
    yield* cuerpo.data;
    url = cuerpo.links?.next ?? null;
  }
}

const token = await obtenerToken();

for await (const empleado of paginar(token, 'empleados', { estado: 1 })) {
  console.log(empleado.nombre_completo, empleado.correo_electronico, empleado.telefono, empleado.nro_item);
}
```

### PHP (Laravel)

```php
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

$token = Cache::remember('rrhh.token', now()->addHours(23), fn () => Http::baseUrl('https://rrhh.example.com/api/v1/integracion')
    ->withHeaders(['X-Integracion-Client-Secret' => config('services.rrhh.secret')])
    ->acceptJson()
    ->post('auth/token')
    ->throw()
    ->json('data.access_token'));

$cliente = Http::baseUrl('https://rrhh.example.com/api/v1/integracion')
    ->withToken($token)
    ->acceptJson();

$feriados = $cliente->get('feriados', ['gestion' => 2026])->throw()->json('data');

$solicitudes = $cliente->get('solicitudes-vacaciones', [
    'desde' => '2026-01-01',
    'hasta' => '2026-12-31',
    'estado' => 'aprobado',
])->throw()->json('data');
```

### Python

```python
import os
import requests

BASE = "https://rrhh.example.com/api/v1/integracion"

token = requests.post(
    f"{BASE}/auth/token",
    headers={"X-Integracion-Client-Secret": os.environ["RRHH_SECRET"], "Accept": "application/json"},
    timeout=10,
).json()["data"]["access_token"]

sesion = requests.Session()
sesion.headers.update({"Authorization": f"Bearer {token}", "Accept": "application/json"})

url = f"{BASE}/empleados"
params = {"estado": 1, "per_page": 100}
while url:
    cuerpo = sesion.get(url, params=params, timeout=10).json()
    for e in cuerpo["data"]:
        print(e["nombre_completo"], e["correo_electronico"], e["telefono"], e["nro_item"])
    url, params = cuerpo["links"]["next"], None
```

### Recomendaciones para el consumidor

- Guardar el token y reutilizarlo hasta unos minutos antes de `expires_at`; no pedir uno nuevo en cada petición (el endpoint de token admite 6 por minuto).
- Si una petición devuelve `401`, pedir un token nuevo y reintentar una vez.
- Para sincronizar solicitudes de forma incremental, consultar con `desde` igual a la fecha de la última sincronización.

## Errores

- `401`: token ausente o vencido, o credencial de servicio inválida.
- `403`: el token no tiene la habilidad `integracion:read`.
- `422`: parámetros inválidos; el cuerpo indica cuáles.
- `503`: la emisión de tokens no está configurada o falta el usuario técnico.
