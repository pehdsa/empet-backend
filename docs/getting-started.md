# Getting Started

## Visao Geral

Empet e uma plataforma de matching de pets perdidos. A API permite que tutores cadastrem seus pets, reportem quando estao perdidos e recebam matches automaticos com pets de outros usuarios na mesma regiao.

## Tech Stack

- **PHP** 8.4
- **Laravel** 12
- **PostgreSQL** 16 + **PostGIS** 3.4
- **Redis** 7 (cache, queue, session)
- **Laravel Sanctum** (autenticacao por token)
- **S3-compatible storage** (fotos de pets)
- **PHPUnit** 11

## Base URL

```
/api/v1
```

Todas as rotas sao prefixadas com `/api/v1`. Definidas em `routes/api/v1.php`.

## Versionamento da API

- Versao atual: `v1` (prefixo na URL)
- Breaking changes resultam em nova versao (`v2`)
- Non-breaking changes (novos campos opcionais, novos endpoints) sao adicionados na versao existente

---

## Convencoes de Nomenclatura

| Contexto | Convencao | Exemplo |
|----------|-----------|---------|
| Banco de dados | snake_case | `breed_id`, `is_active`, `created_at` |
| Request body (campos) | snake_case | `pet_id`, `breed_id`, `lost_at` |
| Response JSON (chaves) | camelCase | `petId`, `breedId`, `lostAt` |
| Enums (valores trafegados) | UPPERCASE string | `"DOG"`, `"LOST"`, `"PENDING"` |
| Datas | ISO 8601 UTC com microssegundos | `"2026-03-15T14:00:00.000000Z"` |
| IDs | integer | `1`, `42` |
| Booleanos | true/false | `true`, `false` |

---

## Shared Resources

Resources reutilizados em multiplos endpoints:

### MessageResource

Retornado por deletes e acoes simples:

```json
{
  "data": {
    "message": "Pet deleted successfully."
  }
}
```

### TokenResource

Retornado por register e login. O campo `user` retorna o **UserResource completo**.

```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "Pedro Santos",
      "email": "pedro@example.com",
      "role": "CLIENT",
      "avatarUrl": null,
      "isActive": true,
      "emailVerifiedAt": "2026-03-15T10:00:00.000000Z",
      "phones": [],
      "createdAt": "2026-03-15T10:00:00.000000Z",
      "updatedAt": "2026-03-15T10:00:00.000000Z"
    },
    "token": "1|abc123def456...",
    "tokenType": "Bearer"
  }
}
```

### UserResource

```json
{
  "id": 1,
  "name": "Pedro Santos",
  "email": "pedro@example.com",
  "role": "CLIENT",
  "avatarUrl": null,
  "isActive": true,
  "emailVerifiedAt": "2026-03-15T10:00:00.000000Z",
  "phones": [
    {
      "id": 1,
      "phone": "+5521999999999",
      "isWhatsapp": true,
      "isPrimary": true,
      "label": "Pessoal"
    }
  ],
  "createdAt": "2026-03-15T10:00:00.000000Z",
  "updatedAt": "2026-03-15T10:00:00.000000Z"
}
```

> `phones` e condicional (`whenLoaded`).

### Collection Padrao (nao paginada)

```json
{
  "data": [
    { ... },
    { ... }
  ]
}
```

---

## Headers Padrao

```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

- Todos os endpoints autenticados exigem header `Authorization`
- Endpoints com upload de arquivo usam `Content-Type: multipart/form-data`
- Endpoints publicos (register, login) nao exigem `Authorization`

---

## Paginacao

Endpoints paginados retornam o formato padrao do Laravel:

```json
{
  "data": [ ... ],
  "links": {
    "first": "http://localhost/api/v1/pets?page=1",
    "last": "http://localhost/api/v1/pets?page=4",
    "prev": null,
    "next": "http://localhost/api/v1/pets?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 4,
    "per_page": 15,
    "to": 15,
    "total": 50,
    "path": "http://localhost/api/v1/pets"
  }
}
```

**Query params:**

| Param | Tipo | Default | Descricao |
|-------|------|---------|-----------|
| page | int | 1 | Numero da pagina |
| per_page | int | 15 | Itens por pagina |

**Endpoints paginados:** `GET /pets`, `GET /pet-reports`

---

## Respostas de Erro

| Status | Quando | Formato |
|--------|--------|---------|
| 401 Unauthorized | Token ausente/invalido | `{ "message": "Unauthenticated." }` |
| 403 Forbidden | Sem permissao (policy) | `{ "message": "This action is unauthorized." }` |
| 404 Not Found | Recurso nao encontrado | `{ "message": "..." }` |
| 422 Unprocessable Entity | Validacao falhou | `{ "message": "...", "errors": { ... } }` |
| 429 Too Many Requests | Rate limit excedido | Nos endpoints de auth |

### Exemplos

**401 Unauthenticated:**

```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden:**

```json
{
  "message": "This action is unauthorized."
}
```

**404 Not Found:**

```json
{
  "message": "Match not found for this report."
}
```

**422 Validation Error:**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "species": ["The selected species is invalid."],
    "breed_id": ["The selected breed does not match the pet species."]
  }
}
```

---

## Idempotencia

Comportamentos idempoentes notaveis:

- `POST /auth/register`: se o email pertence a um usuario soft-deleted, restaura o usuario com os novos dados em vez de retornar erro de duplicata
- `BreedSeeder` e `CharacteristicSeeder`: usam `updateOrCreate`, podem ser executados multiplas vezes sem erro
- `DatabaseSeeder`: usa `firstOrCreate` para o test user

---

## Rate Limiting

| Endpoint | Limite |
|----------|--------|
| `POST /api/v1/auth/register` | 5 requests/minuto |
| `POST /api/v1/auth/login` | 10 requests/minuto |
| Demais endpoints | Sem rate limit global configurado |

---

## Geolocalizacao

O sistema usa PostGIS para operacoes espaciais (busca por proximidade, calculo de distancia).

**Envio** — coordenadas como campos separados:

```json
{
  "latitude": -22.9068,
  "longitude": -43.1729
}
```

**Retorno** — objeto `location`:

```json
{
  "location": {
    "latitude": -22.9068,
    "longitude": -43.1729
  }
}
```

- Armazenamento interno: coluna `GEOGRAPHY(POINT, 4326)` com indice GIST
- `latitude` entre -90 e 90, `longitude` entre -180 e 180

---

## Upload de Imagens

- Usado em: `POST /api/v1/pets` e `PUT /api/v1/pets/{pet}`
- Header: `Content-Type: multipart/form-data`
- Campo: `photos[]` (create) ou `new_photos[]` (update)
- Limites: max 5 fotos por pet, cada max 2MB
- Formatos aceitos: jpeg, png, webp
- Armazenamento: S3-compatible storage com nomes ULID
- Fotos retornam URL completa no campo `url` do PetPhotoResource

---

## Soft Delete

- Usado em: `pets`, `pet_reports`, `users` (coluna `deleted_at`)
- Registros soft-deleted nao aparecem em listagens padrao
- Nao existem endpoints de restore — soft delete e irreversivel pela API (exceto register que restaura user por email)
- Relacionamentos usam `withTrashed()` onde necessario (ex: report continua referenciando pet deletado)

---

## Indexes e Performance

| Tabela | Coluna(s) | Tipo | Notas |
|--------|-----------|------|-------|
| users | email | UNIQUE | |
| breeds | (name, species) | UNIQUE | Composto |
| characteristics | (name, category) | UNIQUE | Composto |
| pet_characteristics | (pet_id, characteristic_id) | UNIQUE | Composto |
| pet_reports | location | GIST | Indice espacial PostGIS |
| personal_access_tokens | token | UNIQUE | Sanctum |
| personal_access_tokens | expires_at | BTREE | |

> FKs do Laravel criam indexes automaticos nas colunas de referencia (pet_id, user_id, report_id, etc.)

---

## Queue e Jobs

| Config | Valor |
|--------|-------|
| Connection | Redis (`QUEUE_CONNECTION=redis`) |
| Queue | default |
| Worker | Container `backend-worker` (Docker Compose) |
| Scheduler | Container `backend-scheduler` (Docker Compose) |

| Job | Descricao |
|-----|-----------|
| `ProcessPetMatching` | Matching assincrono de pets. 3 tentativas, backoff [10s, 60s]. Dispatchado via `DB::afterCommit()` |

Ver [Matching System](matching-system.md) para detalhes completos.
