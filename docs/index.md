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

- [Authentication](api/authentication.md) — Register, login, logout, alterar senha, forgot password, session restore
- [Pets](api/pets.md) — CRUD de pets + toggle active, conversao HEIC
- [Pet Reports](api/pet-reports.md) — Reports de pets perdidos, comunidade (lost/found/map/detail), matches
- [Pet Report Sightings](api/pet-report-sightings.md) — Avistamentos da comunidade em reports de pets perdidos
- [Pet Sightings](api/pet-sightings.md) — Avistamentos independentes, mapa, claim, meus avistamentos
- [Notification Settings](api/notification-settings.md) — Preferencias de notificacao do usuario
- [User Devices](api/user-devices.md) — Registro de dispositivos para push notifications
- [Notifications](api/notifications.md) — Listagem e leitura de notificacoes
- [User Phones](api/user-phones.md) — Gerenciamento de telefones do usuario
- [Breeds](api/breeds.md) — Listagem de racas
- [Characteristics](api/characteristics.md) — Listagem de caracteristicas
