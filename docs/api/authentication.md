# Authentication API

Autenticacao via Laravel Sanctum com Bearer tokens.

---

## POST /api/v1/auth/register

> Registra um novo usuario.

**Auth:** Publico
**Rate Limit:** 5 requests/minuto
**Content-Type:** application/json

### Request

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| name | string | sim | max:255 | Nome do usuario |
| email | string | sim | email, unique (entre nao-deletados), max:255 | Email (normalizado para lowercase/trim) |
| password | string | sim | confirmed, Password::defaults() | Senha |
| password_confirmation | string | sim | — | Confirmacao da senha |

**Exemplo:**

```json
{
  "name": "Pedro Santos",
  "email": "pedro@example.com",
  "password": "MinhaSenh@123",
  "password_confirmation": "MinhaSenh@123"
}
```

### Response

**Status:** 201 Created

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
      "emailVerifiedAt": null,
      "phones": [],
      "createdAt": "2026-03-15T10:00:00.000000Z",
      "updatedAt": "2026-03-15T10:00:00.000000Z"
    },
    "token": "1|abc123def456...",
    "tokenType": "Bearer"
  }
}
```

### Regras de Negocio

- Email e normalizado para lowercase e trimmed antes da validacao
- Se o email pertence a um usuario soft-deleted, o usuario e restaurado com os novos dados (idempotente)
- Novo usuario recebe `role=CLIENT` e `is_active=true`
- Um token Bearer e criado e retornado

### Status Codes

| Status | Quando |
|--------|--------|
| 201 Created | Usuario registrado |
| 422 Unprocessable Entity | Validacao falhou (email duplicado, senha fraca, etc.) |
| 429 Too Many Requests | Rate limit excedido |

---

## POST /api/v1/auth/login

> Autentica um usuario existente.

**Auth:** Publico
**Rate Limit:** 10 requests/minuto
**Content-Type:** application/json

### Request

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| email | string | sim | email | Email (normalizado para lowercase/trim) |
| password | string | sim | — | Senha |

**Exemplo:**

```json
{
  "email": "pedro@example.com",
  "password": "MinhaSenh@123"
}
```

### Response

**Status:** 200 OK

```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "Pedro Santos",
      "email": "pedro@example.com",
      "role": "CLIENT",
      ...
    },
    "token": "2|xyz789...",
    "tokenType": "Bearer"
  }
}
```

### Regras de Negocio

- Valida credenciais e verifica flag `is_active`
- Conta inativa retorna 422 (nao 401)
- Todos os tokens anteriores sao revogados no login (single-session)
- Novo token Bearer e criado

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Login bem-sucedido |
| 422 Unprocessable Entity | Credenciais invalidas ou conta inativa |
| 429 Too Many Requests | Rate limit excedido |

---

## PUT /api/v1/auth/password

> Altera a senha do usuario autenticado.

**Auth:** Bearer token
**Content-Type:** application/json

### Request

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| current_password | string | sim | must match current | Senha atual |
| password | string | sim | confirmed, different:current_password, Password::defaults() | Nova senha |
| password_confirmation | string | sim | — | Confirmacao da nova senha |

**Exemplo:**

```json
{
  "current_password": "MinhaSenh@123",
  "password": "NovaSenha@456",
  "password_confirmation": "NovaSenha@456"
}
```

### Response

**Status:** 200 OK

```json
{
  "data": {
    "message": "Password changed successfully."
  }
}
```

### Regras de Negocio

- Nova senha deve ser diferente da atual
- Todos os outros tokens sao revogados (exceto o token atual — usuario continua logado)

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Senha alterada |
| 401 Unauthorized | Token ausente/invalido |
| 422 Unprocessable Entity | Senha atual incorreta, nova senha fraca, ou igual a atual |

---

## POST /api/v1/auth/logout

> Encerra a sessao do usuario autenticado.

**Auth:** Bearer token

### Request

Sem body.

### Response

**Status:** 200 OK

```json
{
  "data": {
    "message": "Logged out successfully."
  }
}
```

### Regras de Negocio

- Revoga apenas o token atual (outros tokens permanecem validos)

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Logout realizado |
| 401 Unauthorized | Token ausente/invalido |
