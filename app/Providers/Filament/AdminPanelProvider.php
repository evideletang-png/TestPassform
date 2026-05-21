<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Parametres;
use App\Filament\Resources\SessionFormationResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\SessionsTable;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\HtmlString;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Blue,
                'gray'    => Color::Slate,
            ])
            ->brandName('BR Code')
            ->brandLogo('https://ewri63etij3.exactdn.com/wp-content/uploads/2025/04/logo-2-couleurs-2025.png?strip=all&lossy=1&ssl=1')
            ->brandLogoHeight('3.25rem')
            ->favicon(null)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverview::class,
                SessionsTable::class,
            ])
            ->renderHook('panels::head.end', fn () => new HtmlString(<<<'HTML'
                <style>
                    :root {
                        --pf-ink: #10233f;
                        --pf-muted: #5b6f86;
                        --pf-line: rgba(15, 23, 42, .10);
                        --pf-panel: rgba(255, 255, 255, .92);
                        --pf-blue: #143f73;
                        --pf-blue-deep: #0d2848;
                        --pf-gold: #d6a23a;
                        --pf-gold-soft: #f5dfaa;
                    }

                    .fi-body {
                        background:
                            linear-gradient(135deg, rgba(214, 162, 58, .13) 0%, transparent 30%),
                            linear-gradient(225deg, rgba(20, 63, 115, .10) 0%, transparent 34%),
                            linear-gradient(180deg, #f9faf8 0%, #eef3f8 100%);
                        color: var(--pf-ink);
                    }

                    .fi-simple-layout {
                        min-height: 100vh;
                        display: grid;
                        place-items: center;
                        padding: 2rem 1rem;
                        background:
                            linear-gradient(135deg, rgba(13, 40, 72, .96), rgba(20, 63, 115, .88)),
                            linear-gradient(90deg, rgba(214, 162, 58, .26), transparent 34%, rgba(255, 255, 255, .16));
                    }

                    .fi-simple-main-ctn {
                        min-height: 100vh !important;
                        display: grid !important;
                        place-items: center !important;
                        width: 100% !important;
                    }

                    .fi-simple-main {
                        width: min(100%, 30rem) !important;
                        margin-inline: auto !important;
                    }

                    .fi-simple-main .fi-simple-header {
                        margin-bottom: 1.25rem;
                    }

                    .fi-simple-main .fi-simple-header-heading {
                        font-size: 1.75rem !important;
                        line-height: 1.15 !important;
                        letter-spacing: 0 !important;
                    }

                    .fi-simple-main .fi-simple-header-subheading {
                        color: rgba(255, 255, 255, .84) !important;
                    }

                    .fi-simple-main .fi-logo {
                        font-weight: 800;
                        letter-spacing: 0;
                        color: #ffffff;
                    }

                    .fi-sidebar-header .fi-logo {
                        font-weight: 800;
                        letter-spacing: 0;
                        color: var(--pf-ink);
                    }

                    .fi-simple-main .fi-logo img {
                        width: auto !important;
                        height: 5rem !important;
                        max-width: 15rem !important;
                        object-fit: contain !important;
                    }

                    .fi-sidebar-header .fi-logo img {
                        width: auto !important;
                        height: 2.75rem !important;
                        max-width: 11rem !important;
                        object-fit: contain !important;
                    }

                    .fi-simple-main .fi-section,
                    .fi-simple-main .fi-card,
                    .fi-simple-main form {
                        border: 1px solid rgba(255, 255, 255, .72);
                        border-radius: 1.25rem;
                        background: rgba(255, 255, 255, .96);
                        box-shadow: 0 24px 70px rgba(15, 23, 42, .26);
                        backdrop-filter: blur(18px);
                    }

                    .fi-sidebar {
                        border-right: 1px solid var(--pf-line);
                        background: rgba(255, 255, 255, .94);
                        backdrop-filter: blur(18px);
                    }

                    .fi-topbar > nav {
                        border-bottom: 1px solid var(--pf-line);
                        background: rgba(255, 255, 255, .78);
                        backdrop-filter: blur(18px);
                    }

                    .fi-main {
                        max-width: 1440px;
                    }

                    .fi-section,
                    .fi-ta,
                    .fi-wi-widget .fi-section,
                    .fi-fo-repeater-item {
                        border: 1px solid var(--pf-line) !important;
                        border-radius: 1rem !important;
                        background: var(--pf-panel) !important;
                        box-shadow: 0 12px 34px rgba(15, 23, 42, .06) !important;
                    }

                    .fi-wi-stats-overview-stats-ctn {
                        display: grid !important;
                        grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)) !important;
                        gap: 1rem !important;
                    }

                    .fi-wi-stats-overview-stat {
                        min-height: 8rem;
                        border-radius: 1rem !important;
                    }

                    .fi-wi-stats-overview-stat-value {
                        font-size: 2rem !important;
                        line-height: 1.05 !important;
                        letter-spacing: 0 !important;
                    }

                    .fi-ta-table {
                        table-layout: auto;
                    }

                    .fi-ta-row:hover {
                        background: rgba(20, 63, 115, .045);
                    }

                    .fi-btn {
                        border-radius: .75rem !important;
                        font-weight: 650;
                    }

                    .fi-badge {
                        border-radius: 999px !important;
                        font-weight: 650;
                    }

                    .fi-sidebar-item-button {
                        border-radius: .75rem !important;
                    }

                    .fi-sidebar-item-active .fi-sidebar-item-button {
                        background: rgba(20, 63, 115, .10) !important;
                        color: var(--pf-blue) !important;
                    }

                    .fi-sidebar-item-icon,
                    .fi-btn-icon,
                    .fi-ta-icon,
                    .fi-badge-icon,
                    .fi-icon,
                    svg.fi-icon,
                    .fi-section-header-icon,
                    .fi-wi-stats-overview-stat-description-icon {
                        width: 1.125rem !important;
                        height: 1.125rem !important;
                        min-width: 1.125rem !important;
                        min-height: 1.125rem !important;
                        max-width: 1.125rem !important;
                        max-height: 1.125rem !important;
                        flex: 0 0 1.125rem !important;
                    }

                    .fi-sidebar-item-icon svg,
                    .fi-btn-icon svg,
                    .fi-ta-icon svg,
                    .fi-badge-icon svg,
                    .fi-icon svg,
                    .fi-section-header-icon svg,
                    .fi-wi-stats-overview-stat-description-icon svg {
                        width: 100% !important;
                        height: 100% !important;
                    }

                    @media (max-width: 768px) {
                        .fi-main {
                            padding-inline: 1rem !important;
                        }

                        .fi-wi-stats-overview-stat-value {
                            font-size: 1.65rem !important;
                        }
                    }
                </style>
            HTML))
            ->navigationItems([
                NavigationItem::make('Documentation RGPD')
                    ->url('#')
                    ->icon('heroicon-o-shield-check')
                    ->sort(99)
                    ->group('Conformité'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            // Un formateur ne voit que ses propres sessions (appliqué via policy)
            ->authGuard('web');
    }
}
