<?php

namespace App\Exports;

use App\Models\SessionFormation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EmargementsCdcExport implements WithMultipleSheets
{
    public function __construct(private SessionFormation $session) {}

    public function sheets(): array
    {
        return [
            new ParticipantsCdcSheet($this->session),
            new EmargementsCdcSheet($this->session),
        ];
    }
}

// ── Feuille 1 : Participants (NIR, identité) ──────────────────────────────────
class ParticipantsCdcSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(private SessionFormation $session) {}

    public function title(): string { return 'Participants'; }

    public function headings(): array
    {
        return [
            'Code',
            'Nom de famille',
            'Prénom',
            'Nom de naissance',
            'NIR',
            'NIR refusé',
            'Date inscription',
        ];
    }

    public function collection()
    {
        return $this->session->participants()
            ->orderBy('code_identification')
            ->get()
            ->map(fn ($p) => [
                'code'          => $p->code_identification,
                'nom'           => strtoupper($p->nom),
                'prenom'        => $p->prenom,
                'nom_naissance' => $p->nom_naissance ? strtoupper($p->nom_naissance) : '',
                'nir'           => $p->nir_refuse ? '' : ($p->nir ?? ''),
                'nir_refuse'    => $p->nir_refuse ? 'Oui' : 'Non',
                'inscrit_at'    => $p->inscrit_at?->format('d/m/Y H:i'),
            ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'    => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF185FA5']],
            ],
        ];
    }
}

// ── Feuille 2 : Émargements (présences par demi-journée) ─────────────────────
class EmargementsCdcSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(private SessionFormation $session) {}

    public function title(): string { return 'Émargements'; }

    public function headings(): array
    {
        $djs = $this->session->demiJournees;
        $headers = ['Code', 'Nom', 'Prénom'];
        foreach ($djs as $dj) {
            $label = 'DJ' . $dj->ordre . ' '
                . ($dj->creneau === 'matin' ? 'Matin' : 'AM')
                . ' ' . $dj->date->format('d/m');
            $headers[] = $label . ' (présence)';
            $headers[] = $label . ' (heure)';
        }
        return $headers;
    }

    public function collection()
    {
        $djs          = $this->session->demiJournees;
        $participants = $this->session->participants()
            ->with(['emargements' => fn ($q) => $q->whereIn('demi_journee_id', $djs->pluck('id'))])
            ->orderBy('code_identification')
            ->get();

        return $participants->map(function ($p) use ($djs) {
            $row = [
                $p->code_identification,
                strtoupper($p->nom),
                $p->prenom,
            ];
            foreach ($djs as $dj) {
                $ema = $p->emargements->firstWhere('demi_journee_id', $dj->id);
                if ($ema && $ema->est_signe) {
                    $row[] = 'Présent';
                    $row[] = $ema->signe_at->format('H:i');
                } elseif ($ema && !$ema->present) {
                    $row[] = 'Absent';
                    $row[] = '';
                } else {
                    $row[] = 'Non renseigné';
                    $row[] = '';
                }
            }
            return $row;
        });
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0F6E56']],
            ],
        ];
    }
}
