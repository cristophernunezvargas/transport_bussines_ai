# Transportes M.N.S.T — Gestión de costos y rentabilidad de flota

Sistema de gestión para una empresa de transporte de carga. Existe para responder una sola pregunta con datos confiables y rastreables:

> **¿Cuánto cuesta realmente operar cada camión y cuánto dinero genera realmente cada uno?**

## Qué hace

- **Costo real por camión, viaje, ruta y cliente**: combustible, mantenimiento, llantas, gastos y costos fijos del camión, más una parte de los costos generales (salarios, cargas sociales, horas de conductores) repartida según los días trabajados.
- **Rentabilidad**: ingresos, costo, utilidad y margen; costo e ingreso por km.
- **Mantenimiento**: programas preventivos con estado calculado (`AL_DIA`, `PROXIMO`, `VENCIDO`), historial de servicios, talleres y odómetro.
- **Disponibilidad y utilización** de la flota.
- **Dashboard** con resumen semanal (jueves a miércoles), mensual y anual, y alertas de mantenimiento. Los resúmenes semanal y mensual se pueden **descargar en PDF**.
- **Trazabilidad**: cada indicador se puede seguir hasta los registros que lo originaron.

Los km son un **estimado** (por defecto la distancia estándar de la ruta) mientras no se capture el odómetro por viaje; el sistema siempre los rotula así.

### Lo que no hace

- **No emite facturas ni registra pagos.** Las facturas se emiten en un sistema externo. Las tablas de facturación existen en el esquema para una futura importación, pero esta aplicación nunca crea ni edita facturas.
- No calcula depreciación ni costo de capital, ni hace seguimiento GPS.

## Stack

Laravel 13 · PHP 8.3 · MySQL (única fuente de verdad) · Livewire 4 · Flux 2 · Tailwind 4 · Vite (`vite-plus`) · PHPUnit · Larastan nivel 7 · dompdf para los PDF.

Reglas del proyecto: dinero siempre en columnas `DECIMAL`; validación con Form Requests; autorización con Policies; todo cálculo financiero crítico con pruebas. Zona horaria `America/Costa_Rica` e idioma español.

## Puesta en marcha

```bash
composer setup        # instala dependencias, crea .env, genera la clave, migra y compila assets
composer run dev      # servidor + cola + Vite
```

Para crear el primer acceso, define `ADMIN_EMAIL` y `ADMIN_PASSWORD` en el `.env` y ejecuta `php artisan db:seed --class=AdminUserSeeder`. Los valores reales solo viven en el `.env` de cada entorno, nunca en el repositorio.

```bash
php artisan test --compact     # pruebas
vendor/bin/pint                # estilo de código
composer types:check           # análisis estático (si falta memoria: vendor/bin/phpstan analyse --memory-limit=1G)
```

## Estructura

| Ruta | Contenido |
|---|---|
| `app/Services/Financial/` | Motor de cálculo: períodos, prorrateo, llantas, reparto de costos generales, costo y rentabilidad |
| `app/Livewire/` | Pantallas (lista, búsqueda, modal de crear/editar) por cada entidad |
| `app/Policies/`, `app/Http/Requests/` | Autorización y validación |
| `docs/` | Documentos vivos: negocio, finanzas, base de datos, arquitectura, decisiones (ADR) y pruebas |
| `CLAUDE.md` | Guía técnica detallada del proyecto |

Decisiones clave en [`docs/decisions/`](docs/decisions): método de reparto de costos generales, origen de la distancia del viaje, período semanal y asignación de pagos.

## Integraciones con n8n

n8n **mueve datos hacia y desde** el sistema, pero MySQL sigue siendo la única fuente de verdad. Ninguna hoja de cálculo es almacenamiento principal, y lo que llega por n8n pasa por las mismas validaciones que la captura manual.

### Ya disponible: horas de conductores

n8n envía las horas diarias que ya registra y el sistema las convierte en el total semanal (jueves a miércoles) de cada conductor, para no capturarlas dos veces.

- **Ruta:** `POST /api/integrations/driver-worklogs`, protegida con un token (`Authorization: Bearer ...`).
- **Configuración** (en el `.env` de cada entorno y en las credenciales de n8n, nunca en el repositorio): `N8N_API_TOKEN` con un secreto largo y aleatorio, y `N8N_USER_EMAIL` con el correo de un usuario creado en *Usuarios* (por ejemplo "n8n"), al que se atribuyen los registros importados. Sin token, la ruta rechaza todo.
- **Cuerpo:** una entrada por día trabajado.

```json
{
  "entries": [
    { "driver_name": "Iván", "work_date": "2026-11-23", "hours": 10.5 },
    { "driver_name": "Iván", "work_date": "2026-11-24", "hours": 9 }
  ]
}
```

- **Respuesta:** `{ "created": 1, "updated": 0, "unchanged": 0, "rejected": [] }`. En `rejected` vienen los conductores que no se pudieron importar (nombre desconocido o repetido, o más de 168 horas en la semana); el resto sí se importa.
- **Reglas:** el nombre se compara sin distinguir mayúsculas, tildes ni espacios con el nombre del conductor en el sistema. El total semanal se **fija** con los días recibidos, no se suma, así que reenviar la misma semana no duplica nada. Por eso n8n debe enviar **todos los días de la semana** que toque (jueves a miércoles) cada vez, no solo el del día. Si esa semana ya tenía horas escritas a mano, la importación las reemplaza (la nota escrita a mano se conserva).
- **Columnas de la hoja:** se usan `Name` (nombre del conductor), `workDate` y `hoursWorked`. El camión, las horas de inicio y fin y la descripción no se envían, porque las horas de conductores no se atribuyen a un camión (ADR 0001).
- **En n8n:** un nodo *Schedule* (por ejemplo cada noche), un nodo de Google Sheets que lea las filas de la semana en curso, un nodo *Code* o *Aggregate* que arme `entries` con esos tres campos, y un nodo *HTTP Request* `POST` con autenticación *Bearer*. Programa el flujo en hora de Costa Rica.

### Previstas

1. **Importación de estados de cuenta de combustible.** Las estaciones pueden entregarlos bajo pedido; n8n los leería y crearía los registros de combustible.
2. **Importación de facturas y pagos** desde el sistema externo donde se emiten, hacia las tablas de facturación que ya existen en el esquema.
3. **Envío automático de los PDF** semanal y mensual por correo o Telegram al cierre de cada período (el botón *Compartir* ya permite pasarlos por WhatsApp a mano).
4. **Avisos de mantenimiento** cuando un programa pase a `PROXIMO` o `VENCIDO`.

Para las previstas hace falta definir antes cómo entra la información, cómo se evitan duplicados y qué usuario figura como autor, tal como se resolvió para las horas de conductores.

## Despliegue

Se despliega con Forge. En cada entorno, el `.env` debe definir `APP_NAME="Transportes M.N.S.T"`, `APP_LOCALE=es` y, si se quiere cambiar, `APP_TIMEZONE` (por defecto `America/Costa_Rica`). `.npmrc` incluye `legacy-peer-deps=true` porque el alias de `vite-plus` no satisface el rango de peers de `@tailwindcss/vite`.
