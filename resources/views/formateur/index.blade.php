<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PassForm — Espace formateur · {{ $session->intitule }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/passform-public.css') }}?v={{ filemtime(public_path('css/passform-public.css')) }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-size: 15px; min-height: 100vh; }
        .header { display: flex; align-items: center; gap: 12px; padding: 14px 20px; }
    </style>
</head>
<body>

<header class="header public-header">
    <div class="header-brand" aria-label="PassForm">
        <img src="{{ asset('images/brcode-logo.jpg') }}" alt="" class="header-logo-img">
        <span class="header-logo">PassForm</span>
    </div>
    <span class="header-sep">|</span>
    <span class="header-info">{{ $session->intitule }}</span>
    <span class="header-badge header-role">Espace formateur</span>
</header>

<main class="container public-shell formateur-shell">
<div class="dark-shell">

    @if(session('success_dj'))
        <div class="alert alert-success" role="status">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
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
                        · {{ substr($dj->heure_debut, 0, 5) }}–{{ substr($dj->heure_fin, 0, 5) }}
                    </div>
                </div>
                <div>
                    @if($dj->signature_formateur)
                        <span class="badge badge-done">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            Certifiée
                        </span>
                    @elseif($dj->statut_emargement === 'ouvert')
                        <span class="badge badge-open">En cours</span>
                    @else
                        <span class="badge badge-closed">{{ ucfirst($dj->statut_emargement) }}</span>
                    @endif
                </div>
            </div>

            {{-- Taux de signature participants --}}
            @php
                $total  = $session->participants()->count();
                $signes = $dj->emargements()->whereNotNull('signature')->count();
                $pct    = $total > 0 ? round($signes / $total * 100) : 0;
            @endphp
            <div class="progress-row">
                <span class="progress-caption">Participants signés</span>
                <div class="progress-bar" role="progressbar" aria-valuenow="{{ $signes }}" aria-valuemin="0" aria-valuemax="{{ $total }}" aria-label="{{ $signes }} sur {{ $total }} participants signés">
                    <div class="progress-fill" style="--progress: {{ $pct }}%"></div>
                </div>
                <span class="progress-label">{{ $signes }}/{{ $total }}</span>
            </div>

            <div class="sep"></div>

            @if($dj->signature_formateur)
                <div class="certification-done">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:inline-block;vertical-align:middle;margin-right:4px"><polyline points="20 6 9 17 4 12"/></svg>
                    Vous avez certifié cette demi-journée
                </div>
                <div class="certification-date">Le {{ $dj->formateur_signe_at->format('d/m/Y à H\hi') }}</div>
                <img src="{{ $dj->signature_formateur }}" class="sig-done-img" alt="Votre signature pour DJ{{ $dj->ordre }}">

            @else
                <form method="POST"
                    action="{{ route('formateur.signer', ['token' => $session->token_formateur, 'demiJourneeId' => $dj->id]) }}"
                    id="form-dj-{{ $dj->id }}"
                    novalidate>
                    @csrf

                    <div class="signature-title">Signez pour certifier cette demi-journée</div>

                    <div class="sig-wrap" id="sig-wrap-{{ $dj->id }}">
                        <canvas id="sig-{{ $dj->id }}" height="140"></canvas>
                        <div class="sig-placeholder" id="placeholder-{{ $dj->id }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg>
                            Signez ici avec votre doigt ou la souris
                        </div>
                        <div class="sig-error" id="sig-error-{{ $dj->id }}" role="alert" aria-live="polite" hidden></div>
                    </div>
                    <div class="sig-actions">
                        <button type="button" class="btn-clear" onclick="clearSig({{ $dj->id }})" aria-label="Effacer la signature">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            Effacer
                        </button>
                    </div>
                    <input type="hidden" name="signature" id="sig-data-{{ $dj->id }}">

                    <button type="submit" class="btn btn-success submit-btn" id="btn-certifier-{{ $dj->id }}" style="margin-top:12px">
                        <span class="btn-label">Certifier cette demi-journée</span>
                        <span class="btn-spinner" aria-hidden="true" hidden></span>
                    </button>
                </form>
            @endif
        </article>
    @empty
        <div class="card empty-state">
            <div class="empty-state__icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="empty-state__title">Aucune demi-journée configurée</div>
            <div class="empty-state__copy">Cette session ne contient pas encore de demi-journées. Configurez-les depuis le back-office.</div>
        </div>
    @endforelse

    <div class="footer">
        Lien formateur privé · Ne pas partager · Horodatage et IP journalisés
    </div>

</div>{{-- /.dark-shell --}}
</main>

<script>
const pads = {};

function initPad(id) {
    const canvas = document.getElementById('sig-' + id);
    if (!canvas || pads[id]) return;
    const placeholder = document.getElementById('placeholder-' + id);
    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    canvas.width  = canvas.offsetWidth * dpr;
    canvas.height = (parseInt(canvas.getAttribute('height')) || 140) * dpr;
    ctx.scale(dpr, dpr);
    ctx.strokeStyle = canvas.closest('.dark-shell') ? 'rgba(255,255,255,.88)' : '#172033';
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
    function start(e) {
        e.preventDefault();
        drawing = true;
        const p = getPos(e);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
    }
    function move(e) {
        e.preventDefault();
        if (!drawing) return;
        const p = getPos(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        if (!hasSig) {
            hasSig = true;
            placeholder.style.opacity = '0';
            const errEl = document.getElementById('sig-error-' + id);
            if (errEl) errEl.hidden = true;
            document.getElementById('sig-wrap-' + id)?.classList.remove('sig-wrap--error');
        }
    }
    function stop(e) { e.preventDefault(); drawing = false; }

    canvas.addEventListener('mousedown',  start, { passive: false });
    canvas.addEventListener('mousemove',  move,  { passive: false });
    canvas.addEventListener('mouseup',    stop,  { passive: false });
    canvas.addEventListener('mouseleave', stop,  { passive: false });
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove',  move,  { passive: false });
    canvas.addEventListener('touchend',   stop,  { passive: false });

    pads[id] = { canvas, ctx, placeholder, isBlank: () => !hasSig };
}

function clearSig(id) {
    const p = pads[id];
    if (!p) return;
    p.ctx.clearRect(0, 0, p.canvas.width, p.canvas.height);
    p.placeholder.style.opacity = '1';
    delete pads[id];
    initPad(id);
}

document.querySelectorAll('[id^="form-dj-"]').forEach(form => {
    form.addEventListener('submit', function (e) {
        const id = this.id.replace('form-dj-', '');
        const pad = pads[id];

        if (!pad || pad.isBlank()) {
            e.preventDefault();
            const errEl = document.getElementById('sig-error-' + id);
            if (errEl) {
                errEl.textContent = 'Veuillez signer dans la zone ci-dessus.';
                errEl.hidden = false;
            }
            document.getElementById('sig-wrap-' + id)?.classList.add('sig-wrap--error');
            return;
        }

        document.getElementById('sig-data-' + id).value = pad.canvas.toDataURL('image/png');

        const btn    = document.getElementById('btn-certifier-' + id);
        const label  = btn?.querySelector('.btn-label');
        const spinner = btn?.querySelector('.btn-spinner');
        if (btn)    btn.disabled   = true;
        if (label)  label.hidden   = true;
        if (spinner) spinner.hidden = false;
    });
});

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
