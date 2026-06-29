<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'brand_id',
        'purpose',
        'category',
        'supplier_name',
        'supplier_tax_number',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_total',
        'total',
        'currency',
        'original_file_path',
        'original_file_name',
        'image_paths',
        'image_names',
        'mime_type',
        'raw_extracted_text',
        'extracted_data',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'image_paths' => 'array',
            'image_names' => 'array',
            'extracted_data' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (self $invoice): void {
            $invoice->items()->delete();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierInvoiceItem::class)->orderBy('line_order');
    }

    public static function categories(): array
    {
        return [
            'fornecedores' => 'Fornecedores',
            'gastos_empresa' => 'Gastos para empresa',
            'servicos' => 'Servicos',
            'software' => 'Software e subscricoes',
            'material' => 'Material e equipamento',
            'comunicacoes' => 'Comunicacoes',
            'deslocacoes' => 'Deslocacoes',
            'outros' => 'Outros',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categories()[$this->category] ?? ucfirst((string) $this->category);
    }
}
