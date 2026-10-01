{{--
    Tema do painel (01/10/2026), injectado no <head> pelo AdminPanelProvider
    (PanelsRenderHook::STYLES_AFTER), a seguir ao estilos-tarefas.

    Como não há build de Tailwind (ver estilos-tarefas.blade.php), o tema é CSS
    à mão por cima das classes `fi-*` do Filament, e a página "Hoje" usa
    classes próprias com o prefixo `hj-`. As cores do laranja vêm do
    `primary` do painel; aqui só ficam a barra lateral escura e o fundo.

    Se uma actualização do Filament mudar os nomes `fi-sidebar-*`, o pior que
    acontece é a barra voltar a branco — nada parte.
--}}
<link rel="preconnect" href="https://fonts.bunny.net">
<link rel="stylesheet" href="https://fonts.bunny.net/css?family=ibm-plex-mono:400,500&display=swap">
<style>
    :root {
        --at-escuro: #15181D;
        --at-escuro-2: #23272E;
        --at-escuro-3: #2B3038;
        --at-texto-barra: #C9CED6;
        --at-cinza-barra: #8E96A3;
        --at-laranja: #EA7A1A;
        --at-fundo: #F4F5F7;
        --at-linha: #E3E6EA;
        --at-texto: #14171C;
        --at-texto-2: #4A525E;
        --at-mudo: #5B6470;
        --at-mono: 'IBM Plex Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
    }

    .fi-body { background: var(--at-fundo); }

    /* ---------------- Barra lateral escura ---------------- */
    .fi-sidebar { background: var(--at-escuro) !important; }
    .fi-sidebar-header {
        background: var(--at-escuro) !important;
        box-shadow: none !important;
        border-bottom: 1px solid var(--at-escuro-2);
    }
    .fi-sidebar-header .fi-logo { border-radius: 8px; }
    .fi-sidebar-header .fi-icon-btn { color: var(--at-cinza-barra) !important; }
    .fi-sidebar-nav { scrollbar-color: var(--at-escuro-3) transparent; }

    .fi-sidebar-group-label {
        color: #7D8592 !important;
        font-size: 11px !important;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .fi-sidebar-group-icon,
    .fi-sidebar-group-collapse-button { color: #7D8592 !important; }

    .fi-sidebar-item-button { border-radius: 8px !important; }
    .fi-sidebar-item-button:hover,
    .fi-sidebar-item-button:focus-visible { background: var(--at-escuro-2) !important; }
    .fi-sidebar-item-label { color: var(--at-texto-barra) !important; }
    .fi-sidebar-item-icon { color: var(--at-cinza-barra) !important; }

    .fi-sidebar-item-active .fi-sidebar-item-button { background: var(--at-escuro-3) !important; }
    .fi-sidebar-item-active .fi-sidebar-item-label { color: #FFFFFF !important; font-weight: 500; }
    .fi-sidebar-item-active .fi-sidebar-item-icon { color: var(--at-laranja) !important; }

    .fi-sidebar-item-grouped-border div { background: var(--at-escuro-3) !important; }
    .fi-sidebar-item-active .fi-sidebar-item-grouped-border div { background: var(--at-laranja) !important; }

    .fi-sidebar .fi-badge {
        background: var(--at-escuro-3) !important;
        color: #F2C49B !important;
        --tw-ring-color: transparent !important;
        font-family: var(--at-mono);
    }

    /* ---------------- Página "Hoje" (prefixo hj-) ---------------- */
    .hj { display: flex; flex-direction: column; gap: 24px; color: var(--at-texto); }
    .hj-topo { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .hj-data { font-size: 14px; color: var(--at-mudo); }
    .hj-ola { margin: 2px 0 0; font-size: 28px; font-weight: 600; letter-spacing: -0.01em; }

    .hj-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
    .hj-kpi {
        background: #FFFFFF; border: 1px solid var(--at-linha); border-radius: 14px;
        padding: 18px 20px; display: flex; flex-direction: column; gap: 6px;
        text-decoration: none; color: inherit;
    }
    a.hj-kpi:hover { border-color: #C9CED6; }
    .hj-kpi-rotulo { font-size: 13px; color: var(--at-mudo); }
    .hj-kpi-num { font-size: 32px; font-weight: 600; line-height: 1.1; }
    .hj-kpi-desc { font-size: 13px; color: var(--at-mudo); }
    .hj-mau { color: #B91C1C; }
    .hj-bom { color: #0B6B4B; }

    .hj-duas { display: grid; grid-template-columns: minmax(0, 3fr) minmax(0, 2fr); gap: 20px; align-items: start; }
    .hj-caixa { background: #FFFFFF; border: 1px solid var(--at-linha); border-radius: 14px; overflow: hidden; }
    .hj-caixa-topo {
        display: flex; justify-content: space-between; align-items: center; gap: 12px;
        padding: 16px 20px; border-bottom: 1px solid #EEF0F3;
    }
    .hj-caixa-topo h2 { margin: 0; font-size: 16px; font-weight: 600; }
    .hj-nota { font-size: 13px; color: var(--at-mudo); }

    .hj-item { display: flex; gap: 14px; align-items: flex-start; padding: 14px 20px; border-bottom: 1px solid #EEF0F3; }
    .hj-item:last-child { border-bottom: none; }
    .hj-ponto { width: 10px; height: 10px; border-radius: 999px; margin-top: 6px; flex-shrink: 0; }
    .hj-ponto-falha { background: #B91C1C; }
    .hj-ponto-aviso { background: #B45309; }
    .hj-ponto-info { background: #5B6470; }
    .hj-item-corpo { flex-grow: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
    .hj-item-titulo { display: flex; gap: 8px; align-items: baseline; flex-wrap: wrap; }
    .hj-item-titulo strong { font-weight: 600; font-size: 15px; }
    .hj-onde { font-family: var(--at-mono); font-size: 12px; color: var(--at-mudo); }
    .hj-texto { font-size: 14px; color: var(--at-texto-2); line-height: 1.45; overflow-wrap: anywhere; }
    .hj-acao {
        flex-shrink: 0; font-size: 14px; font-weight: 500; text-decoration: none;
        padding: 7px 12px; border-radius: 8px; border: 1px solid #D5D9DF; color: var(--at-texto);
    }
    .hj-acao:hover { background: var(--at-fundo); }
    .hj-vazio { padding: 28px 20px; font-size: 14px; color: var(--at-mudo); }

    .hj-srv {
        display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 8px; align-items: center;
        padding: 10px 20px; border-bottom: 1px solid #F1F2F4; text-decoration: none; color: inherit;
    }
    .hj-srv:last-child { border-bottom: none; }
    a.hj-srv:hover { background: #FAFAFB; }
    .hj-srv-nome { font-size: 14px; font-weight: 500; display: block; }
    .hj-pill { font-size: 12px; font-weight: 500; padding: 3px 10px; border-radius: 999px; white-space: nowrap; }
    .hj-pill-falha { background: #FDECEC; color: #B91C1C; }
    .hj-pill-aviso { background: #FDF1E3; color: #9A4A07; }
    .hj-pill-ok { background: #E6F4EE; color: #0B6B4B; }
    .hj-pill-cinza { background: #EEF0F3; color: #4A525E; }

    .hj-link { font-size: 14px; color: rgb(var(--primary-600)); text-decoration: none; }
    .hj-link:hover { text-decoration: underline; }

    @media (max-width: 1100px) {
        .hj-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .hj-duas { grid-template-columns: minmax(0, 1fr); }
    }
    @media (max-width: 520px) {
        .hj-kpis { grid-template-columns: minmax(0, 1fr); }
        .hj-item { flex-wrap: wrap; }
    }

    /* ---------------- Detalhe da auditoria (prefixo ad-) ---------------- */
    .ad-titulo { margin: 8px 0 0; font-size: 15px; font-weight: 600; color: var(--at-texto); }
    .ad-bem { background: #FFFFFF; border: 1px solid var(--at-linha); border-radius: 12px; overflow: hidden; }
    .ad-bem summary { cursor: pointer; padding: 12px 16px; font-size: 14px; font-weight: 500; }
    .ad-bem-linha {
        display: flex; justify-content: space-between; gap: 12px;
        padding: 9px 16px; border-top: 1px solid #F1F2F4; font-size: 14px;
    }
    .ad-bem-linha span:last-child { color: var(--at-mudo); text-align: right; overflow-wrap: anywhere; }
</style>
