{{--
    As notas do projecto, por cima da lista de tarefas.

    Duas regras que não se podem quebrar aqui:

    1. Isto é renderizado dentro do `<p class="fi-ta-header-description">` do
       cabeçalho da tabela do Filament. Um `<p>` só aceita conteúdo de frase —
       um `<div>` ou um `<details>` fazem o browser fechar o parágrafo antes
       deles e o layout parte-se. Por isso só há `<span>` e `<button>`, com o
       `display` a vir do CSS.

    2. Não há `@if` nem `@foreach` neste ficheiro, de propósito. O Livewire
       envolve esses blocos em marcadores `<!--[if BLOCK]><![endif]-->` e eram
       eles que apareciam como texto no topo da página ("ENDBLOCK]><![endif]").
       A condição de "há notas?" está do lado do PHP, em ManageProjectTasks,
       que também trata de devolver isto como HtmlString.
--}}
<span class="atv-notas" x-data="{ aberto: false }">
    <span class="atv-notas-topo">
        <span class="atv-notas-titulo">Notas do projecto</span>

        <button
            type="button"
            class="atv-notas-botao"
            x-on:click="aberto = ! aberto"
            x-text="aberto ? 'Ver menos' : 'Ver tudo'"
        >Ver tudo</button>
    </span>

    {{-- A classe do recorte já vem no HTML para não haver um piscar de texto
         todo aberto antes de o Alpine arrancar. --}}
    <span
        class="atv-notas-corpo atv-notas-corpo--curto"
        x-bind:class="{ 'atv-notas-corpo--curto': ! aberto }"
    >{{ $notes }}</span>
</span>
