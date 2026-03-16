# Architecture

## Fluxo de Request

```
Route -> FormRequest -> Controller -> DTO -> Action -> Model -> Resource
```

## Camadas

### FormRequests

Validam input do request. Nunca contem logica de negocio. Cada endpoint tem sua propria FormRequest. Incluem metodo `messages()` com mensagens customizadas.

- Localizacao: `app/Http/Requests/{Domain}/`
- Exemplo: `StorePetRequest`, `PetReportIndexRequest`

### Controllers

Thin controllers — nao contem logica de negocio. Responsabilidades:

1. Autorizar via `Gate::authorize()`
2. Construir DTO a partir de dados validados
3. Chamar Action
4. Retornar Resource

- Localizacao: `app/Http/Controllers/Api/V1/`

### DTOs (Data Transfer Objects)

Classes `final readonly` com constructor property promotion. Transportam dados tipados entre camadas. Nao dependem de HTTP (sem imports de FormRequest).

- Localizacao: `app/DTOs/{Domain}/`
- Exemplo: `StorePetData`, `UpdatePetReportData`

### Actions

Contem logica de negocio. Uma classe por operacao, com um unico metodo publico `handle()`. Recebem DTOs, nao FormRequests.

- Localizacao: `app/Actions/{Domain}/`
- Exemplo: `StorePet`, `MarkPetReportFound`, `ProcessPetMatching`

### Resources

Transformam Models em JSON com chaves camelCase. Usam `whenLoaded()` para relacionamentos opcionais e `whenCounted()` para contagens.

- Localizacao: `app/Http/Resources/`
- Exemplo: `PetResource`, `PetReportResource`

### Policies

Controlam autorizacao (quem pode fazer o que). Chamadas via `Gate::authorize()` nos controllers. Ver [Authorization](authorization.md) para detalhes.

- Localizacao: `app/Policies/`

## Padroes

### Paginacao

Todos os endpoints de listagem usam a macro `paginateFromRequest()`, que le `page` e `per_page` do query string.

### Delete

Deletes retornam `MessageResource` com status 200:

```json
{ "data": { "message": "Pet deleted successfully." } }
```

### Jobs

Operacoes demoradas sao executadas via jobs que implementam `ShouldQueue`. Jobs sao dispatchados via `DB::afterCommit()` para garantir que a transacao foi commitada antes do processamento.

### Transacoes

Actions que modificam multiplas tabelas usam `DB::transaction()`.
