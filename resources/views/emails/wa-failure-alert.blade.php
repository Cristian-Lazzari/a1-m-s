<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Alert A1MS</title>
</head>
<body style="margin:0; padding:24px; background:#f4f4f4; color:#111827; font-family:Arial, Helvetica, sans-serif;">
    <div style="max-width:920px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">

        <div style="padding:24px; background:#111827; color:#ffffff;">
            <h1 style="margin:0; font-size:24px;">[ALERT] {{ $alert['flow_label'] }} fallito</h1>
            <p style="margin:8px 0 0; font-size:14px;">Report automatico A1MS — non rispondere a questa mail.</p>
        </div>

        <div style="padding:24px;">

            <h2 style="margin:0 0 12px; font-size:18px;">Contesto</h2>
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tbody>
                    <tr>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb; width:220px;"><strong>Data</strong></td>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;">{{ $alert['reported_at'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;"><strong>WA ID</strong></td>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;">{{ $alert['wa_id'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;"><strong>Lingua cliente</strong></td>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;">{{ $alert['lang'] ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>

            <h2 style="margin:24px 0 12px; font-size:18px;">Ristorante</h2>
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tbody>
                    <tr>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb; width:220px;"><strong>Nome</strong></td>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;">{{ $alert['restaurant']['name'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;"><strong>Database</strong></td>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;">{{ $alert['restaurant']['db'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;"><strong>URL</strong></td>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;">{{ $alert['restaurant']['app_url'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;"><strong>Mail mittente</strong></td>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;">{{ $alert['restaurant']['mail_from'] ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>

            <h2 style="margin:24px 0 12px; font-size:18px;">Cliente</h2>
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tbody>
                    @forelse ($alert['customer'] as $key => $value)
                        <tr>
                            <td style="padding:8px 12px; border:1px solid #e5e7eb; width:220px;"><strong>{{ $key }}</strong></td>
                            <td style="padding:8px 12px; border:1px solid #e5e7eb;">{{ $value }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" style="padding:8px 12px; border:1px solid #e5e7eb;">Nessun dato cliente disponibile.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <h2 style="margin:24px 0 12px; font-size:18px;">Errore</h2>
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tbody>
                    <tr>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb; width:220px;"><strong>Tipo</strong></td>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;">{{ $alert['error']['type'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;"><strong>Messaggio</strong></td>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;">{{ $alert['error']['message'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;"><strong>Classe</strong></td>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;">{{ $alert['error']['exception_class'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;"><strong>File</strong></td>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;">{{ $alert['error']['file'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;"><strong>Riga</strong></td>
                        <td style="padding:8px 12px; border:1px solid #e5e7eb;">{{ $alert['error']['line'] ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>

            @if (!empty($alert['resource']))
                <h2 style="margin:24px 0 12px; font-size:18px;">Risorsa</h2>
                <table style="width:100%; border-collapse:collapse; font-size:14px;">
                    <tbody>
                        @foreach ($alert['resource'] as $key => $value)
                            <tr>
                                <td style="padding:8px 12px; border:1px solid #e5e7eb; width:220px;"><strong>{{ $key }}</strong></td>
                                <td style="padding:8px 12px; border:1px solid #e5e7eb;">
                                    {{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $value }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <h2 style="margin:24px 0 12px; font-size:18px;">Contesto extra</h2>
            <pre style="margin:0; padding:16px; background:#111827; color:#f9fafb; border-radius:8px; overflow:auto; white-space:pre-wrap;">{{ $alert['context_json'] }}</pre>

            @if (!empty($alert['trace']))
                <h2 style="margin:24px 0 12px; font-size:18px;">Stack trace</h2>
                <pre style="margin:0; padding:16px; background:#111827; color:#f9fafb; border-radius:8px; overflow:auto; white-space:pre-wrap;">{{ $alert['trace'] }}</pre>
            @endif

        </div>
    </div>
</body>
</html>
