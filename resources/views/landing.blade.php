@extends('layouts.landing')

@section('content')

    {{-- ============================================================ --}}
    {{-- HEADER --}}
    {{-- ============================================================ --}}
    <header class="sticky top-0 z-50 bg-white" id="header">
        <div class="mx-auto flex items-center justify-between px-6 py-5 lg:px-20">
            {{-- Logo --}}
            <a href="#" class="flex items-center gap-2.5">
                <svg class="h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="11.5" r="2.5"/><circle cx="16.5" cy="12.5" r="2.5"/><circle cx="12" cy="7" r="2"/><circle cx="12" cy="17" r="2"/></svg>
                <span class="text-[28px] font-extrabold text-primary">Empet</span>
            </a>

            {{-- Nav desktop --}}
            <nav class="hidden items-center gap-10 md:flex">
                <a href="#how-it-works" class="text-[15px] font-medium text-dark hover:text-primary transition-colors">Como funciona</a>
                <a href="#features" class="text-[15px] font-medium text-dark hover:text-primary transition-colors">Recursos</a>
                <a href="#social-proof" class="text-[15px] font-medium text-dark hover:text-primary transition-colors">Depoimentos</a>
                <a href="#faq" class="text-[15px] font-medium text-dark hover:text-primary transition-colors">FAQ</a>
                <a href="#" class="rounded-xl bg-primary px-7 py-3 text-[15px] font-semibold text-white hover:bg-primary-dark transition-colors">Baixar App</a>
            </nav>

            {{-- Menu mobile toggle --}}
            <button id="mobile-menu-btn" class="md:hidden p-2" aria-label="Abrir menu">
                <svg class="h-6 w-6 text-dark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
            </button>
        </div>

        {{-- Mobile menu --}}
        <nav id="mobile-menu" class="hidden border-t border-light-gray bg-white px-6 pb-4 md:hidden">
            <div class="flex flex-col gap-4 pt-4">
                <a href="#how-it-works" class="text-[15px] font-medium text-dark">Como funciona</a>
                <a href="#features" class="text-[15px] font-medium text-dark">Recursos</a>
                <a href="#social-proof" class="text-[15px] font-medium text-dark">Depoimentos</a>
                <a href="#" class="text-[15px] font-medium text-dark">FAQ</a>
                <a href="#" class="mt-2 rounded-xl bg-primary px-7 py-3 text-center text-[15px] font-semibold text-white">Baixar App</a>
            </div>
        </nav>
    </header>

    {{-- ============================================================ --}}
    {{-- HERO --}}
    {{-- ============================================================ --}}
    <section class="scroll-mt-20 bg-linear-to-br from-[#F3E8FF] via-white to-[#FFF7ED] px-6 lg:px-30" id="hero">
        <div class="mx-auto flex max-w-300 flex-col items-center gap-10 py-12 lg:h-[700px] lg:flex-row lg:gap-15 lg:py-20">
            {{-- Left: text --}}
            <div class="flex flex-1 flex-col gap-8">
                {{-- Badge --}}
                <div class="w-fit rounded-full bg-primary/10 px-5 py-2">
                    <span class="text-[13px] font-medium text-primary">O app que reencontra pets</span>
                </div>

                <h1 class="text-4xl font-extrabold leading-[1.1] text-dark lg:text-[52px]">
                    Seu pet perdido tem<br>
                    mais chances de voltar<br>
                    para casa
                </h1>

                <p class="max-w-120 text-lg leading-relaxed text-gray">
                    O Empet conecta automaticamente quem perdeu com quem encontrou. Reporte, seja notificado e reencontre.
                </p>

                {{-- CTA buttons --}}
                <div class="flex flex-col gap-3 sm:flex-row">
                    <a href="#" class="flex items-center justify-center gap-2.5 rounded-[14px] bg-primary px-7 h-13 text-base font-semibold text-white shadow-[0_8px_24px_#AD4FFF40] hover:bg-primary-dark transition-colors">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20.94c1.5 0 2.75 1.06 4 1.06 3 0 6-8 6-12.22A4.91 4.91 0 0 0 17 5c-2.22 0-4 1.44-5 2-1-.56-2.78-2-5-2a4.9 4.9 0 0 0-5 4.78C2 14 5 22 8 22c1.25 0 2.5-1.06 4-1.06"/><path d="M10 2c1 .5 2 2 2 5"/></svg>
                        Baixar para iOS
                    </a>
                    <a href="#" class="flex items-center justify-center gap-2.5 rounded-[14px] bg-dark px-7 h-13 text-base font-semibold text-white hover:bg-dark/90 transition-colors">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                        Baixar para Android
                    </a>
                </div>
            </div>

            {{-- Right: mockups --}}
            <div class="relative w-full max-w-120 h-[400px] lg:h-[560px]">
                {{-- Back phone (map) --}}
                <div class="absolute left-0 top-12 lg:top-15 w-60 rounded-[28px] border border-light-gray bg-white opacity-85 overflow-hidden shadow-sm">
                    <div class="flex h-[460px] items-center justify-center bg-bg-light text-sm text-gray">Mapa</div>
                </div>
                {{-- Front phone (matches) --}}
                <div class="absolute left-20 lg:left-30 top-4 lg:top-7 w-[250px] rounded-[28px] border border-light-gray bg-white overflow-hidden shadow-md z-10">
                    <div class="flex h-[480px] items-center justify-center bg-bg-light text-sm text-gray">Matches</div>
                </div>

                {{-- Floating badges --}}
                <div class="absolute left-0 top-32 lg:top-35 z-20 flex items-center gap-2 rounded-xl bg-white px-3.5 py-2.5 shadow-[0_4px_16px_#0000001A]">
                    <svg class="h-4.5 w-4.5 text-green" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 7 17l-5-5"/><path d="m22 10-7.5 7.5L13 16"/></svg>
                    <span class="text-xs font-semibold text-green">3 matches encontrados!</span>
                </div>
                <div class="absolute right-0 top-16 lg:top-20 z-20 flex items-center gap-2 rounded-xl bg-white px-3.5 py-2.5 shadow-[0_4px_16px_#0000001A]">
                    <svg class="h-4.5 w-4.5 text-orange" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
                    <span class="text-xs font-semibold text-gray">Notificação recebida</span>
                </div>
                <div class="hidden lg:flex absolute right-5 bottom-16 z-20 items-center gap-2 rounded-xl bg-white px-3.5 py-2.5 shadow-[0_4px_16px_#0000001A]">
                    <svg class="h-4.5 w-4.5 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
                    <span class="text-xs font-semibold text-primary">Rex está a 1.2 km</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- HOW IT WORKS --}}
    {{-- ============================================================ --}}
    <section class="scroll-mt-20 bg-white px-6 py-20 lg:px-30" id="how-it-works">
        <div class="mx-auto flex max-w-300 flex-col items-center gap-15">
            {{-- Header --}}
            <div class="flex flex-col items-center gap-4 text-center max-w-175">
                <span class="text-[13px] font-bold tracking-[2px] text-primary uppercase">Como funciona</span>
                <h2 class="text-3xl font-extrabold leading-tight text-[#1A1A2E] lg:text-[40px]">Simples. Rápido. Eficaz.</h2>
                <p class="text-base text-gray">Um processo simples que ativa uma rede colaborativa ao seu redor.</p>
            </div>

            {{-- Steps --}}
            <div class="grid w-full grid-cols-1 gap-6 md:grid-cols-3">
                {{-- Step 1 --}}
                <div class="flex flex-col items-center gap-5 rounded-[20px] bg-bg-light px-6 py-8">
                    <div class="flex h-7 w-7 items-center justify-center rounded-[14px] bg-primary text-white text-sm font-extrabold">1</div>
                    <svg class="h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="11.5" r="2.5"/><circle cx="16.5" cy="12.5" r="2.5"/><circle cx="12" cy="7" r="2"/><circle cx="12" cy="17" r="2"/></svg>
                    <h3 class="text-lg font-semibold text-[#1A1A2E] text-center">Reporte o Pet Perdido</h3>
                    <p class="text-sm leading-relaxed text-gray text-center">Adicione o pet, fotos, localização onde foi perdido e características. Quanto mais detalhes, melhor.</p>
                </div>

                {{-- Step 2 --}}
                <div class="flex flex-col items-center gap-5 rounded-[20px] bg-bg-light px-6 py-8">
                    <div class="flex h-7 w-7 items-center justify-center rounded-[14px] bg-orange text-white text-sm font-extrabold">2</div>
                    <svg class="h-8 w-8 text-orange" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <h3 class="text-lg font-semibold text-[#1A1A2E] text-center">O Sistema Busca por Você</h3>
                    <p class="text-sm leading-relaxed text-gray text-center">O algoritmo cruza raça, porte, sexo, cor e proximidade de todos os pets cadastrados na região e gera um score de compatibilidade.</p>
                </div>

                {{-- Step 3 --}}
                <div class="flex flex-col items-center gap-5 rounded-[20px] bg-bg-light px-6 py-8">
                    <div class="flex h-7 w-7 items-center justify-center rounded-[14px] bg-green text-white text-sm font-extrabold">3</div>
                    <svg class="h-8 w-8 text-green" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="m12 13-1-1 2-2-3-3 2-2"/><path d="m12 13 1 1-2 2 3 3-2 2"/></svg>
                    <h3 class="text-lg font-semibold text-[#1A1A2E] text-center">Receba Matches e Confirme</h3>
                    <p class="text-sm leading-relaxed text-gray text-center">Você recebe uma notificação quando há matches. Revise, compare fotos e confirme quando encontrar seu pet.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- FEATURES --}}
    {{-- ============================================================ --}}
    <section class="scroll-mt-20 bg-bg-light px-6 py-20 lg:px-30" id="features">
        <div class="mx-auto flex max-w-300 flex-col items-center gap-15">
            {{-- Header --}}
            <div class="flex flex-col items-center gap-4 text-center max-w-175">
                <span class="text-[13px] font-bold tracking-[2px] text-primary uppercase">Funcionalidades</span>
                <h2 class="text-3xl font-extrabold leading-tight text-dark lg:text-[40px]">
                    Tudo que você precisa para<br>reencontrar seu pet
                </h2>
            </div>

            {{-- Feature grid: 3 columns x 2 rows --}}
            <div class="grid w-full grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                {{-- F1: Mapa --}}
                <div class="flex flex-col gap-4 rounded-2xl border border-light-gray bg-white p-6">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/20">
                        <svg class="h-6 w-6 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.106 5.553a2 2 0 0 0 1.788 0l3.659-1.83A1 1 0 0 1 21 4.619v12.764a1 1 0 0 1-.553.894l-4.553 2.277a2 2 0 0 1-1.788 0l-4.212-2.106a2 2 0 0 0-1.788 0l-3.659 1.83A1 1 0 0 1 3 19.381V6.618a1 1 0 0 1 .553-.894l4.553-2.277a2 2 0 0 1 1.788 0z"/><path d="M15 5.764v15"/><path d="M9 3.236v15"/></svg>
                    </div>
                    <h3 class="text-base font-semibold text-dark">Mapa em Tempo Real</h3>
                    <p class="text-[13px] leading-relaxed text-gray">Veja todos os pets perdidos próximos a você no mapa. Filtre por espécie e porte.</p>
                </div>

                {{-- F2: Notificacoes --}}
                <div class="flex flex-col gap-4 rounded-2xl border border-light-gray bg-white p-6">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange/20">
                        <svg class="h-6 w-6 text-orange" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
                    </div>
                    <h3 class="text-base font-semibold text-dark">Notificações Inteligentes</h3>
                    <p class="text-[13px] leading-relaxed text-gray">Receba alertas quando novos matches forem encontrados para o seu report.</p>
                </div>

                {{-- F3: Matching (destaque) --}}
                <div class="relative flex flex-col gap-4 rounded-2xl border border-light-gray bg-white p-6">
                    <div class="absolute top-4 right-4 rounded-lg bg-primary px-2.5 py-1">
                        <span class="text-[11px] font-bold text-white">Destaque</span>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/20">
                        <svg class="h-6 w-6 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/></svg>
                    </div>
                    <h3 class="text-base font-semibold text-dark">Matching Automático</h3>
                    <p class="text-[13px] leading-relaxed text-gray">Algoritmo compara raça, porte, sexo, cor e características para gerar um score de compatibilidade entre 0 e 100.</p>
                </div>

                {{-- F4: Avistamentos --}}
                <div class="flex flex-col gap-4 rounded-2xl border border-light-gray bg-white p-6">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange/20">
                        <svg class="h-6 w-6 text-orange" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                    </div>
                    <h3 class="text-base font-semibold text-dark">Registro de Avistamentos</h3>
                    <p class="text-[13px] leading-relaxed text-gray">Viu um pet perdido? Registre o avistamento com foto e localização para ajudar o dono a encontrá-lo.</p>
                </div>

                {{-- F5: Comparacao --}}
                <div class="flex flex-col gap-4 rounded-2xl border border-light-gray bg-white p-6">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green/20">
                        <svg class="h-6 w-6 text-green" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M12 3v18"/></svg>
                    </div>
                    <h3 class="text-base font-semibold text-dark">Comparação Detalhada</h3>
                    <p class="text-[13px] leading-relaxed text-gray">Compare seu pet com cada match: fotos lado a lado, atributos e características em comum destacados.</p>
                </div>

                {{-- F6: Historico --}}
                <div class="flex flex-col gap-4 rounded-2xl border border-light-gray bg-white p-6">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-dark/20">
                        <svg class="h-6 w-6 text-dark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
                    </div>
                    <h3 class="text-base font-semibold text-dark">Histórico de Reports</h3>
                    <p class="text-[13px] leading-relaxed text-gray">Gerencie todos os seus reports ativos, encontrados e cancelados em um só lugar.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- MATCHING SPOTLIGHT --}}
    {{-- ============================================================ --}}
    <section class="scroll-mt-20 bg-white px-6 py-20 lg:px-30" id="matching">
        <div class="mx-auto flex max-w-300 flex-col items-center gap-16 lg:flex-row lg:gap-20">
            {{-- Left: match card visual --}}
            <div class="flex w-full max-w-120 flex-col gap-4">
                {{-- Main match card --}}
                <div class="w-full rounded-[20px] border border-light-gray bg-white p-6 shadow-[0_8px_24px_#0000000D]">
                    <div class="flex flex-col gap-4">
                        {{-- Score --}}
                        <div class="flex items-center gap-5">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-green text-white font-bold text-xl">92%</div>
                            <div>
                                <p class="text-sm font-semibold text-green">Alta compatibilidade</p>
                                <p class="text-xs text-gray">Score calculado por 6 critérios</p>
                            </div>
                        </div>
                        {{-- Photos --}}
                        <div class="flex items-center justify-center gap-4">
                            <div class="flex flex-col items-center gap-1.5">
                                <div class="h-20 w-20 rounded-2xl bg-bg-light"></div>
                                <span class="text-xs text-gray">Perdido</span>
                            </div>
                            <svg class="h-6 w-6 text-red" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                            <div class="flex flex-col items-center gap-1.5">
                                <div class="h-20 w-20 rounded-2xl bg-bg-light"></div>
                                <span class="text-xs text-gray">Encontrado</span>
                            </div>
                        </div>
                        {{-- Chips --}}
                        <div class="flex flex-wrap justify-center gap-2">
                            <span class="rounded-full bg-green/10 px-3.5 py-1.5 text-xs font-medium text-green">Raça</span>
                            <span class="rounded-full bg-green/10 px-3.5 py-1.5 text-xs font-medium text-green">Porte</span>
                            <span class="rounded-full bg-green/10 px-3.5 py-1.5 text-xs font-medium text-green">Cor</span>
                            <span class="rounded-full bg-green/10 px-3.5 py-1.5 text-xs font-medium text-green">Próximo</span>
                        </div>
                    </div>
                </div>

                {{-- Secondary card --}}
                <div class="flex items-center gap-3 rounded-[20px] border border-light-gray bg-white p-4 px-6 opacity-50">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-orange">
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15a7 7 0 1 0 0-14 7 7 0 0 0 0 14Z"/><path d="M8.21 13.89 7 23l5-3 5 3-1.21-9.12"/></svg>
                    </div>
                    <div>
                        <p class="text-[13px] font-semibold text-orange">Compatibilidade média</p>
                    </div>
                    <div class="ml-auto flex gap-1.5">
                        <span class="rounded-xl bg-green/10 px-2.5 py-1 text-[11px] font-medium text-green">Porte</span>
                        <span class="rounded-xl bg-red/10 px-2.5 py-1 text-[11px] font-medium text-red">Raça</span>
                    </div>
                </div>
            </div>

            {{-- Right: text --}}
            <div class="flex flex-1 flex-col gap-7">
                <div class="w-fit rounded-full bg-primary/10 px-5 py-2">
                    <span class="text-[13px] font-bold tracking-[2px] text-primary uppercase">Algoritmo de Matching</span>
                </div>
                <h2 class="text-3xl font-bold leading-tight text-dark lg:text-4xl">
                    Não são aleatórios.<br>São calculados.
                </h2>
                <p class="leading-relaxed text-gray">
                    O Empet analisa 6 critérios para gerar um score de 0 a 100 para cada candidato. Quanto mais atributos em comum, mais confiável o match. Atributos divergentes são sinalizados para você decidir.
                </p>

                {{-- Criteria list --}}
                <div class="flex flex-col gap-3.5">
                    @php
                        $criteria = [
                            ['icon' => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>', 'title' => 'Proximidade', 'desc' => '— até 25km de raio'],
                            ['icon' => '<circle cx="7.5" cy="11.5" r="2.5"/><circle cx="16.5" cy="12.5" r="2.5"/><circle cx="12" cy="7" r="2"/><circle cx="12" cy="17" r="2"/>', 'title' => 'Raça', 'desc' => '— primária e secundária'],
                            ['icon' => '<path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/>', 'title' => 'Porte', 'desc' => '— pequeno, médio, grande'],
                            ['icon' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><path d="M12 2v4"/><path d="M12 18v4"/>', 'title' => 'Sexo', 'desc' => '— macho, fêmea ou desconhecido'],
                            ['icon' => '<circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2"/>', 'title' => 'Cor primária', 'desc' => ''],
                            ['icon' => '<path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/>', 'title' => 'Características especiais', 'desc' => '— mancha, coleira, cicatriz...'],
                        ];
                    @endphp
                    @foreach ($criteria as $c)
                        <div class="flex items-center gap-2.5">
                            <svg class="h-4.5 w-4.5 shrink-0 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $c['icon'] !!}</svg>
                            <span class="text-sm"><strong class="font-bold text-dark">{{ $c['title'] }}</strong> @if($c['desc'])<span class="text-gray">{{ $c['desc'] }}</span>@endif</span>
                        </div>
                    @endforeach
                </div>

                <a href="#" class="text-sm font-medium text-primary hover:underline">Ver como funciona &rarr;</a>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- SOCIAL PROOF (Stats + Testimonial) --}}
    {{-- ============================================================ --}}
    <section class="scroll-mt-20 bg-linear-to-br from-primary to-primary-dark px-6 py-20 lg:px-30" id="social-proof">
        <div class="mx-auto flex max-w-300 flex-col items-center gap-15">
            {{-- Stats row --}}
            <div class="flex flex-col items-center gap-10 md:flex-row md:gap-15 lg:gap-15">
                <div class="flex flex-col items-center gap-2">
                    <span class="text-5xl font-extrabold text-white">10.000+</span>
                    <span class="text-base font-medium text-white/80">Pets cadastrados</span>
                </div>
                <div class="hidden md:block h-15 w-px bg-white/20"></div>
                <div class="flex flex-col items-center gap-2">
                    <span class="text-5xl font-extrabold text-white">68%</span>
                    <span class="text-base font-medium text-white/80">Taxa de reencontro com matching</span>
                </div>
                <div class="hidden md:block h-15 w-px bg-white/20"></div>
                <div class="flex flex-col items-center gap-2">
                    <span class="text-5xl font-extrabold text-white">4.8&#9733;</span>
                    <span class="text-base font-medium text-white/80">Avaliação média na loja</span>
                </div>
            </div>

            {{-- Testimonial --}}
            <div class="flex max-w-175 flex-col items-center gap-4">
                <span class="text-6xl font-extrabold leading-none text-orange">&ldquo;</span>
                <p class="text-center text-lg italic leading-relaxed text-white/90">
                    Encontrei minha Mel em menos de 24 horas. O app me mandou um match com 91% de compatibilidade e era ela.
                </p>
                <span class="text-sm font-medium text-white/80">&mdash; Ana P., São Paulo</span>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- FINAL CTA --}}
    {{-- ============================================================ --}}
    <section class="scroll-mt-20 bg-white px-6 py-24 lg:px-30" id="cta">
        <div class="mx-auto flex max-w-300 flex-col items-center gap-8 text-center">
            {{-- Paw icon --}}
            <svg class="h-16 w-16 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="11.5" r="2.5"/><circle cx="16.5" cy="12.5" r="2.5"/><circle cx="12" cy="7" r="2"/><circle cx="12" cy="17" r="2"/></svg>

            <h2 class="text-3xl font-extrabold leading-tight text-dark lg:text-[40px]">
                Cadastre seu pet antes de precisar.
            </h2>

            <p class="max-w-130 text-lg text-gray">
                Pets cadastrados no Empet têm muito mais chances de serem encontrados. Leva menos de 2 minutos.
            </p>

            {{-- Buttons --}}
            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="#" class="flex items-center justify-center gap-2 rounded-[14px] bg-primary px-7 h-13 text-base font-semibold text-white hover:bg-primary-dark transition-colors">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20.94c1.5 0 2.75 1.06 4 1.06 3 0 6-8 6-12.22A4.91 4.91 0 0 0 17 5c-2.22 0-4 1.44-5 2-1-.56-2.78-2-5-2a4.9 4.9 0 0 0-5 4.78C2 14 5 22 8 22c1.25 0 2.5-1.06 4-1.06"/><path d="M10 2c1 .5 2 2 2 5"/></svg>
                    Baixar para iOS
                </a>
                <a href="#" class="flex items-center justify-center gap-2 rounded-[14px] bg-dark px-7 h-13 text-base font-semibold text-white hover:bg-dark/90 transition-colors">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                    Baixar para Android
                </a>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- FAQ --}}
    {{-- ============================================================ --}}
    <section class="scroll-mt-20 bg-bg-light px-6 py-20 lg:px-30" id="faq">
        <div class="mx-auto flex max-w-300 flex-col items-center gap-15">
            {{-- Header --}}
            <div class="flex flex-col items-center gap-4 text-center max-w-175">
                <span class="text-[13px] font-bold tracking-[2px] text-primary uppercase">Dúvidas frequentes</span>
                <h2 class="text-3xl font-extrabold leading-tight text-dark lg:text-[40px]">Perguntas frequentes</h2>
                <p class="text-base text-gray">Tire suas dúvidas sobre o Empet e como ele pode ajudar a reencontrar seu pet.</p>
            </div>

            {{-- FAQ list --}}
            <div class="w-full max-w-200">
                @php
                    $faqs = [
                        ['q' => 'O Empet é gratuito?', 'a' => 'Sim! O Empet é 100% gratuito para reportar pets perdidos e encontrados. Você pode cadastrar quantos pets quiser, receber matches e notificações sem nenhum custo.'],
                        ['q' => 'Como funciona o sistema de matching?', 'a' => 'O algoritmo analisa 6 critérios: proximidade, raça, porte, sexo, cor primária e características especiais. Cada match recebe um score de 0 a 100 baseado na similaridade.'],
                        ['q' => 'Preciso cadastrar meu pet antes de ele se perder?', 'a' => 'Não é obrigatório, mas recomendamos. Pets pré-cadastrados têm muito mais chances de serem encontrados porque o sistema já possui todas as informações e fotos para comparação.'],
                        ['q' => 'O app está disponível para iOS e Android?', 'a' => 'Sim! O Empet está disponível na App Store (iOS) e na Google Play Store (Android). Baixe gratuitamente e comece a proteger seu pet hoje.'],
                        ['q' => 'Meus dados e fotos estão seguros?', 'a' => 'Sim. Utilizamos criptografia de ponta a ponta e seguimos a LGPD. Suas fotos e dados pessoais são usados exclusivamente para o sistema de matching e nunca são compartilhados com terceiros.'],
                    ];
                @endphp
                @foreach ($faqs as $i => $faq)
                    <div class="faq-item {{ $i < count($faqs) - 1 ? 'border-b border-light-gray' : '' }} py-6">
                        <button class="faq-toggle flex w-full items-center justify-between text-left" aria-expanded="false">
                            <span class="text-base font-semibold text-dark">{{ $faq['q'] }}</span>
                            <svg class="faq-chevron h-5 w-5 shrink-0 text-primary transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div class="faq-answer mt-4 hidden">
                            <p class="text-sm leading-relaxed text-gray">{{ $faq['a'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- FOOTER --}}
    {{-- ============================================================ --}}
    <footer class="border-t border-light-gray bg-white px-6 py-8 lg:px-30">
        <div class="mx-auto flex max-w-300 flex-col gap-6">
            {{-- Top row --}}
            <div class="flex flex-col items-center gap-6 md:flex-row md:justify-between">
                {{-- Logo --}}
                <div class="flex items-center gap-2.5">
                    <svg class="h-6 w-6 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="11.5" r="2.5"/><circle cx="16.5" cy="12.5" r="2.5"/><circle cx="12" cy="7" r="2"/><circle cx="12" cy="17" r="2"/></svg>
                    <span class="text-xl font-extrabold text-dark">Empet</span>
                    <span class="text-[13px] text-[#9B9C9D]">&middot; Reencontre quem você ama.</span>
                </div>

                {{-- Links --}}
                <nav class="flex items-center gap-6">
                    <a href="#how-it-works" class="text-sm text-gray hover:text-dark transition-colors">Como funciona</a>
                    <a href="#" class="text-sm text-gray hover:text-dark transition-colors">Privacidade</a>
                    <a href="#" class="text-sm text-gray hover:text-dark transition-colors">Termos de uso</a>
                    <a href="mailto:contato@empet.app" class="text-sm text-gray hover:text-dark transition-colors">Contato</a>
                </nav>

                {{-- Social --}}
                <div class="flex items-center gap-4">
                    <a href="#" class="text-gray hover:text-dark transition-colors" aria-label="Instagram">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                    </a>
                    <a href="#" class="text-gray hover:text-dark transition-colors" aria-label="TikTok">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg>
                    </a>
                </div>
            </div>

            {{-- Divider --}}
            <div class="h-px bg-light-gray"></div>

            {{-- Copyright --}}
            <div class="flex justify-center">
                <p class="text-xs text-[#9B9C9D]">&copy; {{ date('Y') }} Empet. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    {{-- Mobile menu toggle --}}
    <script>
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });

        document.querySelectorAll('#mobile-menu a').forEach(function(link) {
            link.addEventListener('click', function() {
                document.getElementById('mobile-menu').classList.add('hidden');
            });
        });

        document.querySelectorAll('.faq-toggle').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var answer = this.nextElementSibling;
                var chevron = this.querySelector('.faq-chevron');
                var expanded = this.getAttribute('aria-expanded') === 'true';
                answer.classList.toggle('hidden');
                chevron.classList.toggle('rotate-180');
                this.setAttribute('aria-expanded', !expanded);
            });
        });
    </script>

@endsection
