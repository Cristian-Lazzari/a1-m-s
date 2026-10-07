<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hub WhatsApp · Future Plus</title>
    <style>
        :root { --bg: #f4f5f2; --card: #ffffff; --ink: #1d2320; --soft: #5d6862; --rule: #dfe3dc; --ok: #1f8a5b; --warn: #b4441f; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #121614; --card: #1b201d; --ink: #e9ede9; --soft: #a3aea7; --rule: #2c332f; --ok: #3fb47f; --warn: #f08a63; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--ink); font: 15px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; padding: 24px 16px; }
        main { max-width: 1000px; margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
        h1 { font-size: 1.35rem; margin: 0; }
        h2 { font-size: 1.05rem; margin: 28px 0 10px; }
        .checks { display: flex; gap: 10px; flex-wrap: wrap; }
        .pill { border: 1px solid var(--rule); border-radius: 999px; padding: 4px 12px; background: var(--card); font-size: .875rem; }
        .ok { color: var(--ok); } .warn { color: var(--warn); }
        .table { background: var(--card); border: 1px solid var(--rule); border-radius: 12px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 640px; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--rule); vertical-align: top; }
        th { color: var(--soft); font-weight: 600; font-size: .8125rem; }
        tr:last-child td { border-bottom: 0; }
        .muted { color: var(--soft); }
        button { font: inherit; background: none; border: 1px solid var(--rule); border-radius: 8px; padding: 6px 12px; color: var(--ink); cursor: pointer; }
    </style>
</head>
<body>
<main>
    <header>
        <h1>Hub WhatsApp</h1>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Esci</button></form>
    </header>

    <div class="checks">
        <span class="pill {{ $metaConfigured ? 'ok' : 'warn' }}">App Meta {{ $metaConfigured ? 'configurata' : 'da configurare' }}</span>
        <span class="pill {{ $platformConfigured ? 'ok' : 'warn' }}">Numero di piattaforma {{ $platformConfigured ? 'configurato' : 'da configurare' }}</span>
    </div>

    <h2>Ristoranti ({{ $tenants->count() }})</h2>
    <div class="table">
        <table>
            <thead><tr><th>#</th><th>Locale</th><th>Numero</th><th>Eventi in attesa</th><th>Eventi falliti</th></tr></thead>
            <tbody>
            @forelse ($tenants as $tenant)
                @php $account = $tenant->accounts->first(); @endphp
                <tr>
                    <td class="muted">{{ $tenant->id }}</td>
                    <td>{{ $tenant->name }}@unless ($tenant->active) <span class="warn">(disattivato)</span>@endunless<br><span class="muted">{{ $tenant->app_url }}</span></td>
                    <td>
                        @if (! $account)
                            <span class="muted">non collegato</span>
                        @elseif ($account->status === 'active')
                            <span class="ok">{{ $account->display_phone }}</span>
                        @else
                            <span class="warn">{{ $account->status }}</span>
                            @if ($account->last_error)<br><span class="muted">{{ \Illuminate\Support\Str::limit($account->last_error, 120) }}</span>@endif
                        @endif
                    </td>
                    <td>{{ $tenant->pending_count }}</td>
                    <td class="{{ $tenant->failed_count ? 'warn' : '' }}">{{ $tenant->failed_count }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Nessun ristorante. Si aggiungono con <code>php artisan tenant:create</code>.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($failed->isNotEmpty())
        <h2>Ultimi eventi non consegnati</h2>
        <div class="table">
            <table>
                <thead><tr><th>Quando</th><th>Locale</th><th>Evento</th><th>Errore</th></tr></thead>
                <tbody>
                @foreach ($failed as $delivery)
                    <tr>
                        <td class="muted">{{ $delivery->created_at->format('d/m H:i') }}</td>
                        <td>{{ $delivery->tenant?->name }}</td>
                        <td>{{ $delivery->endpoint }}</td>
                        <td class="muted">{{ \Illuminate\Support\Str::limit($delivery->last_error, 160) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</main>
</body>
</html>
