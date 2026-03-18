# Pet Sightings API

## Resource Shape — PetSightingResource

```json
{
  "id": 1,
  "reportId": 1,
  "userId": 2,
  "location": {
    "latitude": -23.5550,
    "longitude": -46.6380
  },
  "addressHint": "Proximo ao parque",
  "description": "Vi um cachorro parecido com a descricao",
  "sightedAt": "2026-03-18T10:00:00.000000Z",
  "sharePhone": true,
  "isActive": true,
  "user": {
    "id": 2,
    "name": "Joao Silva"
  },
  "contactPhone": "+5511999999999",
  "createdAt": "2026-03-18T12:00:00.000000Z",
  "updatedAt": "2026-03-18T12:00:00.000000Z"
}
```

> `contactPhone` so aparece quando `sharePhone=true` **e** o request e feito pelo dono do pet perdido. Para outros usuarios, o campo nao aparece no JSON.

---

## Regras de Dominio

- Apenas reports com status LOST e `is_active=true` podem receber avistamentos
- Usuario nao pode avistar o proprio pet
- Anti-duplicidade: mesmo usuario + mesmo report dentro de 5 minutos retorna o avistamento existente sem criar novo
- Notificacao `PetSightingReported` e disparada ao dono do pet apenas quando o avistamento e efetivamente criado (nao no double-submit)
- `contactPhone` expoe o telefone primario (`is_primary=true`) do avistador para o dono do pet

---

## GET /api/v1/pet-reports/{petReport}/sightings

> Lista avistamentos de um report.

**Auth:** Bearer token
**Autorizacao:** Dono do report ou ADMIN

### Request

| Parametro | Tipo | Obrigatorio | Descricao |
|-----------|------|-------------|-----------|
| page | integer | nao | Pagina (default: 1) |
| per_page | integer | nao | Itens por pagina (default: 10) |

### Response

**Status:** 200 OK (paginado)

```json
{
  "data": [
    {
      "id": 1,
      "reportId": 1,
      "userId": 2,
      "location": { "latitude": -23.5550, "longitude": -46.6380 },
      "addressHint": "Proximo ao parque",
      "description": "Vi um cachorro parecido",
      "sightedAt": "2026-03-18T10:00:00.000000Z",
      "sharePhone": true,
      "isActive": true,
      "user": { "id": 2, "name": "Joao Silva" },
      "contactPhone": "+5511999999999",
      "createdAt": "2026-03-18T12:00:00.000000Z",
      "updatedAt": "2026-03-18T12:00:00.000000Z"
    }
  ],
  "meta": { "...": "..." },
  "links": { "...": "..." }
}
```

### Regras de Negocio

- Ordenado por `created_at DESC`
- `contactPhone` so aparece para o dono do report quando `sharePhone=true`
- Eager load de `user.phones` (primario) para evitar N+1

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao e dono do report nem ADMIN |

---

## POST /api/v1/pet-reports/{petReport}/sightings

> Reporta um avistamento de pet perdido.

**Auth:** Bearer token
**Autorizacao:** Qualquer CLIENT autenticado
**Content-Type:** application/json

### Request

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| latitude | numeric | sim | between:-90,90 | Latitude do avistamento |
| longitude | numeric | sim | between:-180,180 | Longitude do avistamento |
| address_hint | string | nao | max:500 | Descricao textual do local |
| description | string | nao | max:2000 | Detalhes do avistamento |
| sighted_at | date | sim | before_or_equal:now | Quando o pet foi avistado |
| share_phone | boolean | nao | default:false | Se true, telefone primario do avistador fica visivel para o dono do pet |

### Response

**Status:** 201 Created (novo) ou 200 OK (double-submit)

```json
{
  "data": {
    "id": 1,
    "reportId": 1,
    "userId": 2,
    "location": { "latitude": -23.5550, "longitude": -46.6380 },
    "addressHint": "Proximo ao parque",
    "description": "Vi um cachorro parecido",
    "sightedAt": "2026-03-18T10:00:00.000000Z",
    "sharePhone": false,
    "isActive": true,
    "user": { "id": 2, "name": "Joao Silva" },
    "createdAt": "2026-03-18T12:00:00.000000Z",
    "updatedAt": "2026-03-18T12:00:00.000000Z"
  }
}
```

### Regras de Negocio

- Report deve ter status LOST e `is_active=true`
- Usuario nao pode avistar o proprio pet (422)
- Double-submit (mesmo usuario + report em <5min): retorna existente com status 200 sem criar novo
- Notificacao `PetSightingReported` disparada ao dono do pet apenas na criacao efetiva
- `sighted_at` nao pode ser no futuro

### Status Codes

| Status | Quando |
|--------|--------|
| 201 Created | Avistamento criado |
| 200 OK | Double-submit, retornou existente |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao e CLIENT |
| 422 Unprocessable Entity | Validacao falhou (proprio pet, report nao LOST, etc.) |

---

## GET /api/v1/pet-reports/{petReport}/sightings/{petSighting}

> Retorna detalhe de um avistamento.

**Auth:** Bearer token
**Autorizacao:** Dono do report, quem avistou, ou ADMIN

### Request

Sem body. IDs na URL.

### Response

**Status:** 200 OK

```json
{
  "data": {
    "id": 1,
    "reportId": 1,
    "userId": 2,
    "location": { "latitude": -23.5550, "longitude": -46.6380 },
    "addressHint": "Proximo ao parque",
    "description": "Vi um cachorro parecido",
    "sightedAt": "2026-03-18T10:00:00.000000Z",
    "sharePhone": true,
    "isActive": true,
    "user": { "id": 2, "name": "Joao Silva" },
    "contactPhone": "+5511999999999",
    "createdAt": "2026-03-18T12:00:00.000000Z",
    "updatedAt": "2026-03-18T12:00:00.000000Z"
  }
}
```

### Regras de Negocio

- `petSighting` deve pertencer ao `petReport` (404 caso contrario)
- `contactPhone` so aparece para o dono do report quando `sharePhone=true`

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao autorizado |
| 404 Not Found | Sighting nao encontrado ou nao pertence ao report |
