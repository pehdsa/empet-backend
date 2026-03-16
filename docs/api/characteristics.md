# Characteristics API

## Resource Shape — CharacteristicResource

```json
{
  "id": 5,
  "name": "Orelha cortada",
  "category": "MARKING"
}
```

---

## GET /api/v1/characteristics

> Lista caracteristicas disponiveis para pets.

**Auth:** Bearer token
**Content-Type:** application/json

### Request

**Query params:**

| Param | Tipo | Obrigatorio | Default | Descricao |
|-------|------|-------------|---------|-----------|
| category | string | nao | — | Filtro: `MARKING`, `COAT`, `BEHAVIOR`, `IDENTIFICATION` |

**Exemplo:**

```
GET /api/v1/characteristics?category=MARKING
```

### Response

**Status:** 200 OK (collection, nao paginado)

```json
{
  "data": [
    { "id": 1, "name": "Cicatriz visivel", "category": "MARKING" },
    { "id": 2, "name": "Heterocromia", "category": "MARKING" },
    ...
  ]
}
```

### Regras de Negocio

- Retorna apenas characteristics com `is_active = true`
- Ordenadas por nome ASC
- Sem paginacao

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 422 Unprocessable Entity | Category invalida |

---

## Dados de Seed (Reference Data)

Seeder usa `updateOrCreate` — idempoente.

### MARKING (8)

Mancha no focinho, Orelha cortada, Pata branca, Cicatriz visivel, Mancha nos olhos, Heterocromia, Cauda curta, Manchas no corpo

### COAT (11)

Pelo longo, Pelo curto, Pelo medio, Bicolor, Tricolor, Pelo crespo, Pelo liso, Pelo duro/arame, Rajado (tabby), Merle, Albino

### BEHAVIOR (8)

Usa coleira, Usa roupa, Muito docil, Assustado com estranhos, Agressivo quando acuado, Sociavel com outros animais, Treinado para comandos, Castrado

### IDENTIFICATION (4)

Microchip, Tatuagem de identificacao, Plaqueta na coleira, Registro em orgao oficial

> **Nota sobre Seeds**: breeds e characteristics sao **dados de referencia** (essenciais para o dominio, executados em todos os ambientes). O test user em `DatabaseSeeder` e **dado de desenvolvimento** (conveniencia para testes locais).
