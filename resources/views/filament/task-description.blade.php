{{--
    O corpo do painel que abre por baixo de uma linha da lista de tarefas.

    A descrição costuma ter várias linhas e bullets. Antes vinha como
    `->description()` da coluna do título, espremida numa coluna de 80px entre
    mais oito colunas — daí a parede de texto com uma palavra por linha. Aqui
    tem a largura toda da linha e as mudanças de linha são respeitadas
    (`.atv-desc`, em resources/views/filament/estilos-tarefas.blade.php).
--}}
@php
    $texto = trim((string) $getRecord()->description);
@endphp

<div>
    <span class="atv-desc-titulo">Descrição</span>
    <p class="atv-desc">{{ $texto }}</p>
</div>
