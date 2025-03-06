
<div style="width: 100%; display: table; margin-top: 20px;">
    <div style="display: table-row;">
        @php
        $kitsRigth = ceil(count($nestedChildren) / 2);
        $kitsLeft = count($nestedChildren) - $kitsRigth;
        @endphp
        <div style="display: table-cell; width: 48%; padding-right: 2%;">
            <ul>
                @if(!empty($nestedChildren))
                @for($i = 0; $i < $kitsLeft; $i++)
                    <li>{{ $nestedChildren[$i]['data']['name'] }}</li>
                    @endfor
                    @endif
            </ul>
        </div>
        <div style="display: table-cell; width: 48%;">
            <ul>
                @if(!empty($nestedChildren))
                @for($i = $kitsLeft; $i < count($nestedChildren); $i++)
                    <li>{{ $nestedChildren[$i]['data']['name'] }}</li>
                    @endfor
                    @endif
            </ul>
        </div>
    </div>
</div>