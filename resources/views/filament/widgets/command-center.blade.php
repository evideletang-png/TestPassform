<x-filament-widgets::widget>
    <section class="pf-command">
        <div class="pf-command__copy">
            <div class="pf-command__eyebrow">Espace émargement sécurisé</div>
            <h2>Bonjour {{ $name }}</h2>
            <p>
                Pilotez les sessions, signatures et obligations de conservation depuis un tableau de bord clair.
            </p>

            <div class="pf-command__actions">
                <a href="{{ $createSessionUrl }}" class="pf-command__button pf-command__button--primary">
                    Nouvelle session
                </a>
                <a href="{{ $sessionsUrl }}" class="pf-command__button">
                    Voir les sessions
                </a>
                <a href="{{ $parametresUrl }}" class="pf-command__button">
                    Paramètres
                </a>
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
