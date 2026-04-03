# Pets API

## Resource Shape — PetResource

```json
{
  "id": 1,
  "name": "Rex",
  "species": "DOG",
  "size": "MEDIUM",
  "sex": "MALE",
  "breed": {
    "id": 12,
    "name": "Labrador Retriever",
    "species": "DOG"
  },
  "secondaryBreed": null,
  "breedDescription": null,
  "primaryColor": "golden",
  "notes": "Muito docil",
  "isActive": true,
  "photos": [
    { "id": 1, "url": "https://s3.example.com/pets/01HX...", "position": 0 }
  ],
  "characteristics": [
    { "id": 5, "name": "Orelha cortada", "category": "MARKING" }
  ],
  "activeReportId": 42,
  "createdAt": "2026-03-15T10:00:00.000000Z",
  "updatedAt": "2026-03-15T10:00:00.000000Z"
}
```

> `breed`, `secondaryBreed`, `photos` e `characteristics` sao condicionais (`whenLoaded`). `breed` retorna `null` quando `breed_id` e null. `activeReportId` retorna o ID do report LOST ativo do pet, ou `null` se nao ha report ativo.

---

## Endpoints

### GET /api/v1/pets

> Lista pets do usuario (ou todos, para admin).

**Auth:** Bearer token
**Policy:** viewAny (CLIENT ou ADMIN)

#### Request

**Query params:**

| Param | Tipo | Obrigatorio | Default | Descricao |
|-------|------|-------------|---------|-----------|
| user_id | int | nao | — | Filtro por usuario (admin-only) |
| page | int | nao | 1 | Pagina |
| per_page | int | nao | 15 | Itens por pagina |

#### Response

**Status:** 200 OK (paginado)

#### Regras de Negocio

- Client ve apenas seus proprios pets
- Admin ve todos os pets, opcionalmente filtrados por `user_id`
- Sem ordenacao explicita — a ordem de retorno nao deve ser considerada garantida
- Eager loads: photos, characteristics, breed, secondaryBreed

#### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |

---

### POST /api/v1/pets

> Cadastra um novo pet.

**Auth:** Bearer token
**Policy:** create (CLIENT)
**Content-Type:** multipart/form-data

#### Request

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| name | string | sim | max:255 | Nome do pet |
| species | string | sim | `DOG` ou `CAT` | Especie |
| size | string | sim | `SMALL`, `MEDIUM` ou `LARGE` | Porte |
| sex | string | sim | `MALE`, `FEMALE` ou `UNKNOWN` | Sexo |
| breed_id | int | nao | exists:breeds (active, mesma species) | Raca principal |
| secondary_breed_id | int | nao | exists:breeds (active, mesma species), different:breed_id | Raca secundaria |
| breed_description | string | nao | max:255 | Descricao livre da raca |
| primary_color | string | nao | max:100 | Cor principal |
| notes | string | nao | max:1000 | Observacoes |
| characteristic_ids[] | int[] | nao | exists:characteristics (active) | IDs de caracteristicas |
| photos[] | file[] | nao | max 5 arquivos, cada max 5MB, jpeg/png/webp/heic/heif | Fotos |

**Exemplo (JSON body para demonstracao — enviar como multipart):**

```json
{
  "name": "Rex",
  "species": "DOG",
  "size": "MEDIUM",
  "sex": "MALE",
  "breed_id": 12,
  "primary_color": "golden",
  "notes": "Muito docil",
  "characteristic_ids": [5, 8]
}
```

#### Response

**Status:** 201 Created

Retorna `PetResource` completo.

#### Regras de Negocio

- `breed_id` e `secondary_breed_id` devem ser racas ativas e da mesma especie do pet
- `secondary_breed_id` deve ser diferente de `breed_id`
- Fotos armazenadas no S3 com nomes ULID, posicoes 0-based. Formatos HEIC/HEIF sao automaticamente convertidos para JPEG via `ImageConverter`
- Caracteristicas sincronizadas via tabela pivot `pet_characteristics`

#### Status Codes

| Status | Quando |
|--------|--------|
| 201 Created | Pet criado |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Role nao e CLIENT |
| 422 Unprocessable Entity | Validacao falhou |

---

### GET /api/v1/pets/{pet}

> Exibe detalhes de um pet.

**Auth:** Bearer token
**Policy:** view (Owner ou ADMIN)

#### Response

**Status:** 200 OK

Retorna `PetResource` completo com photos, characteristics, breed, secondaryBreed.

#### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao e owner nem admin |
| 404 Not Found | Pet nao encontrado |

---

### PUT /api/v1/pets/{pet}

> Atualiza um pet existente.

**Auth:** Bearer token
**Policy:** update (Owner)
**Content-Type:** multipart/form-data

#### Observacoes

> PUT usado como partial update neste projeto — campos omitidos permanecem inalterados.

#### Request

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| name | string | sim | max:255 | Nome |
| species | string | sim | `DOG` ou `CAT` | Especie |
| size | string | sim | `SMALL`, `MEDIUM` ou `LARGE` | Porte |
| sex | string | sim | `MALE`, `FEMALE` ou `UNKNOWN` | Sexo |
| breed_id | int | nao | exists:breeds (active, mesma species) | Raca principal |
| secondary_breed_id | int | nao | exists:breeds (active, mesma species), different:breed_id | Raca secundaria |
| breed_description | string | nao | max:255 | Descricao livre da raca |
| primary_color | string | nao | max:100 | Cor principal |
| notes | string | nao | max:1000 | Observacoes |
| characteristic_ids[] | int[] | nao | exists:characteristics (active) | IDs de caracteristicas |
| new_photos[] | file[] | nao | max 5MB cada, jpeg/png/webp/heic/heif | Novas fotos |
| delete_photo_ids[] | int[] | nao | IDs de fotos do pet | Fotos a remover |

#### Response

**Status:** 200 OK

Retorna `PetResource` atualizado.

#### Regras de Negocio

- Total de fotos (atuais - deletadas + novas) nao pode exceder 5
- Fotos deletadas sao removidas do S3
- Posicoes das fotos sao reindexadas (0-based continuo) apos adicao/remocao
- Validacao de breed species funciona igual ao create

#### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao e owner |
| 422 Unprocessable Entity | Validacao falhou |

---

### DELETE /api/v1/pets/{pet}

> Remove um pet (soft delete).

**Auth:** Bearer token
**Policy:** delete (Owner)

#### Request

Sem body.

#### Response

**Status:** 200 OK

```json
{
  "data": {
    "message": "Pet deleted successfully."
  }
}
```

#### Regras de Negocio

- Soft delete (marca `deleted_at`)
- Fotos permanecem no S3

#### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao e owner |
| 404 Not Found | Pet nao encontrado |

---

### PATCH /api/v1/pets/{pet}/toggle-active

> Alterna o status ativo/inativo de um pet.

**Auth:** Bearer token
**Policy:** toggleActive (ADMIN)

#### Request

Sem body.

#### Response

**Status:** 200 OK

Retorna `PetResource` com `isActive` atualizado.

#### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 403 Forbidden | Nao e admin |
| 404 Not Found | Pet nao encontrado |
