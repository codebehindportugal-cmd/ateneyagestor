{{--
    Folha de estilos do painel, injectada no <head> pelo AdminPanelProvider
    (render hook PanelsRenderHook::STYLES_AFTER).

    Porque é que isto existe: este projecto usa o CSS **compilado** que vem
    dentro do pacote do Filament (public/css/filament/filament/app.css). Não há
    build de Tailwind próprio — não há package.json, nem tailwind.config.js, nem
    resources/css. Ou seja: só existem as classes que o Filament usa nos ficheiros
    dele. Classes como `whitespace-pre-wrap`, `leading-relaxed`, `uppercase`,
    `tracking-wide` ou `line-through` **não estão lá** e não fazem nada — era por
    isso que as notas do projecto e as descrições das tarefas apareciam com as
    mudanças de linha todas esmagadas numa só parede de texto.

    A primeira secção repõe esses utilitários em falta. A segunda tem os estilos
    próprios da lista de tarefas, todos com o prefixo `atv-` para não colidirem
    com nada do Filament.
--}}
<style>
    /* ---------------------------------------------------------------
       1. Utilitários que faltam no CSS compilado do Filament
       --------------------------------------------------------------- */
    .whitespace-pre-wrap { white-space: pre-wrap; }
    .whitespace-nowrap   { white-space: nowrap; }
    .leading-relaxed     { line-height: 1.625; }
    .uppercase           { text-transform: uppercase; }
    .tracking-wide       { letter-spacing: 0.025em; }
    .line-through        { text-decoration-line: line-through; }
    .line-clamp-\[--line-clamp\] {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: var(--line-clamp);
        overflow: hidden;
    }

    /* ---------------------------------------------------------------
       2. Lista de tarefas
       --------------------------------------------------------------- */

    /* A fila de "chips" por baixo do título: responsável, prazo, estado.
       O Split do Filament não quebra linha; num ecrã estreito os chips
       saíam de dentro da caixa. */
    .atv-chips {
        flex-wrap: wrap;
        gap: 0.25rem 0.5rem;
    }

    /* O bloco das horas, encostado à direita. Não quebra linha nunca:
       "4 h est." e "3 h reais" ficam sempre lado a lado. */
    .atv-horas {
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.25rem 0.375rem;
    }

    /* Tarefa concluída: o título fica riscado, para se distinguir de
       relance sem ter de ler o estado. */
    .atv-feito .fi-ta-text-item-label {
        text-decoration-line: line-through;
        text-decoration-color: #a1a1aa;
        text-decoration-thickness: 1px;
    }

    /* O painel que abre por baixo da linha, com a descrição toda. */
    .atv-desc-titulo {
        display: block;
        margin-bottom: 0.375rem;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #71717a;
    }

    .atv-desc {
        margin: 0;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        font-size: 0.8125rem;
        line-height: 1.7;
        color: #3f3f46;
    }

    /* ---------------------------------------------------------------
       3. Notas do projecto, por cima da lista
       --------------------------------------------------------------- */

    /* Nota: isto vive dentro do <p> do cabeçalho da tabela do Filament,
       por isso são todos <span>. Um <div> ali dentro é HTML inválido e o
       browser fecha o <p> antes dele, o que parte o alinhamento. */
    .atv-notas {
        display: block;
        margin-top: 0.5rem;
        padding: 0.625rem 0.875rem;
        border: 1px solid #e4e4e7;
        border-radius: 0.5rem;
        background-color: #fafafa;
    }

    .atv-notas-topo {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.25rem;
    }

    .atv-notas-titulo {
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #71717a;
    }

    .atv-notas-botao {
        flex-shrink: 0;
        padding: 0;
        border: 0;
        background: none;
        font-size: 0.75rem;
        font-weight: 600;
        color: #4f46e5;
        cursor: pointer;
    }

    .atv-notas-botao:hover { text-decoration: underline; }

    .atv-notas-corpo {
        display: block;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        font-size: 0.8125rem;
        line-height: 1.65;
        color: #3f3f46;
    }

    .atv-notas-corpo--curto {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
        overflow: hidden;
    }
</style>
