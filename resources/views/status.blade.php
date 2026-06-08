@php
    /** @var \Webrek\HealthUi\HealthReport $report */
    $colors = ['ok' => '#16a34a', 'warning' => '#d97706', 'failed' => '#dc2626'];
    $overall = $report->status->value;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Health · {{ $report->status->label() }}</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 2rem 1rem;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc; color: #0f172a;
        }
        @media (prefers-color-scheme: dark) {
            body { background: #0b1120; color: #e2e8f0; }
            .card { background: #111827 !important; border-color: #1f2937 !important; }
            th { color: #94a3b8 !important; }
            tr { border-color: #1f2937 !important; }
        }
        .wrap { max-width: 720px; margin: 0 auto; }
        .header { display: flex; align-items: center; gap: .75rem; margin-bottom: 1.5rem; }
        .dot { width: 14px; height: 14px; border-radius: 50%; }
        h1 { font-size: 1.25rem; margin: 0; font-weight: 600; }
        .muted { color: #64748b; font-size: .85rem; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .85rem 1rem; font-size: .9rem; }
        th { text-transform: uppercase; letter-spacing: .04em; font-size: .7rem; color: #64748b; }
        tr { border-top: 1px solid #e2e8f0; }
        .badge { display: inline-block; padding: .15rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 600; color: #fff; }
        .name { font-weight: 600; }
        .ms { color: #94a3b8; font-variant-numeric: tabular-nums; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="header">
            <span class="dot" style="background: {{ $colors[$overall] }}"></span>
            <div>
                <h1>{{ $report->status->label() }}</h1>
                <div class="muted">{{ count($report->results) }} check(s)</div>
            </div>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr><th>Check</th><th>Status</th><th>Message</th><th>ms</th></tr>
                </thead>
                <tbody>
                    @foreach ($report->results as $result)
                        <tr>
                            <td class="name">{{ $result->name }}</td>
                            <td><span class="badge" style="background: {{ $colors[$result->status->value] }}">{{ strtoupper($result->status->value) }}</span></td>
                            <td>{{ $result->message }}</td>
                            <td class="ms">{{ number_format($result->durationMs, 1) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
