<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    {{-- Favicon --}}
    <link rel="shortcut icon" href="{{ asset('public/favicon.png') }}" type="image/x-icon">
    <title>Dashboard +</title>
    @vite('resources/js/app.js')
    
    <style>
        /* CSS per il loader */
        /* .loader {
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, 50%);
            border: 16px solid #f3f3f3;
            border-radius: 50%;
            border-top: 16px solid #3498db;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite;
            z-index: 9999;
        } */
        .loader {
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50% , -50%);
            display: block;
            width: 84px;
            height: 84px;
        }

        .loader:before , .loader:after {
            content: "";
            position: absolute;
            left: 50%;
            bottom: 0;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #1e2d64;
            transform: translate(-50% , -100%)  scale(0);
            animation: push_401 2s infinite linear;
        }
        .loader:after{
            background: #10b793;
        }

        .loader:after {
        animation-delay: 1s;
        }

        @keyframes push_401 {
        0% , 50% {
            transform: translate(-50% , 0%)  scale(1)
        }

        100% {
            transform: translate(-50%, -100%) scale(0)
        }
        }

        /* @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        } */

        /* Nascondi il contenuto della pagina fino a quando il loader è visibile */
        body.loading {
            overflow: hidden;
        }

        body.loading .main-container-page, body.loading header {
            display: none;
        }

        /* @keyframes scrollBody {
            0% {
                
            }
            10% {
                transform: translateY(0); 
            }
            100% {
                transform: translateY(-100vh); 
            }
        }

        body {
            animation: scrollBody 15s ease-in-out infinite; 
        } */
    </style>
</head>
<body class="loading">

    <span class="loader"></span>

    <header>
        @include('admin.includes.nav')
    </header>

    <div class="main-container-page">
        @yield('contents')
    </div>
    
    
    <script>
        //Rimuovi il loader e mostra il contenuto della pagina quando tutto è caricato
        window.addEventListener('load', function() {
            document.body.classList.remove('loading');
            document.querySelector('.loader').style.display = 'none';

        });
        const toggleButton = document.getElementById('theme-toggle');
        const currentTheme = localStorage.getItem('theme') || 'light';
        localStorage.setItem('theme', currentTheme)
        document.documentElement.setAttribute("data-theme", currentTheme);
        
        toggleButton.addEventListener('click', () => {
            const theme = localStorage.getItem('theme') == 'light' ? 'dark' : 'light';
            localStorage.setItem("theme", theme);
            console.log(theme)
            document.documentElement.setAttribute("data-theme", theme);
        });
   
        


    </script>
    @yield('scripts')

</body>
</html>

