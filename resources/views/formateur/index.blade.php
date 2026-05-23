<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PassForm — Signature formateur</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --blue:#185FA5; --green:#0F6E56; --green-lt:#EAF3DE;
            --amber:#854F0B; --amber-lt:#FAEEDA;
            --border:#E2E0D8; --text:#1A1916; --text-sec:#5F5E5A;
            --radius:10px;
        }
        body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; font-size:15px; background:#F5F4F0; min-height:100vh; }
        .header { background:#fff; border-bottom:1px solid var(--border); padding:14px 20px; display:flex; align-items:center; gap:12px; }
        .header-logo { font-size:18px; font-weight:600; color:var(--blue); }
        .header-role { background:var(--amber-lt); color:var(--amber); font-size:12px; font-weight:500; padding:3px 10px; border-radius:20px; margin-left:auto; }
        .container { max-width:540px; margin:0 auto; padding:20px 16px 40px; }
        .card { background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:20px; margin-bottom:16px; }
        .dj-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
        .dj-title { font-size:15px; font-weight:600; }
        .dj-sub { font-size:12px; color:var(--text-sec); margin-top:2px; }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:500; }
        .badge-open   { background:var(--green-lt); color:var(--green); }
        .badge-closed { background:#F1EFE8; color:var(--text-sec); }
        .badge-done   { background:var(--green-lt); color:var(--green); }
        .progress-row { display:flex; align-items:center; gap:10px; margin-bottom:14px; }
        .progress-bar { flex:1; height:6px; background:#F1EFE8; border-radius:3px; overflow:hidden; }
        .progress-fill { height:100%; background:var(--green); border-radius:3px; }
        .progress-label { font-size:12px; color:var(--text-sec); min-width:40px; text-align:right; }
        .sig-wrap { border:1px solid var(--border); border-radius:8px; overflow:hidden; background:#FAFAFA; position:relative; }
        .sig-wrap canvas { display:block; width:100%; touch-action:none; cursor:crosshair; }
        .sig-placeholder { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:13px; color:#bbb; pointer-events:none; transition:opacity .2s; }
        .sig-actions { display:flex; justify-content:flex-end; padding:6px 10px; background:#F5F4F0; border-top:1px solid var(--border); }
        .btn-clear { font-size:12px; color:var(--text-sec); background:none; border:none; cursor:pointer; }
        .btn-clear:hover { color:#A32D2D; }
        .btn { display:block; width:100%; padding:12px; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; text-align:center; transition:background .15s; }
        .btn-success { background:var(--green); color:#fff; margin-top:12px; }
        .btn-success:hover { background:#085041; }
        .alert-success { background:var(--green-lt); border:1px solid #9FE1CB; border-radius:8px; padding:12px 14px; color:var(--green); font-size:13px; }
        .sep { height:1px; background:var(--border); margin:14px 0; }
        .sig-done-img { border:1px solid var(--border); border-radius:8px; max-width:100%; background:#FAFAFA; }
    </style>
    <link rel="stylesheet" href="{{ asset('css/passform-public.css') }}">
</head>
<body>

<header class="header public-header formateur-header">
    <div class="header-brand" aria-label="PassForm">
        <img src="{{ asset('images/brcode-logo.jpg') }}" alt="" class="header-logo-img">
        <span class="header-logo">PassForm</span>
    </div>
    <span class="header-sep">|</span>
    <span class="header-info">{{ $session->intitule }}</span>
    <span class="header-role">Espace formateur</span>
</header>

<main class="container public-shell formateur-shell">

    @if(session('success_dj'))
        <div class="alert alert-success">
            Demi-journée certifiée avec succès.
        </div>
    @endif

    <section class="card formateur-summary" aria-label="Session">
        <span class="session-card__label">Session</span>
        <strong>{{ $session->intitule }}</strong>
        @if($session->lieu)
            <span class="session-card__place">{{ $session->lieu }}</span>
        @endif
    </section>

    @forelse($demiJournees as $dj)
        <article class="card dj-card">
            <div class="dj-header">
                <div>
                    <div class="dj-title">
                        DJ{{ $dj->ordre }} —
                        {{ $dj->creneau === 'matin' ? 'Matin' : 'Après-midi' }}
                    </div>
                    <div class="dj-sub">
                        {{ $dj->date->translatedFormat('l d F Y') }}
                        · {{ substr($dj->heure_debut,0,5) }}–{{ substr($dj->heure_fin,0,5) }}
                    </div>
                </div>
                <div>
                    @if($dj->signature_formateur)
                        <span class="badge badge-done">Certifiée</span>
                    @elseif($dj->statut_emargement === 'ouvert')
                        <span class="badge badge-open">En cours</span>
                    @else
                        <span class="badge badge-closed">{{ ucfirst($dj->statut_emargement) }}</span>
                    @endif
                </div>
            </div>

            {{-- Taux de signature participants --}}
            @php
                $total   = $session->participants()->count();
                $signes  = $dj->emargements()->whereNotNull('signature')->count();
                $pct     = $total > 0 ? round($signes / $total * 100) : 0;
            @endphp
            <div class="progress-row">
                <span class="progress-caption">Participants signés</span>
                <div class="progress-bar">
                    <div class="progress-fill" style="--progress: {{ $pct }}%"></div>
                </div>
                <span class="progress-label">{{ $signes }}/{{ $total }}</span>
            </div>

            <div class="sep"></div>

            {{-- Signature formateur déjà faite --}}
            @if($dj->signature_formateur)
                <div class="certification-done">
                    Vous avez certifié cette demi-journée
                </div>
                <div class="certification-date">
                    Le {{ $dj->formateur_signe_at->format('d/m/Y à H\hi') }}
                </div>
                <img src="{{ $dj->signature_formateur }}" class="sig-done-img" alt="Signature formateur">

            {{-- Formulaire de signature --}}
            @else
                <form method="POST"
                    action="{{ route('formateur.signer', ['token' => $session->token_formateur, 'demiJourneeId' => $dj->id]) }}"
                    id="form-dj-{{ $dj->id }}">
                    @csrf

                    <div class="signature-title">
                        Signez pour certifier cette demi-journée
                    </div>
                    <div class="sig-wrap">
                        <canvas id="sig-{{ $dj->id }}" height="120"></canvas>
                        <div class="sig-placeholder" id="placeholder-{{ $dj->id }}">
                            Signez ici avec votre doigt ou la souris
                        </div>
                    </div>
                    <div class="sig-actions">
                        <button type="button" class="btn-clear"
                            onclick="clearSig({{ $dj->id }})">Effacer</button>
                    </div>
                    <input type="hidden" name="signature" id="sig-data-{{ $dj->id }}">

                    <button type="submit" class="btn btn-success"
                        onclick="return prepSig({{ $dj->id }})">
                        Certifier cette demi-journée
                    </button>
                </form>
            @endif
        </article>
    @empty
        <div class="card empty-state">
            Aucune demi-journée configurée pour cette session.
        </div>
    @endforelse

    <div class="footer">
        Lien formateur privé · Ne pas partager · Horodatage et IP journalisés
    </div>
</main>

<script>
const pads = {};

function initPad(id) {
    const canvas = document.getElementById('sig-' + id);
    if (!canvas || pads[id]) return;
    const placeholder = document.getElementById('placeholder-' + id);
    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    const w   = canvas.offsetWidth;
    const h   = parseInt(canvas.getAttribute('height')) || 120;
    canvas.width  = w * dpr;
    canvas.height = h * dpr;
    ctx.scale(dpr, dpr);
    ctx.strokeStyle = '#1A1916';
    ctx.lineWidth   = 2.5;
    ctx.lineCap     = 'round';
    ctx.lineJoin    = 'round';

    let drawing = false;
    let hasSig  = false;

    function getPos(e) {
        const r = canvas.getBoundingClientRect();
        const t = e.touches ? e.touches[0] : e;
        return { x: t.clientX - r.left, y: t.clientY - r.top };
    }
    function start(e) { e.preventDefault(); drawing = true; const p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }
    function move(e)  { e.preventDefault(); if (!drawing) return; const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); if (!hasSig) { hasSig = true; placeholder.style.opacity = '0'; } }
    function stop(e)  { e.preventDefault(); drawing = false; }

    canvas.addEventListener('mousedown',  start, { passive: false });
    canvas.addEventListener('mousemove',  move,  { passive: false });
    canvas.addEventListener('mouseup',    stop,  { passive: false });
    canvas.addEventListener('mouseleave', stop,  { passive: false });
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove',  move,  { passive: false });
    canvas.addEventListener('touchend',   stop,  { passive: false });

    pads[id] = { canvas, ctx, placeholder, hasSig: () => hasSig };
}

function clearSig(id) {
    const p = pads[id];
    if (!p) return;
    p.ctx.clearRect(0, 0, p.canvas.width, p.canvas.height);
    p.placeholder.style.opacity = '1';
}

function prepSig(id) {
    const p = pads[id];
    if (!p) return true;
    const data  = p.canvas.toDataURL('image/png');
    const blank = document.createElement('canvas');
    blank.width  = p.canvas.width;
    blank.height = p.canvas.height;
    if (data === blank.toDataURL('image/png')) {
        alert('Veuillez signer avant de valider.');
        return false;
    }
    document.getElementById('sig-data-' + id).value = data;
    return true;
}

// Initialiser tous les pads présents dans la page
window.addEventListener('DOMContentLoaded', () => {
    @foreach($demiJournees as $dj)
        @if(!$dj->signature_formateur)
            initPad({{ $dj->id }});
        @endif
    @endforeach
});
</script>
</body>
</html>
