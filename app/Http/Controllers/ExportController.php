<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SessionFormation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmargementsCdcExport;

class ExportController extends Controller
{
    // ── Export PDF : feuille d'émargement avec signatures ─────────────────────
    public function pdf(Request $request, SessionFormation $session)
    {
        $this->authorize('exporter', $session);

        $session->loadMissing([
            'formateur',
            'demiJournees.emargements.participant',
            'participants',
        ]);

        AuditLog::journaliser('export_pdf', $session, auth()->id());

        $pdf = Pdf::loadView('exports.emargement-pdf', [
            'session'      => $session,
            'demiJournees' => $session->demiJournees,
            'participants' => $session->participants()->orderBy('code_identification')->get(),
        ])
        ->setPaper('a4', 'landscape')
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
            'defaultFont'          => 'DejaVu Sans',
        ]);

        $filename = 'emargement_'
            . \Str::slug($session->intitule) . '_'
            . now()->format('Ymd')
            . '.pdf';

        return $pdf->download($filename);
    }

    // ── Export Excel CDC ──────────────────────────────────────────────────────
    public function excel(Request $request, SessionFormation $session)
    {
        $this->authorize('exporter', $session);

        if (!auth()->user()->isAdmin()) {
            abort(403, 'Seul l\'administrateur peut exporter les données CDC.');
        }

        AuditLog::journaliser('export_excel_cdc', $session, auth()->id());

        $filename = 'cdc_'
            . \Str::slug($session->intitule) . '_'
            . now()->format('Ymd')
            . '.xlsx';

        return Excel::download(new EmargementsCdcExport($session), $filename);
    }

    public function cdc(Request $request, SessionFormation $session)
    {
        return $this->excel($request, $session);
    }
}
