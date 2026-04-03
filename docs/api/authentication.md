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

## GET /api/v1/auth/user

> Retorna os dados do usuario autenticado (session restore).

**Auth:** Bearer token

### Request

Sem body.

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
      "avatarUrl": null,
      "isActive": true,
      "emailVerifiedAt": null,
      "phones": [
        {
          "id": 1,
          "phone": "+5511999999999",
          "isWhatsapp": true,
          "isPrimary": true,
          "label": "Pessoal"
        }
      ],
      "createdAt": "2026-03-15T10:00:00.000000Z",
      "updatedAt": "2026-03-15T10:00:00.000000Z"
    }
  }
}
```

### Regras de Negocio

- Retorna o usuario autenticado com telefones eager-loaded
- Usado pelo app para restaurar sessao ao reabrir

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |

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

---

## POST /api/v1/auth/forgot-password

> Envia codigo de recuperacao de senha por email.

**Auth:** Publico
**Rate Limit:** 5 requests/minuto
**Content-Type:** application/json

### Request

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| email | string | sim | email, max:255 | Email do usuario (normalizado para lowercase/trim) |

**Exemplo:**

```json
{
  "email": "pedro@example.com"
}
```

### Response

**Status:** 200 OK

```json
{
  "data": {
    "message": "Se o email estiver cadastrado, enviaremos um codigo de recuperacao."
  }
}
```

### Regras de Negocio

- Busca usuario ativo pelo email
- Se usuario nao encontrado, retorna 200 sem erro (prevencao de enumeracao de usuarios)
- Gera codigo numerico de 6 digitos
- Armazena hash do codigo na tabela `password_reset_codes` com expiracao de 15 minutos
- Reseta tentativas e tokens anteriores para o mesmo email
- Envia codigo via `PasswordResetCodeNotification`

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sempre (mesmo se email nao encontrado) |
| 422 Unprocessable Entity | Validacao falhou (email invalido) |
| 429 Too Many Requests | Rate limit excedido |

---

## POST /api/v1/auth/verify-reset-code

> Verifica o codigo de recuperacao e retorna um token de reset.

**Auth:** Publico
**Rate Limit:** 5 requests/minuto
**Content-Type:** application/json

### Request

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| email | string | sim | email, max:255 | Email do usuario |
| code | string | sim | exatamente 6 digitos | Codigo recebido por email |

**Exemplo:**

```json
{
  "email": "pedro@example.com",
  "code": "123456"
}
```

### Response

**Status:** 200 OK

```json
{
  "data": {
    "resetToken": "a1b2c3d4e5f6..."
  }
}
```

### Regras de Negocio

- Valida que o registro existe e nao foi verificado ainda
- Maximo de 5 tentativas — apos 5 erros, o registro e deletado e o usuario deve solicitar novo codigo
- Codigo expira em 15 minutos
- Na verificacao bem-sucedida: limpa o hash do codigo, gera token de 64 caracteres, armazena hash do token com expiracao de 15 minutos
- Retorna o token em texto plano (nao o hash)

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Codigo verificado |
| 422 Unprocessable Entity | Codigo invalido, expirado, ou tentativas excedidas |
| 429 Too Many Requests | Rate limit excedido |

---

## POST /api/v1/auth/reset-password

> Redefine a senha do usuario usando o token de reset.

**Auth:** Publico
**Rate Limit:** 5 requests/minuto
**Content-Type:** application/json

### Request

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| email | string | sim | email, max:255 | Email do usuario |
| reset_token | string | sim | string | Token de 64 caracteres obtido na verificacao |
| password | string | sim | confirmed, Password::defaults() | Nova senha |
| password_confirmation | string | sim | — | Confirmacao da nova senha |

**Exemplo:**

```json
{
  "email": "pedro@example.com",
  "reset_token": "a1b2c3d4e5f6...",
  "password": "NovaSenha@456",
  "password_confirmation": "NovaSenha@456"
}
```

### Response

**Status:** 200 OK

```json
{
  "data": {
    "message": "Senha redefinida com sucesso."
  }
}
```

### Regras de Negocio

- Valida que o registro foi previamente verificado (`verified_at` nao nulo)
- Token expira em 15 minutos
- Em transacao: atualiza senha do usuario, revoga todos os tokens Sanctum (force logout em todos os dispositivos), deleta registro de reset
- Usuario precisa fazer login novamente apos o reset

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Senha redefinida |
| 422 Unprocessable Entity | Token invalido, expirado, ou validacao falhou |
| 429 Too Many Requests | Rate limit excedido |
