<?php

namespace App\Filament\AlfaLawson\Resources\TableSimcardResource\Pages;

use App\Filament\AlfaLawson\Resources\TableSimcardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTableSimcards extends ListRecords
{
    protected static string $resource = TableSimcardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah SIM Card')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->size('lg'),
        ];
    }

    // protected function getHeader(): ?\Illuminate\Contracts\View\View
    // {
    //     return view('filament.resources.table-simcard.header', [
    //         'title' => 'Daftar SIM Card',
    //         'description' => 'Kelola data SIM Card untuk keperluan jaringan toko.',
    //     ]);
    // }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->icon('heroicon-o-list-bullet'),
            'active' => Tab::make('Aktif')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('Status', 'active')),
            'inactive' => Tab::make('Tidak Aktif')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('Status', 'inactive')),
        ];
    }
}