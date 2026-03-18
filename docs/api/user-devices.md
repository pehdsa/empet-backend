# User Devices API

## Resource Shape — UserDeviceResource

```json
{
  "id": 1,
  "platform": "IOS",
  "deviceName": "iPhone 15",
  "isActive": true,
  "lastActiveAt": "2026-03-18T12:00:00.000000Z",
  "createdAt": "2026-03-18T12:00:00.000000Z",
  "updatedAt": "2026-03-18T12:00:00.000000Z"
}
```

> `device_token` e `provider_device_id` nunca sao expostos na API — dados sensiveis/internos.

---

## Regras de Dominio

- `device_token` e unique global (nao por usuario)
- Um token fisico pertence a um unico device/usuario por vez
- Se o token ja pertence a outro usuario, o vinculo anterior e desativado (reatribuicao)
- Registro no provider externo (OneSignal) e tentado no mesmo fluxo — se falhar, device fica salvo localmente sem `provider_device_id`
- Delete desativa localmente independente do provider

---

## GET /api/v1/user/devices

> Lista devices ativos do usuario autenticado.

**Auth:** Bearer token

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
      "platform": "IOS",
      "deviceName": "iPhone 15",
      "isActive": true,
      "lastActiveAt": "2026-03-18T12:00:00.000000Z",
      "createdAt": "2026-03-18T12:00:00.000000Z",
      "updatedAt": "2026-03-18T12:00:00.000000Z"
    }
  ],
  "meta": { "...": "..." },
  "links": { "...": "..." }
}
```

### Regras de Negocio

- Retorna apenas devices com `is_active = true`
- Ordenado por `last_active_at DESC`
- Campos sensiveis (`device_token`, `provider_device_id`) nao sao expostos

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |

---

## POST /api/v1/user/devices

> Registra um device para push notifications.

**Auth:** Bearer token
**Content-Type:** application/json

### Request

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| device_token | string | sim | max:500 | Token FCM/APNs do dispositivo |
| platform | string | sim | in:IOS,ANDROID | Plataforma do dispositivo |
| device_name | string | nao | max:255 | Nome do dispositivo (ex: "iPhone 15 de Joao") |

### Response

**Status:** 201 Created

```json
{
  "data": {
    "id": 1,
    "platform": "IOS",
    "deviceName": "iPhone 15",
    "isActive": true,
    "lastActiveAt": "2026-03-18T12:00:00.000000Z",
    "createdAt": "2026-03-18T12:00:00.000000Z",
    "updatedAt": "2026-03-18T12:00:00.000000Z"
  }
}
```

### Regras de Negocio

- Se `device_token` ja existe para o mesmo usuario: atualiza (upsert)
- Se `device_token` pertence a outro usuario: desativa vinculo anterior e reatribui ao novo usuario
- Tenta registrar no provider externo — se falhar, device fica salvo sem `provider_device_id` (push nao sera enviado ate retry)
- `is_active` e setado como `true`, `last_active_at` atualizado para agora

### Status Codes

| Status | Quando |
|--------|--------|
| 201 Created | Device registrado |
| 401 Unauthorized | Token ausente/invalido |
| 422 Unprocessable Entity | Validacao falhou |

---

## DELETE /api/v1/user/devices/{userDevice}

> Remove (desativa) um device.

**Auth:** Bearer token

### Request

Sem body. O `userDevice` e o ID do device na URL.

### Response

**Status:** 200 OK

```json
{
  "data": {
    "message": "Device removed successfully."
  }
}
```

### Regras de Negocio

- Marca `is_active = false` localmente (fonte de verdade)
- Tenta remover do provider externo — se falhar, desativacao local permanece
- Retorna 404 se o device nao pertence ao usuario autenticado

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Device removido |
| 401 Unauthorized | Token ausente/invalido |
| 404 Not Found | Device nao encontrado ou nao pertence ao usuario |
