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
            <span class="session-card__dot" aria-hidden="true"></span>
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
    <div class="alert alert-error">{{ $errors->first('global') }}</div>
@endif

@if(!$djEnCours)
    <div class="card empty-state">
        <div class="empty-state__icon" aria-hidden="true">i</div>
        <div class="empty-state__title">Aucun émargement ouvert</div>
        <div class="empty-state__copy">
            Le formateur n'a pas encore ouvert l'émargement pour la demi-journée en cours.<br>
            Actualisez cette page dans quelques instants.
        </div>
    </div>
@else

{{-- ══════════════════════════════════════════════════════════════
     BLOC 1 : Identification via code (participants déjà inscrits)
══════════════════════════════════════════════════════════════ --}}
<div class="card flow-card flow-card--code" id="bloc-code">
    <div class="card-heading">
        <span class="card-kicker">Déjà inscrit</span>
        <h1 class="card-title">Signez avec votre code</h1>
    </div>

    @if($errors->has('code'))
        <div class="alert alert-error">{{ $errors->first('code') }}</div>
    @endif

    <form method="POST" action="{{ route('participant.signer', $session->token_participant) }}" id="form-code">
        @csrf

        <div class="form-group">
            <label for="code-field">Code à 3 chiffres</label>
            <div class="code-input-wrap">
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
                    oninput="this.value=this.value.replace(/\D/g,'').slice(0,3)"
                    class="{{ $errors->has('code') ? 'error' : '' }}"
                >
                <button type="button" class="btn btn-primary" id="btn-valider-code" onclick="validerCode()">
                    Continuer
                </button>
            </div>
            <div class="code-hint" id="code-hint"></div>
        </div>

        {{-- Pad de signature — affiché après validation du code ── --}}
        <div id="zone-signature-code" class="signature-zone" hidden>
            <div class="sep"></div>
            <div class="form-group">
                <label>Signez votre émargement <span class="req">*</span></label>
                <div class="sig-wrap">
                    <canvas id="sig-canvas-code" height="130"></canvas>
                    <div class="sig-placeholder" id="sig-placeholder-code">Signez ici avec votre doigt ou la souris</div>
                </div>
                <div class="sig-actions">
                    <button type="button" class="btn-clear" onclick="clearSig('sig-canvas-code', 'sig-placeholder-code')">Effacer</button>
                </div>
                <input type="hidden" name="signature" id="sig-data-code">
                @if($errors->has('signature'))
                    <div class="field-error">{{ $errors->first('signature') }}</div>
                @endif
            </div>
            <button type="submit" class="btn btn-success" onclick="return prepSig('sig-canvas-code','sig-data-code')">
                Valider mon émargement
            </button>
        </div>

    </form>
</div>

{{-- Séparateur OU ── --}}
<div class="or-sep">Première présence</div>

{{-- ══════════════════════════════════════════════════════════════
     BLOC 2 : Première inscription
══════════════════════════════════════════════════════════════ --}}
<div class="card flow-card" id="bloc-inscription">
    <div class="card-heading">
        <span class="card-kicker">Nouveau participant</span>
        <h2 class="card-title">Créer votre inscription</h2>
    </div>

    @if($errors->any() && !$errors->has('code') && !$errors->has('global'))
        <div class="alert alert-error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('participant.inscrire', $session->token_participant) }}" id="form-inscription">
        @csrf

        <div class="form-group">
            <label for="prenom">Prénom <span class="req">*</span></label>
            <input type="text" name="prenom" id="prenom"
                value="{{ old('prenom') }}"
                autocomplete="given-name"
                class="{{ $errors->has('prenom') ? 'error' : '' }}"
                placeholder="Votre prénom">
            @error('prenom')<div class="field-error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="nom">Nom de famille <span class="req">*</span></label>
            <input type="text" name="nom" id="nom"
                value="{{ old('nom') }}"
                autocomplete="family-name"
                class="{{ $errors->has('nom') ? 'error' : '' }}"
                placeholder="Votre nom">
            @error('nom')<div class="field-error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="nom_naissance">
                Nom de naissance
            </label>
            <input type="text" name="nom_naissance" id="nom_naissance"
                value="{{ old('nom_naissance') }}"
                placeholder="Facultatif">
            <div class="field-help">À renseigner uniquement s'il diffère du nom de famille.</div>
        </div>

        {{-- NIR ── --}}
        <div class="form-group" id="nir-group">
            <label for="nir">
                Numéro de Sécurité Sociale (NIR)
                <span class="req" id="nir-req">*</span>
            </label>
            <input type="text" name="nir" id="nir-field"
                value="{{ old('nir') }}"
                maxlength="13"
                inputmode="numeric"
                placeholder="13 chiffres"
                class="{{ $errors->has('nir') ? 'error' : '' }}"
                oninput="this.value=this.value.replace(/\D/g,'')">
            <div class="field-help">Format attendu : 13 chiffres, sans espaces.</div>
            @error('nir')<div class="field-error">{{ $message }}</div>@enderror
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
            <label>Signature de l'émargement <span class="req">*</span></label>
            <div class="sig-wrap">
                <canvas id="sig-canvas-insc" height="130"></canvas>
                <div class="sig-placeholder" id="sig-placeholder-insc">Signez ici avec votre doigt ou la souris</div>
            </div>
            <div class="sig-actions">
                <button type="button" class="btn-clear" onclick="clearSig('sig-canvas-insc','sig-placeholder-insc')">Effacer</button>
            </div>
            <input type="hidden" name="signature" id="sig-data-insc">
            @if($errors->has('signature'))
                <div class="field-error">{{ $errors->first('signature') }}</div>
            @endif
        </div>

        {{-- Notice RGPD ── --}}
        <div class="rgpd-notice">
            Vos données sont chiffrées et traitées conformément au RGPD.
            Le NIR est collecté dans le cadre du Passeport de Prévention pour déclaration à la Caisse des Dépôts.
            Il sera supprimé automatiquement après traitement.
        </div>

        <div class="submit-row">
            <button type="submit" class="btn btn-success"
                onclick="return prepSig('sig-canvas-insc','sig-data-insc')">
                Valider mon inscription et signer
            </button>
        </div>

    </form>

    {{-- Lien retardataire ── --}}
    <a href="{{ route('participant.session', $session->token_participant) }}?retard=1" class="retard-link">
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
// ── Pad de signature ──────────────────────────────────────────────────────────
function initPad(canvasId, placeholderId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const placeholder = document.getElementById(placeholderId);
    const ctx = canvas.getContext('2d');

    // Ajuster la résolution pour les écrans Retina
    const dpr = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    canvas.width  = (rect.width  || canvas.offsetWidth)  * dpr;
    canvas.height = (parseInt(canvas.getAttribute('height')) || 130) * dpr;
    ctx.scale(dpr, dpr);

    ctx.strokeStyle = '#1A1916';
    ctx.lineWidth   = 2.5;
    ctx.lineCap     = 'round';
    ctx.lineJoin    = 'round';

    let drawing = false;
    let hasSig  = false;
    let lastX, lastY;

    function getPos(e) {
        const r = canvas.getBoundingClientRect();
        const t = e.touches ? e.touches[0] : e;
        return {
            x: (t.clientX - r.left),
            y: (t.clientY - r.top),
        };
    }

    function start(e) {
        e.preventDefault();
        drawing = true;
        const p = getPos(e);
        lastX = p.x; lastY = p.y;
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
    }
    function move(e) {
        e.preventDefault();
        if (!drawing) return;
        const p = getPos(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        lastX = p.x; lastY = p.y;
        if (!hasSig) { hasSig = true; placeholder.style.opacity = '0'; }
    }
    function end(e) { e.preventDefault(); drawing = false; }

    canvas.addEventListener('mousedown',  start, { passive: false });
    canvas.addEventListener('mousemove',  move,  { passive: false });
    canvas.addEventListener('mouseup',    end,   { passive: false });
    canvas.addEventListener('mouseleave', end,   { passive: false });
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove',  move,  { passive: false });
    canvas.addEventListener('touchend',   end,   { passive: false });
}

function clearSig(canvasId, placeholderId) {
    const canvas = document.getElementById(canvasId);
    const ctx    = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    document.getElementById(placeholderId).style.opacity = '1';
}

function prepSig(canvasId, hiddenId) {
    const canvas = document.getElementById(canvasId);
    const data   = canvas.toDataURL('image/png');
    // Vérifier que la signature n'est pas vide (canvas tout blanc)
    const blank  = document.createElement('canvas');
    blank.width  = canvas.width;
    blank.height = canvas.height;
    if (data === blank.toDataURL('image/png')) {
        alert('Veuillez signer avant de valider.');
        return false;
    }
    document.getElementById(hiddenId).value = data;
    return true;
}

// ── Toggle NIR ────────────────────────────────────────────────────────────────
function toggleNir(checkbox) {
    const field = document.getElementById('nir-field');
    const req   = document.getElementById('nir-req');
    field.disabled = checkbox.checked;
    field.value    = checkbox.checked ? '' : field.value;
    if (req) req.style.display = checkbox.checked ? 'none' : '';
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

    hint.textContent = 'Vérification…';
    hint.className   = 'code-hint';

    codeVerifTimeout = setTimeout(async () => {
        try {
            const res = await fetch("{{ route('participant.verifier_code', $session->token_participant) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({ code: val }),
            });
            const json = await res.json();
            if (json.valide) {
                hint.textContent = '✓ Bonjour ' + json.nom;
                hint.className   = 'code-hint valid';
                document.getElementById('zone-signature-code').hidden = false;
                initPad('sig-canvas-code', 'sig-placeholder-code');
            } else {
                hint.textContent = '✗ Code non reconnu';
                hint.className   = 'code-hint invalid';
                document.getElementById('zone-signature-code').hidden = true;
            }
        } catch (e) {
            hint.textContent = '';
        }
    }, 400);
});

function validerCode() {
    const val  = document.getElementById('code-field').value;
    const hint = document.getElementById('code-hint');
    if (val.length !== 3) {
        hint.textContent = 'Entrez votre code à 3 chiffres.';
        hint.className   = 'code-hint invalid';
    }
}

// ── Init pads au chargement ───────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
    initPad('sig-canvas-insc', 'sig-placeholder-insc');

    // Si erreurs de validation sur le formulaire code, ré-ouvrir la zone
    @if(old('code') && $errors->has('signature'))
        document.getElementById('zone-signature-code').hidden = false;
        initPad('sig-canvas-code', 'sig-placeholder-code');
    @endif

    // Scroll vers le formulaire en erreur
    @if($errors->any())
        const errEl = document.querySelector('.alert-error, .field-error');
        if (errEl) errEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    @endif
});
</script>
@endpush
