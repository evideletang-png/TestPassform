<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PassForm — Émargement {{ $session->intitule ?? '' }}</title>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue:    #185FA5;
            --blue-dk: #0C447C;
            --green:   #0F6E56;
            --green-lt:#EAF3DE;
            --amber:   #854F0B;
            --amber-lt:#FAEEDA;
            --red:     #A32D2D;
            --red-lt:  #FCEBEB;
            --gray:    #5F5E5A;
            --gray-lt: #F5F4F0;
            --border:  #E2E0D8;
            --text:    #1A1916;
            --text-sec:#5F5E5A;
            --radius:  10px;
            --shadow:  0 1px 4px rgba(0,0,0,.08);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 15px;
            background: #F5F4F0;
            color: var(--text);
            min-height: 100vh;
        }

        /* ── Header ── */
        .header {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header-logo { font-size: 18px; font-weight: 600; color: var(--blue); }
        .header-sep  { color: var(--border); }
        .header-info { font-size: 13px; color: var(--text-sec); }
        .header-badge {
            margin-left: auto;
            background: var(--amber-lt);
            color: var(--amber);
            font-size: 12px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 20px;
        }

        /* ── Conteneur ── */
        .container {
            max-width: 480px;
            margin: 0 auto;
            padding: 20px 16px 40px;
        }

        /* ── Card ── */
        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 16px;
        }
        .card-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        /* ── Infos session ── */
        .session-info {
            background: #EEF5FF;
            border: 1px solid #C5D9F0;
            border-radius: var(--radius);
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 13px;
        }
        .session-info strong { color: var(--blue); }
        .session-info .dj-badge {
            display: inline-block;
            background: var(--blue);
            color: #fff;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 20px;
            margin-top: 4px;
        }

        /* ── Formulaire ── */
        .form-group { margin-bottom: 14px; }
        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-sec);
            margin-bottom: 5px;
        }
        label .req { color: var(--red); margin-left: 2px; }
        input[type=text], input[type=number] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            color: var(--text);
            background: #fff;
            transition: border-color .15s;
            -webkit-appearance: none;
        }
        input[type=text]:focus, input[type=number]:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(24,95,165,.1);
        }
        input[type=text].error, input[type=number].error {
            border-color: var(--red);
        }
        .field-error {
            font-size: 12px;
            color: var(--red);
            margin-top: 4px;
        }

        /* ── Toggle NIR ── */
        .toggle-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
        }
        .toggle-wrap {
            position: relative;
            width: 40px;
            height: 22px;
            flex-shrink: 0;
        }
        .toggle-wrap input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            inset: 0;
            background: #ccc;
            border-radius: 22px;
            cursor: pointer;
            transition: .2s;
        }
        .toggle-slider::before {
            content: '';
            position: absolute;
            width: 16px; height: 16px;
            left: 3px; top: 3px;
            background: #fff;
            border-radius: 50%;
            transition: .2s;
        }
        .toggle-wrap input:checked + .toggle-slider { background: var(--blue); }
        .toggle-wrap input:checked + .toggle-slider::before { transform: translateX(18px); }
        .toggle-label { font-size: 13px; color: var(--text-sec); }

        /* ── Pad de signature ── */
        .sig-wrap {
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            background: #FAFAFA;
        }
        .sig-wrap canvas {
            display: block;
            width: 100%;
            touch-action: none;
            cursor: crosshair;
        }
        .sig-placeholder {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            color: #bbb;
            pointer-events: none;
            transition: opacity .2s;
        }
        .sig-actions {
            display: flex;
            justify-content: flex-end;
            padding: 6px 10px;
            background: #F5F4F0;
            border-top: 1px solid var(--border);
        }
        .btn-clear {
            font-size: 12px;
            color: var(--text-sec);
            background: none;
            border: none;
            cursor: pointer;
            padding: 2px 6px;
        }
        .btn-clear:hover { color: var(--red); }

        /* ── Boutons ── */
        .btn {
            display: block;
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: background .15s, transform .1s;
        }
        .btn:active { transform: scale(.98); }
        .btn-primary { background: var(--blue);  color: #fff; }
        .btn-primary:hover { background: var(--blue-dk); }
        .btn-success { background: var(--green); color: #fff; }
        .btn-success:hover { background: #085041; }
        .btn-disabled { background: #ccc; color: #888; cursor: not-allowed; }

        /* ── Code ── */
        .code-input-wrap {
            display: flex;
            gap: 10px;
            align-items: stretch;
        }
        .code-input-wrap input {
            flex: 0 0 100px;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 6px;
            text-align: center;
            padding: 10px;
        }
        .code-input-wrap .btn {
            flex: 1;
            font-size: 14px;
        }
        .code-hint {
            font-size: 12px;
            color: var(--text-sec);
            margin-top: 6px;
            min-height: 18px;
        }
        .code-hint.valid   { color: var(--green); }
        .code-hint.invalid { color: var(--red); }

        /* ── Séparateur ── */
        .sep {
            height: 1px;
            background: var(--border);
            margin: 18px 0;
        }
        .or-sep {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            font-size: 12px;
            color: var(--text-sec);
        }
        .or-sep::before, .or-sep::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* ── Alertes ── */
        .alert {
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 14px;
        }
        .alert-error   { background: var(--red-lt);   color: var(--red);   border: 1px solid #f0c0c0; }
        .alert-success { background: var(--green-lt); color: var(--green); border: 1px solid #9FE1CB; }
        .alert-info    { background: #EEF5FF;          color: var(--blue);  border: 1px solid #C5D9F0; }

        /* ── RGPD notice ── */
        .rgpd-notice {
            font-size: 11px;
            color: var(--text-sec);
            line-height: 1.5;
            padding: 10px;
            background: var(--gray-lt);
            border-radius: 6px;
            margin-top: 14px;
        }

        /* ── Lien retard ── */
        .retard-link {
            display: block;
            text-align: center;
            font-size: 12px;
            color: var(--text-sec);
            margin-top: 14px;
            text-decoration: none;
        }
        .retard-link:hover { color: var(--blue); text-decoration: underline; }

        /* ── Footer ── */
        .footer {
            text-align: center;
            font-size: 11px;
            color: #bbb;
            margin-top: 30px;
        }

        /* ── Responsive ── */
        @media (max-width: 400px) {
            .container { padding: 12px 10px 40px; }
            .card { padding: 16px; }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/passform-public.css') }}?v={{ filemtime(public_path('css/passform-public.css')) }}">
</head>
<body>

<header class="header public-header">
    <div class="header-brand" aria-label="PassForm">
        <img src="{{ asset('images/brcode-logo.jpg') }}" alt="" class="header-logo-img">
        <span class="header-logo">PassForm</span>
    </div>
    <span class="header-sep">|</span>
    <span class="header-info">{{ $session->intitule }}</span>
    @if(isset($djEnCours) && $djEnCours)
        <span class="header-badge">
            {{ $djEnCours->creneau === 'matin' ? 'Matin' : 'Après-midi' }}
            · {{ $djEnCours->date->format('d/m/Y') }}
        </span>
    @endif
</header>

<main class="container public-shell">
    @yield('content')

    <div class="footer">
        Signature électronique sécurisée · Données chiffrées · Conforme RGPD
    </div>
</main>

@stack('scripts')
</body>
</html>
