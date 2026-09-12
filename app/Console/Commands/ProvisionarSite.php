<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Server;
use App\Models\SiteProvision;
use App\Services\Provisioning\ProvisionadorDeSite;
use Illuminate\Console\Command;

/**
 * php artisan sites:provisionar "Contabo A" exemplo.pt --cliente=3 --email=geral@exemplo.pt
 *
 * O caminho de confiança quando o site demora (descarregar o WordPress, pedir
 * o certificado): aqui não há browser a desistir a meio.
 */
class ProvisionarSite extends Command
{
    protected $signature = 'sites:provisionar
                            {servidor : Id ou nome do servidor}
                            {dominio : O domínio, sem https:// nem www}
                            {--cliente= : Id do cliente}
                            {--email= : Email do administrador e do certificado}
                            {--titulo= : Título do site (por omissão, o domínio)}
                            {--admin=ateneya : Nome do utilizador administrador do WordPress}
                            {--sem-ssl : Não pedir certificado}
                            {--sem-wordpress : Só a pasta, o vhost e a base de dados}';

    protected $description = 'Cria um site novo num VPS: utilizador, pastas, PHP-FPM, Apache, base de dados, SSL e WordPress';

    public function handle(ProvisionadorDeSite $provisionador): int
    {
        $servidor = Server::where('id', $this->argument('servidor'))
            ->orWhere('name', $this->argument('servidor'))
            ->first();

        if (! $servidor) {
            $this->error('Não encontrei esse servidor.');

            return self::FAILURE;
        }

        $cliente = $this->option('cliente') ? Client::find($this->option('cliente')) : null;

        $provisao = SiteProvision::create([
            'server_id' => $servidor->id,
            'client_id' => $cliente?->id,
            'dominio'   => $this->argument('dominio'),
            'estado'    => 'pendente',
            'opcoes'    => [
                'ssl'       => ! $this->option('sem-ssl'),
                'wordpress' => ! $this->option('sem-wordpress'),
                'email'     => $this->option('email'),
                'titulo'    => $this->option('titulo'),
                'admin'     => $this->option('admin'),
            ],
        ]);

        $this->info("A montar {$provisao->dominio} em {$servidor->name} ({$servidor->host})…");
        $this->newLine();

        $provisao = $provisionador->correr($provisao);

        foreach ($provisao->passos ?? [] as $passo) {
            $sinal = match ($passo['estado']) {
                'ok'    => '<fg=green>ok   </>',
                'aviso' => '<fg=yellow>aviso</>',
                default => '<fg=red>ERRO </>',
            };

            $this->line("  {$sinal} {$passo['label']}");
        }

        $this->newLine();

        if ($provisao->estado === 'erro') {
            $this->error("Parou: {$provisao->erro}");
            $this->line('O que já correu fica registado no painel, em Sites novos.');

            return self::FAILURE;
        }

        $this->info('Pronto. As senhas ficaram no Cofre de Passwords e o site já está registado para as cópias.');

        return self::SUCCESS;
    }
}
