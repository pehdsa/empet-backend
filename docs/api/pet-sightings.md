# Pet Sightings API

Avistamentos de pets sao entidades independentes — nao vinculadas diretamente a um report. O sistema de matching conecta sightings a reports automaticamente.

## Resource Shapes

### PetSightingResource

```json
{
  "id": 1,
  "userId": 2,
  "title": "Cachorro perdido visto no parque",
  "species": "DOG",
  "size": "MEDIUM",
  "sex": "MALE",
  "color": "golden",
  "breed": {
    "id": 12,
    "name": "Labrador Retriever",
    "species": "DOG"
  },
  "photos": [
    { "id": 1, "url": "https://s3.example.com/sightings/01HX...", "position": 0 }
  ],
  "characteristics": [
    { "id": 5, "name": "Orelha cortada", "category": "MARKING" }
  ],
  "location": {
    "latitude": -23.5550,
    "longitude": -46.6380
  },
  "addressHint": "Proximo ao parque",
  "description": "Vi um cachorro parecido com labrador, parecia assustado",
  "sightedAt": "2026-03-18T10:00:00.000000Z",
  "sharePhone": true,
  "user": {
    "id": 2,
    "name": "Joao Silva",
    "avatarUrl": null
  },
  "distanceMeters": 1234.56,
  "createdAt": "2026-03-18T12:00:00.000000Z",
  "updatedAt": "2026-03-18T12:00:00.000000Z"
}
```

> `breed`, `photos`, `characteristics` sao condicionais (`whenLoaded`). `distanceMeters` aparece quando a query inclui calculo de distancia.

### PetSightingClaimResource

```json
{
  "sightingId": 1,
  "sightingOwner": {
    "name": "Joao Silva",
    "phone": "+5511999999999",
    "phoneIsWhatsapp": true
  }
}
```

> `phone` e `phoneIsWhatsapp` sao `null` se o avistador nao compartilhou telefone (`share_phone=false`).

---

## Regras de Dominio

- Sightings sao entidades independentes com especies, porte, sexo, cor, raca e caracteristicas proprias
- Apos criacao, o job `ProcessSightingMatching` conecta automaticamente o sighting a reports compativeis
- Maximo de 3 fotos por sighting, convertidas para JPEG via `ImageConverter`
- Claims permitem que donos de pets perdidos entrem em contato com quem avistou

---

## GET /api/v1/pet-sightings

> Lista avistamentos da comunidade, ordenados por proximidade.

**Auth:** Bearer token
**Policy:** viewAny (CLIENT ou ADMIN)

### Request

**Query params:**

| Param | Tipo | Obrigatorio | Default | Descricao |
|-------|------|-------------|---------|-----------|
| latitude | numeric | sim | — | Latitude do usuario |
| longitude | numeric | sim | — | Longitude do usuario |
| radius_km | numeric | nao | 10 | Raio de busca em km (max: 50) |
| species | string | nao | — | Filtro: `DOG`, `CAT` |
| size | string | nao | — | Filtro: `SMALL`, `MEDIUM`, `LARGE` |
| page | int | nao | 1 | Pagina |
| per_page | int | nao | 15 | Itens por pagina |

### Response

**Status:** 200 OK (paginado)

Retorna colecao paginada de `PetSightingResource` com `distanceMeters`. Ordenacao por distancia ASC.

### Regras de Negocio

- Calcula distancia via PostGIS e ordena por proximidade
- Eager loads: photos, characteristics, breed, user

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 422 Unprocessable Entity | Validacao falhou |

---

## GET /api/v1/pet-sightings/my

> Lista avistamentos do usuario autenticado.

**Auth:** Bearer token

### Request

**Query params:**

| Param | Tipo | Obrigatorio | Default | Descricao |
|-------|------|-------------|---------|-----------|
| page | int | nao | 1 | Pagina |
| per_page | int | nao | 15 | Itens por pagina |

### Response

**Status:** 200 OK (paginado)

Retorna colecao paginada de `PetSightingResource`. Ordenacao por `created_at DESC`.

### Regras de Negocio

- Retorna apenas sightings do usuario autenticado
- Eager loads: photos, characteristics, breed, user

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |

---

## GET /api/v1/pet-sightings/map

> Retorna avistamentos para exibicao no mapa (nao paginado).

**Auth:** Bearer token

### Request

**Query params:**

| Param | Tipo | Obrigatorio | Default | Descricao |
|-------|------|-------------|---------|-----------|
| latitude | numeric | sim | — | Latitude central |
| longitude | numeric | sim | — | Longitude central |
| radius_km | numeric | nao | 10 | Raio de busca em km (max: 50) |
| species | string | nao | — | Filtro: `DOG`, `CAT` |
| size | string | nao | — | Filtro: `SMALL`, `MEDIUM`, `LARGE` |

### Response

**Status:** 200 OK (colecao simples, sem paginacao)

```json
{
  "data": [ ... ]
}
```

Limite tecnico de 500 registros. Cada item inclui `distanceMeters`.

### Regras de Negocio

- Filtra por raio via PostGIS `ST_DWithin`
- Ordenacao por distancia ASC
- Limite de 500 para protecao operacional

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 422 Unprocessable Entity | Validacao falhou |

---

## GET /api/v1/pet-sightings/{petSighting}

> Retorna detalhe de um avistamento.

**Auth:** Bearer token
**Policy:** view (qualquer usuario autenticado)

### Response

**Status:** 200 OK

Retorna `PetSightingResource` completo com photos, characteristics, breed, user.

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 404 Not Found | Sighting nao encontrado |

---

## POST /api/v1/pet-sightings

> Cria um novo avistamento de pet.

**Auth:** Bearer token
**Policy:** create (CLIENT)
**Content-Type:** multipart/form-data

### Request

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| title | string | sim | max:255 | Titulo do avistamento |
| latitude | numeric | sim | between:-90,90 | Latitude do avistamento |
| longitude | numeric | sim | between:-180,180 | Longitude do avistamento |
| sighted_at | date | sim | before_or_equal:now | Quando o pet foi avistado |
| species | string | sim | `DOG` ou `CAT` | Especie do pet avistado |
| size | string | nao | `SMALL`, `MEDIUM`, `LARGE` | Porte estimado |
| sex | string | nao | `MALE`, `FEMALE`, `UNKNOWN` | Sexo estimado |
| color | string | nao | max:100 | Cor principal |
| breed_id | int | nao | exists:breeds (active, mesma species) | Raca identificada |
| address_hint | string | nao | max:500 | Referencia do local |
| description | string | nao | max:2000 | Detalhes do avistamento |
| share_phone | boolean | nao | default:false | Se true, telefone primario fica visivel via claim |
| characteristic_ids[] | int[] | nao | exists:characteristics (active) | IDs de caracteristicas |
| photos[] | file[] | nao | max 3 arquivos, cada max 5MB, jpeg/png/webp/heic/heif | Fotos do pet avistado |

### Response

**Status:** 201 Created

Retorna `PetSightingResource` completo.

### Regras de Negocio

- Fotos sao convertidas para JPEG e armazenadas no S3
- Location armazenado como `GEOGRAPHY(POINT, 4326)` via PostGIS
- `breed_id` deve ser de raca ativa e da mesma especie informada
- Apos commit da transacao, dispatcha job `ProcessSightingMatching` para conectar com reports compativeis

### Status Codes

| Status | Quando |
|--------|--------|
| 201 Created | Sighting criado |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Role nao e CLIENT |
| 422 Unprocessable Entity | Validacao falhou |

---

## POST /api/v1/pet-sightings/{petSighting}/claim

> Reivindica um avistamento para obter contato do avistador.

**Auth:** Bearer token
**Policy:** claim (nao pode ser o proprio avistador)

### Request

Sem body.

### Response

**Status:** 200 OK

```json
{
  "data": {
    "sightingId": 1,
    "sightingOwner": {
      "name": "Joao Silva",
      "phone": "+5511999999999",
      "phoneIsWhatsapp": true
    }
  }
}
```

### Regras de Negocio

- Cria registro em `pet_sighting_claims` (unique constraint `[pet_sighting_id, user_id]`)
- Idempotente: se claim ja existe, retorna os mesmos dados sem criar duplicata
- Apenas no primeiro claim: notifica o avistador via `PetSightingClaimed`
- `phone` e `phoneIsWhatsapp` sao `null` se o avistador nao compartilhou telefone (`share_phone=false`)
- Retorna telefone primario (`is_primary=true`) do avistador

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Claim realizado ou ja existente |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Tentou fazer claim no proprio sighting |

---

## DELETE /api/v1/pet-sightings/{petSighting}

> Remove um avistamento (soft delete).

**Auth:** Bearer token
**Policy:** delete (Owner ou ADMIN)

### Request

Sem body.

### Response

**Status:** 200 OK

```json
{
  "data": {
    "message": "Sighting deleted successfully."
  }
}
```

### Regras de Negocio

- Soft delete (marca `deleted_at`)
- Matches com status `PENDING` sao deletados
- Matches com status `DISMISSED` ou `CONFIRMED` sao preservados para historico

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao e owner nem admin |
| 404 Not Found | Sighting nao encontrado |
