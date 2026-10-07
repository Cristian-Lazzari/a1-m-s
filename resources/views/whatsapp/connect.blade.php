<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Collega WhatsApp · {{ $source->app_name }}</title>
    <style>
        :root { --bg: #f4f5f2; --card: #ffffff; --ink: #1d2320; --soft: #5d6862; --rule: #dfe3dc; --accent: #1f8a5b; --accent-ink: #ffffff; --warn: #b4441f; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #121614; --card: #1b201d; --ink: #e9ede9; --soft: #a3aea7; --rule: #2c332f; --accent: #3fb47f; --accent-ink: #0d1410; --warn: #f08a63; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: var(--bg); color: var(--ink); font: 16px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; display: grid; place-items: center; padding: 24px 16px; }
        main { width: 100%; max-width: 560px; background: var(--card); border: 1px solid var(--rule); border-radius: 16px; padding: 28px; }
        h1 { font-size: 1.4rem; margin: 0 0 4px; }
        .sub { color: var(--soft); margin: 0 0 20px; }
        ol { padding-left: 1.2rem; margin: 0 0 20px; }
        li { margin-bottom: 8px; }
        .state { border: 1px solid var(--rule); border-radius: 12px; padding: 12px 14px; margin-bottom: 20px; }
        .state--error { border-color: var(--warn); color: var(--warn); }
        button, .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px 18px; border: 0; border-radius: 12px; background: var(--accent); color: var(--accent-ink); font: inherit; font-weight: 600; cursor: pointer; text-decoration: none; }
        button[disabled] { opacity: .6; cursor: progress; }
        #result { margin-top: 18px; }
        .ok { color: var(--accent); font-weight: 600; }
        .err { color: var(--warn); }
        .back { margin-top: 12px; background: transparent; color: var(--ink); border: 1px solid var(--rule); }
    </style>
</head>
<body>
<main>
    <h1>Collega il WhatsApp di {{ $source->app_name }}</h1>
    <p class="sub">Da questo numero i clienti riceveranno il promemoria della prenotazione e potranno disdire con un tocco.</p>

    @if ($account && $account->status === 'active')
        <div class="state">Numero già collegato: <strong>{{ $account->display_phone }}</strong>. Puoi ricollegarlo o collegarne un altro.</div>
    @elseif ($account && $account->status === 'error')
        <div class="state state--error">L'ultimo tentativo non è andato a buon fine: {{ $account->last_error }}</div>
    @endif

    <ol>
        <li>Tieni a portata di mano il telefono del numero da collegare: arriverà un codice via SMS o con una chiamata (va bene anche il fisso del locale).</li>
        <li>Il numero <strong>non deve essere attivo</strong> nell'app WhatsApp o WhatsApp Business. Se lo è, elimina prima l'account dall'app.</li>
        <li>Accedi con Facebook, scegli o crea l'account aziendale del locale e inserisci il nome che i clienti vedranno.</li>
        <li>Alla fine aggiungi un metodo di pagamento in WhatsApp Manager: Meta addebita lì i promemoria (pochi centesimi l'uno).</li>
    </ol>

    @if (! $appId || ! $configId)
        <div class="state state--error">Collegamento non ancora configurato (META_APP_ID / META_ES_CONFIG_ID).</div>
    @else
        <button type="button" id="connect">Collega con Facebook</button>
    @endif

    <div id="result" role="status" aria-live="polite"></div>

    @if ($returnUrl)
        <a class="btn back" href="{{ $returnUrl }}">Torna al gestionale</a>
    @endif
</main>

@if ($appId && $configId)
<script>
    window.fbAsyncInit = function () {
        FB.init({ appId: @json($appId), autoLogAppEvents: true, xfbml: false, version: @json($graphVersion) });
    };

    // I dati del numero arrivano dal popup con un postMessage, il codice dal
    // callback di FB.login: si manda tutto al server quando ci sono entrambi.
    const signup = { code: null, info: null, sent: false };
    const result = document.getElementById('result');
    const button = document.getElementById('connect');

    const show = (html, cls) => { result.innerHTML = '<p class="' + (cls || '') + '">' + html + '</p>'; };
    const escape = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    window.addEventListener('message', (event) => {
        if (!event.origin.endsWith('facebook.com')) return;
        let data;
        try { data = JSON.parse(event.data); } catch (e) { return; }
        if (data.type !== 'WA_EMBEDDED_SIGNUP') return;

        if (String(data.event).startsWith('FINISH')) {
            signup.info = data.data || {};
            submit();
        } else if (data.event === 'CANCEL') {
            button.disabled = false;
            show('Collegamento interrotto' + (data.data && data.data.current_step ? ' (passaggio: ' + escape(data.data.current_step) + ')' : '') + '. Puoi riprovare quando vuoi.', 'err');
        } else if (data.event === 'ERROR') {
            button.disabled = false;
            show('Meta ha segnalato un errore: ' + escape(data.data && data.data.error_message), 'err');
        }
    });

    async function submit() {
        if (signup.sent || !signup.code || !signup.info) return;
        if (!signup.info.phone_number_id || !signup.info.waba_id) {
            show('Il popup si è chiuso senza un numero collegato. Riprova e completa tutti i passaggi.', 'err');
            button.disabled = false;
            return;
        }
        signup.sent = true;
        show('Sto attivando il numero e creando il messaggio di promemoria…');

        try {
            const response = await fetch(@json(route('whatsapp.connect.complete')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    code: signup.code,
                    waba_id: signup.info.waba_id,
                    phone_number_id: signup.info.phone_number_id,
                    business_id: signup.info.business_id || null,
                }),
            });
            const json = await response.json();

            if (json.ok) {
                show('✅ Numero <strong>' + escape(json.display_phone) + '</strong> collegato. Il messaggio di promemoria è in approvazione da Meta (di solito pochi minuti). Ricorda di aggiungere il metodo di pagamento in WhatsApp Manager, poi accendi i promemoria dal gestionale.', 'ok');
            } else {
                show('Collegamento non completato: ' + escape(json.error || json.message), 'err');
                button.disabled = false;
                signup.sent = false;
            }
        } catch (e) {
            show('Collegamento non completato: il server non risponde. Riprova tra poco.', 'err');
            button.disabled = false;
            signup.sent = false;
        }
    }

    button.addEventListener('click', () => {
        button.disabled = true;
        signup.code = null; signup.info = null; signup.sent = false;
        result.innerHTML = '';

        FB.login((response) => {
            if (response.authResponse && response.authResponse.code) {
                signup.code = response.authResponse.code;
                submit();
            } else {
                button.disabled = false;
                if (!signup.info) show('Accesso a Facebook annullato.', 'err');
            }
        }, {
            config_id: @json($configId),
            response_type: 'code',
            override_default_response_type: true,
            extras: { setup: {}, sessionInfoVersion: '3' },
        });
    });
</script>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/it_IT/sdk.js"></script>
@endif
</body>
</html>
