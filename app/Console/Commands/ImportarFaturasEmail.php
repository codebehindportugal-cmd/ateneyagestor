<?php

namespace App\Console\Commands;

use App\Services\Faturacao\ImportadorFaturasEmail;
use App\Support\Ntfy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportarFaturasEmail extends Command
{
    protected $signature = 'faturas:importar-email
        {--dias= : Quantos dias para tras procurar (por omissao, o do config)}
        {--limite= : Quantas mensagens processar nesta corrida}
        {--todas : Inclui mensagens ja lidas, nao so as novas}
        {--teste : So testa a ligacao e sai}';

    protected $description = 'Traz as facturas que chegaram a faturacao@ateneya.com e cria os documentos de contabilidade';

    public function handle(ImportadorFaturasEmail $importador): int
    {
        if (! config('faturas_email.enabled') && ! $this->option('teste')) {
            $this->warn('A importacao por email esta desligada. Poe FATURAS_EMAIL_ENABLED=true no .env.');

            return self::SUCCESS;
        }

        if ($this->option('teste')) {
            $resultado = $importador->testar();

            $resultado['ok']
                ? $this->info($resultado['mensagem'])
                : $this->error($resultado['mensagem']);

            if (! empty($resultado['pastas'])) {
                $this->line('Pastas: '.implode(', ', $resultado['pastas']));
            }

            return $resultado['ok'] ? self::SUCCESS : self::FAILURE;
        }

        try {
            $contas = $importador->correr(
                dias: $this->option('dias') !== null ? (int) $this->option('dias') : null,
                limite: $this->option('limite') !== null ? (int) $this->option('limite') : null,
                incluirLidas: (bool) $this->option('todas'),
                relatar: fn (string $linha) => $this->line($linha),
            );
        } catch (\Throwable $e) {
            // Uma caixa que deixa de abrir e' silencio: nao entram facturas e
            // ninguem da por isso ate o contabilista perguntar. Por isso avisa.
            Log::error('faturas:importar-email falhou: '.$e->getMessage(), ['excepcao' => $e]);

            Ntfy::falhou(
                'faturas',
                'Nao consegui ler a caixa das facturas',
                $e->getMessage()."\n\nEnquanto isto durar NAO entram facturas novas.",
                rtrim((string) config('app.url'), '/').'/admin/accounting-documents',
            );

            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info(sprintf(
            '%d mensagem(ns) analisadas · %d documento(s) criados · %d duplicado(s) · %d sem anexo de factura.',
            $contas['mensagens'],
            $contas['documentos'],
            $contas['duplicados'],
            $contas['semAnexo'],
        ));

        if ($contas['erros'] !== []) {
            $this->newLine();
            $this->error(count($contas['erros']).' mensagem(ns) com erro:');

            foreach ($contas['erros'] as $erro) {
                $this->line('  - '.$erro);
            }

            Ntfy::falhou(
                'faturas',
                'Facturas por email com erros',
                count($contas['erros']).' mensagem(ns) nao deram para importar:'."\n- "
                    .implode("\n- ", array_slice($contas['erros'], 0, 5)),
                rtrim((string) config('app.url'), '/').'/admin/accounting-documents',
            );
        }

        return self::SUCCESS;
    }
}
