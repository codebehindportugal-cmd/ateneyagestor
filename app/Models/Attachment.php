<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'name',
        'file_path',
        'storage_type',
        'original_name',
        'file_size',
        'mime_type',
        'origem',
        'notes',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /**
     * O ficheiro vai atras do registo. Fica aqui e nao no sitio que apaga
     * porque ha quatro caminhos ate aqui — a accao da tabela, a accao em lote,
     * o projecto apagado que leva as tarefas, e as tarefas que levam os anexos.
     * Num observer, e' um sitio; espalhado, sao quatro fugas de ficheiros.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $anexo): void {
            app(\App\Services\AttachmentService::class)->apagarFicheiro($anexo);
        });
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public static function origens(): array
    {
        return [
            'cliente' => 'Enviado pelo cliente',
            'equipa'  => 'Da equipa',
        ];
    }

    public function getOrigemLabelAttribute(): string
    {
        return self::origens()[$this->origem] ?? ucfirst((string) $this->origem);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;

        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    /** O que o browser mostra sem descarregar. */
    public function isPreviewable(): bool
    {
        return $this->isImage() || $this->mime_type === 'application/pdf';
    }
}
