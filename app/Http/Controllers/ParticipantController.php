<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CodeAttempt;
use App\Models\DemiJournee;
use App\Models\Emargement;
use App\Models\Parametre;
use App\Models\Participant;
use App\Models\SessionFormation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParticipantController extends Controller
{
    // ── Page principale : détection auto de la demi-journée en cours ──────────
    public function index(string $token)
    {
        $session = $this->resolveSession($token);
        $djEnCours = $this->detecterDemiJourneeEnCours($session);

        return view('participant.index', [
            'session'   => $session,
            'djEnCours' => $djEnCours,
        ]);
    }

    // ── Première inscription ──────────────────────────────────────────────────
    public function inscrire(Request $request, string $token)
    {
        $session = $this->resolveSession($token);
        $dj      = $this->detecterDemiJourneeEnCours($session);

        if (!$dj) {
            return back()->withErrors(['global' => 'Aucune demi-journée d\'émargement n\'est ouverte actuellement.']);
        }

        $validator = Validator::make($request->all(), [
            'prenom'       => 'required|string|max:100',
            'nom'          => 'required|string|max:100',
            'nom_naissance'=> 'nullable|string|max:100',
            'nir'          => 'nullable|string|size:13',
            'nir_refuse'   => 'nullable|boolean',
            'signature'    => 'required|string', // base64 PNG
        ], [
            'prenom.required'    => 'Le prénom est obligatoire.',
            'nom.required'       => 'Le nom est obligatoire.',
            'nir.size'           => 'Le NIR doit contenir exactement 13 chiffres.',
            'signature.required' => 'La signature est obligatoire.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Validation NIR si fourni
        $nirRefuse = (bool) $request->input('nir_refuse', false);
        $nir       = $request->input('nir');

        if (!$nirRefuse && $nir && !Participant::validerNir($nir)) {
            return back()->withErrors(['nir' => 'Le numéro de sécurité sociale n\'est pas valide.'])->withInput();
        }

        // Vérifier que le participant n'est pas déjà inscrit (via nom+prénom approximatif)
        // On ne bloque pas — deux homonymes peuvent exister dans une session

        // Créer le participant
        $participant = Participant::create([
            'session_formation_id' => $session->id,
            'prenom'               => trim($request->prenom),
            'nom'                  => trim($request->nom),
            'nom_naissance'        => trim($request->nom_naissance) ?: null,
            'nir_encrypted'        => (!$nirRefuse && $nir) ? $nir : null,
            'nir_refuse'           => $nirRefuse,
            'code_identification'  => $session->genererCodeUnique(),
            'ip_inscription'       => $request->ip(),
            'inscrit_at'           => now(),
        ]);

        AuditLog::journaliser('inscription', $participant, null, [
            'session'          => $session->intitule,
            'participant_code' => $participant->code_identification,
        ]);

        // Créer et signer l'émargement de la demi-journée en cours
        $emargement = Emargement::create([
            'participant_id' => $participant->id,
            'demi_journee_id'=> $dj->id,
            'present'        => true,
        ]);
        $emargement->signer($request->signature, $request->ip());

        return view('participant.confirmation', [
            'session'     => $session,
            'participant' => $participant,
            'dj'          => $dj,
        ]);
    }

    // ── Inscription tardive (arrivée en cours de formation) ───────────────────
    public function inscrireRetard(Request $request, string $token)
    {
        // Même logique que inscrire, mais explicitement signalée comme tardive
        $request->merge(['inscription_tardive' => true]);
        return $this->inscrire($request, $token);
    }

    // ── Émargement via code (sessions suivantes) ──────────────────────────────
    public function signer(Request $request, string $token)
    {
        $session = $this->resolveSession($token);

        // Vérification du rate limiting
        $blocage = $this->verifierBlocage($session, $request->ip());
        if ($blocage) {
            return back()->withErrors(['code' => $blocage]);
        }

        $validator = Validator::make($request->all(), [
            'code'      => 'required|string|size:3|regex:/^[1-9][0-9]{2}$/',
            'signature' => 'required|string',
        ]);

        if ($validator->fails()) {
            $this->incrementerTentatives($session, $request->ip());
            return back()->withErrors($validator)->withInput();
        }

        $participant = $session->participants()
            ->where('code_identification', $request->code)
            ->first();

        if (!$participant) {
            $this->incrementerTentatives($session, $request->ip());
            $restantes = $this->tentativesRestantes($session, $request->ip());
            return back()->withErrors([
                'code' => "Code non reconnu. {$restantes} tentative(s) restante(s) avant blocage temporaire.",
            ])->withInput();
        }

        // Réinitialiser les tentatives après succès
        $this->reinitialiserTentatives($session, $request->ip());

        $dj = $this->detecterDemiJourneeEnCours($session);
        if (!$dj) {
            return back()->withErrors(['global' => 'Aucune demi-journée d\'émargement n\'est ouverte actuellement.']);
        }

        // Vérifier si déjà signé pour cette demi-journée
        $dejaSign = Emargement::where('participant_id', $participant->id)
            ->where('demi_journee_id', $dj->id)
            ->whereNotNull('signature')
            ->exists();

        if ($dejaSign) {
            return back()->withErrors(['code' => 'Vous avez déjà signé pour cette demi-journée.']);
        }

        // Créer ou mettre à jour l'émargement
        $emargement = Emargement::firstOrCreate(
            ['participant_id' => $participant->id, 'demi_journee_id' => $dj->id],
            ['present' => true]
        );
        $emargement->signer($request->signature, $request->ip());

        return view('participant.confirmation', [
            'session'     => $session,
            'participant' => $participant,
            'dj'          => $dj,
        ]);
    }

    // ── Vérification AJAX du code ─────────────────────────────────────────────
    public function verifierCode(Request $request, string $token)
    {
        $session     = $this->resolveSession($token);
        $code        = $request->input('code');
        $participant = $session->participants()
            ->where('code_identification', $code)
            ->first();

        return response()->json([
            'valide' => (bool) $participant,
            'nom'    => $participant ? $participant->prenom . ' ' . strtoupper($participant->nom) : null,
        ]);
    }

    // ── Helpers privés ────────────────────────────────────────────────────────

    private function resolveSession(string $token): SessionFormation
    {
        $session = SessionFormation::where('token_participant', $token)->firstOrFail();

        // Vérifier que le lien est actif et non expiré
        abort_if(!$session->lien_actif, 403, 'Ce lien de session a été désactivé.');
        abort_if(
            $session->lien_expire_at && $session->lien_expire_at->isPast(),
            403,
            'Ce lien de session a expiré.'
        );

        return $session;
    }

    private function detecterDemiJourneeEnCours(SessionFormation $session): ?DemiJournee
    {
        $tolAvant = (int) Parametre::get('signature_tolerance_avant_minutes', 15);
        $tolApres = (int) Parametre::get('signature_tolerance_apres_minutes', 30);
        $now      = now();

        return $session->demiJournees
            ->where('statut_emargement', 'ouvert')
            ->first(function (DemiJournee $dj) use ($now, $tolAvant, $tolApres) {
                $debut = \Carbon\Carbon::parse($dj->date->format('Y-m-d') . ' ' . $dj->heure_debut)
                    ->subMinutes($tolAvant);
                $fin   = \Carbon\Carbon::parse($dj->date->format('Y-m-d') . ' ' . $dj->heure_fin)
                    ->addMinutes($tolApres);
                return $now->between($debut, $fin);
            });
    }

    private function verifierBlocage(SessionFormation $session, string $ip): ?string
    {
        $tentative = CodeAttempt::where('session_formation_id', $session->id)
            ->where('ip', $ip)
            ->first();

        if ($tentative && $tentative->bloque_jusqu_at && $tentative->bloque_jusqu_at->isFuture()) {
            $minutes = $tentative->bloque_jusqu_at->diffInMinutes(now());
            return "Trop de tentatives. Réessayez dans {$minutes} minute(s).";
        }

        return null;
    }

    private function incrementerTentatives(SessionFormation $session, string $ip): void
    {
        $max     = (int) Parametre::get('max_tentatives_code', 5);
        $blocage = (int) Parametre::get('blocage_duree_minutes', 15);

        $tentative = CodeAttempt::firstOrCreate(
            ['session_formation_id' => $session->id, 'ip' => $ip],
            ['tentatives' => 0]
        );

        $tentative->increment('tentatives');

        if ($tentative->tentatives >= $max) {
            $tentative->update(['bloque_jusqu_at' => now()->addMinutes($blocage)]);
        }
    }

    private function reinitialiserTentatives(SessionFormation $session, string $ip): void
    {
        CodeAttempt::where('session_formation_id', $session->id)
            ->where('ip', $ip)
            ->delete();
    }

    private function tentativesRestantes(SessionFormation $session, string $ip): int
    {
        $max       = (int) Parametre::get('max_tentatives_code', 5);
        $tentative = CodeAttempt::where('session_formation_id', $session->id)
            ->where('ip', $ip)
            ->first();

        return max(0, $max - ($tentative?->tentatives ?? 0));
    }
}
