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
- Exemplo: `StorePet`, `MarkPetReportFound`, `CancelPetReport`

### Services

Servicos de dominio que encapsulam logica reutilizavel. Diferente de Actions (que representam uma operacao), Services sao stateless e podem ser usados por multiplas Actions ou Jobs.

- Localizacao: `app/Services/`
- `MatchScoringService` — algoritmo de scoring (proximidade, raca, porte, sexo, cor, caracteristicas)
- `MatchAiEvaluationService` — orquestra avaliacao por IA de matches (score + confidence → final_score)
- `MatchAiPayloadBuilder` — constroi payload para envio ao provider de IA

### Contracts (Interfaces)

- Localizacao: `app/Contracts/`
- `PushNotificationService` — interface para envio de push notifications (impl: OneSignal, Log)
- `MatchAiProvider` — interface para providers de avaliacao IA (impl: OpenAI, Log, Null)

### Support Classes

Value objects e DTOs internos usados pelas camadas de matching.

- Localizacao: `app/Support/Matching/`
- `MatchScoreResult` — resultado do calculo de score (total + breakdown por criterio)
- `MatchAiResult` — resultado normalizado de uma avaliacao IA
- `MatchAiInput` — input estruturado para o provider de IA

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

| Job | Trigger | Descricao |
|-----|---------|-----------|
| `ProcessReportSightingMatching` | Report criado/location atualizado | Busca sightings candidatos e cria matches |
| `ProcessSightingMatching` | Sighting criado | Busca reports candidatos e cria matches |
| `ProcessMatchAiEvaluation` | Apos matching (se AI habilitada) | Avalia match via IA e ajusta final_score |
| `NotifyNearbyUsersOfLostPet` | Report criado | Notifica usuarios proximos sobre pet perdido |

### Transacoes

Actions que modificam multiplas tabelas usam `DB::transaction()`.

---

## Admin Panel

Painel web interno construido com **Inertia.js + React + TypeScript** no mesmo monolito Laravel. Acesso exclusivo para usuarios com role `ADMIN`.

### Stack Admin

| Camada | Ferramenta |
|--------|-----------|
| Bridge | Inertia.js (`inertiajs/inertia-laravel` + `@inertiajs/react`) |
| View | React 19 + TypeScript |
| Styling | TailwindCSS 4 + shadcn/ui |
| Charts | recharts (via shadcn chart) |

### Estrutura

```
app/Http/Controllers/Admin/     # Controllers Inertia (DashboardController, BreedController, etc.)
app/Http/Middleware/             # EnsureUserIsAdmin, HandleInertiaRequests
app/Http/Requests/Admin/        # FormRequests do admin
app/Actions/Admin/              # LogAdminAction (auditoria)
app/Models/AdminActionLog.php   # Log de acoes administrativas
routes/admin.php                # Rotas web do admin (prefix: /admin)
resources/js/pages/             # Paginas Inertia (React)
resources/js/Layouts/           # AdminLayout, GuestLayout
resources/js/Components/ui/     # Componentes shadcn
resources/views/app.blade.php   # Shell HTML do Inertia
```

### Autenticacao Admin

Admin usa **auth session** (guard `web` padrao com cookies), separado do Sanctum (tokens) usado pelo mobile. Middleware `admin` valida role `ADMIN` em todas as rotas protegidas.

### Auditoria

Toda acao que muta dados no admin e registrada na tabela `admin_action_logs` via `LogAdminAction::handle()`. Registros sao imutaveis (append-only, sem `updated_at`).
