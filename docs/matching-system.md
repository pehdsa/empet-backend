# Pet Matching System

## Visao Geral

O sistema de matching conecta pets perdidos com pets de outros usuarios que estejam proximos geograficamente e compartilhem caracteristicas semelhantes. O processamento e assincrono via job de queue.

---

## Fluxo Geral

```
Pet Report criado / location atualizado
        |
        v
DB::afterCommit()
        |
        v
Dispatch ProcessPetMatching (queue)
        |
        v
Adquirir Redis lock (pet-matching:report:{id})
        |
        v
Validar report (status=LOST, is_active=true)
        |
        v
Buscar candidatos (mesma especie, raio 25km, PostGIS)
        |
        v
Calcular score para cada candidato
        |
        v
Filtrar score < 30 (SCORE_THRESHOLD)
        |
        v
Ordenar (score DESC, distance ASC, id ASC)
        |
        v
Limitar a 20 matches (MAX_MATCHES)
        |
        v
Criar PetMatch records (status=PENDING)
        |
        v
Enviar notificacao PetMatchesFound (se matches > 0)
        |
        v
Liberar lock
```

---

## Configuracao do Job

| Parametro | Valor |
|-----------|-------|
| Classe | `App\Jobs\ProcessPetMatching` |
| Interface | `ShouldQueue` |
| Tentativas | 3 |
| Backoff | [10s, 60s] |
| Lock | Redis, chave `pet-matching:report:{id}`, TTL 60s |
| Queue | default (nao define `onQueue()` especifico) |
| Connection | Redis (`QUEUE_CONNECTION=redis`) |

---

## Busca de Candidatos

A busca filtra pets que possam corresponder ao pet perdido:

- **Mesma especie** do pet perdido
- **Pet ativo** (`is_active = true`) e **nao deletado** (`deleted_at IS NULL`)
- **Dono diferente** (`user_id != owner do pet perdido`)
- **Raio maximo**: 25.000 metros (constante `MAX_RADIUS_METERS`)
- **Proximidade**: calculada via PostGIS `ST_DWithin` (filtro) e `ST_Distance` (distancia)
- **Pets previamente dismissed** sao excluidos da busca
- **Agrupamento**: por pet, usando a menor distancia (`MIN`) quando o pet tem multiplos reports

---

## Algoritmo de Scoring

Pontuacao maxima: **100 pontos**

| Criterio | Max Pts | Calculo |
|----------|---------|---------|
| Proximidade | 35 | `35 * (1 - distancia / 25000)` — linear, 35pts em 0m, 0pts em 25km |
| Raca | 25 | Primaria exata = 25, cruzada (prim/sec) = 12, ambas null = 5, sem match = 0 |
| Porte | 10 | Exato = 10, 1 nivel de diferenca = 4, 2+ niveis = 0 |
| Sexo | 10 | Exato = 10, um UNKNOWN = 5, diferente = 0 |
| Cor | 10 | Exata (case-insensitive) = 10, ambas null = 3, sem match = 0 |
| Caracteristicas | 10 | Indice de Jaccard * 10. Ambas vazias = 5 |

### Detalhes do Score de Raca

| Situacao | Pontos |
|----------|--------|
| `lost.breed_id == candidate.breed_id` | 25 |
| `lost.breed_id == candidate.secondary_breed_id` | 12 |
| `lost.secondary_breed_id == candidate.breed_id` | 12 |
| Ambos `breed_id == null` | 5 |
| Sem match | 0 |

### Detalhes do Score de Porte

| Niveis (`SMALL=0, MEDIUM=1, LARGE=2`) | Pontos |
|----------------------------------------|--------|
| Diferenca 0 | 10 |
| Diferenca 1 | 4 |
| Diferenca 2 | 0 |

---

## Constantes

| Constante | Valor | Descricao |
|-----------|-------|-----------|
| `MAX_RADIUS_METERS` | 25000 | Raio maximo de busca em metros |
| `SCORE_THRESHOLD` | 30 | Score minimo para criar um match |
| `MAX_MATCHES` | 20 | Maximo de matches por report |

---

## Notificacao

Quando matches sao encontrados (count > 0), uma notificacao `PetMatchesFound` e enviada ao dono do report.

| Campo | Valor |
|-------|-------|
| Canal | `database` |
| Payload: `report_id` | ID do report |
| Payload: `pet_name` | Nome do pet perdido |
| Payload: `matches_count` | Quantidade de matches encontrados |

---

## Ciclo de Vida dos Matches

| Evento | Comportamento |
|--------|--------------|
| Report criado | Job processa e cria matches com status `PENDING` |
| Location atualizado | Matches `PENDING` sao deletados, job reprocessa. Matches `DISMISSED` permanecem intactos |
| Reprocessamento | Pets com matches `DISMISSED` anteriores sao excluidos da busca (nao sao reavaliados) |
| Cancel report | Todos matches `PENDING` viram `DISMISSED`, report vira `CANCELLED` |
| Mark found (sem match) | Todos matches `PENDING` viram `DISMISSED`, report vira `FOUND` |
| Mark found (com match) | Match especificado vira `CONFIRMED`, demais `PENDING` viram `DISMISSED` |
| Confirm match | Delega para MarkPetReportFound — mesmo comportamento de "mark found com match" |
| Dismiss match | Match individual vira `DISMISSED` |

> **CONFIRMED nunca e recalculado**: uma vez confirmado, o match e final e nao participa de reprocessamentos.
