<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PassForm — {{ $session->intitule ?? 'Émargement' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/passform-public.css') }}?v={{ filemtime(public_path('css/passform-public.css')) }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    </style>
</head>
<body>

<header class="header public-header">
    <div class="header-brand" aria-label="PassForm">
        <img src="{{ asset('images/brcode-logo.jpg') }}" alt="" class="header-logo-img">
        <span class="header-logo">PassForm</span>
    </div>
    <span class="header-sep" aria-hidden="true">|</span>
    <span class="header-info">{{ $session->intitule }}</span>
    @if(isset($djEnCours) && $djEnCours)
        <span class="header-badge">
            {{ $djEnCours->creneau === 'matin' ? 'Matin' : 'Après-midi' }}
            · {{ $djEnCours->date->format('d/m/Y') }}
        </span>
    @endif
</header>

<main class="container public-shell" id="main-content">
    @yield('content')

    <footer class="footer">
        Signature électronique sécurisée · Données chiffrées · Conforme RGPD
    </footer>
</main>

@stack('scripts')
</body>
</html>
