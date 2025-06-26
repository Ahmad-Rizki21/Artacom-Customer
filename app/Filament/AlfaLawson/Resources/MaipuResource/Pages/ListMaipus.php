<?php

namespace App\Filament\AlfaLawson\Resources\MaipuResource\Pages;

use App\Filament\AlfaLawson\Resources\MaipuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaipus extends ListRecords
{
    protected static string $resource = MaipuResource::class;
public function getTitle(): string 
    {
        return 'Maipu Device Management';
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
