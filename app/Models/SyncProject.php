<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class SyncProject extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'site_url',
        'client_id',
        'host',
        'runner_mode',
        'runner_script_path',
        'runner_schedule',
        'phc_base_url',
        'phc_api_key',
        'phc_username',
        'phc_password',
        'phc_database',
        'phc_company',
        'wintouch_base_url',
        'wintouch_api_key',
        'wintouch_login_email',
        'wintouch_login_password',
        'woo_consumer_key',
        'woo_consumer_secret',
        'woo_api_version',
        'woo_admin_username',
        'woo_admin_app_password',
        'images_base_url',
        'sync_batch_size',
        'sync_default_currency',
        'sync_download_images',
        'sync_orders',
        'sync_products',
        'sync_prices',
        'sync_images',
        'sync_descriptions',
        'sync_short_descriptions',
        'sync_stock',
        'sync_metadata',
        'smtp_host',
        'smtp_port',
        'smtp_user',
        'smtp_password',
        'smtp_from',
        'smtp_to',
        'is_active',
        'status',
        'last_run_at',
        'notes',
        'code_archive_path',
        'code_archive_name',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'phc_api_key' => 'encrypted',
            'phc_password' => 'encrypted',
            'wintouch_api_key' => 'encrypted',
            'wintouch_login_password' => 'encrypted',
            'woo_consumer_key' => 'encrypted',
            'woo_consumer_secret' => 'encrypted',
            'woo_admin_app_password' => 'encrypted',
            'smtp_password' => 'encrypted',
            'sync_batch_size' => 'integer',
            'sync_download_images' => 'boolean',
            'sync_orders' => 'boolean',
            'sync_products' => 'boolean',
            'sync_prices' => 'boolean',
            'sync_images' => 'boolean',
            'sync_descriptions' => 'boolean',
            'sync_short_descriptions' => 'boolean',
            'sync_stock' => 'boolean',
            'sync_metadata' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SyncProject $project) {
            $project->slug ??= Str::slug($project->name).'-'.Str::random(6);
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function syncRuns(): HasMany
    {
        return $this->hasMany(SyncRun::class);
    }

    public function latestSyncRun(): HasOne
    {
        return $this->hasOne(SyncRun::class)->latestOfMany('started_at');
    }

    public static function typeOptions(): array
    {
        return [
            'phc_woo' => 'PHC → WooCommerce',
            'wintouch_woo' => 'Wintouch → WooCommerce',
            'primavera_woo' => 'Primavera → WooCommerce',
            'csharp' => 'C# (cliente)',
            'other' => 'Outro',
        ];
    }

    public static function runnerModeOptions(): array
    {
        return [
            'local' => 'Corre neste projeto/servidor',
            'external' => 'Corre no cliente e só envia report',
        ];
    }

    public function runsLocally(): bool
    {
        return $this->runner_mode === 'local';
    }

    public function hasLocalApiConfig(): bool
    {
        if ($this->type === 'phc_woo') {
            return filled($this->site_url)
                && filled($this->woo_consumer_key)
                && filled($this->woo_consumer_secret)
                && (
                    filled($this->phc_api_key)
                    || (filled($this->phc_username) && filled($this->phc_password))
                );
        }

        return filled($this->site_url)
            && filled($this->woo_consumer_key)
            && filled($this->woo_consumer_secret)
            && filled($this->wintouch_base_url)
            && filled($this->wintouch_api_key)
            && filled($this->wintouch_login_email)
            && filled($this->wintouch_login_password);
    }

    public function toRunnerConfig(): array
    {
        return [
            'wintouch' => [
                'base_url' => $this->wintouch_base_url ?: 'https://api.wintouchcloud.com',
                'api_key' => $this->wintouch_api_key,
                'login_email' => $this->wintouch_login_email,
                'login_password' => $this->wintouch_login_password,
            ],
            'phc' => [
                'base_url' => $this->phc_base_url,
                'api_key' => $this->phc_api_key,
                'username' => $this->phc_username,
                'password' => $this->phc_password,
                'database' => $this->phc_database,
                'company' => $this->phc_company,
            ],
            'woocommerce' => [
                'base_url' => $this->site_url,
                'consumer_key' => $this->woo_consumer_key,
                'consumer_secret' => $this->woo_consumer_secret,
                'version' => $this->woo_api_version ?: 'wc/v3',
                'admin_username' => $this->woo_admin_username,
                'admin_app_password' => $this->woo_admin_app_password,
                'images_base_url' => $this->images_base_url,
            ],
            'sync' => [
                'batch_size' => $this->sync_batch_size ?: 50,
                'default_currency' => $this->sync_default_currency ?: 'EUR',
                'download_images' => (bool) ($this->sync_images ?? $this->sync_download_images),
                'scope' => [
                    'orders' => (bool) ($this->sync_orders ?? true),
                    'products' => (bool) ($this->sync_products ?? true),
                    'prices' => (bool) ($this->sync_prices ?? true),
                    'images' => (bool) ($this->sync_images ?? $this->sync_download_images),
                    'descriptions' => (bool) ($this->sync_descriptions ?? true),
                    'short_descriptions' => (bool) ($this->sync_short_descriptions ?? true),
                    'stock' => (bool) ($this->sync_stock ?? true),
                    'metadata' => (bool) ($this->sync_metadata ?? true),
                ],
            ],
            'logging' => [
                'level' => 'INFO',
            ],
            'smtp' => [
                'user' => $this->smtp_user,
                'from' => $this->smtp_from ?: $this->smtp_user,
                'password' => $this->smtp_password,
                'to' => $this->smtp_to,
                'host' => $this->smtp_host,
                'port' => $this->smtp_port ?: 587,
            ],
        ];
    }
}
