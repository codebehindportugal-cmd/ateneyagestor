<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteProvision extends Model
{
    protected $fillable = [
        'server_id',
        'client_id',
        'site_id',
        'user_id',
        'dominio',
        'estado',
        'opcoes',
        'passos',
        'log',
        'erro',
        'comecou_em',
        'acabou_em',
    ];

    protected function casts(): array
    {
        return [
            'opcoes'     => 'array',
            'passos'     => 'array',
            'comecou_em' => 'datetime',
            'acabou_em'  => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public static function estadoLabels(): array
    {
        return [
            'pendente'  => 'À espera',
            'a_correr'  => 'A correr',
            'concluido' => 'Pronto',
            'erro'      => 'Falhou',
        ];
    }

    public static function estadoCores(): array
    {
        return [
            'pendente'  => 'gray',
            'a_correr'  => 'info',
            'concluido' => 'success',
            'erro'      => 'danger',
        ];
    }

    public function estadoLabel(): string
    {
        return self::estadoLabels()[$this->estado] ?? $this->estado;
    }
}
