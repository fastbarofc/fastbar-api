<li class="menu-folder">
    <span class="menu-folder-name">{{ $folder['name'] }}</span>
    <ul>
        @foreach ($folder['drinks'] as $drink)
            <li class="menu-drink">{{ $drink }}</li>
        @endforeach
        @foreach ($folder['folders'] as $child)
            @include('pdfs.partials.contract-menu-folder', ['folder' => $child])
        @endforeach
    </ul>
</li>
