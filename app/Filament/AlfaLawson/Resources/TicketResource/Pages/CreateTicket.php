<?php

namespace App\Filament\AlfaLawson\Resources\TicketResource\Pages;

use App\Filament\AlfaLawson\Resources\TicketResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['Status'] = 'OPEN';
        $data['Open_By'] = Auth::id();
        $data['Open_Time'] = now();
        $data['Open_Level'] = 'Level 1'; // Default level
        
        return $data;
    }

    /**
     * The URL to redirect to after a new record is created.
     *
     * @return string
    */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}