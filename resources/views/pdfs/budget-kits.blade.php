<h3 style="font-weight: normal; margin-bottom: 0px !important;">&nbsp; &nbsp; &nbsp; {{ $subChild['data']['name'] }}</h3>
@if(isset($showObservations) && $showObservations && !empty($subChild['data']['observation']))
    <p style="font-size: 11px; color: #555; margin: 4px 0 0 15px;">Obs: {{ $subChild['data']['observation'] }}</p>
@endif
<hr style="margin-top: 0px !important; ">
<div style="width: 100%;">
    <div>
        @php
        $child3 = App\Models\EventKit::find($subChild['data']['id']);
        $nestedChildren = $child3?->children ?? [];
        @endphp

        @if($nestedChildren[0]['data']['type'] == 'kit')
        @foreach($nestedChildren as $subChild2)
                @include('pdfs.budget-sub-kits', ['subChild' => $subChild2, 'showObservations' => $showObservations ?? false])
            @endforeach
        @else
        @include('pdfs.budget-products', ['nestedChildren' => $nestedChildren, 'showObservations' => $showObservations ?? false])
        @endif
    </div>
</div>