<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Formateurs / Utilisateurs ───────────────────────────────────────
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'formateur'])->default('formateur');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Sessions de formation ────────────────────────────────────────────
        Schema::create('sessions_formation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->comment('Formateur responsable');
            $table->string('intitule');
            $table->text('description')->nullable();
            $table->string('lieu')->nullable();

            // Lien participant unique (UUID)
            $table->uuid('token_participant')->unique();
            $table->boolean('lien_actif')->default(true);
            $table->timestamp('lien_expire_at')->nullable()->comment('Calculé à la fin de session + délai paramétrable');

            // Lien formateur unique (UUID distinct)
            $table->uuid('token_formateur')->unique();

            $table->enum('statut', ['planifiee', 'en_cours', 'terminee'])->default('planifiee');

            // Délai de purge RGPD (jours après déclaration CDC)
            $table->unsignedSmallInteger('purge_delai_jours')->default(30);
            $table->timestamp('cdc_declare_at')->nullable()->comment('Date de déclaration à la CDC');
            $table->timestamp('purge_at')->nullable()->comment('Planifiée automatiquement après déclaration CDC');
            $table->boolean('purge_effectuee')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Demi-journées ────────────────────────────────────────────────────
        Schema::create('demi_journees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_formation_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('creneau', ['matin', 'apres_midi']);
            $table->time('heure_debut')->default('08:30');
            $table->time('heure_fin')->default('12:00');
            $table->unsignedSmallInteger('ordre')->default(1)->comment('Ordre chronologique');

            $table->enum('statut_emargement', ['ferme', 'ouvert', 'cloture'])->default('ferme');
            $table->timestamp('emargement_ouvert_at')->nullable();
            $table->timestamp('emargement_cloture_at')->nullable();

            // Signature du formateur
            $table->longText('signature_formateur')->nullable()->comment('Base64 PNG');
            $table->timestamp('formateur_signe_at')->nullable();
            $table->string('formateur_sign_ip', 45)->nullable();

            $table->timestamps();

            $table->unique(['session_formation_id', 'ordre']);
        });

        // ─── Participants ─────────────────────────────────────────────────────
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_formation_id')->constrained()->cascadeOnDelete();

            $table->string('prenom');
            $table->string('nom');
            $table->string('nom_naissance')->nullable();

            // NIR chiffré (cast Encrypted dans le modèle)
            $table->text('nir_encrypted')->nullable()->comment('Chiffré via Laravel Encrypter (AES-256-CBC)');
            $table->boolean('nir_refuse')->default(false)->comment('Participant a refusé de communiquer son NIR');

            // Code unique 3 chiffres (100-999) par session
            $table->string('code_identification', 3)->comment('Code unique 100-999 dans la session');
            $table->boolean('code_consulte')->default(false)->comment('Formateur a consulté le code');

            $table->string('ip_inscription', 45)->nullable();
            $table->timestamp('inscrit_at');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['session_formation_id', 'code_identification']);
        });

        // ─── Émargements (une ligne par participant x demi-journée) ───────────
        Schema::create('emargements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('demi_journee_id')->constrained()->cascadeOnDelete();

            $table->longText('signature')->nullable()->comment('Base64 PNG de la signature dessinée');
            $table->timestamp('signe_at')->nullable();
            $table->string('ip_signature', 45)->nullable();

            $table->boolean('present')->default(true);

            $table->timestamps();

            $table->unique(['participant_id', 'demi_journee_id']);
        });

        // ─── Journal d'audit (RGPD - conservé 12 mois) ───────────────────────
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 100)->comment('inscription|signature|code_consulte|emargement_ouvert|emargement_cloture|export|lien_desactive|purge');
            $table->string('entite_type', 100)->nullable()->comment('Classe du modèle concerné');
            $table->unsignedBigInteger('entite_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->comment('Formateur/Admin connecté, null si participant');
            $table->string('participant_code', 3)->nullable()->comment('Code du participant si action participant');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('contexte')->nullable()->comment('Données complémentaires sérialisées');
            $table->timestamp('created_at')->useCurrent();

            // Index pour la purge des logs > 12 mois
            $table->index('created_at');
            $table->index(['entite_type', 'entite_id']);
        });

        // ─── Tentatives de connexion (rate limiting code participant) ─────────
        Schema::create('code_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_formation_id')->constrained()->cascadeOnDelete();
            $table->string('ip', 45);
            $table->unsignedTinyInteger('tentatives')->default(0);
            $table->timestamp('bloque_jusqu_at')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['session_formation_id', 'ip']);
        });

        // ─── Table de configuration globale ──────────────────────────────────
        Schema::create('parametres', function (Blueprint $table) {
            $table->string('cle')->primary();
            $table->text('valeur');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_attempts');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('emargements');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('demi_journees');
        Schema::dropIfExists('sessions_formation');
        Schema::dropIfExists('users');
        Schema::dropIfExists('parametres');
    }
};
