<?php

namespace App\Filament\Widgets;

use App\Models\SessionFormation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class SessionsTable extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Sessions en cours et planifiées';
    protected static ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $user  = auth()->user();
                $query = SessionFormation::with(['formateur', 'demiJournees', 'participants'])
                    ->whereIn('statut', ['planifiee', 'en_cours'])
                    ->orderByRaw("FIELD(statut, 'en_cours', 'planifiee')")
                    ->orderBy('created_at', 'desc');

                if ($user->isFormateur()) {
                    $query->where('user_id', $user->id);
                }

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('statut')
                    ->label('')
                    ->badge()
                    ->colors(['warning' => 'en_cours', 'gray' => 'planifiee'])
                    ->formatStateUsing(fn ($s) => $s === 'en_cours' ? '● En cours' : '○ Planifiée')
                    ->width('110px'),

                Tables\Columns\TextColumn::make('intitule')
                    ->label('Formation')
                    ->weight('bold')
                    ->description(fn (SessionFormation $s) => $s->lieu ?: ''),

                Tables\Columns\TextColumn::make('formateur.name')
                    ->label('Formateur')
                    ->hidden(fn () => auth()->user()->isFormateur()),

                // Demi-journée en cours
                Tables\Columns\TextColumn::make('dj_en_cours')
                    ->label('Demi-journée active')
                    ->getStateUsing(function (SessionFormation $s) {
                        $dj = $s->demi_journee_en_cours;
                        if (!$dj) return '—';
                        return ($dj->creneau === 'matin' ? 'Matin' : 'AM')
                            . ' · ' . $dj->date->format('d/m')
                            . ' · ' . $dj->heure_debut . '-' . $dj->heure_fin;
                    }),

                // Barre de progression émargements
                Tables\Columns\TextColumn::make('taux_completion')
                    ->label('Complétion')
                    ->getStateUsing(fn (SessionFormation $s) => $s->taux_completion . ' %')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('participants_count')
                    ->label('Participants')
                    ->counts('participants')
                    ->alignCenter(),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Détail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (SessionFormation $s) => \App\Filament\Resources\SessionFormationResource::getUrl('view', ['record' => $s])),
            ])
            ->emptyStateHeading('Aucune session active')
            ->emptyStateDescription('Créez une session pour commencer.');
    }
}
