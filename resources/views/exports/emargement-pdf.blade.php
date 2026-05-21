<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 9px;
        color: #1A1916;
    }

    /* ── En-tête ── */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 2px solid #185FA5;
    }
    .header-left h1 { font-size: 14px; color: #185FA5; font-weight: 700; }
    .header-left p  { font-size: 9px; color: #5F5E5A; margin-top: 2px; }
    .header-right   { text-align: right; font-size: 9px; color: #5F5E5A; }
    .header-right strong { color: #1A1916; font-size: 10px; }

    /* ── Métadonnées session ── */
    .meta-grid {
        display: table;
        width: 100%;
        margin-bottom: 12px;
        background: #F5F4F0;
        padding: 8px 10px;
        border-radius: 4px;
    }
    .meta-row { display: table-row; }
    .meta-lbl { display: table-cell; font-weight: 700; color: #5F5E5A; padding-right: 8px; padding-bottom: 3px; width: 140px; }
    .meta-val { display: table-cell; padding-bottom: 3px; }

    /* ── Tableau des émargements ── */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
    }
    th {
        background: #185FA5;
        color: #fff;
        padding: 5px 6px;
        text-align: center;
        font-size: 8px;
        font-weight: 700;
        border: 1px solid #0C447C;
    }
    th.col-name { text-align: left; }
    td {
        padding: 4px 6px;
        border: 1px solid #E2E0D8;
        vertical-align: middle;
        text-align: center;
        font-size: 8px;
    }
    td.col-name { text-align: left; font-weight: 500; }
    td.col-code { font-weight: 700; color: #185FA5; }
    tr:nth-child(even) td { background: #F8F7F4; }

    /* Cellule de signature */
    .sig-cell { padding: 2px 4px; }
    .sig-cell img {
        max-width: 80px;
        max-height: 32px;
        display: block;
        margin: 0 auto;
    }
    .sig-absent  { color: #A32D2D; font-style: italic; }
    .sig-missing { color: #bbb; }
    .sig-ok      { color: #0F6E56; }

    /* ── Signature formateur ── */
    .formateur-section {
        margin-top: 14px;
        padding-top: 10px;
        border-top: 1px solid #E2E0D8;
    }
    .formateur-grid {
        display: table;
        width: 100%;
    }
    .formateur-col {
        display: table-cell;
        width: 50%;
        padding-right: 16px;
        vertical-align: top;
    }
    .formateur-col:last-child { padding-right: 0; }
    .formateur-title { font-size: 9px; font-weight: 700; color: #5F5E5A; margin-bottom: 4px; }
    .formateur-sig-box {
        border: 1px solid #E2E0D8;
        border-radius: 4px;
        padding: 4px;
        background: #FAFAFA;
        min-height: 50px;
    }
    .formateur-sig-box img { max-width: 100%; max-height: 50px; }
    .formateur-info { font-size: 8px; color: #5F5E5A; margin-top: 3px; }

    /* ── Pied de page ── */
    .footer {
        margin-top: 14px;
        padding-top: 8px;
        border-top: 1px solid #E2E0D8;
        display: flex;
        justify-content: space-between;
        font-size: 7px;
        color: #bbb;
    }
    .footer strong { color: #5F5E5A; }

    /* ── Légende ── */
    .legende {
        font-size: 7px;
        color: #5F5E5A;
        margin-bottom: 10px;
        background: #FAFAFA;
        padding: 4px 8px;
        border-radius: 3px;
        border: 1px solid #E2E0D8;
    }
</style>
</head>
<body>

{{-- ── En-tête ── --}}
<div class="header">
    <div class="header-left">
        <h1>Feuille d'émargement numérique</h1>
        <p>Passeport de Prévention — Document généré automatiquement</p>
    </div>
    <div class="header-right">
        <strong>{{ config('app.name', 'PassForm') }}</strong><br>
        Généré le {{ now()->format('d/m/Y à H\hi') }}
    </div>
</div>

{{-- ── Métadonnées session ── --}}
<div class="meta-grid">
    <div class="meta-row">
        <div class="meta-lbl">Formation</div>
        <div class="meta-val"><strong>{{ $session->intitule }}</strong></div>
        <div class="meta-lbl" style="padding-left:20px">Formateur</div>
        <div class="meta-val">{{ $session->formateur->name }}</div>
    </div>
    <div class="meta-row">
        <div class="meta-lbl">Lieu</div>
        <div class="meta-val">{{ $session->lieu ?: '—' }}</div>
        <div class="meta-lbl" style="padding-left:20px">Statut</div>
        <div class="meta-val">{{ ucfirst($session->statut) }}</div>
    </div>
    <div class="meta-row">
        <div class="meta-lbl">Période</div>
        <div class="meta-val">
            @if($demiJournees->isNotEmpty())
                {{ $demiJournees->first()->date->format('d/m/Y') }}
                @if($demiJournees->count() > 1)
                    → {{ $demiJournees->last()->date->format('d/m/Y') }}
                @endif
            @endif
        </div>
        <div class="meta-lbl" style="padding-left:20px">Participants</div>
        <div class="meta-val">{{ $participants->count() }}</div>
    </div>
</div>

{{-- ── Légende ── --}}
<div class="legende">
    ✍ = Signé · — = Non signé / Absent · Les signatures sont stockées avec horodatage et adresse IP
</div>

{{-- ── Tableau des émargements ── --}}
<table>
    <thead>
        <tr>
            <th class="col-name">Participant</th>
            <th style="width:35px">Code</th>
            @foreach($demiJournees as $dj)
                <th style="width:90px">
                    DJ{{ $dj->ordre }}<br>
                    {{ $dj->creneau === 'matin' ? 'Matin' : 'AM' }}<br>
                    {{ $dj->date->format('d/m/Y') }}<br>
                    {{ substr($dj->heure_debut,0,5) }}-{{ substr($dj->heure_fin,0,5) }}
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($participants as $participant)
            <tr>
                <td class="col-name">
                    {{ strtoupper($participant->nom) }} {{ $participant->prenom }}
                    @if($participant->nom_naissance && $participant->nom_naissance !== $participant->nom)
                        <br><span style="color:#5F5E5A; font-size:7px">né(e) {{ strtoupper($participant->nom_naissance) }}</span>
                    @endif
                </td>
                <td class="col-code">{{ $participant->code_identification }}</td>
                @foreach($demiJournees as $dj)
                    @php
                        $ema = $participant->emargements->firstWhere('demi_journee_id', $dj->id);
                    @endphp
                    <td class="sig-cell">
                        @if($ema && $ema->est_signe)
                            {{-- Afficher la signature en miniature --}}
                            <img src="{{ $ema->signature }}" alt="Signé">
                            <div style="font-size:6px; color:#5F5E5A; margin-top:1px">
                                {{ $ema->signe_at->format('H:i') }}
                            </div>
                        @elseif($ema && !$ema->present)
                            <span class="sig-absent">Absent</span>
                        @else
                            <span class="sig-missing">—</span>
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

{{-- ── Signatures formateur par demi-journée ── --}}
<div class="formateur-section">
    <div style="font-size:10px; font-weight:700; color:#185FA5; margin-bottom:8px">
        Certifications du formateur
    </div>
    <div class="formateur-grid">
        @foreach($demiJournees as $dj)
            <div class="formateur-col">
                <div class="formateur-title">
                    DJ{{ $dj->ordre }} —
                    {{ $dj->creneau === 'matin' ? 'Matin' : 'Après-midi' }}
                    · {{ $dj->date->format('d/m/Y') }}
                </div>
                <div class="formateur-sig-box">
                    @if($dj->signature_formateur)
                        <img src="{{ $dj->signature_formateur }}" alt="Signature {{ $session->formateur->name }}">
                    @else
                        <div style="font-size:8px; color:#bbb; padding:8px; text-align:center">Non signé</div>
                    @endif
                </div>
                <div class="formateur-info">
                    {{ $session->formateur->name }}
                    @if($dj->formateur_signe_at)
                        · {{ $dj->formateur_signe_at->format('d/m/Y H:i') }}
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- ── Pied de page ── --}}
<div class="footer">
    <span>
        <strong>Traçabilité :</strong>
        Signatures horodatées · IP journalisées · Données chiffrées AES-256 · Conforme RGPD
    </span>
    <span>
        PassForm · {{ now()->format('d/m/Y') }}
    </span>
</div>

</body>
</html>
