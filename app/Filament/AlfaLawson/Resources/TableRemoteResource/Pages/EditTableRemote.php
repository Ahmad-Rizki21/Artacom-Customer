<?php

namespace App\Filament\AlfaLawson\Resources\TableRemoteResource\Pages;

use App\Filament\AlfaLawson\Resources\TableRemoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTableRemote extends EditRecord
{
    protected static string $resource = TableRemoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
