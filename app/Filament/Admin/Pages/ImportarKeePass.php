<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Support\ExigeCofreAberto;
use Filament\Pages\Page;

/**
 * Importar um .kdbx do KeePass.
 *
 * O ficheiro NÃO é enviado para o servidor: é aberto no browser, com a senha
 * do KeePass, e só as entradas já lidas seguem para cá — por HTTPS e para
 * serem logo cifradas com a chave do cofre. Assim a base de dados do KeePass
 * nunca fica pousada no disco do servidor, nem sequer por instantes.
 *
 * Nota sobre ficheiros KDBX 4 com Argon2 (o que o KeePass 2 faz por omissão):
 * o kdbxweb não traz Argon2, por isso carrega-se também o argon2-bundled e
 * liga-se um ao outro antes de abrir o ficheiro.
 */
class ImportarKeePass extends Page
{
    use ExigeCofreAberto;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Importar do KeePass';

    protected static ?string $navigationGroup = 'Infraestrutura';

    protected static ?int $navigationSort = 8;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.admin.pages.importar-keepass';


    public function mount(): void
    {
        $this->exigirCofreAberto();
    }
}
