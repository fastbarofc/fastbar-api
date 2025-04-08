<h3 style="font-weight: normal; margin-bottom: 0px !important;">&nbsp; &nbsp; &nbsp; {{ $subChild['data']['name'] }}</h3>
<hr style="margin-top: 0px !important; ">
<div style="width: 100%;">
    <div>
        @php
        $child3 = App\Models\EventKit::find($subChild['data']['id']);
        $nestedChildren = $child3?->children ?? [];
        @endphp

        @if($nestedChildren[0]['data']['type'] == 'kit')
        @foreach($nestedChildren as $subChild2)
                @include('pdfs.budget-sub-kits', ['subChild' => $subChild2])
            @endforeach
        @else
        @php
        $component = 'budget-products';
        @endphp
        @include('pdfs.' . $component, $nestedChildren)
        @endif
    </div>
</div>