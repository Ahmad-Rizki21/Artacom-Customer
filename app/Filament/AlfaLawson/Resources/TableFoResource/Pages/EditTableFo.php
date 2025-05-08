<?php

namespace App\Filament\AlfaLawson\Resources\TableFoResource\Pages;

use App\Filament\AlfaLawson\Resources\TableFoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTableFo extends EditRecord
{
    protected static string $resource = TableFoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('FO Connection Deleted')
                        ->body('The fiber optic connection has been deleted successfully.')
                        ->duration(5000)
                ),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('FO Connection Updated')
            ->body('The fiber optic connection has been updated successfully.')
            ->duration(5000);
    }
}