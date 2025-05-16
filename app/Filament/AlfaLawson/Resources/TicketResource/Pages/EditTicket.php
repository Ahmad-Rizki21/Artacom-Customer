<?php

namespace App\Filament\AlfaLawson\Resources\TicketResource\Pages;

use App\Filament\AlfaLawson\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}