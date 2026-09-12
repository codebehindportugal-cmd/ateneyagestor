<?php

namespace App\Models;

use App\Services\Cofre\CofreCrypto;
use App\Services\Cofre\CofreSessao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma senha privada.
 *
 * Título, pasta, utilizador e URL ficam em claro — é o que permite procurar e
 * ordenar sem abrir o cofre. A senha e as notas ficam cifradas com a chave do
 * cofre, que só existe em memória enquanto ele está aberto.
 *
 * De propósito não há accessor mágico para a senha: quem a quer tem de pedir
 * a chave, e assim não há caminho em que ela apareça sem querer num log, num
 * `dd()` ou numa resposta JSON.
 */
class VaultEntry extends Model
{
    protected $fillable = [
        'user_id',
        'pasta',
        'titulo',
        'utilizador',
        'url',
        'segredo',
        'notas',
        'usado_em',
    ];

    protected $hidden = ['segredo', 'notas'];

    protected function casts(): array
    {
        return [
            'usado_em' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Só as entradas de quem está com sessão iniciada. */
    public function scopeMinhas(Builder $query, ?int $userId = null): Builder
    {
        return $query->where('user_id', $userId ?? auth()->id());
    }

    public function senha(?string $chave = null): string
    {
        return CofreCrypto::decifrar($this->segredo, $chave ?? CofreSessao::chaveObrigatoria());
    }

    public function notasClaras(?string $chave = null): ?string
    {
        if (blank($this->notas)) {
            return null;
        }

        return CofreCrypto::decifrar($this->notas, $chave ?? CofreSessao::chaveObrigatoria());
    }

    public function definirSenha(string $senha, ?string $chave = null): void
    {
        $this->segredo = CofreCrypto::cifrar($senha, $chave ?? CofreSessao::chaveObrigatoria());
    }

    public function definirNotas(?string $notas, ?string $chave = null): void
    {
        $this->notas = blank($notas)
            ? null
            : CofreCrypto::cifrar($notas, $chave ?? CofreSessao::chaveObrigatoria());
    }

    public function marcarUsada(): void
    {
        $this->forceFill(['usado_em' => now()])->saveQuietly();
    }
}
