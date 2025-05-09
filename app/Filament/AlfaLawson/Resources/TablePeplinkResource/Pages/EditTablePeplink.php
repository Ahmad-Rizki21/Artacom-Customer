<?php

namespace App\Filament\AlfaLawson\Resources\TablePeplinkResource\Pages;

use App\Filament\AlfaLawson\Resources\TablePeplinkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTablePeplink extends EditRecord
{
    protected static string $resource = TablePeplinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
