# User Phones API

## Resource Shape — UserPhoneResource

```json
{
  "id": 1,
  "phone": "11999999999",
  "isWhatsapp": true,
  "isPrimary": true,
  "label": "Celular"
}
```

---

## Regras de Dominio

**Invariante**: enquanto existir pelo menos um telefone do usuario, sempre deve haver exatamente um telefone primario.

- Maximo de 5 telefones por usuario
- Sem duplicata de numero por usuario (permitido entre usuarios diferentes)
- Primeiro telefone cadastrado vira primario automaticamente
- Marcar novo primario desmarca o anterior
- Update removendo primario promove o mais antigo (menor id)
- Delete de primario promove o mais antigo (menor id)
- Permitido deletar todos — usuario pode ficar sem telefone

---

## GET /api/v1/user/phones

> Lista telefones do usuario autenticado.

**Auth:** Bearer token

### Request

Sem parametros.

### Response

**Status:** 200 OK (collection, nao paginado)

```json
{
  "data": [
    { "id": 1, "phone": "11999999999", "isWhatsapp": true, "isPrimary": true, "label": "Celular" },
    { "id": 2, "phone": "21988888888", "isWhatsapp": false, "isPrimary": false, "label": null }
  ]
}
```

### Regras de Negocio

- Retorna apenas telefones do usuario autenticado
- Ordenado por `is_primary DESC, id ASC` (primario primeiro)
- Sem paginacao (max 5 itens)

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |

---

## POST /api/v1/user/phones

> Cadastra um novo telefone.

**Auth:** Bearer token
**Content-Type:** application/json

### Request

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| phone | string | sim | max:20, trim | Numero de telefone (armazenado como enviado, sem normalizacao) |
| is_whatsapp | boolean | nao | default false | Telefone e WhatsApp |
| is_primary | boolean | nao | default false | Telefone primario |
| label | string | nao | max:50, trim, vazio vira null | Descritivo (ex: "Celular") |

> **Formato do telefone**: o backend aplica apenas trim e max:20. Nao faz normalizacao (E.164, prefixo de pais, etc.) — o valor e persistido exatamente como enviado pelo front.

**Exemplo:**

```json
{
  "phone": "11999999999",
  "is_whatsapp": true,
  "is_primary": true,
  "label": "Celular"
}
```

### Response

**Status:** 201 Created

```json
{
  "data": {
    "id": 1,
    "phone": "11999999999",
    "isWhatsapp": true,
    "isPrimary": true,
    "label": "Celular"
  }
}
```

### Regras de Negocio

- Maximo de 5 telefones por usuario
- Numero nao pode ser duplicado para o mesmo usuario
- Se for o primeiro telefone, vira primario automaticamente (ignora `is_primary` do request)
- Se `is_primary = true`, desmarca o primario anterior

### Status Codes

| Status | Quando |
|--------|--------|
| 201 Created | Telefone criado |
| 401 Unauthorized | Token ausente/invalido |
| 422 Unprocessable Entity | Validacao falhou (limite, duplicata, campo invalido) |

---

## PUT /api/v1/user/phones/{id}

> Atualiza um telefone existente.

**Auth:** Bearer token
**Content-Type:** application/json

### Request

| Campo | Tipo | Obrigatorio | Regras | Descricao |
|-------|------|-------------|--------|-----------|
| phone | string | sim | max:20, trim | Numero de telefone |
| is_whatsapp | boolean | nao | default false | Telefone e WhatsApp |
| is_primary | boolean | nao | default false | Telefone primario |
| label | string | nao | max:50, trim, vazio vira null | Descritivo |

**Exemplo:**

```json
{
  "phone": "21988888888",
  "is_whatsapp": false,
  "is_primary": true,
  "label": "Trabalho"
}
```

### Response

**Status:** 200 OK

```json
{
  "data": {
    "id": 1,
    "phone": "21988888888",
    "isWhatsapp": false,
    "isPrimary": true,
    "label": "Trabalho"
  }
}
```

### Regras de Negocio

- Numero nao pode ser duplicado para o mesmo usuario (excluindo o proprio registro)
- Se `is_primary = true`, desmarca o primario anterior
- Se `is_primary = false` no telefone que era primario, promove o mais antigo (menor id) restante
- Ownership: telefone e resolvido no escopo do usuario autenticado — retorna 404 se nao pertence

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 404 Not Found | Telefone nao encontrado ou nao pertence ao usuario |
| 422 Unprocessable Entity | Validacao falhou |

---

## DELETE /api/v1/user/phones/{id}

> Remove um telefone.

**Auth:** Bearer token

### Request

Sem body.

### Response

**Status:** 200 OK

```json
{
  "data": {
    "message": "Phone deleted successfully."
  }
}
```

### Regras de Negocio

- Hard delete (sem soft delete)
- Se o telefone deletado era primario e restam outros, o mais antigo (menor id) e promovido a primario
- Permitido deletar o unico telefone — usuario fica sem telefone
- Ownership: retorna 404 se nao pertence ao usuario

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 404 Not Found | Telefone nao encontrado ou nao pertence ao usuario |
