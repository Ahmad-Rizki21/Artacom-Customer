<?php

namespace App\Filament\AlfaLawson\Resources\TableRemoteResource\Pages;

use App\Filament\AlfaLawson\Resources\TableRemoteResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTableRemote extends CreateRecord
{
    protected static string $resource = TableRemoteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
{
    $user = \Illuminate\Support\Facades\Auth::user()->name ?? 'User';
    $currentTime = now()->format('H:i');
    
    return Notification::make()
        ->success()
        ->title('Remote Connection Created Successfully!')
        ->icon('heroicon-o-server')
        ->iconColor('success')
        ->body("**{$this->record->Site_ID} - {$this->record->Nama_Toko}**\n" .
              "Distribution Center: **{$this->record->DC}**\n" .
              "Created by: **{$user}** at {$currentTime}")
        ->actions([
            \Filament\Notifications\Actions\Action::make('edit')
                ->button()
                ->label('Edit Remote')
                ->url($this->getResource()::getUrl('edit', ['record' => $this->record]))
                ->icon('heroicon-o-pencil'),
            \Filament\Notifications\Actions\Action::make('back')
                ->button()
                ->label('Back to List')
                ->url($this->getResource()::getUrl('index'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ])
        ->duration(8000);
}
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }
}