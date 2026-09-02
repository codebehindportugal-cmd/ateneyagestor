<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Project extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_internal',
        'open_to_interns',
        'client_id',
        'server_id',
        'type',
        'status',
        'url',
        'code_source',
        'code_path',
        'site_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_internal'     => 'boolean',
            'open_to_interns' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /** O site de onde se tira a copia do codigo, quando code_source = remote. */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function tasksTotal(): int
    {
        return $this->tasks_count ?? $this->tasks()->count();
    }

    public function tasksDone(): int
    {
        return $this->tasks_done_count ?? $this->tasks()->where('status', 'done')->count();
    }

    public function tasksOpen(): int
    {
        return max(0, $this->tasksTotal() - $this->tasksDone());
    }

    /** Percentagem de tarefas concluídas (0-100). */
    public function progressPercent(): int
    {
        $total = $this->tasksTotal();

        return $total > 0 ? (int) round($this->tasksDone() / $total * 100) : 0;
    }

    public static function typeOptions(): array
    {
        return [
            'laravel'     => 'Laravel',
            'wordpress'   => 'WordPress',
            'woocommerce' => 'WooCommerce',
            'sync'        => 'Sincronizador',
            'other'       => 'Outro',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'active'      => 'Activo',
            'development' => 'Em desenvolvimento',
            'suspended'   => 'Suspenso',
        ];
    }

    /**
     * De onde o Claude le o codigo deste projecto.
     *
     * `none` e o valor por defeito de proposito: sem configuracao nenhuma o
     * botao continua a funcionar, so que o Claude planeia sem ver ficheiros.
     */
    public static function codeSourceOptions(): array
    {
        return [
            'none'   => 'Sem código — só planeia',
            'local'  => 'Pasta local nesta máquina',
            'remote' => 'Servidor (cópia só de leitura)',
        ];
    }

    public function codeSourceLabel(): string
    {
        return self::codeSourceOptions()[$this->code_source] ?? (string) $this->code_source;
    }

    /**
     * O que o worker precisa de saber para preparar a pasta de trabalho.
     *
     * Vai por HTTP para uma maquina que nao tem acesso a esta base de dados,
     * por isso leva tudo resolvido. Nao leva segredos: no caso remoto leva o
     * host e o utilizador, e a chave e a que essa maquina ja tem no ~/.ssh.
     */
    public function codeDescriptor(): array
    {
        $descritor = [
            'source'  => $this->code_source ?: 'none',
            'project' => $this->name,
            'slug'    => $this->slug,
            'path'    => $this->code_path,
            'remote'  => null,
        ];

        if ($this->code_source === 'remote' && $this->site && $this->site->server) {
            $site   = $this->site;
            $server = $site->server;

            $descritor['remote'] = [
                'host'     => $server->host,
                'port'     => (int) ($server->port ?: 22),
                'user'     => $server->user ?: 'root',
                'key_path' => $server->ssh_key_path,
                'path'     => $site->wp_root ?: $site->app_path,
                'type'     => $site->type instanceof \BackedEnum ? $site->type->value : (string) $site->type,
                'domain'   => $site->domain ?: $site->name,
            ];
        }

        return $descritor;
    }

    /** Ha configuracao suficiente para o Claude chegar ao codigo? */
    public function hasCode(): bool
    {
        return match ($this->code_source) {
            'local'  => filled($this->code_path),
            'remote' => $this->site_id !== null,
            default  => false,
        };
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }
}
