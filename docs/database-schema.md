# Database Schema

> PostgreSQL 16 + PostGIS 3.4. Todas as tabelas usam `id bigint unsigned auto-increment` como primary key, salvo indicacao.

---

## users

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | bigint unsigned | nao | auto-increment | PK |
| name | varchar(255) | nao | — | |
| email | varchar(255) | nao | — | UNIQUE |
| email_verified_at | timestamp | sim | null | |
| password | varchar(255) | nao | — | Hashed via bcrypt |
| role | varchar(255) | nao | `'CLIENT'` | Enum: `ADMIN`, `CLIENT`, `SHOP` |
| avatar_url | varchar(500) | sim | null | |
| is_active | boolean | nao | `true` | |
| remember_token | varchar(100) | sim | null | |
| created_at | timestamp | sim | null | |
| updated_at | timestamp | sim | null | |
| deleted_at | timestamp | sim | null | Soft Delete |

**Constraints:**
- UNIQUE: `email`
- Soft Delete via `deleted_at`

**Relationships:**
- hasMany UserPhone
- hasMany Pet
- hasMany PetReport
- hasMany PersonalAccessToken (morphMany via Sanctum)

---

## user_phones

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | bigint unsigned | nao | auto-increment | PK |
| user_id | bigint unsigned | nao | — | FK |
| phone | varchar(255) | nao | — | |
| is_whatsapp | boolean | nao | `false` | |
| is_primary | boolean | nao | `false` | |
| label | varchar(255) | sim | null | |
| created_at | timestamp | sim | null | |
| updated_at | timestamp | sim | null | |

**Foreign Keys:**

| Coluna | Referencia | On Delete |
|--------|------------|-----------|
| user_id | users.id | CASCADE |

**Relationships:**
- belongsTo User

---

## breeds

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | bigint unsigned | nao | auto-increment | PK |
| name | varchar(255) | nao | — | |
| species | varchar(255) | nao | — | Enum: `DOG`, `CAT` |
| is_active | boolean | nao | `true` | |
| created_at | timestamp | sim | null | |
| updated_at | timestamp | sim | null | |

**Constraints:**
- UNIQUE composto: `(name, species)`
- Sem Soft Delete — usa `is_active` para desativacao logica

**Relationships:**
- hasMany Pet (como breed)
- hasMany Pet (como secondaryBreed)

---

## pets

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | bigint unsigned | nao | auto-increment | PK |
| user_id | bigint unsigned | nao | — | FK |
| name | varchar(255) | nao | — | |
| species | varchar(255) | nao | — | Enum: `DOG`, `CAT` |
| size | varchar(255) | nao | — | Enum: `SMALL`, `MEDIUM`, `LARGE` |
| sex | varchar(255) | nao | — | Enum: `MALE`, `FEMALE`, `UNKNOWN` |
| breed_id | bigint unsigned | sim | null | FK |
| secondary_breed_id | bigint unsigned | sim | null | FK |
| breed_description | varchar(255) | sim | null | Descricao livre da raca |
| primary_color | varchar(255) | sim | null | |
| notes | text | sim | null | |
| is_active | boolean | nao | `true` | |
| deleted_at | timestamp | sim | null | Soft Delete |
| created_at | timestamp | sim | null | |
| updated_at | timestamp | sim | null | |

**Foreign Keys:**

| Coluna | Referencia | On Delete |
|--------|------------|-----------|
| user_id | users.id | CASCADE |
| breed_id | breeds.id | SET NULL |
| secondary_breed_id | breeds.id | SET NULL |

**Constraints:**
- Soft Delete via `deleted_at`

**Relationships:**
- belongsTo User (user_id)
- belongsTo Breed (breed_id)
- belongsTo Breed (secondary_breed_id)
- hasMany PetPhoto
- belongsToMany Characteristic (via pet_characteristics)
- hasMany PetReport

---

## pet_photos

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | bigint unsigned | nao | auto-increment | PK |
| pet_id | bigint unsigned | nao | — | FK |
| path | varchar(500) | nao | — | Caminho no S3 |
| position | smallint unsigned | nao | `0` | Ordem 0-based |
| created_at | timestamp | sim | null | |
| updated_at | timestamp | sim | null | |

**Foreign Keys:**

| Coluna | Referencia | On Delete |
|--------|------------|-----------|
| pet_id | pets.id | CASCADE |

**Relationships:**
- belongsTo Pet

---

## characteristics

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | bigint unsigned | nao | auto-increment | PK |
| name | varchar(255) | nao | — | |
| category | varchar(255) | nao | — | Enum: `MARKING`, `COAT`, `BEHAVIOR`, `IDENTIFICATION` |
| is_active | boolean | nao | `true` | |
| created_at | timestamp | sim | null | |
| updated_at | timestamp | sim | null | |

**Constraints:**
- UNIQUE composto: `(name, category)`
- Sem Soft Delete — usa `is_active` para desativacao logica

**Relationships:**
- belongsToMany Pet (via pet_characteristics)

---

## pet_characteristics

> Tabela pivot entre `pets` e `characteristics`.

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | bigint unsigned | nao | auto-increment | PK |
| pet_id | bigint unsigned | nao | — | FK |
| characteristic_id | bigint unsigned | nao | — | FK |
| created_at | timestamp | sim | null | Apenas created_at (sem updated_at) |

**Foreign Keys:**

| Coluna | Referencia | On Delete |
|--------|------------|-----------|
| pet_id | pets.id | CASCADE |
| characteristic_id | characteristics.id | CASCADE |

**Constraints:**
- UNIQUE composto: `(pet_id, characteristic_id)`

---

## pet_reports

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | bigint unsigned | nao | auto-increment | PK |
| pet_id | bigint unsigned | sim | null | FK — nullable para permitir reports sem pet associado |
| user_id | bigint unsigned | nao | — | FK |
| status | varchar(255) | nao | — | Enum: `LOST`, `FOUND`, `CANCELLED` |
| location | geography(Point, 4326) | sim | null | PostGIS — indice GIST |
| address_hint | varchar(255) | sim | null | |
| description | text | sim | null | |
| lost_at | timestamp | sim | null | |
| found_at | timestamp | sim | null | |
| is_active | boolean | nao | `true` | |
| deleted_at | timestamp | sim | null | Soft Delete |
| created_at | timestamp | sim | null | |
| updated_at | timestamp | sim | null | |

**Foreign Keys:**

| Coluna | Referencia | On Delete |
|--------|------------|-----------|
| pet_id | pets.id | SET NULL |
| user_id | users.id | CASCADE |

**Constraints:**
- Indice GIST em `location` (`pet_reports_location_gist`) para busca por proximidade via PostGIS
- Soft Delete via `deleted_at`

**Relationships:**
- belongsTo Pet (pet_id)
- belongsTo User (user_id)
- hasMany PetMatch
- hasMany PetReportSighting

---

## pet_matches

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | bigint unsigned | nao | auto-increment | PK |
| report_id | bigint unsigned | nao | — | FK |
| matched_pet_id | bigint unsigned | nao | — | FK |
| score | decimal(5,2) | nao | — | Score de matching (0-100) |
| distance_meters | decimal(10,2) | sim | null | Distancia em metros |
| status | varchar(255) | nao | — | Enum: `PENDING`, `CONFIRMED`, `DISMISSED` |
| created_at | timestamp | sim | null | |
| updated_at | timestamp | sim | null | |

**Foreign Keys:**

| Coluna | Referencia | On Delete |
|--------|------------|-----------|
| report_id | pet_reports.id | CASCADE |
| matched_pet_id | pets.id | CASCADE |

**Relationships:**
- belongsTo PetReport (report_id)
- belongsTo Pet (matched_pet_id)

---

## pet_sightings

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | bigint unsigned | nao | auto-increment | PK |
| user_id | bigint unsigned | nao | — | FK |
| title | varchar(255) | nao | — | |
| description | text | sim | null | |
| address_hint | varchar(500) | sim | null | |
| sighted_at | timestamp | nao | — | |
| species | varchar(255) | nao | — | Enum: `DOG`, `CAT` |
| size | varchar(255) | sim | null | Enum: `SMALL`, `MEDIUM`, `LARGE` |
| sex | varchar(255) | sim | null | Enum: `MALE`, `FEMALE`, `UNKNOWN` |
| color | varchar(100) | sim | null | |
| breed_id | bigint unsigned | sim | null | FK |
| share_phone | boolean | nao | `false` | |
| location | geography(Point, 4326) | nao | — | PostGIS — indice GIST |
| created_at | timestamp | sim | null | |
| updated_at | timestamp | sim | null | |
| deleted_at | timestamp | sim | null | Soft Delete |

**Foreign Keys:**

| Coluna | Referencia | On Delete |
|--------|------------|-----------|
| user_id | users.id | CASCADE |
| breed_id | breeds.id | SET NULL |

**Constraints:**
- Indice GIST em `location` para busca espacial
- Indice composto: `(species, created_at)`
- Soft Delete via `deleted_at`

**Relationships:**
- belongsTo User (user_id)
- belongsTo Breed (breed_id)
- hasMany PetSightingPhoto
- belongsToMany Characteristic (via pet_sighting_characteristics)
- hasMany PetSightingClaim
- hasMany PetMatch

---

## pet_sighting_photos

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | bigint unsigned | nao | auto-increment | PK |
| pet_sighting_id | bigint unsigned | nao | — | FK |
| path | varchar(500) | nao | — | Caminho no S3 |
| position | smallint unsigned | nao | `0` | Ordem 0-based (max 3) |
| created_at | timestamp | sim | null | |
| updated_at | timestamp | sim | null | |

**Foreign Keys:**

| Coluna | Referencia | On Delete |
|--------|------------|-----------|
| pet_sighting_id | pet_sightings.id | CASCADE |

**Relationships:**
- belongsTo PetSighting

---

## pet_sighting_characteristics

> Tabela pivot entre `pet_sightings` e `characteristics`.

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | bigint unsigned | nao | auto-increment | PK |
| pet_sighting_id | bigint unsigned | nao | — | FK |
| characteristic_id | bigint unsigned | nao | — | FK |

**Foreign Keys:**

| Coluna | Referencia | On Delete |
|--------|------------|-----------|
| pet_sighting_id | pet_sightings.id | CASCADE |
| characteristic_id | characteristics.id | CASCADE |

**Constraints:**
- UNIQUE composto: `(pet_sighting_id, characteristic_id)`

---

## pet_sighting_claims

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | bigint unsigned | nao | auto-increment | PK |
| pet_sighting_id | bigint unsigned | nao | — | FK |
| user_id | bigint unsigned | nao | — | FK |
| created_at | timestamp | sim | null | |
| updated_at | timestamp | sim | null | |

**Foreign Keys:**

| Coluna | Referencia | On Delete |
|--------|------------|-----------|
| pet_sighting_id | pet_sightings.id | CASCADE |
| user_id | users.id | CASCADE |

**Constraints:**
- UNIQUE composto: `(pet_sighting_id, user_id)`

**Relationships:**
- belongsTo PetSighting
- belongsTo User

---

## password_reset_codes

> PK: `email` (varchar, nao usa bigint auto-increment).

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| email | varchar(255) | nao | — | PK |
| code_hash | varchar(255) | sim | null | Hash bcrypt do codigo de 6 digitos |
| code_expires_at | timestamp | sim | null | Expiracao do codigo (15 min) |
| reset_token_hash | varchar(255) | sim | null | Hash bcrypt do token de reset |
| token_expires_at | timestamp | sim | null | Expiracao do token (15 min) |
| attempts | tinyint unsigned | nao | `0` | Tentativas de verificacao (max 5) |
| verified_at | timestamp | sim | null | Quando o codigo foi verificado |
| created_at | timestamp | sim | null | |
| updated_at | timestamp | sim | null | |

---

## user_notification_settings

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | bigint unsigned | nao | auto-increment | PK |
| user_id | bigint unsigned | nao | — | FK, UNIQUE |
| notify_lost_nearby | boolean | nao | `true` | |
| notify_matches | boolean | nao | `true` | |
| notify_sightings | boolean | nao | `true` | |
| nearby_radius_km | integer | nao | `5` | Raio em km (1-50) |
| location | geography(Point, 4326) | sim | null | Localizacao base do usuario |
| created_at | timestamp | sim | null | |
| updated_at | timestamp | sim | null | |

**Foreign Keys:**

| Coluna | Referencia | On Delete |
|--------|------------|-----------|
| user_id | users.id | CASCADE |

**Constraints:**
- UNIQUE: `user_id`

**Relationships:**
- belongsTo User

---

## user_devices

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | bigint unsigned | nao | auto-increment | PK |
| user_id | bigint unsigned | nao | — | FK |
| device_token | varchar(500) | nao | — | Token FCM/APNs (UNIQUE global) |
| platform | varchar(255) | nao | — | Enum: `IOS`, `ANDROID` |
| device_name | varchar(255) | sim | null | |
| provider_device_id | varchar(255) | sim | null | ID no provider externo (OneSignal) |
| is_active | boolean | nao | `true` | |
| last_active_at | timestamp | sim | null | |
| created_at | timestamp | sim | null | |
| updated_at | timestamp | sim | null | |

**Foreign Keys:**

| Coluna | Referencia | On Delete |
|--------|------------|-----------|
| user_id | users.id | CASCADE |

**Constraints:**
- UNIQUE: `device_token`

**Relationships:**
- belongsTo User

---

## Tabelas Padrao Laravel

Tabelas de infraestrutura do framework. Documentacao resumida.

### notifications

> PK: `uuid` (nao usa bigint auto-increment).

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | uuid | nao | — | PK |
| type | varchar(255) | nao | — | Classe da notificacao |
| notifiable_type | varchar(255) | nao | — | Morph type (ex: `App\Models\User`) |
| notifiable_id | bigint unsigned | nao | — | Morph ID |
| data | text | nao | — | JSON payload |
| read_at | timestamp | sim | null | |
| created_at | timestamp | sim | null | |
| updated_at | timestamp | sim | null | |

### personal_access_tokens

| Coluna | Tipo | Nullable | Default | Notas |
|--------|------|----------|---------|-------|
| id | bigint unsigned | nao | auto-increment | PK |
| tokenable_type | varchar(255) | nao | — | Morph type |
| tokenable_id | bigint unsigned | nao | — | Morph ID |
| name | text | nao | — | Nome do token |
| token | varchar(64) | nao | — | UNIQUE — hash SHA-256 |
| abilities | text | sim | null | JSON de abilities |
| last_used_at | timestamp | sim | null | |
| expires_at | timestamp | sim | null | Indice BTREE |
| created_at | timestamp | sim | null | |
| updated_at | timestamp | sim | null | |

### password_reset_tokens

> PK: `email` (varchar, nao usa bigint auto-increment).

| Coluna | Tipo | Nullable | Default |
|--------|------|----------|---------|
| email | varchar(255) | nao | — |
| token | varchar(255) | nao | — |
| created_at | timestamp | sim | null |

### sessions

> PK: `id` (varchar, nao usa bigint auto-increment).

| Coluna | Tipo | Nullable | Default |
|--------|------|----------|---------|
| id | varchar(255) | nao | — |
| user_id | bigint unsigned | sim | null |
| ip_address | varchar(45) | sim | null |
| user_agent | text | sim | null |
| payload | longtext | nao | — |
| last_activity | integer | nao | — |

### cache / cache_locks / jobs / job_batches / failed_jobs

Tabelas de infraestrutura para cache e queue. Criadas pelas migrations padrao do Laravel — nao possuem customizacoes.
