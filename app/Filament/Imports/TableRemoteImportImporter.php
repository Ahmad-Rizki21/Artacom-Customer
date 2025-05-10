<?php

namespace App\Filament\Imports;

use App\Models\AlfaLawson\TableRemote;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TableRemoteImportImporter extends Importer
{
    protected static ?string $model = TableRemote::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('Site_ID')
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('Nama_Toko')
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('DC')
                ->rules(['required', 'string']),
            ImportColumn::make('IP_Address')
                ->rules(['required', 'ip']),
            ImportColumn::make('Vlan')
                ->rules(['required', 'integer', 'min:1', 'max:4094']),
            ImportColumn::make('Controller')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('Customer')
                ->rules(['required', 'string']),
            ImportColumn::make('Online_Date')
                ->rules(['nullable', 'date']),
            ImportColumn::make('Link')
                ->rules(['required', 'string']),
            ImportColumn::make('Status')
                ->rules(['required', 'string', 'in:OPERATIONAL']),
            ImportColumn::make('Keterangan')
                ->rules(['nullable', 'string']),
        ];
    }

    public function resolveRecord(): ?TableRemote
    {
        Log::info('Importing row:', $this->data);

        try {
            $record = TableRemote::firstOrNew([
                'Site_ID' => $this->data['Site_ID'],
            ]);

            // Transform the Link value before saving
            $link = strtoupper(trim($this->data['Link']));
            if ($link === 'FO') $link = 'FO-GSM';
            elseif ($link === 'SINGLE') $link = 'SINGLE-GSM';
            elseif ($link === 'DUAL') $link = 'DUAL-GSM';
            elseif (!str_contains($link, '-GSM')) $link .= '-GSM';

            // Transform Customer value
            $customer = str_contains(strtoupper($this->data['Customer']), 'ALFAMAR') 
                ? 'ALFAMART' 
                : $this->data['Customer'];

            // Handle date format
            $onlineDate = null;
            if (!empty($this->data['Online_Date'])) {
                try {
                    $onlineDate = Carbon::parse($this->data['Online_Date']);
                } catch (\Exception $e) {
                    Log::warning('Could not parse date: ' . $this->data['Online_Date']);
                }
            }

            $record->fill([
                'Nama_Toko' => $this->data['Nama_Toko'],
                'DC' => strtoupper(trim($this->data['DC'])),
                'IP_Address' => $this->data['IP_Address'],
                'Vlan' => $this->data['Vlan'],
                'Controller' => $this->data['Controller'] ?? null,
                'Customer' => $customer,
                'Link' => $link,
                'Online_Date' => $onlineDate,
                'Status' => 'OPERATIONAL',
                'Keterangan' => $this->data['Keterangan'] ?? null,
            ]);

            Log::info('Processed record:', [
                'Site_ID' => $record->Site_ID,
                'DC' => $record->DC,
                'Link' => $record->Link
            ]);

            return $record;
        } catch (\Exception $e) {
            Log::error('Error processing record: ' . $e->getMessage(), [
                'data' => $this->data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function mutateBeforeCreate(array $data): array
    {
        $data['Link'] = match (strtoupper(trim($data['Link']))) {
            'FO' => 'FO-GSM',
            'SINGLE' => 'SINGLE-GSM',
            'DUAL' => 'DUAL-GSM',
            default => str_contains($data['Link'], '-GSM') ? $data['Link'] : $data['Link'] . '-GSM',
        };

        $data['Customer'] = str_contains(strtoupper($data['Customer']), 'ALFAMAR') 
            ? 'ALFAMART' 
            : $data['Customer'];

        $data['DC'] = strtoupper(trim($data['DC']));

        return $data;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your table remote import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}