<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
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
            ->login(Login::class)
            ->colors([
                'primary' => Color::Blue,
                'gray'    => Color::Slate,
            ])
            ->brandName('BR Code')
            ->brandLogo(asset('images/brcode-logo.jpg'))
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
                            linear-gradient(135deg, rgba(8, 17, 38, .98), rgba(21, 34, 62, .96)),
                            linear-gradient(90deg, rgba(214, 162, 58, .14), transparent 34%, rgba(20, 63, 115, .28));
                    }

                    .fi-simple-layout::before {
                        content: "";
                        position: fixed;
                        inset: 0;
                        background:
                            linear-gradient(120deg, rgba(255, 255, 255, .045), transparent 24%),
                            linear-gradient(300deg, rgba(214, 162, 58, .10), transparent 34%);
                        pointer-events: none;
                    }

                    .fi-simple-main-ctn {
                        min-height: 100vh !important;
                        display: grid !important;
                        place-items: center !important;
                        width: 100% !important;
                    }

                    .fi-simple-main {
                        width: min(100%, 32rem) !important;
                        margin-inline: auto !important;
                        border: 1px solid rgba(255, 255, 255, .08);
                        border-radius: 1.5rem;
                        background: rgba(31, 43, 70, .82);
                        box-shadow: 0 30px 90px rgba(0, 0, 0, .38);
                        backdrop-filter: blur(20px);
                        padding: 3rem;
                    }

                    .fi-simple-main .fi-simple-header {
                        align-items: flex-start !important;
                        margin-bottom: 2rem;
                        text-align: left;
                    }

                    .fi-simple-main .fi-simple-header-heading {
                        color: #ffffff !important;
                        font-size: 1.65rem !important;
                        font-weight: 800 !important;
                        line-height: 1.15 !important;
                        letter-spacing: 0 !important;
                    }

                    .fi-simple-main .fi-simple-header-subheading {
                        max-width: 24rem;
                        color: rgba(226, 232, 240, .58) !important;
                        font-size: .96rem !important;
                        line-height: 1.55 !important;
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
                        height: 4.25rem !important;
                        max-width: 13rem !important;
                        object-fit: contain !important;
                        filter: drop-shadow(0 18px 34px rgba(0, 0, 0, .26));
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
                        border: 0 !important;
                        border-radius: 0 !important;
                        background: transparent !important;
                        box-shadow: none !important;
                        backdrop-filter: none;
                    }

                    .fi-simple-main form {
                        padding: 0 !important;
                    }

                    .fi-simple-main .fi-fo-field-wrp-label,
                    .fi-simple-main .fi-fo-field-wrp-label span {
                        color: rgba(226, 232, 240, .62) !important;
                        font-size: .75rem !important;
                        font-weight: 800 !important;
                        letter-spacing: .08em !important;
                        text-transform: uppercase !important;
                    }

                    .fi-simple-main .fi-input-wrp {
                        border: 1px solid rgba(226, 232, 240, .12) !important;
                        border-radius: .75rem !important;
                        background: rgba(255, 255, 255, .07) !important;
                        box-shadow: none !important;
                        transition: border-color .18s ease, background .18s ease, box-shadow .18s ease;
                    }

                    .fi-simple-main .fi-input-wrp:focus-within {
                        border-color: rgba(214, 162, 58, .62) !important;
                        background: rgba(255, 255, 255, .10) !important;
                        box-shadow: 0 0 0 4px rgba(214, 162, 58, .13) !important;
                    }

                    .fi-simple-main .fi-input,
                    .fi-simple-main input {
                        min-height: 3.25rem !important;
                        color: #f8fafc !important;
                        font-size: .98rem !important;
                    }

                    .fi-simple-main .fi-input::placeholder,
                    .fi-simple-main input::placeholder {
                        color: rgba(226, 232, 240, .42) !important;
                    }

                    .fi-simple-main .fi-checkbox-input {
                        border-color: rgba(226, 232, 240, .28) !important;
                        background: rgba(255, 255, 255, .07) !important;
                    }

                    .fi-simple-main .fi-checkbox-input:checked {
                        background-color: var(--pf-gold) !important;
                    }

                    .fi-simple-main .fi-checkbox-list-option-label,
                    .fi-simple-main .fi-fo-field-wrp-helper-text,
                    .fi-simple-main label {
                        color: rgba(226, 232, 240, .64) !important;
                    }

                    .fi-simple-main .fi-btn {
                        min-height: 3.35rem !important;
                        justify-content: center !important;
                        border-radius: .75rem !important;
                        background: linear-gradient(135deg, var(--pf-gold), #e8c56d) !important;
                        color: #0d1b2f !important;
                        font-weight: 800 !important;
                        box-shadow: 0 18px 32px rgba(214, 162, 58, .18) !important;
                    }

                    .fi-simple-main .fi-btn:hover {
                        filter: brightness(1.05);
                    }

                    .fi-simple-main .fi-ac {
                        margin-top: 1.5rem !important;
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
