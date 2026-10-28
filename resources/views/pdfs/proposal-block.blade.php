{{--
    Um bloco de conteudo das paginas de orcamento da proposta.

    $block  titulo, itens e linha de valor por pessoa, montados em
            EventProposalService::pages()
    $first  primeiro bloco da coluna: nao leva o respiro do topo
--}}
@if (!empty($block['title']))
    <h2 class="{{ $first ? '' : 'spaced' }}">{{ $block['title'] }}</h2>
@endif

@if (!empty($block['items']))
    <ul class="{{ $block['color'] ?? 'pink' }}{{ ($block['tight'] ?? false) ? ' tight' : '' }}">
        @foreach ($block['items'] as $item)
            <li><span>{{ $item }}</span></li>
        @endforeach
    </ul>
@endif

@if (!empty($block['line']))
    <p class="line">{{ $block['line'] }}</p>
@endif
