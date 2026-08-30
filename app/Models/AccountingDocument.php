<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo',
        'title',
        'invoice_number',
        'fornecedor',
        'supplier_nif',
        'atcud',
        'estado',
        'amount_cents',
        'iva_cents',
        'currency',
        'date',
        'year',
        'month',
        'category',
        'notes',
        'products',
        'brand_id',
        'file_path',
        'file_name',
        'image_paths',
        'image_names',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'amount_cents' => 'integer',
            'iva_cents'    => 'integer',
            'year'         => 'integer',
            'month'        => 'integer',
            'products'     => 'array',
            'image_paths'  => 'array',
            'image_names'  => 'array',
        ];
    }

    /**
     * O QR das facturas portuguesas traz o NIF do emitente (campo A) mas nao
     * traz o nome. Em vez de o adivinhar a partir da disposicao do texto —
     * que e' onde estas leituras costumam errar — reaproveitamos o nome que ja
     * foi usado para este NIF. Acerta sempre a partir da segunda factura do
     * mesmo fornecedor e nao depende de nenhum servico externo.
     */
    public static function fornecedorPorNif(?string $nif): ?string
    {
        $nif = trim((string) $nif);

        if ($nif === '') {
            return null;
        }

        return static::query()
            ->where('supplier_nif', $nif)
            ->whereNotNull('fornecedor')
            ->where('fornecedor', '!=', '')
            ->orderByDesc('date')
            ->value('fornecedor');
    }

    public static function tipos(): array
    {
        return [
            'fatura'       => 'Fatura',
            'recibo'       => 'Recibo',
            'nota_credito' => 'Nota de Crédito',
            'outro'        => 'Outro',
        ];
    }

    public static function estados(): array
    {
        return [
            'pendente'  => 'Pendente',
            'aprovado'  => 'Aprovado',
            'pago'      => 'Pago',
        ];
    }

    public function getIvaAttribute(): float
    {
        return $this->iva_cents / 100;
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

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categories()[$this->category] ?? ucfirst($this->category);
    }

    /**
     * Para que serviu a compra — o que o contabilista precisa de saber para
     * decidir o tratamento fiscal. É diferente da categoria, que diz o que se
     * comprou: gasóleo para revenda e gasóleo para as carrinhas caem os dois
     * em "Combustíveis", mas não têm o mesmo destino contabilístico.
     */
    public static function finalidades(): array
    {
        return [
            'consumo_empresa' => 'Consumo da empresa',
            'revenda'         => 'Para venda / revenda',
            'cliente'         => 'Para cliente / projecto',
            'equipamento'     => 'Equipamento / imobilizado',
            'servico'         => 'Serviço contratado',
            'deslocacao'      => 'Deslocação',
            'manutencao'      => 'Manutenção e reparação',
            'formacao'        => 'Formação',
            'outro'           => 'Outro',
        ];
    }

    public static function finalidadeLabel(?string $valor): string
    {
        // Devolve o proprio valor quando nao esta na lista: os documentos
        // antigos tem texto livre neste campo e nao podem ficar em branco.
        return self::finalidades()[$valor] ?? (string) $valor;
    }

    public static function categories(): array
    {
        return [
            'fornecedores'   => 'Fornecedores',
            'gastos_empresa' => 'Gastos para empresa',
            'servicos'       => 'Serviços',
            'software'       => 'Software & Subscrições',
            'material'       => 'Material & Equipamento',
            'comunicacoes'   => 'Comunicações',
            'combustiveis'   => 'Combustíveis',
            'viaturas'       => 'Viaturas (portagens, estacionamento, reparações)',
            'deslocacoes'    => 'Deslocações e estadias',
            'refeicoes'      => 'Refeições e representação',
            'rendas'         => 'Rendas e espaços',
            'energia_agua'   => 'Energia e água',
            'seguros'        => 'Seguros',
            'impostos'       => 'Impostos e taxas',
            'bancos'         => 'Encargos bancários',
            'publicidade'    => 'Publicidade e marketing',
            'contabilidade'  => 'Contabilidade e serviços jurídicos',
            'formacao'       => 'Formação',
            'mercadorias'    => 'Mercadorias para revenda',
            'rph'            => 'Rec. Honorários',
            'outros'         => 'Outros',
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
