<div style="width: 100%; display: table; margin-top: 20px;">
    <div style="display: table-row;">
        @php
        $kitsLeft = ceil(count($nestedChildren) / 2);
        $kitsRight = count($nestedChildren) - $kitsLeft;
        @endphp
        <div style="display: table-cell; width: 48%; padding-right: 2%;">
            <ul>
                @if(!empty($nestedChildren))
                @for($i = 0; $i < $kitsLeft; $i++)
                    <li>
                        {{ $nestedChildren[$i]['data']['name'] }}
                        @if(isset($showObservations) && $showObservations && !empty($nestedChildren[$i]['data']['observation']))
                            <br><span style="font-size: 11px; color: #555;">Obs: {{ $nestedChildren[$i]['data']['observation'] }}</span>
                        @endif
                    </li>
                    @endfor
                    @endif
            </ul>
        </div>
        <div style="display: table-cell; width: 48%;">
            <ul>
                @if(!empty($nestedChildren))
                @for($i = $kitsLeft; $i < count($nestedChildren); $i++)
                    <li>
                        {{ $nestedChildren[$i]['data']['name'] }}
                        @if(isset($showObservations) && $showObservations && !empty($nestedChildren[$i]['data']['observation']))
                            <br><span style="font-size: 11px; color: #555;">Obs: {{ $nestedChildren[$i]['data']['observation'] }}</span>
                        @endif
                    </li>
                    @endfor
                    @endif
            </ul>
        </div>
    </div>
</div>