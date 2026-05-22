<x-filament-widgets::widget>
    <section class="pf-command">
        <div class="pf-command__copy">
            <div class="pf-command__topline">
                <div class="pf-command__eyebrow">Espace émargement sécurisé</div>
                <div class="pf-command__nav">
                    <a href="{{ $sessionsUrl }}">Sessions</a>
                    @if ($isAdmin)
                        <a href="{{ $usersUrl }}">Formateurs</a>
                        <a href="{{ $parametresUrl }}">Paramètres</a>
                    @endif
                </div>
            </div>

            <h2>Bonjour {{ $name }}</h2>
            <p>
                Pilotez les sessions, signatures et obligations de conservation depuis un tableau de bord clair.
            </p>

            <div class="pf-command__grid">
                <a href="{{ $createSessionUrl }}" class="pf-command__tile pf-command__tile--primary">
                    <strong>Nouvelle session</strong>
                    <span>Créer une formation, planifier les créneaux et préparer les liens d'émargement.</span>
                </a>

                <a href="{{ $sessionsUrl }}" class="pf-command__tile">
                    <strong>Piloter les sessions</strong>
                    <span>Ouvrir les émargements, suivre les signatures et accéder aux exports.</span>
                </a>

                @if ($isAdmin)
                    <a href="{{ $usersUrl }}" class="pf-command__tile">
                        <strong>Formateurs</strong>
                        <span>Créer les accès, attribuer les rôles et suivre l'activité des comptes.</span>
                    </a>

                    <a href="{{ $parametresUrl }}" class="pf-command__tile">
                        <strong>Paramètres</strong>
                        <span>Configurer l'organisme, la sécurité des liens et les règles RGPD.</span>
                    </a>
                @endif
            </div>
        </div>

        <div class="pf-command__panel">
            <div>
                <span class="pf-command__metric">{{ $sessionsEnCoursCount }}</span>
                <span class="pf-command__label">en cours</span>
            </div>
            <div>
                <span class="pf-command__metric">{{ $sessionsPlanifieesCount }}</span>
                <span class="pf-command__label">planifiées</span>
            </div>

            <div class="pf-command__status">
                <span></span>
                @if ($sessionEnCours)
                    Session active : {{ $sessionEnCours->intitule }}
                @else
                    Aucune session active pour le moment
                @endif
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
