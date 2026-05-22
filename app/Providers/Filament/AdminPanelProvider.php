<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Parametres;
use App\Filament\Resources\SessionFormationResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\CommandCenter;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\SessionsTable;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
                CommandCenter::class,
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
                        --pf-dashboard-gutter: clamp(1rem, 2vw, 2.75rem);
                        --pf-dashboard-width: calc(100vw - (var(--pf-dashboard-gutter) * 2));
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
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                        background:
                            linear-gradient(135deg, rgba(20, 63, 115, .10), transparent 28rem),
                            linear-gradient(225deg, rgba(15, 110, 86, .08), transparent 30rem),
                            linear-gradient(180deg, #fbfcfd 0%, #edf3f8 100%);
                    }

                    .fi-simple-layout::before {
                        content: "";
                        position: fixed;
                        inset: 0;
                        background:
                            linear-gradient(120deg, rgba(255, 255, 255, .78), transparent 24%),
                            linear-gradient(300deg, rgba(20, 63, 115, .06), transparent 34%);
                        pointer-events: none;
                    }

                    .fi-simple-main-ctn {
                        min-height: 100vh !important;
                        display: grid !important;
                        place-items: center !important;
                        width: 100% !important;
                    }

                    .fi-simple-main {
                        width: min(100%, 31rem) !important;
                        margin-inline: auto !important;
                        border: 1px solid rgba(15, 23, 42, .10);
                        border-radius: 1rem;
                        background: rgba(255, 255, 255, .94);
                        box-shadow: 0 24px 70px rgba(16, 35, 63, .14);
                        backdrop-filter: blur(20px);
                        padding: clamp(1.75rem, 4vw, 3rem);
                    }

                    .fi-simple-main .fi-simple-header {
                        align-items: center !important;
                        margin-bottom: 2rem;
                        text-align: center;
                    }

                    .fi-simple-main .fi-simple-header-heading {
                        color: var(--pf-ink) !important;
                        font-size: 1.65rem !important;
                        font-weight: 800 !important;
                        line-height: 1.15 !important;
                        letter-spacing: 0 !important;
                    }

                    .fi-simple-main .fi-simple-header-subheading {
                        max-width: 24rem;
                        margin-inline: auto;
                        color: var(--pf-muted) !important;
                        font-size: .96rem !important;
                        line-height: 1.55 !important;
                    }

                    .fi-simple-main .fi-logo {
                        font-weight: 800;
                        letter-spacing: 0;
                        color: var(--pf-ink);
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
                        max-width: 25rem !important;
                        margin-inline: auto !important;
                    }

                    .fi-simple-main .fi-fo-field-wrp-label,
                    .fi-simple-main .fi-fo-field-wrp-label span {
                        color: var(--pf-blue) !important;
                        font-size: .75rem !important;
                        font-weight: 800 !important;
                        letter-spacing: .08em !important;
                        text-transform: uppercase !important;
                    }

                    .fi-simple-main .fi-input-wrp {
                        overflow: hidden;
                        border: 1px solid rgba(20, 63, 115, .18) !important;
                        border-radius: .75rem !important;
                        background: #ffffff !important;
                        box-shadow: none !important;
                        transition: border-color .18s ease, background .18s ease, box-shadow .18s ease;
                    }

                    .fi-simple-main .fi-input-wrp:focus-within {
                        border-color: rgba(20, 63, 115, .58) !important;
                        background: #ffffff !important;
                        box-shadow: 0 0 0 4px rgba(20, 63, 115, .12) !important;
                    }

                    .fi-simple-main .fi-input-wrp {
                        display: flex !important;
                        align-items: center !important;
                        min-height: 3.25rem !important;
                    }

                    .fi-simple-main .fi-input-wrp > :first-child,
                    .fi-simple-main .fi-input-wrp .fi-input-wrp-input {
                        flex: 1 1 auto !important;
                        min-width: 0 !important;
                    }

                    .fi-simple-main .fi-input,
                    .fi-simple-main input:not([type="checkbox"]):not([type="radio"]) {
                        width: 100% !important;
                        min-height: 3.25rem !important;
                        border: 0 !important;
                        outline: 0 !important;
                        background: transparent !important;
                        box-shadow: none !important;
                        color: var(--pf-ink) !important;
                        font-size: .98rem !important;
                        padding-block: 0 !important;
                        -webkit-appearance: none !important;
                        appearance: none !important;
                    }

                    .fi-simple-main .fi-input {
                        width: 100% !important;
                    }

                    .fi-simple-main .fi-input:focus,
                    .fi-simple-main input:not([type="checkbox"]):not([type="radio"]):focus {
                        border: 0 !important;
                        outline: 0 !important;
                        box-shadow: none !important;
                    }

                    .fi-simple-main input[type="checkbox"],
                    .fi-simple-main input[type="radio"] {
                        width: 1rem !important;
                        height: 1rem !important;
                        min-width: 1rem !important;
                        min-height: 1rem !important;
                    }

                    .fi-simple-main input:-webkit-autofill,
                    .fi-simple-main input:-webkit-autofill:hover,
                    .fi-simple-main input:-webkit-autofill:focus {
                        -webkit-text-fill-color: var(--pf-ink) !important;
                        box-shadow: 0 0 0 1000px #ffffff inset !important;
                        caret-color: var(--pf-ink) !important;
                    }

                    .fi-simple-main .fi-input::placeholder,
                    .fi-simple-main input::placeholder {
                        color: rgba(91, 111, 134, .50) !important;
                    }

                    .fi-simple-main .fi-input-wrp-prefix,
                    .fi-simple-main .fi-input-wrp-suffix {
                        color: var(--pf-blue) !important;
                        background: transparent !important;
                    }

                    .fi-simple-main .fi-checkbox-input {
                        border-color: rgba(20, 63, 115, .28) !important;
                        background: #ffffff !important;
                    }

                    .fi-simple-main .fi-checkbox-input:checked {
                        background-color: var(--pf-blue) !important;
                    }

                    .fi-simple-main .fi-checkbox-list-option-label,
                    .fi-simple-main .fi-fo-field-wrp-helper-text,
                    .fi-simple-main label {
                        color: var(--pf-muted) !important;
                    }

                    .fi-simple-main .fi-btn {
                        min-height: 3.35rem !important;
                        justify-content: center !important;
                        border-radius: .75rem !important;
                        background: linear-gradient(135deg, var(--pf-blue), var(--pf-blue-deep)) !important;
                        color: #ffffff !important;
                        font-weight: 800 !important;
                        box-shadow: 0 18px 32px rgba(20, 63, 115, .18) !important;
                    }

                    .fi-simple-main .fi-btn:hover {
                        filter: brightness(1.05);
                    }

                    .fi-simple-main .fi-icon-btn,
                    .fi-simple-main .fi-icon-btn.fi-btn {
                        display: inline-flex !important;
                        width: 2.5rem !important;
                        height: 2.5rem !important;
                        min-width: 2.5rem !important;
                        min-height: 2.5rem !important;
                        align-items: center !important;
                        justify-content: center !important;
                        border-radius: .65rem !important;
                        background: transparent !important;
                        color: var(--pf-blue) !important;
                        box-shadow: none !important;
                        padding: 0 !important;
                    }

                    .fi-simple-main .fi-icon-btn:hover,
                    .fi-simple-main .fi-icon-btn.fi-btn:hover {
                        background: rgba(20, 63, 115, .08) !important;
                        filter: none;
                    }

                    .fi-simple-main .fi-icon-btn svg,
                    .fi-simple-main .fi-icon-btn.fi-btn svg {
                        width: 1.15rem !important;
                        height: 1.15rem !important;
                    }

                    .fi-simple-main .fi-icon-btn .fi-btn-label,
                    .fi-simple-main .fi-icon-btn .sr-only {
                        position: absolute !important;
                        width: 1px !important;
                        height: 1px !important;
                        margin: -1px !important;
                        overflow: hidden !important;
                        clip: rect(0, 0, 0, 0) !important;
                        white-space: nowrap !important;
                    }

                    .fi-simple-main .fi-ac {
                        margin-top: 1.5rem !important;
                    }

                    .fi-sidebar {
                        border-right: 1px solid var(--pf-line);
                        background: rgba(255, 255, 255, .94);
                        backdrop-filter: blur(18px);
                    }

                    .fi-sidebar,
                    .fi-topbar {
                        display: none !important;
                    }

                    .fi-topbar > nav {
                        border-bottom: 1px solid var(--pf-line);
                        background: rgba(255, 255, 255, .78);
                        backdrop-filter: blur(18px);
                    }

                    .fi-topbar .fi-logo img {
                        width: auto !important;
                        height: 2.4rem !important;
                        max-width: 10rem !important;
                        object-fit: contain !important;
                    }

                    .fi-topbar nav {
                        min-height: 4.75rem;
                    }

                    .fi-topbar .fi-topbar-item,
                    .fi-topbar .fi-dropdown-trigger,
                    .fi-topbar a {
                        font-weight: 750;
                    }

                    .fi-main {
                        max-width: none;
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

                    .fi-layout {
                        min-height: 100vh;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                        background:
                            linear-gradient(135deg, rgba(20, 63, 115, .08), transparent 26rem),
                            linear-gradient(225deg, rgba(214, 162, 58, .08), transparent 28rem),
                            #f5f7fb;
                    }

                    .fi-body,
                    .fi-layout,
                    .fi-layout * {
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                    }

                    .fi-main-ctn {
                        width: 100vw !important;
                        max-width: none !important;
                        margin: 0 !important;
                        background: transparent;
                    }

                    .fi-main {
                        width: var(--pf-dashboard-width) !important;
                        max-width: var(--pf-dashboard-width) !important;
                        margin-inline: auto !important;
                        padding-inline: 0 !important;
                        padding-block: clamp(2rem, 4vw, 3.5rem) !important;
                    }

                    .fi-header {
                        width: var(--pf-dashboard-width) !important;
                        max-width: var(--pf-dashboard-width) !important;
                        margin-inline: auto !important;
                    }

                    .fi-main > *,
                    .fi-page,
                    .fi-page > *,
                    .fi-page-content,
                    .fi-page-content > *,
                    .fi-widgets,
                    .fi-widgets > *,
                    .fi-wi-widget,
                    .fi-wi-widget > div,
                    .fi-wi-stats-overview,
                    .fi-wi-stats-overview-stats-ctn,
                    .fi-ta-ctn,
                    .fi-section,
                    .fi-ta {
                        width: 100% !important;
                        max-width: var(--pf-dashboard-width) !important;
                        min-width: 0 !important;
                    }

                    .fi-widgets,
                    .fi-widgets > div,
                    .fi-page-content > div {
                        display: grid !important;
                        grid-template-columns: repeat(12, minmax(0, 1fr)) !important;
                        gap: 1rem !important;
                    }

                    .fi-wi-widget {
                        grid-column: 1 / -1 !important;
                    }

                    .fi-header-heading {
                        color: var(--pf-ink) !important;
                        font-size: clamp(1.6rem, 2vw, 2.15rem) !important;
                        font-weight: 850 !important;
                        letter-spacing: 0 !important;
                    }

                    .fi-sidebar-nav {
                        padding-inline: .75rem !important;
                    }

                    .fi-sidebar,
                    .fi-sidebar-nav {
                        width: 17rem !important;
                    }

                    .fi-sidebar-header {
                        padding-inline: 1rem !important;
                    }

                    .fi-sidebar-item-button {
                        min-height: 2.75rem !important;
                        align-items: center !important;
                        gap: .75rem !important;
                        padding-inline: .85rem !important;
                    }

                    .fi-sidebar-item-label {
                        font-weight: 700 !important;
                    }

                    .fi-wi-stats-overview-stat,
                    .fi-section,
                    .fi-ta {
                        overflow: hidden;
                        border-color: rgba(15, 35, 63, .10) !important;
                        background:
                            linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(255, 255, 255, .88)) !important;
                    }

                    .fi-wi-stats-overview-stat::before {
                        content: "";
                        position: absolute;
                        inset: 0 auto 0 0;
                        width: .25rem;
                        background: linear-gradient(180deg, var(--pf-gold), var(--pf-blue));
                    }

                    .fi-wi-stats-overview-stat {
                        position: relative;
                        padding-left: 1.25rem !important;
                    }

                    .fi-wi-stats-overview-stat-label,
                    .fi-section-header-heading,
                    .fi-ta-header-heading {
                        color: var(--pf-ink) !important;
                        font-weight: 800 !important;
                        letter-spacing: 0 !important;
                    }

                    .fi-wi-stats-overview-stat-description {
                        color: var(--pf-muted) !important;
                    }

                    .fi-ta-content {
                        border-radius: 0 0 1rem 1rem;
                    }

                    .fi-ta-header,
                    .fi-section-header {
                        background: linear-gradient(180deg, rgba(255, 255, 255, .96), rgba(248, 250, 252, .88));
                    }

                    .fi-ta-empty-state {
                        display: grid !important;
                        min-height: 18rem !important;
                        place-items: center !important;
                        padding-block: 3rem !important;
                        text-align: center !important;
                    }

                    .fi-ta-empty-state svg,
                    .fi-ta-empty-state-icon,
                    .fi-ta-empty-state-icon svg {
                        width: 3rem !important;
                        height: 3rem !important;
                        min-width: 3rem !important;
                        min-height: 3rem !important;
                        max-width: 3rem !important;
                        max-height: 3rem !important;
                    }

                    .fi-ta-empty-state > svg {
                        display: block !important;
                        margin-inline: auto !important;
                    }

                    .fi-ta-empty-state-heading {
                        margin-top: 1rem !important;
                        color: var(--pf-ink) !important;
                        font-size: 1rem !important;
                        font-weight: 800 !important;
                    }

                    .fi-ta-empty-state-description {
                        margin-top: .35rem !important;
                        color: var(--pf-muted) !important;
                        font-size: .92rem !important;
                    }

                    .pf-command {
                        display: grid;
                        grid-template-columns: minmax(0, 1.8fr) minmax(24rem, .55fr);
                        gap: clamp(1rem, 2vw, 1.5rem);
                        align-items: stretch;
                        overflow: hidden;
                        width: 100%;
                        max-width: none;
                        margin-inline: auto;
                        border: 1px solid rgba(255, 255, 255, .18);
                        border-radius: 1.25rem;
                        background:
                            linear-gradient(135deg, rgba(16, 35, 63, .98), rgba(20, 63, 115, .94)),
                            linear-gradient(90deg, rgba(214, 162, 58, .18), transparent);
                        box-shadow: 0 24px 70px rgba(16, 35, 63, .16);
                        color: #fff;
                        padding: clamp(1.25rem, 3vw, 2rem);
                    }

                    .pf-command__grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(min(20rem, 100%), 1fr));
                        gap: .85rem;
                        margin-top: 1.35rem;
                    }

                    .pf-command__tile {
                        display: flex;
                        min-height: 6.75rem;
                        flex-direction: column;
                        justify-content: space-between;
                        border: 1px solid rgba(255, 255, 255, .14);
                        border-radius: .9rem;
                        background: rgba(255, 255, 255, .08);
                        color: #ffffff;
                        padding: 1rem;
                        text-decoration: none;
                        transition: background .18s ease, transform .18s ease, border-color .18s ease;
                    }

                    .pf-command__tile:hover {
                        border-color: rgba(214, 162, 58, .62);
                        background: rgba(255, 255, 255, .12);
                        transform: translateY(-1px);
                    }

                    .pf-command__tile strong {
                        color: #ffffff;
                        font-size: .98rem;
                        font-weight: 850;
                        line-height: 1.2;
                    }

                    .pf-command__tile span {
                        margin-top: .6rem;
                        color: rgba(226, 232, 240, .70);
                        font-size: .82rem;
                        line-height: 1.45;
                    }

                    .pf-command__tile--primary {
                        border-color: rgba(214, 162, 58, .72);
                        background: linear-gradient(135deg, var(--pf-gold), #edcb76);
                        color: #10233f;
                    }

                    .pf-command__tile--primary strong,
                    .pf-command__tile--primary span {
                        color: #10233f;
                    }

                    .pf-command__copy {
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        min-width: 0;
                    }

                    .pf-command__topline {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 1rem;
                    }

                    .pf-command__eyebrow {
                        color: var(--pf-gold-soft);
                        font-size: .78rem;
                        font-weight: 800;
                        letter-spacing: .08em;
                        text-transform: uppercase;
                    }

                    .pf-command__nav {
                        display: flex;
                        flex-wrap: wrap;
                        gap: .5rem;
                        justify-content: flex-end;
                    }

                    .pf-command__nav a {
                        display: inline-flex;
                        min-height: 2.15rem;
                        align-items: center;
                        border: 1px solid rgba(255, 255, 255, .16);
                        border-radius: .65rem;
                        background: rgba(255, 255, 255, .08);
                        color: rgba(255, 255, 255, .84);
                        font-size: .78rem;
                        font-weight: 800;
                        padding-inline: .75rem;
                        text-decoration: none;
                    }

                    .pf-command__nav a:hover {
                        border-color: rgba(214, 162, 58, .52);
                        color: #ffffff;
                    }

                    .pf-command h2 {
                        margin-top: .55rem;
                        color: #fff;
                        font-size: clamp(1.55rem, 3vw, 2.45rem);
                        font-weight: 850;
                        letter-spacing: 0;
                        line-height: 1.05;
                    }

                    .pf-command p {
                        max-width: 42rem;
                        margin-top: .8rem;
                        color: rgba(226, 232, 240, .72);
                        font-size: .98rem;
                        line-height: 1.7;
                    }

                    .pf-command__actions {
                        display: flex;
                        flex-wrap: wrap;
                        gap: .7rem;
                        margin-top: 1.35rem;
                    }

                    .pf-command__button {
                        display: inline-flex;
                        min-height: 2.65rem;
                        align-items: center;
                        justify-content: center;
                        border: 1px solid rgba(255, 255, 255, .16);
                        border-radius: .8rem;
                        background: rgba(255, 255, 255, .08);
                        color: rgba(255, 255, 255, .86);
                        font-size: .88rem;
                        font-weight: 750;
                        padding-inline: 1rem;
                        text-decoration: none;
                    }

                    .pf-command__button--primary {
                        border-color: rgba(214, 162, 58, .78);
                        background: linear-gradient(135deg, var(--pf-gold), #edcb76);
                        color: #10233f;
                        box-shadow: 0 16px 30px rgba(214, 162, 58, .22);
                    }

                    .pf-command__panel {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: .85rem;
                        align-content: center;
                        border: 1px solid rgba(255, 255, 255, .12);
                        border-radius: 1rem;
                        background: rgba(255, 255, 255, .08);
                        padding: 1rem;
                    }

                    .pf-command__panel > div:not(.pf-command__status) {
                        border-radius: .85rem;
                        background: rgba(255, 255, 255, .08);
                        padding: 1rem;
                    }

                    .pf-command__metric {
                        display: block;
                        color: #fff;
                        font-size: 2rem;
                        font-weight: 850;
                        line-height: 1;
                    }

                    .pf-command__label {
                        display: block;
                        margin-top: .35rem;
                        color: rgba(226, 232, 240, .62);
                        font-size: .78rem;
                        font-weight: 800;
                        letter-spacing: .06em;
                        text-transform: uppercase;
                    }

                    .pf-command__status {
                        grid-column: 1 / -1;
                        display: flex;
                        align-items: center;
                        gap: .65rem;
                        border-radius: .85rem;
                        background: rgba(13, 27, 47, .28);
                        color: rgba(255, 255, 255, .78);
                        font-size: .9rem;
                        font-weight: 700;
                        padding: .95rem 1rem;
                    }

                    .pf-command__status span {
                        width: .6rem;
                        height: .6rem;
                        border-radius: 999px;
                        background: #51d88a;
                        box-shadow: 0 0 0 .35rem rgba(81, 216, 138, .16);
                    }

                    .fi-sidebar-item-icon,
                    .fi-btn-icon,
                    .fi-ta-icon,
                    .fi-badge-icon,
                    .fi-icon,
                    svg.fi-icon,
                    .fi-section-header-icon,
                    .fi-wi-stats-overview-stat-description-icon,
                    .fi-user-avatar,
                    .fi-avatar {
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
                    .fi-wi-stats-overview-stat-description-icon svg,
                    .fi-user-avatar svg,
                    .fi-avatar svg {
                        width: 100% !important;
                        height: 100% !important;
                    }

                    .fi-sidebar svg,
                    .fi-topbar svg,
                    .fi-btn svg,
                    .fi-badge svg,
                    .fi-ta svg:not(.fi-ta-empty-state svg) {
                        width: 1.125rem !important;
                        height: 1.125rem !important;
                        min-width: 1.125rem !important;
                        min-height: 1.125rem !important;
                    }

                    .fi-layout svg:not(.fi-ta-empty-state svg) {
                        max-width: 1.25rem !important;
                        max-height: 1.25rem !important;
                    }

                    .fi-page,
                    .fi-page > section,
                    .fi-wi-widget,
                    .fi-wi-widget > div,
                    .fi-wi-stats-overview,
                    .fi-ta-ctn {
                        width: 100% !important;
                        max-width: var(--pf-dashboard-width) !important;
                        margin-inline: auto !important;
                    }

                    @media (max-width: 768px) {
                        :root {
                            --pf-dashboard-gutter: 1rem;
                        }

                        .fi-main {
                            max-width: var(--pf-dashboard-width) !important;
                            padding-inline: 0 !important;
                        }

                        .fi-wi-stats-overview-stat-value {
                            font-size: 1.65rem !important;
                        }

                        .pf-command {
                            grid-template-columns: 1fr;
                        }

                        .pf-command__grid {
                            grid-template-columns: 1fr;
                        }

                        .pf-command__topline {
                            align-items: flex-start;
                            flex-direction: column;
                        }

                        .pf-command__nav {
                            justify-content: flex-start;
                        }
                    }
                </style>
            HTML))
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
