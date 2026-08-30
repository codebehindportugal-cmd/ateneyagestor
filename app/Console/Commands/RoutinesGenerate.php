<?php

namespace App\Console\Commands;

use App\Models\Routine;
use App\Models\RoutineOccurrence;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Materializa as regras das rotinas em datas concretas, para o calendário ter
 * o que mostrar e haver o que marcar como feito.
 *
 * Corre todos os dias e é idempotente: a chave única (routine_id, due_date)
 * garante que uma segunda corrida no mesmo dia não duplica nada.
 */
class RoutinesGenerate extends Command
{
    protected $signature = 'routines:generate
                            {--dias=120 : Quantos dias para a frente materializar}
                            {--dry-run : Mostra o que faria, sem gravar}';

    protected $description = 'Gera as ocorrências futuras das rotinas (tarefas e pagamentos recorrentes).';

    public function handle(): int
    {
        $dias = max(1, (int) $this->option('dias'));
        $dryRun = (bool) $this->option('dry-run');

        $de = CarbonImmutable::today();
        $ate = $de->addDays($dias);

        $rotinas = Routine::where('is_active', true)->get();

        if ($rotinas->isEmpty()) {
            $this->info('Não há rotinas activas.');

            return self::SUCCESS;
        }

        $criadas = 0;
        $existentes = 0;

        foreach ($rotinas as $rotina) {
            foreach ($rotina->datasNoIntervalo($de, $ate) as $data) {
                $jaExiste = RoutineOccurrence::where('routine_id', $rotina->id)
                    ->whereDate('due_date', $data)
                    ->exists();

                if ($jaExiste) {
                    $existentes++;
                    continue;
                }

                $criadas++;

                if ($dryRun) {
                    $this->line("  + {$rotina->nome} — {$data}");
                    continue;
                }

                RoutineOccurrence::create([
                    'routine_id'   => $rotina->id,
                    'due_date'     => $data,
                    'status'       => 'pendente',
                    // Cópia do valor no momento em que se gera: se a renda subir,
                    // as ocorrências já passadas mantêm o que se pagou na altura.
                    'amount_cents' => $rotina->amount_cents,
                ]);
            }
        }

        $this->info(sprintf(
            '%s%d ocorrência(s) %s, %d já existiam. Janela: %s a %s (%d rotinas activas).',
            $dryRun ? '[dry-run] ' : '',
            $criadas,
            $dryRun ? 'a criar' : 'criadas',
            $existentes,
            $de->toDateString(),
            $ate->toDateString(),
            $rotinas->count(),
        ));

        return self::SUCCESS;
    }
}
