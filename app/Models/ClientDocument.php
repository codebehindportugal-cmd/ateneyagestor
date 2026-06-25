<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientDocument extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'type',
        'file_path',
        'storage_type',
        'original_name',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::types()[$this->type] ?? ucfirst($this->type);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024)       return "{$bytes} B";
        if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function isPreviewable(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        ]);
    }

    public static function types(): array
    {
        return [
            'contrato'  => 'Contrato',
            'proposta'  => 'Proposta',
            'fatura'    => 'Fatura',
            'recibo'    => 'Recibo',
            'relatorio' => 'Relatório',
            'outro'     => 'Outro',
        ];
    }

    public static function typeColors(): array
    {
        return [
            'contrato'  => 'success',
            'proposta'  => 'info',
            'fatura'    => 'warning',
            'recibo'    => 'warning',
            'relatorio' => 'primary',
            'outro'     => 'gray',
        ];
    }
}
