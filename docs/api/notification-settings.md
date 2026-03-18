# Notification Settings API

## Resource Shape — UserNotificationSettingResource

```json
{
  "id": 1,
  "notifyLostNearby": true,
  "notifyMatches": true,
  "notifySightings": true,
  "nearbyRadiusKm": 5,
  "location": {
    "latitude": -23.5505,
    "longitude": -46.6333
  },
  "createdAt": "2026-03-18T12:00:00.000000Z",
  "updatedAt": "2026-03-18T12:00:00.000000Z"
}
```

> `location` e nullable — retorna `null` se o usuario nao configurou localizacao.

---

## Regras de Dominio

- Um registro por usuario (`user_id` unique)
- GET nao cria registro no banco — retorna defaults em memoria
- Registro e criado apenas no primeiro PUT (via `updateOrCreate`)
- `location` representa localizacao fixa/residencia, nao GPS em tempo real
- `clear_location` e `latitude/longitude` sao mutuamente exclusivos

---

## GET /api/v1/user/notification-settings

> Retorna as preferencias de notificacao do usuario autenticado.

**Auth:** Bearer token

### Request

Sem parametros.

### Response

**Status:** 200 OK

```json
{
  "data": {
    "id": null,
    "notifyLostNearby": true,
    "notifyMatches": true,
    "notifySightings": true,
    "nearbyRadiusKm": 5,
    "location": null,
    "createdAt": null,
    "updatedAt": null
  }
}
```

### Regras de Negocio

- Se o usuario nunca configurou, retorna defaults em memoria sem criar registro no banco
- Defaults: `notifyLostNearby=true`, `notifyMatches=true`, `notifySightings=true`, `nearbyRadiusKm=5`, `location=null`
- Se registro existe, retorna com coordenadas reais

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |

---

## PUT /api/v1/user/notification-settings

> Atualiza preferencias de notificacao (cria se nao existir).

**Auth:** Bearer token
**Content-Type:** application/json

### Request

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| notify_lost_nearby | boolean | nao | sometimes | Receber alerta de pet perdido no bairro |
| notify_matches | boolean | nao | sometimes | Receber alerta de matches encontrados |
| notify_sightings | boolean | nao | sometimes | Receber alerta de avistamentos |
| nearby_radius_km | integer | nao | min:1, max:50 | Raio em km para notificacoes de proximidade |
| latitude | numeric | nao | between:-90,90, required_with:longitude, prohibited_if:clear_location,true | Latitude da localizacao base |
| longitude | numeric | nao | between:-180,180, required_with:latitude, prohibited_if:clear_location,true | Longitude da localizacao base |
| clear_location | boolean | nao | sometimes | Se true, limpa a localizacao |

### Response

**Status:** 200 OK

```json
{
  "data": {
    "id": 1,
    "notifyLostNearby": false,
    "notifyMatches": true,
    "notifySightings": true,
    "nearbyRadiusKm": 10,
    "location": {
      "latitude": -23.5505,
      "longitude": -46.6333
    },
    "createdAt": "2026-03-18T12:00:00.000000Z",
    "updatedAt": "2026-03-18T12:00:00.000000Z"
  }
}
```

### Regras de Negocio

- Aceita atualizacao parcial (apenas os campos enviados sao alterados)
- Se registro nao existe, cria com defaults + campos enviados
- `latitude` e `longitude` devem ser enviados juntos
- `clear_location=true` limpa a localizacao, ignorando latitude/longitude
- `clear_location=true` + latitude/longitude retorna 422

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 422 Unprocessable Entity | Validacao falhou |
