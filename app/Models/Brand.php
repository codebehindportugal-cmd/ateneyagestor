<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = [
        'name',
        'logo_path',
        'color',
        'is_active',
        'parent_brand_id',
        'despesas_api_url',
        'despesas_api_token',
    ];

    protected $hidden = ['despesas_api_token'];

    protected function casts(): array
    {
        return [
            'is_active'          => 'boolean',
            'despesas_api_token' => 'encrypted',
        ];
    }

    /**
     * Esta marca tem painel próprio que recebe as despesas dela (01/10/2026)?
     * Ver App\Services\Contabilidade\EnvioDespesaMarca.
     */
    public function recebeDespesas(): bool
    {
        return filled($this->despesas_api_url) && filled($this->despesas_api_token);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'parent_brand_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Brand::class, 'parent_brand_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function accountingDocuments(): HasMany
    {
        return $this->hasMany(AccountingDocument::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /** "Ateneya › Horta da Maria" or just "Ateneya" */
    public function getFullNameAttribute(): string
    {
        return $this->parent
            ? $this->parent->name . ' › ' . $this->name
            : $this->name;
    }

    /** Options array for Select components: [id => full_name] */
    public static function selectOptions(): array
    {
        return static::with('parent')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($b) => [$b->id => $b->full_name])
            ->toArray();
    }
}
