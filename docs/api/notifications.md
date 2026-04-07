# Notifications API

## Resource Shape — DatabaseNotificationResource

```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "type": "matches_found",
  "data": {
    "report_id": 1,
    "pet_name": "Rex",
    "matches_count": 2
  },
  "readAt": null,
  "createdAt": "2026-03-18T12:00:00.000000Z"
}
```

---

## Mapeamento de Tipos

O campo `type` e um alias estavel (nao FQCN):

| Classe interna | Alias na API | Descricao |
|----------------|-------------|-----------|
| `PetLostNearby` | `pet_lost_nearby` | Pet perdido proximo ao usuario |
| `PetMatchesFound` | `matches_found` | Novos matches encontrados para um report |
| `PetReportSightingReported` | `pet_report_sighting_reported` | Alguem reportou um avistamento no report do usuario |
| `PetSightingReported` | `pet_sighting_reported` | Avistamento independente reportado |

---

## GET /api/v1/user/notifications

> Lista notificacoes do usuario autenticado.

**Auth:** Bearer token

### Request

| Parametro | Tipo | Obrigatorio | Descricao |
|-----------|------|-------------|-----------|
| page | integer | nao | Pagina (default: 1) |
| per_page | integer | nao | Itens por pagina (default: 10) |
| unread | boolean | nao | Se `true`, retorna apenas nao lidas |

### Response

**Status:** 200 OK (paginado)

```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "type": "matches_found",
      "data": { "report_id": 1, "pet_name": "Rex", "matches_count": 2 },
      "readAt": null,
      "createdAt": "2026-03-18T12:00:00.000000Z"
    }
  ],
  "meta": { "...": "..." },
  "links": { "...": "..." }
}
```

### Regras de Negocio

- Ordenado por `created_at DESC` (mais recentes primeiro)
- Filtro `?unread=true` retorna apenas notificacoes com `read_at = null`
- Usuario so ve suas proprias notificacoes (filtrado pela relation polimorfica)

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |

---

## PATCH /api/v1/user/notifications/{notification}/read

> Marca uma notificacao como lida.

**Auth:** Bearer token

### Request

Sem body. O `notification` e o UUID da notificacao na URL.

### Response

**Status:** 200 OK

```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "type": "matches_found",
  "data": { "report_id": 1, "pet_name": "Rex", "matches_count": 2 },
  "readAt": "2026-03-18T12:30:00.000000Z",
  "createdAt": "2026-03-18T12:00:00.000000Z"
}
```

### Regras de Negocio

- Busca via `$user->notifications()->findOrFail()` — garante ownership, retorna 404 se nao pertence ao usuario
- Idempotente: marcar como lida uma notificacao ja lida nao causa erro

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 404 Not Found | Notificacao nao encontrada ou nao pertence ao usuario |

---

## PATCH /api/v1/user/notifications/read-all

> Marca todas as notificacoes do usuario como lidas.

**Auth:** Bearer token

### Request

Sem body.

### Response

**Status:** 200 OK

```json
{
  "message": "All notifications marked as read."
}
```

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |

---

## GET /api/v1/user/notifications/unread-count

> Retorna contagem de notificacoes nao lidas.

**Auth:** Bearer token

### Request

Sem parametros.

### Response

**Status:** 200 OK

```json
{
  "unreadCount": 5
}
```

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
