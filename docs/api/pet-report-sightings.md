# Pet Report Sightings API

> Avistamentos reportados pela comunidade dentro de um report de pet perdido. Diferente de `pet-sightings` (avistamentos independentes), estes sao vinculados a um report especifico.

## Resource Shape

### PetReportSightingResource

```json
{
  "data": {
    "id": 1,
    "reportId": 5,
    "userId": 3,
    "location": {
      "latitude": -22.9068,
      "longitude": -43.1729
    },
    "addressHint": "Proximo ao mercado da esquina",
    "description": "Vi um cachorro parecido correndo na rua",
    "sightedAt": "2026-03-16T10:00:00.000000Z",
    "sharePhone": true,
    "isActive": true,
    "user": { ... },
    "contactPhone": "+5521999999999",
    "createdAt": "2026-03-16T11:00:00.000000Z",
    "updatedAt": "2026-03-16T11:00:00.000000Z"
  }
}
```

> `user` e condicional (`whenLoaded`). `contactPhone` so aparece quando `sharePhone = true` E o requester e o dono do report.

---

## Endpoints

### GET /api/v1/pet-reports/{petReport}/sightings

> Lista avistamentos de um report.

**Auth:** Bearer token
**Policy:** viewAny — CLIENT/ADMIN podem ver reports LOST/FOUND; reports CANCELLED so visivel para owner e admin

#### Request

**Query params:** Paginacao padrao (`page`, `per_page`)

#### Response

**Status:** 200 OK (paginado)

Retorna colecao paginada de `PetReportSightingResource` com `user` eager-loaded. Ordenacao por `created_at DESC`.

#### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao autorizado a ver sightings deste report |
| 404 Not Found | Report nao encontrado |

---

### POST /api/v1/pet-reports/{petReport}/sightings

> Cria um avistamento em um report de pet perdido.

**Auth:** Bearer token
**Policy:** create (CLIENT)
**Content-Type:** application/json

#### Request

**Body:**

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| latitude | numeric | sim | between:-90,90 | Latitude do avistamento |
| longitude | numeric | sim | between:-180,180 | Longitude do avistamento |
| address_hint | string | nao | max:500 | Referencia do local |
| description | string | nao | max:2000 | Descricao do avistamento |
| sighted_at | datetime | sim | before_or_equal:now | Quando o animal foi avistado |
| share_phone | boolean | nao | — | Compartilhar telefone com o dono do report |

**Exemplo:**

```json
{
  "latitude": -22.9068,
  "longitude": -43.1729,
  "address_hint": "Proximo ao mercado da esquina",
  "description": "Vi um cachorro parecido correndo na rua",
  "sighted_at": "2026-03-16T10:00:00",
  "share_phone": true
}
```

#### Response

**Status:** 201 Created (novo) | 200 OK (usuario ja reportou neste report)

#### Regras de Negocio

- Report deve ter status `LOST` e estar ativo
- Usuario nao pode reportar avistamento no proprio report
- Se o usuario ja reportou um sighting neste report, o existente e atualizado (retorna 200)
- Location e armazenado como `GEOGRAPHY(POINT, 4326)` via PostGIS

#### Status Codes

| Status | Quando |
|--------|--------|
| 201 Created | Sighting criado |
| 200 OK | Sighting existente atualizado |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Role nao e CLIENT |
| 404 Not Found | Report nao encontrado |
| 422 Unprocessable Entity | Validacao falhou (report nao e LOST, report inativo, proprio report, etc.) |

---

### GET /api/v1/pet-reports/{petReport}/sightings/{petReportSighting}

> Exibe detalhes de um avistamento.

**Auth:** Bearer token
**Policy:** view — Owner do report, autor do sighting ou admin

#### Response

**Status:** 200 OK

Retorna `PetReportSightingResource` com `user` eager-loaded.

#### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao autorizado |
| 404 Not Found | Sighting nao pertence ao report ou nao encontrado |
