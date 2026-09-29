<?php

namespace App\Console\Commands;

use App\Services\Moloni\MoloniClient;
use App\Services\Moloni\MoloniException;
use App\Services\Moloni\SincronizadorMoloni;
use Illuminate\Console\Command;

class SincronizarMoloni extends Command
{
    protected $signature = 'moloni:sincronizar
        {--ano=* : Ano(s) a trazer. Sem nada: o ano corrente (e o anterior, de Janeiro a Marco)}
        {--teste : So testa a ligacao e lista empresas e series}';

    protected $description = 'Traz do Moloni as faturas de venda para os Resultados';

    public function handle(): int
    {
        $cliente = MoloniClient::daConfig();

        try {
            if ($this->option('teste')) {
                return $this->testar($cliente);
            }

            $anos = array_map('intval', (array) $this->option('ano'));
            if ($anos === []) {
                $anos = [(int) now()->year];
                // Em Janeiro-Marco ainda chegam notas de credito e correccoes
                // do ano anterior.
                if (now()->month <= 3) {
                    $anos[] = (int) now()->year - 1;
                }
            }

            $sincronizador = new SincronizadorMoloni($cliente);

            foreach ($anos as $ano) {
                $r = $sincronizador->ano($ano);
                $this->info("{$ano}: {$r['recebidos']} documentos lidos, {$r['gravados']} gravados, {$r['removidos']} removidos (empresa {$r['empresa']}).");
            }

            return self::SUCCESS;
        } catch (MoloniException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function testar(MoloniClient $cliente): int
    {
        $empresas = $cliente->empresas();
        $this->info('Ligacao ao Moloni OK.');

        $this->table(['company_id', 'Nome', 'NIF'], array_map(fn ($e) => [
            $e['company_id'] ?? '', $e['name'] ?? '', $e['vat'] ?? '',
        ], $empresas));

        $empresa = $cliente->empresaId();
        $this->line("A usar a empresa {$empresa}".(config('moloni.company_id') ? '' : ' (a primeira; fixa com MOLONI_COMPANY_ID)').'.');

        $this->table(['document_set_id', 'Serie'], array_map(fn ($s) => [
            $s['document_set_id'] ?? '', $s['name'] ?? '',
        ], $cliente->series($empresa)));

        return self::SUCCESS;
    }
}
