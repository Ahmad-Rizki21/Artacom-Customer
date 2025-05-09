<?php

namespace App\Filament\AlfaLawson\Resources\TablePeplinkResource\Pages;

use App\Filament\AlfaLawson\Resources\TablePeplinkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListTablePeplinks extends ListRecords
{
    protected static string $resource = TablePeplinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add New Device')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->size('lg')
                ->after(function () {
                    Notification::make()
                        ->title('Device Added Successfully')
                        ->success()
                        ->body('New Peplink device has been registered to the system.')
                        ->duration(5000)
                        ->send();
                }),
        ];
    }

    public function getTitle(): string 
    {
        return 'Peplink Device Management';
    }

    protected function getActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->export()),
                
            Actions\Action::make('help')
                ->label('Documentation')
                ->icon('heroicon-o-question-mark-circle')
                ->url('/docs/peplink', shouldOpenInNewTab: true),
        ];
    }
}