<?php

namespace App\Filament\Admin\Resources\SiteResource\RelationManagers;

use App\Filament\Admin\Resources\ServerResource\RelationManagers\BackupRunsRelationManager as ServerBackupRuns;

/**
 * O histórico de um site é a mesma tabela do histórico de um servidor — a
 * relação `backupRuns` existe nos dois modelos. Herdar evita duas cópias da
 * mesma tabela a divergirem com o tempo.
 */
class BackupRunsRelationManager extends ServerBackupRuns
{
    protected static string $relationship = 'backupRuns';

    protected static ?string $title = 'Historico de backups deste site';
}
