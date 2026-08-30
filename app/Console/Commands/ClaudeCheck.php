<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\ClaudeBinary;

/**
 * Descobre como arrancar o Claude Code nesta maquina.
 *
 * Em Windows ha tres caminhos e nem todos funcionam: o .cmd do npm falha quando
 * o caminho leva espacos ou acentos (o "André Mendes" da pasta do utilizador
 * chega ao cmd noutra pagina de codigo e nao resolve). Este comando tenta os
 * tres, diz qual pega, e escreve a linha exacta para o .env.
 *
 *   php artisan claude:check
 */
class ClaudeCheck extends Command
{
    protected $signature = 'claude:check';

    protected $description = 'Descobre como arrancar o Claude Code nesta máquina';

    public function handle(): int
    {
        $this->line('');
        $this->info('A procurar o Claude Code…');
        $this->line('');

        $candidatos = ClaudeBinary::candidates();
        $vencedor   = null;

        foreach ($candidatos as $nome => $comando) {
            $resultado = ClaudeBinary::probe($comando);

            if ($resultado['ok']) {
                $this->line("  <fg=green>✔</> {$nome} — {$resultado['saida']}");
                $vencedor ??= [$nome, $comando];
            } else {
                $this->line("  <fg=red>✘</> {$nome} — {$resultado['saida']}");
            }
        }

        $this->line('');

        if (! $vencedor) {
            $this->error('Nenhum dos caminhos funcionou.');
            $this->line('Confirma que o Claude Code está instalado: npm install -g @anthropic-ai/claude-code');
            $this->line('Depois corre `where claude` e diz o que aparece.');

            return 1;
        }

        [$nome, $comando] = $vencedor;

        $this->info("Usa este: {$nome}");
        $this->line('');

        if ($nome === 'claude (do PATH)') {
            $this->line('  Não precisas de nada no .env — é o valor por defeito.');
            $this->line('  Se tiveres uma linha CLAUDE_BINARY, comenta-a.');
        } elseif (str_starts_with($nome, 'node')) {
            $this->line('  No .env desta máquina, com aspas simples:');
            $this->line('');
            $this->line("  CLAUDE_NODE_SCRIPT='" . $comando[1] . "'");
        } else {
            $this->line('  No .env desta máquina, com aspas simples:');
            $this->line('');
            $this->line("  CLAUDE_BINARY='" . $comando[0] . "'");
        }

        $this->line('');
        $this->line('Depois: php artisan config:clear && php artisan claude:work --once');
        $this->line('');

        return 0;
    }

}
