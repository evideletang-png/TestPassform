<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DemiJournee;
use App\Models\SessionFormation;
use Illuminate\Http\Request;

class FormateurPublicController extends Controller
{
    public function index(string $token)
    {
        $session = SessionFormation::where('token_formateur', $token)->firstOrFail();

        return view('formateur.index', [
            'session'      => $session,
            'demiJournees' => $session->demiJournees()->with('emargements')->get(),
        ]);
    }

    public function signer(Request $request, string $token, int $demiJourneeId)
    {
        $session = SessionFormation::where('token_formateur', $token)->firstOrFail();
        $dj      = DemiJournee::where('id', $demiJourneeId)
            ->where('session_formation_id', $session->id)
            ->firstOrFail();

        $request->validate([
            'signature' => 'required|string',
        ]);

        $dj->signerFormateur($request->signature, $request->ip());

        return back()->with('success_dj', $demiJourneeId);
    }
}
