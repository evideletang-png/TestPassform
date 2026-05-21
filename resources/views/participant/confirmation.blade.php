@extends('participant.layout')

@section('content')

<div class="card" style="text-align:center; padding: 32px 24px">

    {{-- Icône succès --}}
    <div style="
        width: 64px; height: 64px;
        background: var(--green-lt);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 16px;
        font-size: 32px;
    ">✓</div>

    <h1 style="font-size:20px; font-weight:600; margin-bottom:6px">
        Émargement enregistré !
    </h1>
    <p style="font-size:14px; color:var(--text-sec); margin-bottom:24px">
        Votre présence a bien été enregistrée pour :<br>
        <strong>{{ $dj->libelle }}</strong>
    </p>

    {{-- Code à 3 chiffres --}}
    <div style="
        background: #EEF5FF;
        border: 2px dashed #C5D9F0;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    ">
        <div style="font-size:12px; font-weight:600; color:var(--blue); text-transform:uppercase; letter-spacing:.08em; margin-bottom:8px">
            Votre code d'identification
        </div>
        <div style="font-size:52px; font-weight:700; letter-spacing:12px; color:var(--blue); line-height:1">
            {{ $participant->code_identification }}
        </div>
        <div style="font-size:12px; color:var(--text-sec); margin-top:10px; line-height:1.5">
            Notez ce code. Il vous sera demandé pour signer<br>
            les prochaines demi-journées de cette formation.<br>
            En cas d'oubli, le formateur peut vous le retrouver.
        </div>
    </div>

    {{-- Récapitulatif --}}
    <div style="
        text-align:left;
        background: var(--gray-lt);
        border-radius: 8px;
        padding: 14px;
        font-size:13px;
        margin-bottom:20px;
    ">
        <div style="display:flex; justify-content:space-between; padding:4px 0; border-bottom:1px solid var(--border)">
            <span style="color:var(--text-sec)">Participant</span>
            <span style="font-weight:500">{{ $participant->prenom }} {{ strtoupper($participant->nom) }}</span>
        </div>
        <div style="display:flex; justify-content:space-between; padding:4px 0; border-bottom:1px solid var(--border)">
            <span style="color:var(--text-sec)">Formation</span>
            <span style="font-weight:500">{{ $session->intitule }}</span>
        </div>
        <div style="display:flex; justify-content:space-between; padding:4px 0; border-bottom:1px solid var(--border)">
            <span style="color:var(--text-sec)">Demi-journée</span>
            <span style="font-weight:500">
                {{ $dj->creneau === 'matin' ? 'Matin' : 'Après-midi' }}
                · {{ $dj->date->format('d/m/Y') }}
            </span>
        </div>
        <div style="display:flex; justify-content:space-between; padding:4px 0">
            <span style="color:var(--text-sec)">Horodatage</span>
            <span style="font-weight:500">{{ now()->format('d/m/Y à H\hi') }}</span>
        </div>
    </div>

    {{-- NIR --}}
    @if($participant->nir_refuse)
        <div class="alert alert-info" style="text-align:left; margin-bottom:16px">
            ℹ️ Vous avez choisi de ne pas communiquer votre NIR.
            Si vous souhaitez le compléter ultérieurement, contactez votre formateur.
        </div>
    @endif

    {{-- Bouton imprimer / capture d'écran --}}
    <button onclick="window.print()" style="
        background: none;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 10px 20px;
        font-size: 13px;
        color: var(--text-sec);
        cursor: pointer;
        margin-top: 4px;
    ">
        🖨 Imprimer / sauvegarder ce récapitulatif
    </button>

</div>

<div class="rgpd-notice" style="margin-top:0">
    🔒 Signature enregistrée avec horodatage et adresse IP journalisée — Conforme RGPD
</div>

@endsection

@push('scripts')
<style>
@media print {
    .header, .footer, button { display: none !important; }
    body { background: #fff; }
    .card { box-shadow: none; border: 1px solid #ccc; }
}
</style>
@endpush
