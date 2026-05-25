@extends('participant.layout')

@section('content')

<div class="participant-workspace confirmation-workspace">
<div class="workspace-main">

<div class="card confirmation-card">

    <div class="confirmation-card__icon" aria-hidden="true">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </div>

    <div class="confirmation-card__eyebrow">Signature enregistrée</div>
    <h1>Émargement confirmé</h1>
    <p>
        Votre présence a bien été enregistrée pour :<br>
        <strong>{{ $dj->libelle }}</strong>
    </p>

    {{-- Code d'identification --}}
    <div class="code-panel">
        <div class="code-panel__label">Votre code pour les prochaines séances</div>
        <div class="code-panel__value">{{ $participant->code_identification }}</div>
        <div class="code-panel__hint">
            Notez ce code — il vous sera demandé pour signer les prochaines demi-journées.<br>
            En cas d'oubli, votre formateur peut vous le retrouver.
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
            <span>{{ $dj->creneau === 'matin' ? 'Matin' : 'Après-midi' }} · {{ $dj->date->format('d/m/Y') }}</span>
        </div>
        <div class="summary-row">
            <span>Horodatage</span>
            <span>{{ now()->format('d/m/Y à H\hi') }}</span>
        </div>
    </div>

    @if($participant->nir_refuse)
        <div class="alert alert-info text-left mb-16">
            Vous avez choisi de ne pas communiquer votre NIR.
            Si vous souhaitez le compléter ultérieurement, contactez votre formateur.
        </div>
    @endif

    <button onclick="window.print()" class="print-button">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Imprimer / sauvegarder
    </button>

</div>

<div class="rgpd-notice rgpd-notice--compact">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;margin-top:1px"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    Signature enregistrée avec horodatage et adresse IP journalisée — Conforme RGPD
</div>

</div>

<aside class="visual-panel" aria-hidden="true">
    <img src="{{ asset('images/passform-hero.png') }}" alt="">
</aside>
</div>

@endsection

@push('scripts')
<style>
@media print {
    .header, .footer, .print-button { display: none !important; }
    body { background: #fff; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; }
}
</style>
@endpush
