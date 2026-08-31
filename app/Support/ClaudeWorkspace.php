<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Decide em que pasta o Claude vai trabalhar, consoante o projecto.
 *
 *   local  — a pasta ja esta no disco desta maquina (os Laravel do laragon,
 *            e os WordPress que se fazem no PC e depois se enviam).
 *   remote — o codigo so existe no servidor. Puxa-se uma copia SO DE LEITURA
 *            para storage/app/claude/snapshots e o Claude le a copia. Producao
 *            nunca e tocada, e a copia nao traz wp-config.php nem .env.
 *   none   — nao ha codigo a que chegar. O Claude planeia com o contexto do
 *            painel, que para tarefas de manutencao chega perfeitamente.
 */
class ClaudeWorkspace
{
    private function __construct(
        public readonly string $kind,
        public readonly string $path,
        public readonly string $summary,
    ) {}

    /**
     * @param array{source:string,project:string,slug:string,path:?string,remote:?array} $code
     *        O descritor que o painel envia (Project::codeDescriptor()).
     */
    public static function fromDescriptor(array $code): self
    {
        return match ($code['source'] ?? 'none') {
            'local'  => self::local($code),
            'remote' => self::remote($code),
            default  => self::none(),
        };
    }

    private static function local(array $code): self
    {
        $path = (string) ($code['path'] ?? '');
        $nome = $code['project'] ?? 'sem nome';

        if ($path === '') {
            throw new RuntimeException("O projecto '{$nome}' está marcado como pasta local mas não tem caminho preenchido.");
        }

        if (! is_dir($path)) {
            // O caminho e do PC do Andre; aqui pode viver noutro sitio.
            $alternativo = self::naBaseLocal($path);

            if ($alternativo === null) {
                throw new RuntimeException("A pasta do projecto '{$nome}' não existe nesta máquina: {$path}");
            }

            $path = $alternativo;
        }

        return new self('local', $path, "Estás na pasta de trabalho do projecto, em {$path}. É o código a sério — mas nesta ronda não alteras nada.");
    }

    /**
     * O mesmo repositorio, procurado em CLAUDE_REPOS_BASE. Aceita caminhos de
     * Windows vindos do painel mesmo quando se corre em Linux.
     */
    private static function naBaseLocal(string $path): ?string
    {
        $base = trim((string) config('claude.repos_base'), " \t\"'");

        if ($base === '') {
            return null;
        }

        $pasta = basename(str_replace('\\', '/', rtrim($path, '\\/')));

        if ($pasta === '') {
            return null;
        }

        $tentativa = rtrim($base, '\\/') . DIRECTORY_SEPARATOR . $pasta;

        return is_dir($tentativa) ? $tentativa : null;
    }

    private static function none(): self
    {
        // O Claude precisa de uma pasta onde arrancar; esta e propositadamente vazia.
        $path = storage_path('app/claude/sem-codigo');

        if (! is_dir($path)) {
            mkdir($path, 0775, true);
        }

        @file_put_contents(
            $path . DIRECTORY_SEPARATOR . 'LEIA-ME.txt',
            "Pasta vazia de proposito.\n\nEste projecto nao tem codigo acessivel a partir daqui, por isso o Claude\n"
            . "trabalha so com o contexto do painel. Para lhe dar codigo, preenche a\n"
            . "seccao Codigo na ficha do projecto.\n"
        );

        return new self('none', $path, 'Não tens acesso ao código deste projecto. Não inventes ficheiros nem caminhos: trabalha com o que o painel diz e assinala o que precisarias de ver.');
    }

    private static function remote(array $code): self
    {
        $remoto = $code['remote'] ?? null;
        $nome   = $code['project'] ?? 'sem nome';

        if (! $remoto || blank($remoto['host'] ?? null)) {
            throw new RuntimeException("O projecto '{$nome}' está marcado como código no servidor mas não tem site nem servidor associado.");
        }

        if (blank($remoto['path'] ?? null)) {
            throw new RuntimeException("O site de '{$nome}' não tem wp_root nem app_path preenchidos — sem isso não sei o que copiar.");
        }

        $slug = $code['slug'] ?: 'projecto';
        $dest = storage_path('app/claude/snapshots/' . $slug);
        $tgz  = storage_path('app/claude/snapshots/' . $slug . '.tgz');

        if (! self::snapshotIsFresh($dest)) {
            self::pullSnapshot($remoto, $dest, $tgz);
        }

        $quando = date('d/m/Y H:i', (int) @filemtime($dest . DIRECTORY_SEPARATOR . '.claude-snapshot'));

        return new self('remote', $dest, implode(' ', [
            "Estás a ler uma CÓPIA só de leitura de {$remoto['domain']}, tirada de {$remoto['path']} em {$remoto['host']} a {$quando}.",
            'Não é a pasta de produção e não traz wp-config.php nem .env.',
            "Quando indicares ficheiros, dá o caminho como está em produção, a partir de {$remoto['path']}.",
        ]));
    }

    private static function snapshotIsFresh(string $dest): bool
    {
        $marca = $dest . DIRECTORY_SEPARATOR . '.claude-snapshot';

        if (! is_file($marca)) {
            return false;
        }

        $idade = (time() - (int) filemtime($marca)) / 60;

        return $idade < (int) config('claude.snapshot.ttl_minutes');
    }

    private static function pullSnapshot(array $remoto, string $dest, string $tgz): void
    {
        $remotePath = (string) $remoto['path'];
        $perfil     = self::profileFor((string) ($remoto['type'] ?? 'wordpress'));

        $excludes = collect($perfil['exclude'])
            ->map(fn (string $padrao) => "--exclude='{$padrao}'")
            ->implode(' ');

        $includes = collect($perfil['include'])->map(fn (string $p) => "'{$p}'")->implode(' ');

        // `|| true` no tar: um caminho da lista que nao exista neste site nao
        // deve deitar abaixo a copia toda.
        $remoto = "cd '{$remotePath}' && tar czf - --ignore-failed-read {$excludes} {$includes} 2>/dev/null || true";

        $ssh = ['ssh', '-p', (string) ($remoto['port'] ?: 22), '-o', 'BatchMode=yes', '-o', 'StrictHostKeyChecking=accept-new'];

        // A chave so entra se existir mesmo nesta maquina: o campo do painel
        // aponta muitas vezes para o caminho no agente, nao aqui.
        $chave = $remoto['key_path'] ?? null;

        if (filled($chave) && is_file($chave)) {
            array_push($ssh, '-i', $chave);
        }

        $ssh[] = ($remoto['user'] ?: 'root') . '@' . $remoto['host'];
        $ssh[] = $remoto;

        self::ensureDir(dirname($tgz));

        $comando = implode(' ', array_map(fn ($parte) => str_contains($parte, ' ') || str_contains($parte, "'")
            ? '"' . str_replace('"', '\"', $parte) . '"'
            : $parte, $ssh)) . ' > "' . $tgz . '"';

        $processo = Process::fromShellCommandline($comando);
        $processo->setTimeout((float) config('claude.snapshot.timeout', 300));
        $processo->run();

        if (! is_file($tgz) || filesize($tgz) < 100) {
            @unlink($tgz);

            throw new RuntimeException(
                "Não consegui trazer os ficheiros de {$remoto['host']}:{$remotePath}. "
                . (trim($processo->getErrorOutput()) ?: 'Confirma que o ssh liga a esta máquina sem pedir password.')
            );
        }

        $maxBytes = ((int) config('claude.snapshot.max_mb')) * 1024 * 1024;

        if (filesize($tgz) > $maxBytes) {
            $mb = round(filesize($tgz) / 1024 / 1024);
            @unlink($tgz);

            throw new RuntimeException("A cópia de {$remoto['domain']} deu {$mb} MB, acima do limite. Aperta os excludes em config/claude.php.");
        }

        self::resetDir($dest);

        $extrair = new Process(['tar', '-xzf', $tgz, '-C', $dest]);
        $extrair->setTimeout(300);
        $extrair->run();

        @unlink($tgz);

        if (! $extrair->isSuccessful()) {
            throw new RuntimeException('Trouxe o ficheiro mas não o consegui abrir: ' . trim($extrair->getErrorOutput()));
        }

        touch($dest . DIRECTORY_SEPARATOR . '.claude-snapshot');
    }

    /** @return array{include: list<string>, exclude: list<string>} */
    private static function profileFor(string $tipo): array
    {
        $perfil = $tipo === 'vps_laravel' ? 'laravel' : 'wordpress';

        return config("claude.snapshot.{$perfil}");
    }

    private static function ensureDir(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }

    /** Apaga o conteudo antigo para nao ficarem ficheiros que ja nao existem em producao. */
    private static function resetDir(string $path): void
    {
        if (is_dir($path)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($it as $ficheiro) {
                $ficheiro->isDir() ? @rmdir($ficheiro->getPathname()) : @unlink($ficheiro->getPathname());
            }
        }

        self::ensureDir($path);
    }
}
