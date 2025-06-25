<?php

namespace App\Filament\Imports;

use App\Models\AlfaLawson\RemoteAtmbsi;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RemoteAtmbsiImportImporter extends Importer
{
    protected static ?string $model = RemoteAtmbsi::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('Site_ID')
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('Site_Name')
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('Branch')
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('IP_Address')
                ->rules(['required', 'ip']),
            ImportColumn::make('Vlan')
                ->rules(['required', 'integer', 'min:1', 'max:4094']),
            ImportColumn::make('Controller')
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('Customer')
                ->rules(['required', 'string']),
            ImportColumn::make('Online_Date')
                ->rules(['nullable', 'date']),
            ImportColumn::make('Link')
                ->rules(['required', 'string']),
            ImportColumn::make('Status')
                ->rules(['required', 'string', 'in:OPERATIONAL,DISMANTLED']),
            ImportColumn::make('Keterangan')
                ->rules(['nullable', 'string']),
        ];
    }

    public function resolveRecord(): ?RemoteAtmbsi
    {
        Log::info('Importing row:', $this->data);

        try {
            $record = RemoteAtmbsi::firstOrNew([
                'Site_ID' => $this->data['Site_ID'],
            ]);

            // Transform Link value
            $link = strtoupper(trim($this->data['Link']));
            if ($link === 'FO') $link = 'FO-GSM';
            elseif ($link === 'SINGLE') $link = 'SINGLE-GSM';
            elseif ($link === 'DUAL') $link = 'DUAL-GSM';
            elseif (!str_contains($link, '-GSM')) $link .= '-GSM';

            // Transform Customer if needed (example)
            $customer = strtoupper(trim($this->data['Customer']));

            // Handle Online_Date format safely
            $onlineDate = null;
            if (!empty($this->data['Online_Date'])) {
                try {
                    $onlineDate = Carbon::parse($this->data['Online_Date']);
                } catch (\Exception $e) {
                    Log::warning('Could not parse date: ' . $this->data['Online_Date']);
                }
            }

            $record->fill([
                'Site_Name' => $this->data['Site_Name'],
                'Branch' => $this->data['Branch'],
                'IP_Address' => $this->data['IP_Address'],
                'Vlan' => $this->data['Vlan'],
                'Controller' => $this->data['Controller'],
                'Customer' => $customer,
                'Link' => $link,
                'Online_Date' => $onlineDate,
                'Status' => strtoupper($this->data['Status']),
                'Keterangan' => $this->data['Keterangan'] ?? null,
            ]);

            Log::info('Processed record:', [
                'Site_ID' => $record->Site_ID,
                'Link' => $record->Link,
                'Status' => $record->Status,
            ]);

            return $record;
        } catch (\Exception $e) {
            Log::error('Error processing record: ' . $e->getMessage(), [
                'data' => $this->data,
                'error' => $e->getMessage(),
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

        $data['Customer'] = strtoupper(trim($data['Customer']));

        $data['Status'] = strtoupper(trim($data['Status']));

        return $data;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your Remote ATM BSI import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
