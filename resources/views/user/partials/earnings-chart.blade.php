@php
    $showLabels = $showLabels ?? false;
    $uid = 'ec'.substr(md5(uniqid((string) mt_rand(), true)), 0, 8);

    $productValues = collect($chart)->map(fn ($row) => (float) ($row['rewards'] ?? 0) + (float) ($row['gaming'] ?? 0));
    $referralValues = collect($chart)->map(fn ($row) => (float) ($row['referral'] ?? 0));
    $dates = collect($chart)->pluck('date');

    $rawMax = max($productValues->max() ?: 0, $referralValues->max() ?: 0);
    $max = $rawMax <= 0 ? 1.0 : (ceil($rawMax * 2) / 2);
    if ($max < 1) {
        $max = 1.0;
    }

    $width = 640;
    $height = 220;
    $padLeft = $showLabels ? 36 : 8;
    $padRight = 8;
    $padTop = 12;
    $padBottom = $showLabels ? 28 : 10;
    $plotW = $width - $padLeft - $padRight;
    $plotH = $height - $padTop - $padBottom;
    $plotBottom = $padTop + $plotH;

    $count = max($productValues->count(), 1);
    $step = $count > 1 ? $plotW / ($count - 1) : $plotW;

    $toPoints = function ($series) use ($max, $padLeft, $padTop, $plotH, $step) {
        return $series->values()->map(function ($value, $index) use ($max, $padLeft, $padTop, $plotH, $step) {
            $x = $padLeft + ($index * $step);
            $y = $padTop + ($plotH - (($value / $max) * $plotH));

            return ['x' => round($x, 1), 'y' => round($y, 1)];
        });
    };

    $productCoords = $toPoints($productValues);
    $referralCoords = $toPoints($referralValues);

    $toStroke = function ($coords) {
        if ($coords->isEmpty()) {
            return '';
        }
        $parts = $coords->map(fn ($p) => $p['x'].','.$p['y']);

        return 'M'.$parts->implode(' L ');
    };

    $toArea = function ($coords) use ($padLeft, $plotW, $plotBottom) {
        if ($coords->isEmpty()) {
            return '';
        }
        $parts = $coords->map(fn ($p) => $p['x'].','.$p['y']);
        $first = $parts->first();
        $line = $parts->implode(' L ');

        return 'M'.$first.' L'.$line.' L'.($padLeft + $plotW).','.$plotBottom.' L'.$padLeft.','.$plotBottom.' Z';
    };

    $productStroke = $toStroke($productCoords);
    $referralStroke = $toStroke($referralCoords);
    $productArea = $toArea($productCoords);
    $hasData = ($productValues->sum() + $referralValues->sum()) > 0;

    $yTicks = [
        ['value' => $max, 'label' => rtrim(rtrim(number_format($max, 1, '.', ''), '0'), '.') ?: '0'],
        ['value' => $max / 2, 'label' => rtrim(rtrim(number_format($max / 2, 1, '.', ''), '0'), '.') ?: '0'],
        ['value' => 0, 'label' => '0'],
    ];

    $labelIndexes = [];
    if ($showLabels && $count > 1) {
        $labelIndexes = array_values(array_unique([
            0,
            (int) floor(($count - 1) / 4),
            (int) floor(($count - 1) / 2),
            (int) floor(3 * ($count - 1) / 4),
            $count - 1,
        ]));
    }
@endphp
<svg viewBox="0 0 {{ $width }} {{ $height }}" class="earnings-chart" preserveAspectRatio="xMidYMid meet" aria-hidden="true">
    <defs>
        <linearGradient id="{{ $uid }}-product" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#22c55e" stop-opacity="0.28"/>
            <stop offset="100%" stop-color="#22c55e" stop-opacity="0"/>
        </linearGradient>
        <linearGradient id="{{ $uid }}-referral" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.22"/>
            <stop offset="100%" stop-color="#3b82f6" stop-opacity="0"/>
        </linearGradient>
    </defs>

    {{-- Grid + Y labels --}}
    @foreach ($yTicks as $tick)
        @php $y = $padTop + ($plotH - (($tick['value'] / $max) * $plotH)); @endphp
        <line
            x1="{{ $padLeft }}"
            y1="{{ round($y, 1) }}"
            x2="{{ $padLeft + $plotW }}"
            y2="{{ round($y, 1) }}"
            stroke="rgba(255,255,255,0.08)"
            stroke-width="1"
        />
        @if ($showLabels)
            <text
                x="{{ $padLeft - 8 }}"
                y="{{ round($y + 3.5, 1) }}"
                text-anchor="end"
                fill="#94a3b8"
                font-size="11"
                font-weight="600"
            >{{ $tick['label'] }}</text>
        @endif
    @endforeach

    @if ($hasData)
        @if ($productArea)
            <path d="{{ $productArea }}" fill="url(#{{ $uid }}-product)"/>
        @endif
        @if ($productStroke)
            <path d="{{ $productStroke }}" fill="none" stroke="#22c55e" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
        @endif
        @if ($referralStroke)
            <path d="{{ $referralStroke }}" fill="none" stroke="#3b82f6" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
        @endif
    @else
        <path
            d="M{{ $padLeft }},{{ $plotBottom }} L{{ $padLeft + $plotW }},{{ $plotBottom }}"
            fill="none"
            stroke="#22c55e"
            stroke-width="2.4"
            stroke-linecap="round"
            opacity="0.85"
        />
        <path
            d="M{{ $padLeft }},{{ $plotBottom }} L{{ $padLeft + $plotW }},{{ $plotBottom }}"
            fill="none"
            stroke="#3b82f6"
            stroke-width="2.4"
            stroke-linecap="round"
            stroke-dasharray="5 5"
            opacity="0.75"
        />
    @endif

    @foreach ($labelIndexes as $i)
        @php
            $raw = $dates[$i] ?? null;
            $label = $raw ? \Illuminate\Support\Carbon::parse($raw)->format('M d') : '';
            $x = $padLeft + ($i * $step);
            $anchor = $i === 0 ? 'start' : ($i === $count - 1 ? 'end' : 'middle');
        @endphp
        @if ($label)
            <text
                x="{{ round($x, 1) }}"
                y="{{ $height - 8 }}"
                text-anchor="{{ $anchor }}"
                fill="#94a3b8"
                font-size="11"
                font-weight="600"
            >{{ $label }}</text>
        @endif
    @endforeach
</svg>
