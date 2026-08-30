<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Descobre como arrancar o Claude Code nesta maquina.
 *
 * Existe porque em Windows nao ha uma resposta unica. O .cmd que o npm instala
 * falha quando o caminho tem espacos ou acentos — a pasta "André Mendes" chega
 * ao cmd noutra pagina de codigo e ele responde "is not recognized". Em vez de
 * obrigar a configurar a mao, tenta-se cada hipotese uma vez e usa-se a que
 * responder ao --version.
 *
 * A ordem respeita quem configurou: o que esta no .env primeiro, o PATH a
 * seguir, e so depois a descoberta automatica do cli.js.
 */
class ClaudeBinary
{
    /** @var array{command: list<string>, label: string}|null */
    private static ?array $resolvido = null;

    /**
     * @return array{command: list<string>, label: string}
     *
     * @throws RuntimeException quando nenhuma hipotese responde
     */
    public static function resolve(): array
    {
        if (self::$resolvido !== null) {
            return self::$resolvido;
        }

        $tentativas = [];

        foreach (self::candidates() as $label => $comando) {
            if (self::works($comando)) {
                return self::$resolvido = ['command' => $comando, 'label' => $label];
            }

            $tentativas[] = $label;
        }

        throw new RuntimeException(
            'Não consegui arrancar o Claude Code. Tentei: ' . implode(', ', $tentativas) . '. '
            . 'Corre `php artisan claude:check` nesta máquina para veres o detalhe de cada tentativa.'
        );
    }

    /**
     * Hipoteses por ordem de preferencia.
     *
     * @return array<string, list<string>>
     */
    public static function candidates(): array
    {
        $candidatos = [];

        if ($script = self::clean(config('claude.node_script'))) {
            $candidatos["node + CLAUDE_NODE_SCRIPT"] = ['node', $script];
        }

        if (($binario = self::clean(config('claude.binary'))) && $binario !== 'claude') {
            $candidatos["CLAUDE_BINARY ({$binario})"] = [$binario];
        }

        $candidatos['claude (do PATH)'] = ['claude'];

        // O node e a hipotese mais solida em Windows: o executavel nao tem
        // espacos nem acentos, e o caminho dificil vai como argumento.
        if ($descoberto = self::discoverNodeScript()) {
            $candidatos['node + cli.js descoberto'] = ['node', $descoberto];
        }

        // Ultimo recurso: o PHP nao executa ficheiros .cmd directamente, e o
        // claude do npm em Windows e um .cmd. Com o cmd pelo meio arranca — mas
        // fica em ultimo porque passar um prompt com varias linhas atraves do
        // cmd e fragil.
        if (PHP_OS_FAMILY === 'Windows') {
            $candidatos['cmd /c claude'] = ['cmd', '/c', 'claude'];
        }

        return $candidatos;
    }

    /** @param list<string> $comando */
    public static function works(array $comando): bool
    {
        return self::probe($comando)['ok'];
    }

    /**
     * @param  list<string>  $comando
     * @return array{ok: bool, saida: string}
     */
    public static function probe(array $comando): array
    {
        try {
            $processo = new Process([...$comando, '--version']);
            $processo->setTimeout(30);
            $processo->run();
        } catch (\Throwable $e) {
            return ['ok' => false, 'saida' => $e->getMessage()];
        }

        $saida = trim($processo->getOutput()) ?: trim($processo->getErrorOutput());

        return [
            'ok'    => $processo->isSuccessful() && $saida !== '',
            'saida' => mb_substr($saida ?: 'sem resposta (código ' . $processo->getExitCode() . ')', 0, 200),
        ];
    }

    /** O cli.js que o npm instala, nos sitios habituais de Windows e Linux. */
    public static function discoverNodeScript(): ?string
    {
        $sufixo = DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . '@anthropic-ai'
            . DIRECTORY_SEPARATOR . 'claude-code' . DIRECTORY_SEPARATOR . 'cli.js';

        $bases = array_filter([
            getenv('APPDATA'),
            getenv('USERPROFILE') ? getenv('USERPROFILE') . DIRECTORY_SEPARATOR . 'AppData' . DIRECTORY_SEPARATOR . 'Roaming' : null,
            getenv('HOME'),
            '/usr/lib',
            '/usr/local/lib',
        ]);

        foreach ($bases as $base) {
            foreach ([DIRECTORY_SEPARATOR . 'npm', ''] as $meio) {
                $caminho = rtrim($base, '\\/') . $meio . $sufixo;

                if (is_file($caminho)) {
                    return $caminho;
                }
            }
        }

        return null;
    }

    /** Apara aspas e espacos que fiquem agarrados ao valor no .env. */
    private static function clean(mixed $valor): string
    {
        return trim((string) $valor, " \t\n\r\0\x0B\"'");
    }
}
