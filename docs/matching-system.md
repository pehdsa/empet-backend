# Pet Matching System

## Visao Geral

O sistema de matching conecta **reports de pets perdidos** com **avistamentos (sightings)** proximos geograficamente que compartilhem caracteristicas semelhantes. O matching e bidirecional: dispara quando um report e criado/atualizado E quando um sighting e criado. O processamento e assincrono via jobs de queue com avaliacao opcional por IA.

---

## Fluxo Geral

### Fluxo 1 — Report criado / location atualizado

```
Pet Report criado / location atualizado
        |
        v
DB::afterCommit()
        |
        v
Dispatch ProcessReportSightingMatching (queue)
        |
        v
Adquirir Redis lock (report-sighting-matching:report:{id})
        |
        v
Validar report (status=LOST, is_active=true)
        |
        v
Buscar sightings candidatos (mesma especie, raio 25km, PostGIS)
        |
        v
Para cada sighting: calcular score, pular CONFIRMED/DISMISSED existentes
        |
        v
Filtrar score < 30 (SCORE_THRESHOLD)
        |
        v
Criar/atualizar PetMatch records (status=PENDING)
        |
        v
Enviar notificacao PetMatchesFound (se novos matches > 0)
        |
        v
Dispatch ProcessMatchAiEvaluation para elegiveis (se AI habilitada)
        |
        v
Liberar lock
```

### Fluxo 2 — Sighting criado

```
Pet Sighting criado
        |
        v
DB::afterCommit()
        |
        v
Dispatch ProcessSightingMatching (queue)
        |
        v
Adquirir Redis lock (sighting-matching:sighting:{id})
        |
        v
Validar sighting (nao deletado)
        |
        v
Buscar reports candidatos (mesma especie, status LOST, raio 25km, PostGIS)
        |
        v
Para cada report: calcular score, pular CONFIRMED/DISMISSED existentes
        |
        v
Filtrar score < 30 (SCORE_THRESHOLD)
        |
        v
Respeitar limite de 20 matches PENDING por report
        |
        v
Criar/atualizar PetMatch records (status=PENDING)
        |
        v
Enviar notificacao PetMatchesFound para cada report afetado
        |
        v
Dispatch ProcessMatchAiEvaluation para elegiveis (se AI habilitada)
        |
        v
Liberar lock
```

---

## Configuracao dos Jobs

### ProcessReportSightingMatching

| Parametro | Valor |
|-----------|-------|
| Classe | `App\Jobs\ProcessReportSightingMatching` |
| Trigger | Report criado ou location atualizado |
| Tentativas | 3 |
| Backoff | [10s, 60s] |
| Lock | Redis, chave `report-sighting-matching:report:{id}`, TTL 60s |

### ProcessSightingMatching

| Parametro | Valor |
|-----------|-------|
| Classe | `App\Jobs\ProcessSightingMatching` |
| Trigger | Sighting criado |
| Tentativas | 3 |
| Backoff | [10s, 60s] |
| Lock | Redis, chave `sighting-matching:sighting:{id}`, TTL 60s |

### ProcessMatchAiEvaluation

| Parametro | Valor |
|-----------|-------|
| Classe | `App\Jobs\ProcessMatchAiEvaluation` |
| Trigger | Dispatched apos matching (se AI habilitada) |
| Tentativas | 2 |
| Backoff | [30s] |
| Timeout | 30s |
| Delay | 5s apos dispatch |

---

## Busca de Candidatos

### Sightings candidatos (para um report)

- **Mesma especie** do pet perdido
- **Dono diferente** (`user_id != report.user_id`)
- **Nao deletado** (`deleted_at IS NULL`)
- **Raio maximo**: 25.000 metros via PostGIS `ST_DWithin`
- Matches `CONFIRMED` ou `DISMISSED` existentes sao preservados (nao recriados)

### Reports candidatos (para um sighting)

- **Status LOST** e **ativo** (`is_active = true`)
- **Dono diferente** (`user_id != sighting.user_id`)
- **Pet existente** com mesma especie e nao deletado
- **Location nao nula** no report
- **Raio maximo**: 25.000 metros via PostGIS `ST_DWithin`
- Respeita limite de 20 matches PENDING por report

---

## Algoritmo de Scoring

Servico: `App\Services\MatchScoringService`

Compara um **PetSighting** com um **Pet** (do report). Pontuacao base pode variar de negativa a **95 pontos** (maximo teorico sem penalties).

| Criterio | Max Pts | Calculo |
|----------|---------|---------|
| Proximidade | 35 | `35 * (1 - distancia / 25000)` — linear, 35pts em 0m, 0pts em 25km |
| Raca | 25 | Match primaria = 25, match secundaria = 12, ambas null = 5, uma null = 0, **mismatch conhecido = -15** |
| Porte | 10 | Exato = 10, 1 nivel de diferenca = 4, 2+ niveis = 0, um null = 0 |
| Sexo | 10 | Exato = 10, um UNKNOWN = 5, um null = 0, **diferente (ambos conhecidos) = -10** |
| Cor | 5 | Jaccard de tokens * 5. Ambas null = 3, uma null = 0 |
| Caracteristicas | 10 | Jaccard de IDs * 10. Ambas vazias = 5 |

### Detalhes do Score de Raca

| Situacao | Pontos |
|----------|--------|
| `sighting.breed_id == pet.breed_id` (primaria) | 25 |
| `sighting.breed_id == pet.secondary_breed_id` | 12 |
| Ambos `breed_id == null` | 5 |
| Um dos dois `null` | 0 |
| Mismatch conhecido (ambos definidos, nenhum bate) | **-15** |

### Detalhes do Score de Porte

| Niveis (`SMALL=0, MEDIUM=1, LARGE=2`) | Pontos |
|----------------------------------------|--------|
| Diferenca 0 | 10 |
| Diferenca 1 | 4 |
| Diferenca 2 | 0 |
| Um dos dois `null` | 0 |

### Detalhes do Score de Sexo

| Situacao | Pontos |
|----------|--------|
| Iguais (ambos conhecidos) | 10 |
| Um `UNKNOWN` | 5 |
| Um `null` | 0 |
| Diferentes (ambos conhecidos) | **-10** |

### Detalhes do Score de Cor

Tokeniza a string de cor (split por espaco/virgula/barra, remove stopwords `e, com, de, o, a`), calcula indice de Jaccard entre tokens e multiplica pelo max (5). Ambas null = 3.

---

## Constantes

| Constante | Valor | Descricao |
|-----------|-------|-----------|
| `MAX_RADIUS_METERS` | 25000 | Raio maximo de busca em metros |
| `SCORE_THRESHOLD` | 30 | Score minimo para criar um match |
| `MAX_MATCHES` | 20 | Maximo de matches PENDING por report |
| `PROXIMITY_MAX` | 35 | Pontuacao maxima de proximidade |
| `BREED_MAX` | 25 | Pontuacao maxima de raca |
| `SIZE_MAX` | 10 | Pontuacao maxima de porte |
| `SEX_MAX` | 10 | Pontuacao maxima de sexo |
| `COLOR_MAX` | 5 | Pontuacao maxima de cor |
| `CHARACTERISTICS_MAX` | 10 | Pontuacao maxima de caracteristicas |

---

## Pipeline de Avaliacao por IA

Apos o matching deterministico, matches PENDING elegiveis podem ser avaliados por IA para refinar o score.

### Elegibilidade

- Status `PENDING`
- `ai_status IS NULL` (nao avaliado ainda)
- `base_score >= min_base_score` (config, default 25)
- Limitado a `max_evaluations_per_report` por report (config, default 5)
- Ordenados por `base_score DESC, distance_meters ASC`

### Arquitetura

| Componente | Classe |
|---|---|
| Job | `App\Jobs\ProcessMatchAiEvaluation` |
| Servico | `App\Services\MatchAiEvaluationService` |
| Payload Builder | `App\Services\MatchAiPayloadBuilder` |
| Contrato | `App\Contracts\MatchAiProvider` |
| Provider OpenAI | `App\Services\OpenAiMatchAiProvider` |
| Provider Log (dev) | `App\Services\LogMatchAiProvider` |
| Provider Null (test) | `App\Services\NullMatchAiProvider` |

### Configuracao

```
services.match_ai.enabled        # bool — habilita/desabilita pipeline AI
services.match_ai.provider       # string — provider ativo (openai, log, null)
services.match_ai.min_base_score # int — score minimo para avaliacao (default 25)
services.match_ai.max_evaluations_per_report # int — max avaliacoes por report (default 5)
```

### Calculo do Final Score

O `final_score` combina `base_score` com um ajuste baseado no `ai_score`:

| ai_score | Ajuste | Condicao |
|----------|--------|----------|
| >= 80 | +10 | confidence >= 0.4 |
| >= 60 | +5 | confidence >= 0.4 |
| >= 40 | 0 | — |
| < 40 | -10 | confidence >= 0.4 |
| qualquer | 0 | confidence < 0.4 (guarda de baixa confianca) |

```
final_score = clamp(base_score + adjustment, 0, 100)
```

### Status da IA no Match

| ai_status | Significado |
|-----------|-------------|
| `null` | Nao avaliado (aguardando ou nao elegivel) |
| `SUCCESS` | Avaliado com sucesso — `ai_score`, `ai_confidence`, `ai_summary` preenchidos |
| `FAILED` | Avaliacao falhou — `final_score` permanece igual a `base_score` |

---

## Notificacao

Quando novos matches sao encontrados, uma notificacao `PetMatchesFound` e enviada ao dono do report.

| Campo | Valor |
|-------|-------|
| Canal | `database` |
| Payload: `report_id` | ID do report |
| Payload: `pet_name` | Nome do pet perdido |
| Payload: `matches_count` | Quantidade de novos matches encontrados |

---

## Ciclo de Vida dos Matches

| Evento | Comportamento |
|--------|--------------|
| Report criado | ProcessReportSightingMatching cria matches PENDING com sightings candidatos |
| Report location atualizado | ProcessReportSightingMatching reavalia — atualiza existentes, cria novos. CONFIRMED/DISMISSED preservados |
| Sighting criado | ProcessSightingMatching cria matches PENDING com reports candidatos |
| Cancel report | Todos matches PENDING viram DISMISSED, report vira CANCELLED |
| Mark found (sem match) | Todos matches PENDING viram DISMISSED, report vira FOUND |
| Mark found (com match) | Match especificado vira CONFIRMED, demais PENDING viram DISMISSED |
| Confirm match | Delega para MarkPetReportFound — mesmo comportamento de "mark found com match" |
| Dismiss match | Match individual vira DISMISSED |

> **CONFIRMED nunca e recalculado**: uma vez confirmado, o match e final e nao participa de reprocessamentos.
