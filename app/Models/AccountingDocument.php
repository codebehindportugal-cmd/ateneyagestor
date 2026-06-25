<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'invoice_number',
        'amount_cents',
        'currency',
        'date',
        'year',
        'month',
        'category',
        'notes',
        'file_path',
        'file_name',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'amount_cents' => 'integer',
            'year'         => 'integer',
            'month'        => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $model) {
            if ($model->date) {
                $model->year  = $model->date->year;
                $model->month = $model->date->month;
            }
        });
    }

    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categories()[$this->category] ?? ucfirst($this->category);
    }

    public static function categories(): array
    {
        return [
            'fornecedores'  => 'Fornecedores',
            'servicos'      => 'Serviços',
            'software'      => 'Software & Subscrições',
            'material'      => 'Material & Equipamento',
            'comunicacoes'  => 'Comunicações',
            'rph'           => 'Rec. Honorários',
            'outros'        => 'Outros',
        ];
    }

    public static function monthName(int $month): string
    {
        return [
            1  => 'Janeiro',
            2  => 'Fevereiro',
            3  => 'Março',
            4  => 'Abril',
            5  => 'Maio',
            6  => 'Junho',
            7  => 'Julho',
            8  => 'Agosto',
            9  => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ][$month] ?? '';
    }
}
