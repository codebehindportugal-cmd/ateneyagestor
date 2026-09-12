<?php

namespace App\Models;

use App\Services\Cofre\CofreCrypto;
use App\Services\Cofre\CofreException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * O cofre de uma pessoa. Um por utilizador.
 *
 * Guarda o embrulho da chave — nunca a chave, nunca a master password. Ver
 * App\Services\Cofre\CofreCrypto para o porquê de cada campo.
 */
class Vault extends Model
{
    protected $fillable = [
        'user_id',
        'salt',
        'opslimit',
        'memlimit',
        'chave_embrulhada',
        'salt_recuperacao',
        'chave_embrulhada_recuperacao',
        'recuperacao_criada_em',
        'aberto_em',
    ];

    protected $hidden = [
        'salt',
        'chave_embrulhada',
        'salt_recuperacao',
        'chave_embrulhada_recuperacao',
    ];

    protected function casts(): array
    {
        return [
            'opslimit'              => 'integer',
            'memlimit'              => 'integer',
            'recuperacao_criada_em' => 'datetime',
            'aberto_em'             => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entradas(): HasMany
    {
        return $this->hasMany(VaultEntry::class, 'user_id', 'user_id');
    }

    /**
     * Cria o cofre desta pessoa e devolve [cofre, chave, código de recuperação].
     * O código só aqui se vê em claro — a seguir só existe embrulhado.
     */
    public static function criar(User $user, string $masterPassword): array
    {
        $chave    = CofreCrypto::chaveNova();
        $salt     = CofreCrypto::salt();
        $opslimit = (int) config('cofre.opslimit', 3);
        $memlimit = (int) config('cofre.memlimit', 67108864);

        $codigo      = CofreCrypto::gerarCodigoRecuperacao();
        $saltCodigo  = CofreCrypto::salt();

        $cofre = static::create([
            'user_id'                      => $user->id,
            'salt'                         => base64_encode($salt),
            'opslimit'                     => $opslimit,
            'memlimit'                     => $memlimit,
            'chave_embrulhada'             => CofreCrypto::cifrar(
                $chave,
                CofreCrypto::derivar($masterPassword, $salt, $opslimit, $memlimit),
            ),
            'salt_recuperacao'             => base64_encode($saltCodigo),
            'chave_embrulhada_recuperacao' => CofreCrypto::cifrar(
                $chave,
                CofreCrypto::derivar(CofreCrypto::normalizarCodigo($codigo), $saltCodigo, $opslimit, $memlimit),
            ),
            'recuperacao_criada_em'        => now(),
        ]);

        return [$cofre, $chave, $codigo];
    }

    /** Abre o cofre com a master password. Devolve a chave em claro. */
    public function abrirCom(string $masterPassword): string
    {
        return $this->desembrulhar(
            $this->chave_embrulhada,
            base64_decode($this->salt, true) ?: '',
            $masterPassword,
        );
    }

    /** Abre o cofre com o código de recuperação. */
    public function abrirComCodigo(string $codigo): string
    {
        if (blank($this->chave_embrulhada_recuperacao) || blank($this->salt_recuperacao)) {
            throw new CofreException('Este cofre não tem código de recuperação.');
        }

        return $this->desembrulhar(
            $this->chave_embrulhada_recuperacao,
            base64_decode($this->salt_recuperacao, true) ?: '',
            CofreCrypto::normalizarCodigo($codigo),
        );
    }

    /**
     * Troca a master password. Reembrulha a mesma chave: as entradas ficam
     * como estão e nada tem de ser decifrado e cifrado outra vez.
     */
    public function mudarMasterPassword(string $chave, string $nova): void
    {
        $salt     = CofreCrypto::salt();
        $opslimit = (int) config('cofre.opslimit', 3);
        $memlimit = (int) config('cofre.memlimit', 67108864);

        $this->update([
            'salt'             => base64_encode($salt),
            'opslimit'         => $opslimit,
            'memlimit'         => $memlimit,
            'chave_embrulhada' => CofreCrypto::cifrar(
                $chave,
                CofreCrypto::derivar($nova, $salt, $opslimit, $memlimit),
            ),
        ]);
    }

    /** Gera um código de recuperação novo e invalida o anterior. */
    public function novoCodigoRecuperacao(string $chave): string
    {
        $codigo = CofreCrypto::gerarCodigoRecuperacao();
        $salt   = CofreCrypto::salt();

        $this->update([
            'salt_recuperacao'             => base64_encode($salt),
            'chave_embrulhada_recuperacao' => CofreCrypto::cifrar(
                $chave,
                CofreCrypto::derivar(
                    CofreCrypto::normalizarCodigo($codigo),
                    $salt,
                    (int) $this->opslimit,
                    (int) $this->memlimit,
                ),
            ),
            'recuperacao_criada_em'        => now(),
        ]);

        return $codigo;
    }

    private function desembrulhar(string $embrulho, string $salt, string $segredo): string
    {
        if ($salt === '') {
            throw CofreException::dadosCorrompidos();
        }

        try {
            $chave = CofreCrypto::decifrar(
                $embrulho,
                CofreCrypto::derivar($segredo, $salt, (int) $this->opslimit, (int) $this->memlimit),
            );
        } catch (CofreException) {
            // Não se distingue "senha errada" de "dados estragados" para quem
            // está a tentar adivinhar.
            throw CofreException::senhaErrada();
        }

        $this->forceFill(['aberto_em' => now()])->saveQuietly();

        return $chave;
    }
}
