# Breeds API

## Resource Shape — BreedResource

```json
{
  "id": 12,
  "name": "Labrador Retriever",
  "species": "DOG"
}
```

---

## GET /api/v1/breeds

> Lista racas disponiveis.

**Auth:** Bearer token
**Content-Type:** application/json

### Request

**Query params:**

| Param | Tipo | Obrigatorio | Default | Descricao |
|-------|------|-------------|---------|-----------|
| species | string | nao | — | Filtro: `DOG` ou `CAT` |

**Exemplo:**

```
GET /api/v1/breeds?species=DOG
```

### Response

**Status:** 200 OK (collection, nao paginado)

```json
{
  "data": [
    { "id": 1, "name": "Akita", "species": "DOG" },
    { "id": 2, "name": "American Bully", "species": "DOG" },
    ...
  ]
}
```

### Regras de Negocio

- Retorna apenas breeds com `is_active = true`
- Ordenadas por nome ASC
- Sem paginacao

### Status Codes

| Status | Quando |
|--------|--------|
| 200 OK | Sucesso |
| 401 Unauthorized | Token ausente/invalido |
| 422 Unprocessable Entity | Species invalida |

---

## Dados de Seed (Reference Data)

Seeder usa `updateOrCreate` — idempoente, pode ser re-executado.

### Cachorros (51 racas)

Akita, American Bully, American Staffordshire Terrier, Basset Hound, Beagle, Bernese Mountain Dog, Bichon Frise, Border Collie, Boston Terrier, Boxer, Buldogue Frances, Buldogue Ingles, Bull Terrier, Cane Corso, Cavalier King Charles Spaniel, Chihuahua, Chow Chow, Cocker Spaniel Americano, Cocker Spaniel Ingles, Dachshund, Dalmata, Doberman, Dogo Argentino, Fila Brasileiro, Fox Paulistinha, Golden Retriever, Husky Siberiano, Jack Russell Terrier, Labrador Retriever, Lhasa Apso, Lulu da Pomerania, Maltes, Malinois (Pastor Belga), Mastiff Ingles, Mastim Tibetano, Pinscher Miniatura, Pit Bull, Pointer Ingles, Poodle, Pug, Rottweiler, Samoieda, Schnauzer, Shar-Pei, Shiba Inu, Shih Tzu, Staffordshire Bull Terrier, Weimaraner, West Highland White Terrier, Whippet, Yorkshire Terrier, **SRD (Sem Raca Definida)**

### Gatos (25 racas)

Abissinio, American Shorthair, Angora, Bengal, British Shorthair, Burmes, Chartreux, Cornish Rex, Devon Rex, Exotico, Himalaia, Maine Coon, Munchkin, Noruegues da Floresta, Persa, Ragdoll, Russian Blue, Scottish Fold, Siames, Singapura, Somali, Sphynx, Tonquines, Turkish Van, **SRD (Sem Raca Definida)**

> **SRD (Sem Raca Definida)** esta presente para ambas as especies.
