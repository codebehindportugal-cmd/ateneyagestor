<?php

namespace App\Filament\Admin\Resources\AccountingDocumentResource\Pages;

use App\Filament\Admin\Resources\AccountingDocumentResource;
use App\Models\AccountingDocument;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListAccountingDocuments extends ListRecords
{
    protected static string $resource = AccountingDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Todos'),
        ];

        $years = AccountingDocument::query()
            ->selectRaw('DISTINCT year')
            ->orderByDesc('year')
            ->pluck('year');

        foreach ($years as $year) {
            $total = AccountingDocument::where('year', $year)->sum('amount_cents') / 100;
            $tabs[(string) $year] = Tab::make((string) $year)
                ->badge(number_format($total, 0, ',', '.') . ' €')
                ->modifyQueryUsing(fn ($query) => $query->where('year', $year));
        }

        return $tabs;
    }
}
