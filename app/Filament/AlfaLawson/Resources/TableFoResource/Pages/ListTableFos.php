<?php

namespace App\Filament\AlfaLawson\Resources\TableFoResource\Pages;

use App\Filament\AlfaLawson\Resources\TableFoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTableFos extends ListRecords
{
    protected static string $resource = TableFoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label('Add New Connection')
                ->modalHeading('Create New FO Connection')
                ->modalDescription('Add a new fiber optic connection to the system.')
                ->modalWidth('lg'),
        ];
    }

    protected function getDefaultTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-signal';
    }

    protected function getDefaultTableEmptyStateHeading(): ?string
    {
        return 'No FO Connections Found';
    }

    protected function getDefaultTableEmptyStateDescription(): ?string
    {
        return 'Create your first fiber optic connection by clicking the button below.';
    }

    protected function getDefaultTableEmptyStateActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create FO Connection')
                ->icon('heroicon-o-plus'),
        ];
    }
}