<?php

namespace App\Console\Commands;

use App\Support\Ntfy;
use Illuminate\Console\Command;

/**
 * Confirma que as notificacoes chegam ao telemovel antes de haver um problema
 * a serio. Se isto nao aparecer na app, nada do resto vai aparecer.
 */
class NtfyTest extends Command
{
    protected $signature = 'ntfy:test {mensagem? : Texto a enviar}';

    protected $description = 'Envia uma notificacao de teste para o ntfy';

    public function handle(): int
    {
        if (! config('ntfy.enabled')) {
            $this->error('NTFY_ENABLED esta a false.');

            return self::FAILURE;
        }

        if (blank(config('ntfy.topic'))) {
            $this->error('Falta NTFY_TOPIC no .env.');

            return self::FAILURE;
        }

        $this->line('Servidor: ' . config('ntfy.url'));
        $this->line('Topico:   ' . config('ntfy.topic'));

        $enviado = Ntfy::enviar(
            'teste',
            'Painel Ateneya',
            $this->argument('mensagem') ?: 'Teste de notificacao. Se ves isto, os avisos estao a funcionar.',
            tags: 'satellite',
            link: rtrim((string) config('app.url'), '/') . '/admin',
        );

        if (! $enviado) {
            $this->error('Nao foi enviado. Ve o laravel.log — o erro fica la registado.');

            return self::FAILURE;
        }

        $this->info('Enviado. Deve aparecer na app em segundos.');

        return self::SUCCESS;
    }
}
