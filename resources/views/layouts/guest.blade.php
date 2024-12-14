<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    {{-- Favicon --}}
    <link rel="shortcut icon" href="{{ asset('public/favicon.png') }}" type="image/x-icon">
    <title>error</title>
    @vite('resources/js/app.js')
</head>
<body>
    <header>
        <div class="container my-5" >
            <h1>
                Pagina attualmente non disponibile
            </h1>
            <p>
                Per accedere alla demo vai su <a href="https://db-demo3.future-plus.it">https://db-demo3.future-plus.it</a>.
            </p>
            {{-- <p>
                Esegui l'accesso per vedere i tuoi contenuti!
            </p>
            @if (config('configurazione.APP_URL') == 'https://db-demo4.future-plus.it')
                <p style="font-style: italic">(Le credenziali per accedere alla demo sono email: <strong>demo@demo.it</strong> password: <strong>demo1</strong>) </p>
            @endif --}}
        </div>
    </header>


    <div class="container">
        <main>
            @yield('contents')
        </main>
    </div>
    

</body>
</html>
