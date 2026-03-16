# Pet Reports API

## Resource Shapes

### PetReportResource

```json
{
  "data": {
    "id": 1,
    "petId": 5,
    "userId": 2,
    "status": "LOST",
    "location": {
      "latitude": -22.9068,
      "longitude": -43.1729
    },
    "addressHint": "Perto da praca central",
    "description": "Fugiu pelo portao aberto",
    "lostAt": "2026-03-15T14:00:00.000000Z",
    "foundAt": null,
    "isActive": true,
    "pet": { ... },
    "user": { ... },
    "matches": [ ... ],
    "matchesCount": 3,
    "createdAt": "2026-03-15T15:30:00.000000Z",
    "updatedAt": "2026-03-15T15:30:00.000000Z"
  }
}
```

> `pet`, `user`, `matches` e `matchesCount` sao condicionais (`whenLoaded`/`whenCounted`). O campo `user` so aparece para Admin.

### PetMatchResource

```json
{
  "data": {
    "id": 1,
    "reportId": 1,
    "matchedPetId": 42,
    "score": "85.50",
    "distanceMeters": "1234.56",
    "status": "PENDING",
    "matchedPet": { ... },
    "createdAt": "2026-03-15T16:00:00.000000Z",
    "updatedAt": "2026-03-15T16:00:00.000000Z"
  }
}
```

---

## Endpoints

### GET /api/v1/pet-reports

> Lista reports de pets perdidos.

**Auth:** Bearer token
**Policy:** viewAny (CLIENT ou ADMIN)

#### Request

**Query params:**

| Param | Tipo | Obrigatorio | Default | Descricao |
|-------|------|-------------|---------|-----------|
| pet_id | int | nao | — | Filtra por pet especifico |
| status | string | nao | — | Filtro: `LOST`, `FOUND`, `CANCELLED` |
| latitude | numeric | nao | — | Latitude para busca por proximidade. `required_with: longitude` |
| longitude | numeric | nao | — | Longitude para busca por proximidade. `required_with: latitude` |
| radius_km | numeric | nao | 10 | Raio de busca em km (1-100) |
| page | int | nao | 1 | Pagina |
| per_page | int | nao | 15 | Itens por pagina |

#### Response

**Status:** 200 OK (paginado)

#### Regras de Negocio

- Client ve apenas seus proprios reports
- Admin ve todos os reports (com `user` eager-loaded)
- Com `latitude` + `longitude`, filtra por proximidade via PostGIS `ST_DWithin` e ordena por distancia ASC
- Sem `latitude`/`longitude`, nao aplica ordenacao explicita — a ordem de retorno nao deve ser considerada garantida

#### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 422 Unprocessable Entity | Validacao falhou (status invalido, lat sem lng, etc.) |

---

### POST /api/v1/pet-reports

> Cria um report de pet perdido.

**Auth:** Bearer token
**Policy:** create (CLIENT)
**Content-Type:** application/json

#### Request

**Body:**

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| pet_id | int | sim | exists:pets (active, nao deletado) | ID do pet perdido |
| latitude | numeric | sim | between:-90,90 | Latitude do local |
| longitude | numeric | sim | between:-180,180 | Longitude do local |
| address_hint | string | nao | max:500 | Referencia do local |
| description | string | nao | max:2000 | Descricao das circunstancias |
| lost_at | datetime | sim | before_or_equal:now | Data/hora em que o pet se perdeu |

**Exemplo:**

```json
{
  "pet_id": 5,
  "latitude": -22.9068,
  "longitude": -43.1729,
  "address_hint": "Perto da praca central",
  "description": "Fugiu pelo portao aberto durante a chuva",
  "lost_at": "2026-03-15T14:00:00"
}
```

#### Response

**Status:** 201 Created

```json
{
  "data": {
    "id": 1,
    "petId": 5,
    "userId": 2,
    "status": "LOST",
    "location": {
      "latitude": -22.9068,
      "longitude": -43.1729
    },
    "addressHint": "Perto da praca central",
    "description": "Fugiu pelo portao aberto durante a chuva",
    "lostAt": "2026-03-15T14:00:00.000000Z",
    "foundAt": null,
    "isActive": true,
    "pet": { ... },
    "createdAt": "2026-03-15T15:30:00.000000Z",
    "updatedAt": "2026-03-15T15:30:00.000000Z"
  }
}
```

#### Regras de Negocio

- O pet deve pertencer ao usuario autenticado
- O pet deve estar ativo (`is_active = true`) e nao deletado
- O pet nao pode ter outro report ativo com status `LOST`
- Location e armazenado como `GEOGRAPHY(POINT, 4326)` via PostGIS
- Apos commit da transacao, dispatcha job `ProcessPetMatching` via `DB::afterCommit()`

#### Status Codes

| Status | Quando |
|--------|--------|
| 201 Created | Report criado |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Role nao e CLIENT |
| 422 Unprocessable Entity | Validacao falhou (pet de outro user, pet ja com report ativo, lost_at futuro, etc.) |

---

### GET /api/v1/pet-reports/{petReport}

> Exibe detalhes de um report.

**Auth:** Bearer token
**Policy:** view (Owner ou ADMIN)

#### Response

**Status:** 200 OK

Retorna `PetReportResource` com `matchesCount`. Admin tambem recebe o campo `user`.

#### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao e owner nem admin |
| 404 Not Found | Report nao encontrado |

---

### PUT /api/v1/pet-reports/{petReport}

> Atualiza um report de pet perdido.

**Auth:** Bearer token
**Policy:** update (Owner E status=LOST)
**Content-Type:** application/json

#### Observacoes

> PUT usado como partial update neste projeto — campos omitidos permanecem inalterados.

#### Request

**Body (todos sometimes):**

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| latitude | numeric | nao | between:-90,90 | Nova latitude |
| longitude | numeric | nao | between:-180,180 | Nova longitude |
| address_hint | string | nao | max:500 | Nova referencia |
| description | string | nao | max:2000 | Nova descricao |
| lost_at | datetime | nao | before_or_equal:now | Nova data de perda |

**Exemplo:**

```json
{
  "latitude": -22.9100,
  "longitude": -43.1750,
  "description": "Atualizado: visto perto do parque"
}
```

#### Response

**Status:** 200 OK

#### Regras de Negocio

- Apenas reports com status `LOST` podem ser atualizados
- Se location muda (latitude ou longitude diferente do atual), matches `PENDING` sao deletados e `ProcessPetMatching` e re-dispatchado via `DB::afterCommit()`
- Matches `DISMISSED` permanecem intactos apos mudanca de location

#### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao e owner ou status nao e LOST |
| 422 Unprocessable Entity | Validacao falhou |

---

### PATCH /api/v1/pet-reports/{petReport}/cancel

> Cancela um report de pet perdido.

**Auth:** Bearer token
**Policy:** cancel (Owner E status=LOST)

#### Request

Sem body.

#### Response

**Status:** 200 OK

```json
{
  "data": {
    "id": 1,
    "status": "CANCELLED",
    ...
  }
}
```

#### Regras de Negocio

- Status muda para `CANCELLED`
- Todos os matches com status `PENDING` viram `DISMISSED`
- Operacao executada em transacao

#### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao e owner ou status nao e LOST |

---

### PATCH /api/v1/pet-reports/{petReport}/found

> Marca um report como encontrado.

**Auth:** Bearer token
**Policy:** markFound (Owner E status=LOST)
**Content-Type:** application/json

#### Request

**Body (opcional):**

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| confirmed_match_id | int | nao | integer, deve pertencer ao report | ID do match confirmado |

**Exemplo sem match:**

```json
{}
```

**Exemplo com match:**

```json
{
  "confirmed_match_id": 42
}
```

#### Response

**Status:** 200 OK

#### Regras de Negocio

- Status muda para `FOUND`, `found_at` recebe timestamp atual
- **Com `confirmed_match_id`**: o match especificado vira `CONFIRMED`, todos os demais `PENDING` viram `DISMISSED`
- **Sem `confirmed_match_id`**: todos os matches `PENDING` viram `DISMISSED`
- Se `confirmed_match_id` nao pertence ao report, retorna 422

#### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao e owner ou status nao e LOST |
| 422 Unprocessable Entity | `confirmed_match_id` nao pertence ao report |

---

### GET /api/v1/pet-reports/{petReport}/matches

> Lista matches de um report.

**Auth:** Bearer token
**Policy:** viewMatches (Owner ou ADMIN)

#### Request

**Query params:**

| Param | Tipo | Obrigatorio | Default | Descricao |
|-------|------|-------------|---------|-----------|
| status | string | nao | `PENDING` | Filtro: `PENDING`, `CONFIRMED`, `DISMISSED` |

#### Response

**Status:** 200 OK (collection, nao paginado)

> Nao paginado porque o sistema limita a `MAX_MATCHES = 20` por report.

Ordenacao: `score` DESC, `distance_meters` ASC, `id` ASC.

Cada match inclui `matchedPet` com photos, characteristics, breed e secondaryBreed.

#### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao e owner nem admin |
| 404 Not Found | Report nao encontrado |

---

### PATCH /api/v1/pet-reports/{petReport}/matches/{petMatch}/dismiss

> Descarta um match.

**Auth:** Bearer token
**Policy:** dismissMatch (Owner)

#### Request

Sem body.

#### Response

**Status:** 200 OK

```json
{
  "data": {
    "id": 1,
    "reportId": 1,
    "matchedPetId": 42,
    "score": "85.50",
    "distanceMeters": "1234.56",
    "status": "DISMISSED",
    ...
  }
}
```

#### Regras de Negocio

- Match deve pertencer ao report (senao 404)
- Report deve ter status `LOST` (senao 422 — validado na Action)
- Match deve ter status `PENDING` (senao 422 — validado na Action)

#### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao e owner |
| 404 Not Found | Match nao pertence ao report |
| 422 Unprocessable Entity | Report nao e LOST ou match nao e PENDING |

---

### PATCH /api/v1/pet-reports/{petReport}/matches/{petMatch}/confirm

> Confirma um match, marcando o report como encontrado.

**Auth:** Bearer token
**Policy:** confirmMatch (Owner)

#### Request

Sem body.

#### Response

**Status:** 200 OK

```json
{
  "data": {
    "id": 1,
    "reportId": 1,
    "matchedPetId": 42,
    "score": "85.50",
    "distanceMeters": "1234.56",
    "status": "CONFIRMED",
    ...
  }
}
```

#### Regras de Negocio

- Match deve pertencer ao report (senao 404)
- Report deve ter status `LOST` (senao 422 — validado na Action)
- Match deve ter status `PENDING` (senao 422 — validado na Action)
- Confirmar delega para `MarkPetReportFound`: report vira `FOUND`, match vira `CONFIRMED`, demais `PENDING` viram `DISMISSED`

#### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao e owner |
| 404 Not Found | Match nao pertence ao report |
| 422 Unprocessable Entity | Report nao e LOST ou match nao e PENDING |
