# Empet API Documentation

Documentacao completa da API do backend Empet — plataforma de matching de pets perdidos.

## Guias

- [Getting Started](getting-started.md) — Visao geral, headers, paginacao, convencoes, erros
- [Architecture](architecture.md) — Camadas, fluxo de request, padroes do projeto
- [Database Schema](database-schema.md) — Tabelas, colunas, FKs, constraints
- [Enums](enums.md) — Todos os enums com valores
- [Authorization](authorization.md) — Policies e regras de autorizacao
- [Matching System](matching-system.md) — Algoritmo de matching, scoring, job, notificacoes

## API Endpoints

- [Authentication](api/authentication.md) — Register, login, logout, alterar senha
- [Pets](api/pets.md) — CRUD de pets + toggle active
- [Pet Reports](api/pet-reports.md) — Reports de pets perdidos, matches, dismiss, confirm
- [Breeds](api/breeds.md) — Listagem de racas
- [Characteristics](api/characteristics.md) — Listagem de caracteristicas
