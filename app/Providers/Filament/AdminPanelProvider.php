<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            // Perfil: é aqui que cada pessoa muda a sua password. Sem isto, um
            // estagiário ficava preso à password temporária com que foi criado.
            ->profile(isSimple: false)
            ->brandName('Gestao Ateneya')
            ->brandLogo(asset('images/ateneya-logo.jpg'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/ateneya-logo.jpg'))
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(MaxWidth::Full)
            ->darkMode(false)
            ->colors([
                'primary' => Color::Indigo,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'gray' => Color::Slate,
            ])
            // A ordem do menu. Os nomes têm de bater certo, acentos incluídos,
            // com os `navigationGroup` dos recursos — o Filament trata
            // "Operacao" e "Operação" como dois grupos diferentes, e era isso
            // que fazia o menu aparecer com grupos repetidos.
            ->navigationGroups([
                NavigationGroup::make('Projectos'),
                NavigationGroup::make('Operação'),
                NavigationGroup::make('Infraestrutura'),
                NavigationGroup::make('Integrações'),
                NavigationGroup::make('Contabilidade'),
                NavigationGroup::make('Clientes'),
                NavigationGroup::make('Administração'),
                NavigationGroup::make('Sistema')
                    ->collapsed(),
            ])
            // A folha de estilos do painel. Este projecto não tem build de
            // Tailwind próprio — usa o CSS já compilado que vem no pacote do
            // Filament, onde só existem as classes que o Filament usa. Coisas
            // como `whitespace-pre-wrap` ou `line-through` não estão lá e não
            // faziam nada. O ficheiro repõe esses utilitários e traz os
            // estilos da lista de tarefas. Ver resources/views/filament/estilos-tarefas.blade.php.
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): \Illuminate\Contracts\View\View => view('filament.estilos-tarefas'),
            )
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                // O AuthenticateSession fica de fora de proposito.
                //
                // Ele guarda o hash da password na sessao e, quando a sessao
                // expira mas o cookie de "lembrar-me" ainda existe, faz logout
                // em vez de deixar reentrar — era isto que punha o painel a
                // pedir login sozinho. O que se perde: uma sessao antiga fica
                // valida depois de mudar a password, e ha que sair a mao nas
                // outras maquinas. Com duas ou tres contas internas vale a
                // troca; num painel aberto a clientes nao valeria.
                // AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                \Filament\Http\Middleware\Authenticate::class,
            ]);
    }
}
