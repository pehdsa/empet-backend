# Authorization

## Regras Gerais

- Policies sao chamadas via `Gate::authorize()` nos controllers
- Admin pode acessar recursos de outros usuarios nas acoes de leitura documentadas (view, viewAny, viewMatches), conforme cada policy. Acoes de escrita do cliente (create, update, cancel, markFound) sao restritas ao owner
- Owner = `user_id` do recurso corresponde ao usuario autenticado
- **Separacao Policy vs Regra de Negocio**: policies controlam apenas autorizacao (quem pode). Invariantes de estado (ex: "report deve estar LOST para dismiss") sao validadas nas Actions e retornam 422, nao 403

---

## PetPolicy

`App\Policies\PetPolicy`

| Action | Regra | Usado em |
|--------|-------|----------|
| viewAny | CLIENT ou ADMIN | `GET /pets` |
| view | Owner ou ADMIN | `GET /pets/{pet}` |
| create | CLIENT | `POST /pets` |
| update | Owner | `PUT /pets/{pet}` |
| delete | Owner | `DELETE /pets/{pet}` |
| toggleActive | ADMIN | `PATCH /pets/{pet}/toggle-active` |

---

## PetReportPolicy

`App\Policies\PetReportPolicy`

| Action | Regra | Usado em |
|--------|-------|----------|
| viewAny | CLIENT ou ADMIN | `GET /pet-reports` |
| view | Owner ou ADMIN | `GET /pet-reports/{id}` |
| viewLost | CLIENT ou ADMIN | `GET /pet-reports/lost`, `GET /pet-reports/lost/map` |
| viewFound | CLIENT ou ADMIN | `GET /pet-reports/found` |
| viewDetail | Comunidade (LOST/FOUND) ou Owner ou ADMIN | `GET /pet-reports/{id}/detail` |
| create | CLIENT | `POST /pet-reports` |
| update | Owner E status=LOST | `PUT /pet-reports/{id}` |
| cancel | Owner E status=LOST | `PATCH /pet-reports/{id}/cancel` |
| markFound | Owner E status=LOST | `PATCH /pet-reports/{id}/found` |
| viewMatches | Owner ou ADMIN | `GET /pet-reports/{id}/matches` |
| dismissMatch | Owner | `PATCH .../matches/{id}/dismiss` |
| confirmMatch | Owner | `PATCH .../matches/{id}/confirm` |

> **Nota**: `dismissMatch` e `confirmMatch` verificam apenas ownership na policy. A validacao de estado do report (`status=LOST`) e do match (`status=PENDING`) acontece nas Actions `DismissMatch` e `ConfirmMatch`, retornando 422.

> **Nota**: `viewDetail` permite que qualquer usuario da comunidade veja reports LOST/FOUND. O owner pode ver seus proprios reports independente do status.

---

## PetSightingPolicy

`App\Policies\PetSightingPolicy`

| Action | Regra | Usado em |
|--------|-------|----------|
| viewAny | CLIENT ou ADMIN | `GET /pet-sightings` |
| view | Qualquer usuario autenticado | `GET /pet-sightings/{id}` |
| create | CLIENT | `POST /pet-sightings` |
| delete | Owner ou ADMIN | `DELETE /pet-sightings/{id}` |
| claim | Nao pode ser o proprio avistador | `POST /pet-sightings/{id}/claim` |

---

## PetReportSightingPolicy

`App\Policies\PetReportSightingPolicy`

| Action | Regra | Usado em |
|--------|-------|----------|
| create | CLIENT | `POST /pet-reports/{id}/sightings` |
| viewAny | CLIENT/ADMIN (LOST/FOUND); Owner/ADMIN (CANCELLED) | `GET /pet-reports/{id}/sightings` |
| view | Owner do report, autor do sighting ou ADMIN | `GET /pet-reports/{id}/sightings/{id}` |

> **Nota**: `viewAny` permite que qualquer CLIENT/ADMIN veja sightings de reports LOST/FOUND. Reports CANCELLED so sao visiveis para o owner do report e admin.
