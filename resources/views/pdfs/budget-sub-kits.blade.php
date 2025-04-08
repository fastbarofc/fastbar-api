<h3 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 4px; margin-left: 15px; color: #555;">&nbsp; &nbsp; &nbsp; {{ $subChild['data']['name'] }}</h3>
<hr style="margin-top: 0px !important; margin-left: 24px; border: none; border-top: 1px solid #ccc;">
<div style="width: 100%;">
    <div>
        @php
        $child3 = App\Models\EventKit::find($subChild['data']['id']);
        $nestedChildren = $child3?->children ?? [];
        $component = 'budget-products';
        @endphp
        @include('pdfs.' . $component, $nestedChildren)
    </div>
</div>