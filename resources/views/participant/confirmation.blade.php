@extends('participant.layout')

@section('content')

<div class="card confirmation-card">

    {{-- Icône succès --}}
    <div class="confirmation-card__icon" aria-hidden="true">✓</div>
    <div class="confirmation-card__eyebrow">Signature enregistrée</div>

    <h1>
        Émargement enregistré
    </h1>
    <p>
        Votre présence a bien été enregistrée pour :<br>
        <strong>{{ $dj->libelle }}</strong>
    </p>

    {{-- Code à 3 chiffres --}}
    <div class="code-panel">
        <div class="code-panel__label">
            Votre code d'identification
        </div>
        <div class="code-panel__value">
            {{ $participant->code_identification }}
        </div>
        <div class="code-panel__hint">
            Notez ce code. Il vous sera demandé pour signer<br>
            les prochaines demi-journées de cette formation.<br>
            En cas d'oubli, le formateur peut vous le retrouver.
        </div>
    </div>

    {{-- Récapitulatif --}}
    <div class="summary-list">
        <div class="summary-row">
            <span>Participant</span>
            <span>{{ $participant->prenom }} {{ strtoupper($participant->nom) }}</span>
        </div>
        <div class="summary-row">
            <span>Formation</span>
            <span>{{ $session->intitule }}</span>
        </div>
        <div class="summary-row">
            <span>Demi-journée</span>
            <span>
                {{ $dj->creneau === 'matin' ? 'Matin' : 'Après-midi' }}
                · {{ $dj->date->format('d/m/Y') }}
            </span>
        </div>
        <div class="summary-row">
            <span>Horodatage</span>
            <span>{{ now()->format('d/m/Y à H\hi') }}</span>
        </div>
    </div>

    {{-- NIR --}}
    @if($participant->nir_refuse)
        <div class="alert alert-info text-left mb-16">
            Vous avez choisi de ne pas communiquer votre NIR.
            Si vous souhaitez le compléter ultérieurement, contactez votre formateur.
        </div>
    @endif

    {{-- Bouton imprimer / capture d'écran --}}
    <button onclick="window.print()" class="print-button">
        Imprimer / sauvegarder ce récapitulatif
    </button>

</div>

<div class="rgpd-notice rgpd-notice--compact">
    Signature enregistrée avec horodatage et adresse IP journalisée — Conforme RGPD
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
