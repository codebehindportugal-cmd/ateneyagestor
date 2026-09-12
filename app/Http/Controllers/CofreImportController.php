<?php

namespace App\Http\Controllers;

use App\Models\VaultEntry;
use App\Services\Cofre\CofreCrypto;
use App\Services\Cofre\CofreSessao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recebe as entradas já lidas do .kdbx pelo browser e grava-as cifradas.
 *
 * Fica fora do painel porque o Filament não serve pedidos JSON destes; a
 * sessão (`auth`) e o cofre aberto é que mandam em quem pode chamar isto.
 */
class CofreImportController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $chave = CofreSessao::chave();

        if ($chave === null) {
            return response()->json([
                'erro' => 'O cofre está trancado. Abre-o e tenta outra vez.',
            ], 423);
        }

        $dados = $request->validate([
            'entradas'            => ['required', 'array', 'min:1', 'max:5000'],
            'entradas.*.titulo'   => ['required', 'string', 'max:255'],
            'entradas.*.senha'    => ['present', 'nullable', 'string', 'max:4096'],
            'entradas.*.utilizador' => ['nullable', 'string', 'max:255'],
            'entradas.*.url'      => ['nullable', 'string', 'max:2048'],
            'entradas.*.pasta'    => ['nullable', 'string', 'max:255'],
            'entradas.*.notas'    => ['nullable', 'string', 'max:20000'],
            'ignorar_repetidas'   => ['sometimes', 'boolean'],
        ]);

        $ignorarRepetidas = (bool) ($dados['ignorar_repetidas'] ?? true);
        $userId           = (int) auth()->id();

        // O que já cá está, para não duplicar em importações repetidas.
        $existentes = $ignorarRepetidas
            ? VaultEntry::minhas($userId)
                ->get(['titulo', 'utilizador'])
                ->map(fn ($e) => self::assinatura($e->titulo, $e->utilizador))
                ->flip()
            : collect();

        $importadas = 0;
        $saltadas   = 0;
        $agora      = now();

        foreach ($dados['entradas'] as $entrada) {
            $titulo     = trim($entrada['titulo']);
            $utilizador = self::limpar($entrada['utilizador'] ?? null);
            $assinatura = self::assinatura($titulo, $utilizador);

            if ($ignorarRepetidas && $existentes->has($assinatura)) {
                $saltadas++;

                continue;
            }

            VaultEntry::create([
                'user_id'    => $userId,
                'titulo'     => $titulo,
                'pasta'      => self::limpar($entrada['pasta'] ?? null),
                'utilizador' => $utilizador,
                'url'        => self::limpar($entrada['url'] ?? null),
                'segredo'    => CofreCrypto::cifrar((string) ($entrada['senha'] ?? ''), $chave),
                'notas'      => filled($entrada['notas'] ?? null)
                    ? CofreCrypto::cifrar($entrada['notas'], $chave)
                    : null,
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);

            $existentes->put($assinatura, true);
            $importadas++;
        }

        return response()->json([
            'importadas' => $importadas,
            'saltadas'   => $saltadas,
        ]);
    }

    private static function assinatura(string $titulo, ?string $utilizador): string
    {
        return mb_strtolower(trim($titulo)).'|'.mb_strtolower(trim((string) $utilizador));
    }

    private static function limpar(?string $valor): ?string
    {
        $valor = is_string($valor) ? trim($valor) : null;

        return $valor === '' ? null : $valor;
    }
}
