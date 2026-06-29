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
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
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
