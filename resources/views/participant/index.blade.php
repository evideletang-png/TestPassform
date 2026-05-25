@extends('participant.layout')

@section('content')

<div class="participant-workspace">
<div class="workspace-main">

{{-- ── Infos session ── --}}
<section class="session-info session-card" aria-label="Session">
    <div class="session-card__main">
        <span class="session-card__label">Session</span>
        <strong>{{ $session->intitule }}</strong>
        @if($session->lieu)
            <span class="session-card__place">{{ $session->lieu }}</span>
        @endif
    </div>

    @if($djEnCours)
        <div class="session-card__slot">
            <span class="session-card__dot pulsing" aria-hidden="true"></span>
            <div>
                <span class="session-card__slot-label">Émargement ouvert</span>
                <span class="dj-badge">
                    DJ{{ $djEnCours->ordre }} ·
                    {{ $djEnCours->creneau === 'matin' ? 'Matin' : 'Après-midi' }} ·
                    {{ $djEnCours->date->translatedFormat('l d F Y') }} ·
                    {{ substr($djEnCours->heure_debut, 0, 5) }}–{{ substr($djEnCours->heure_fin, 0, 5) }}
                </span>
            </div>
        </div>
    @else
        <div class="session-card__slot session-card__slot--waiting">
            <span class="session-card__dot" aria-hidden="true"></span>
            <div>Aucun émargement ouvert actuellement.</div>
        </div>
    @endif
</section>

{{-- ── Erreurs globales ── --}}
@if($errors->has('global'))
    <div class="alert alert-error" role="alert">{{ $errors->first('global') }}</div>
@endif

@if(!$djEnCours)
    <div class="card empty-state">
        <div class="empty-state__icon" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="empty-state__title">Aucun émargement ouvert</div>
        <div class="empty-state__copy">
            Le formateur n'a pas encore ouvert l'émargement pour la demi-journée en cours.<br>
            Actualisez cette page dans quelques instants.
        </div>
        <button class="btn btn-outline refresh-btn" onclick="window.location.reload()">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.96"/></svg>
            Actualiser
        </button>
    </div>
@else

{{-- ── Onglets ── --}}
<div class="tabs" role="tablist" aria-label="Méthode d'émargement">
    <button class="tab {{ !$errors->any() || $errors->has('code') || $errors->has('signature') && old('code') ? 'tab--active' : '' }}"
        role="tab"
        id="tab-code"
        aria-controls="panel-code"
        aria-selected="{{ !$errors->any() || $errors->has('code') || ($errors->has('signature') && old('code')) ? 'true' : 'false' }}"
        onclick="switchTab('code')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 15h0M12 15h0M17 15h0"/></svg>
        J'ai mon code
    </button>
    <button class="tab {{ $errors->any() && !$errors->has('code') && !($errors->has('signature') && old('code')) ? 'tab--active' : '' }}"
        role="tab"
        id="tab-inscription"
        aria-controls="panel-inscription"
        aria-selected="{{ $errors->any() && !$errors->has('code') && !($errors->has('signature') && old('code')) ? 'true' : 'false' }}"
        onclick="switchTab('inscription')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Première fois
    </button>
</div>

{{-- ══════════════════════════════════════════════════════════════
     PANNEAU 1 : Code (participants déjà inscrits)
══════════════════════════════════════════════════════════════ --}}
<div class="tab-panel flow-card flow-card--code" id="panel-code" role="tabpanel" aria-labelledby="tab-code">

    <div class="card-heading">
        <span class="card-kicker">Déjà inscrit</span>
        <h1 class="card-title">Signez avec votre code</h1>
    </div>

    @if($errors->has('code'))
        <div class="alert alert-error" role="alert">{{ $errors->first('code') }}</div>
    @endif

    <form method="POST" action="{{ route('participant.signer', $session->token_participant) }}" id="form-code" novalidate>
        @csrf

        <div class="form-group">
            <label for="code-field">Code à 3 chiffres</label>
            <div class="digit-input-wrap" aria-describedby="code-hint">
                <input
                    type="text"
                    name="code"
                    id="code-field"
                    minlength="3"
                    maxlength="3"
                    placeholder="•••"
                    value="{{ old('code') }}"
                    autocomplete="off"
                    inputmode="numeric"
                    pattern="[0-9]{3}"
                    class="digit-input {{ $errors->has('code') ? 'error' : '' }}"
                    aria-label="Code à 3 chiffres"
                >
            </div>
            <div class="code-hint" id="code-hint" role="status" aria-live="polite"></div>
        </div>

        {{-- Zone de signature — affichée après validation du code ── --}}
        <div id="zone-signature-code" class="signature-zone" hidden>
            <div class="sep"></div>
            <div class="form-group">
                <label>Signez votre émargement <span class="req" aria-hidden="true">*</span></label>
                <div class="sig-wrap" id="sig-wrap-code">
                    <canvas id="sig-canvas-code" height="160"></canvas>
                    <div class="sig-placeholder" id="sig-placeholder-code">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg>
                        Signez ici avec votre doigt ou la souris
                    </div>
                    <div class="sig-error" id="sig-error-code" role="alert" aria-live="polite" hidden></div>
                </div>
                <div class="sig-actions">
                    <button type="button" class="btn-clear" onclick="clearSig('sig-canvas-code', 'sig-placeholder-code')" aria-label="Effacer la signature">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        Effacer
                    </button>
                </div>
                <input type="hidden" name="signature" id="sig-data-code">
                @if($errors->has('signature') && old('code'))
                    <div class="field-error" role="alert">{{ $errors->first('signature') }}</div>
                @endif
            </div>
            <button type="submit" class="btn btn-success submit-btn" id="btn-signer-code">
                <span class="btn-label">Valider mon émargement</span>
                <span class="btn-spinner" aria-hidden="true" hidden></span>
            </button>
        </div>

    </form>
</div>

{{-- ══════════════════════════════════════════════════════════════
     PANNEAU 2 : Première inscription
══════════════════════════════════════════════════════════════ --}}
<div class="tab-panel flow-card" id="panel-inscription" role="tabpanel" aria-labelledby="tab-inscription">

    <div class="card-heading">
        <span class="card-kicker">Nouveau participant</span>
        <h2 class="card-title">Créer votre inscription</h2>
    </div>

    @if($errors->any() && !$errors->has('code') && !($errors->has('signature') && old('code')) && !$errors->has('global'))
        <div class="alert alert-error" role="alert">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('participant.inscrire', $session->token_participant) }}" id="form-inscription" novalidate>
        @csrf

        <div class="form-row">
            <div class="form-group">
                <label for="prenom">Prénom <span class="req" aria-hidden="true">*</span></label>
                <input type="text" name="prenom" id="prenom"
                    value="{{ old('prenom') }}"
                    autocomplete="given-name"
                    class="{{ $errors->has('prenom') ? 'error' : '' }}"
                    placeholder="Votre prénom">
                @error('prenom')<div class="field-error" role="alert">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="nom">Nom de famille <span class="req" aria-hidden="true">*</span></label>
                <input type="text" name="nom" id="nom"
                    value="{{ old('nom') }}"
                    autocomplete="family-name"
                    class="{{ $errors->has('nom') ? 'error' : '' }}"
                    placeholder="Votre nom">
                @error('nom')<div class="field-error" role="alert">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-group">
            <label for="nom_naissance">Nom de naissance <span class="field-optional">(facultatif)</span></label>
            <input type="text" name="nom_naissance" id="nom_naissance"
                value="{{ old('nom_naissance') }}"
                placeholder="Si différent du nom de famille">
        </div>

        {{-- NIR ── --}}
        <div class="form-group" id="nir-group">
            <label for="nir-field">
                Numéro de Sécurité Sociale (NIR)
                <span class="req" id="nir-req" aria-hidden="true">*</span>
            </label>
            <input type="text" name="nir" id="nir-field"
                value="{{ old('nir') }}"
                maxlength="13"
                inputmode="numeric"
                placeholder="13 chiffres"
                class="{{ $errors->has('nir') ? 'error' : '' }}"
                oninput="this.value=this.value.replace(/\D/g,'')">
            <div class="field-help">13 chiffres, sans espaces ni clé.</div>
            @error('nir')<div class="field-error" role="alert">{{ $message }}</div>@enderror
        </div>

        <label class="toggle-row" for="nir-refuse">
            <span class="toggle-wrap">
                <input type="checkbox" name="nir_refuse" id="nir-refuse"
                    onchange="toggleNir(this)"
                    {{ old('nir_refuse') ? 'checked' : '' }}>
                <span class="toggle-slider"></span>
            </span>
            <span class="toggle-label">Je ne souhaite pas communiquer mon numéro de sécurité sociale</span>
        </label>

        {{-- Signature ── --}}
        <div class="sep"></div>
        <div class="form-group">
            <label>Signature <span class="req" aria-hidden="true">*</span></label>
            <div class="sig-wrap" id="sig-wrap-insc">
                <canvas id="sig-canvas-insc" height="160"></canvas>
                <div class="sig-placeholder" id="sig-placeholder-insc">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg>
                    Signez ici avec votre doigt ou la souris
                </div>
                <div class="sig-error" id="sig-error-insc" role="alert" aria-live="polite" hidden></div>
            </div>
            <div class="sig-actions">
                <button type="button" class="btn-clear" onclick="clearSig('sig-canvas-insc','sig-placeholder-insc')" aria-label="Effacer la signature">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    Effacer
                </button>
            </div>
            <input type="hidden" name="signature" id="sig-data-insc">
            @if($errors->has('signature') && !old('code'))
                <div class="field-error" role="alert">{{ $errors->first('signature') }}</div>
            @endif
        </div>

        {{-- Notice RGPD ── --}}
        <div class="rgpd-notice">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;margin-top:1px"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Vos données sont chiffrées et traitées conformément au RGPD.
            Le NIR est collecté dans le cadre du Passeport de Prévention pour déclaration à la Caisse des Dépôts.
            Il sera supprimé automatiquement après traitement.
        </div>

        <div class="submit-row">
            <button type="submit" class="btn btn-success submit-btn" id="btn-valider-insc">
                <span class="btn-label">Valider mon inscription et signer</span>
                <span class="btn-spinner" aria-hidden="true" hidden></span>
            </button>
        </div>

    </form>

    <a href="{{ route('participant.session', $session->token_participant) }}?retard=1" class="retard-link">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Vous n'étiez pas présent à la 1ère séance ?
    </a>
</div>

@endif {{-- fin @if($djEnCours) --}}

</div>

<aside class="visual-panel" aria-hidden="true">
    <img src="{{ asset('images/passform-hero.png') }}" alt="">
</aside>
</div>
@endsection

@push('scripts')
<script>
// ── Onglets ──────────────────────────────────────────────────────────────────
function switchTab(name) {
    const panels = document.querySelectorAll('.tab-panel');
    const tabs   = document.querySelectorAll('.tab');
    panels.forEach(p => {
        const isTarget = p.id === 'panel-' + name;
        p.hidden = !isTarget;
        p.setAttribute('aria-hidden', isTarget ? 'false' : 'true');
    });
    tabs.forEach(t => {
        const isTarget = t.id === 'tab-' + name;
        t.classList.toggle('tab--active', isTarget);
        t.setAttribute('aria-selected', isTarget ? 'true' : 'false');
    });
    // Re-init signature pad si nécessaire
    if (name === 'inscription') initPad('sig-canvas-insc', 'sig-placeholder-insc');
}

// ── Pad de signature ──────────────────────────────────────────────────────────
const _pads = {};

function initPad(canvasId, placeholderId) {
    if (_pads[canvasId]) return;
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const placeholder = document.getElementById(placeholderId);
    const ctx = canvas.getContext('2d');

    const dpr  = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    canvas.width  = (rect.width  || canvas.offsetWidth)  * dpr;
    canvas.height = (parseInt(canvas.getAttribute('height')) || 160) * dpr;
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
            const errEl = document.getElementById(canvasId.replace('sig-canvas-', 'sig-error-'));
            if (errEl) errEl.hidden = true;
        }
    }
    function end(e) { e.preventDefault(); drawing = false; }

    canvas.addEventListener('mousedown',  start, { passive: false });
    canvas.addEventListener('mousemove',  move,  { passive: false });
    canvas.addEventListener('mouseup',    end,   { passive: false });
    canvas.addEventListener('mouseleave', end,   { passive: false });
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove',  move,  { passive: false });
    canvas.addEventListener('touchend',   end,   { passive: false });

    _pads[canvasId] = { canvas, ctx, placeholder, isBlank: () => !hasSig };
}

function clearSig(canvasId, placeholderId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    document.getElementById(placeholderId).style.opacity = '1';
    if (_pads[canvasId]) {
        delete _pads[canvasId];
        initPad(canvasId, placeholderId);
    }
}

function prepSig(canvasId, hiddenId, errorId) {
    const pad = _pads[canvasId];
    if (!pad || pad.isBlank()) {
        const errEl = document.getElementById(errorId);
        if (errEl) {
            errEl.textContent = 'Veuillez signer dans la zone ci-dessus.';
            errEl.hidden = false;
        }
        document.getElementById(canvasId)?.parentElement?.classList.add('sig-wrap--error');
        return false;
    }
    document.getElementById(hiddenId).value = document.getElementById(canvasId).toDataURL('image/png');
    return true;
}

// ── Chargement du bouton ──────────────────────────────────────────────────────
function setLoading(btnId, loading) {
    const btn    = document.getElementById(btnId);
    if (!btn) return;
    const label  = btn.querySelector('.btn-label');
    const spinner = btn.querySelector('.btn-spinner');
    btn.disabled = loading;
    if (label)  label.hidden  = loading;
    if (spinner) spinner.hidden = !loading;
}

// ── Toggle NIR ────────────────────────────────────────────────────────────────
function toggleNir(checkbox) {
    const field = document.getElementById('nir-field');
    const req   = document.getElementById('nir-req');
    field.disabled = checkbox.checked;
    field.value    = checkbox.checked ? '' : field.value;
    if (req) req.style.visibility = checkbox.checked ? 'hidden' : '';
}

// ── Vérification AJAX du code ─────────────────────────────────────────────────
let codeVerifTimeout;

document.getElementById('code-field')?.addEventListener('input', function () {
    clearTimeout(codeVerifTimeout);
    const hint = document.getElementById('code-hint');
    const val  = this.value;

    if (val.length !== 3) {
        hint.textContent = '';
        hint.className   = 'code-hint';
        document.getElementById('zone-signature-code').hidden = true;
        return;
    }

    hint.innerHTML = '<span class="code-hint__spinner"></span> Vérification…';
    hint.className = 'code-hint';

    codeVerifTimeout = setTimeout(async () => {
        try {
            const res  = await fetch("{{ route('participant.verifier_code', $session->token_participant) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({ code: val }),
            });
            const json = await res.json();
            if (json.valide) {
                hint.innerHTML = '✓ Bonjour <strong>' + json.nom + '</strong>';
                hint.className = 'code-hint valid';
                const zone = document.getElementById('zone-signature-code');
                zone.hidden = false;
                zone.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                initPad('sig-canvas-code', 'sig-placeholder-code');
            } else {
                hint.textContent = '✗ Code non reconnu — vérifiez votre code ou passez en "Première fois"';
                hint.className   = 'code-hint invalid';
                document.getElementById('zone-signature-code').hidden = true;
            }
        } catch {
            hint.textContent = '';
        }
    }, 400);
});

// ── Soumission formulaire code ───────────────────────────────────────────────
document.getElementById('form-code')?.addEventListener('submit', function (e) {
    if (!prepSig('sig-canvas-code', 'sig-data-code', 'sig-error-code')) {
        e.preventDefault();
        return;
    }
    setLoading('btn-signer-code', true);
});

// ── Soumission formulaire inscription ────────────────────────────────────────
document.getElementById('form-inscription')?.addEventListener('submit', function (e) {
    if (!prepSig('sig-canvas-insc', 'sig-data-insc', 'sig-error-insc')) {
        e.preventDefault();
        return;
    }
    setLoading('btn-valider-insc', true);
});

// ── Init au chargement ────────────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
    // Déterminer quel panneau afficher par défaut
    @if($errors->any() && !$errors->has('code') && !($errors->has('signature') && old('code')))
        switchTab('inscription');
    @else
        switchTab('code');
    @endif

    // Si erreur de signature sur le formulaire code, ré-ouvrir la zone
    @if(old('code') && $errors->has('signature'))
        document.getElementById('zone-signature-code').hidden = false;
        initPad('sig-canvas-code', 'sig-placeholder-code');
    @endif

    // Scroll vers le formulaire en erreur
    @if($errors->any())
        const errEl = document.querySelector('.alert-error, .field-error');
        if (errEl) errEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    @endif

    // Focus automatique sur le champ code
    @if(!$errors->any() || $errors->has('code'))
        document.getElementById('code-field')?.focus();
    @endif
});
</script>
@endpush
