<h3 style="font-weight: normal;">&nbsp; &nbsp; &nbsp; {{ $subChild['data']['name'] }}</h3>
<hr style="margin-top: 0px !important;">
<div style="width: 100%;">
    <div>
        @php
        $child3 = App\Models\EventKit::find($subChild['data']['id']);
        $nestedChildren = $child3?->children ?? [];
        @endphp
        @php
        $component = 'budget-products';
        @endphp
        @include('pdfs.' . $component, $subChild)
    </div>
</div>