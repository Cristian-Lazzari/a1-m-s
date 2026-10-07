# Hub WhatsApp v2 — deploy su whatsapp.future-plus.it

L'hub è l'unico sistema che parla con Meta. I gestionali dei ristoranti
(template-pro-be) lo chiamano via API con una chiave per ristorante, e l'hub
li chiama sulle loro `/api/private/wa/*` con il loro `PRIVATE_REPORT_TOKEN`.
Nessuno dei due entra nel database dell'altro.

## 1. cPanel

1. **Sottodominio** `whatsapp.future-plus.it`, document root = `<cartella>/public`.
2. **SSL** (AutoSSL / Let's Encrypt) attivo prima di tutto il resto: Meta e il
   popup di Facebook accettano solo https.
3. **Database MySQL nuovo** + **utente nuovo** con permessi **solo** su quel
   database (non riusare l'utente che vede i database dei ristoranti).
4. **PHP 8.1+** per il sottodominio.

## 2. Codice

```bash
git clone -b v2 https://github.com/Cristian-Lazzari/a1-m-s.git whatsapp-hub
cd whatsapp-hub
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Compila `.env` (vedi i commenti in `.env.example`). Per il verify token del
webhook genera una stringa casuale:

```bash
php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"
```

Poi:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan admin:create tuo@email.it
```

`.env` non va mai in git e non deve essere leggibile dal web (il document root
è `public/`, quindi non lo è).

## 3. Cron (uno solo)

```
* * * * * cd /home/USER/whatsapp-hub && php artisan schedule:run >> /dev/null 2>&1
```

Riprova ogni minuto gli eventi che un ristorante non ha ricevuto.

## 4. App Meta

- **Webhook** (WhatsApp → Configurazione): URL `https://whatsapp.future-plus.it/webhook/whatsapp`,
  verify token = `WA_WEBHOOK_VERIFY_TOKEN`, campo `messages` sottoscritto.
- **Facebook Login for Business**: dominio consentito per JS SDK
  `https://whatsapp.future-plus.it`, URI di reindirizzamento valido
  `https://whatsapp.future-plus.it/whatsapp/connect`.
- **Impostazioni → Di base → Domini app**: `whatsapp.future-plus.it`.

## 5. Un ristorante

Sul gestionale del ristorante genera un token privato (se non c'è già):

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

e mettilo nel suo `.env` come `PRIVATE_REPORT_TOKEN`. Poi sull'hub:

```bash
php artisan tenant:create "Nome locale" https://dashboard.locale.it
```

Incolla il token quando lo chiede; il comando stampa `WA_HUB_URL` e
`WA_HUB_API_KEY` da mettere nel `.env` del gestionale. Se una chiave finisce
dove non deve: `php artisan tenant:rotate-key <id>`.

## Controlli

- `/admin` (dopo il login): ristoranti, numeri collegati, eventi bloccati.
- `php artisan wa:test-alert`: prova la mail degli avvisi.
