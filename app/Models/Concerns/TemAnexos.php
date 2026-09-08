<?php

namespace App\Models\Concerns;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Poe uma pasta de ficheiros em qualquer modelo.
 *
 * Apagar o dono apaga os registos dos anexos — e o observer do
 * AttachmentService trata de apagar os ficheiros no disco ou no NAS, para nao
 * ficarem orfaos a ocupar espaco que ninguem sabe de quem e'.
 */
trait TemAnexos
{
    public function anexos(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }

    public static function bootTemAnexos(): void
    {
        static::deleting(function ($model): void {
            $model->anexos->each->delete();
        });
    }
}
