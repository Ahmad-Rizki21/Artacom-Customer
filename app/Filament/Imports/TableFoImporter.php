<?php

namespace App\Filament\Imports;

use App\Models\AlfaLawson\TableFo;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TableFoImporter extends Importer
{
    protected static ?string $model = TableFo::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('CID')
                ->label('Connection ID')
                ->rules(['required', 'string', 'max:32']),

            ImportColumn::make('Provider')
                ->label('Provider Name')
                ->rules(['required', 'string']),

            ImportColumn::make('Register_Name')
                ->label('Register Name')
                ->rules(['nullable', 'string', 'max:32']),

            ImportColumn::make('Site_ID')
                ->label('Site ID')
                ->rules(['required', 'string', 'exists:table_remote,Site_ID']),

            ImportColumn::make('Status')
                ->label('Status')
                ->rules(['required', 'string', 'in:Active,Dismantle,Suspend,Not Active']),
        ];
    }

    public function resolveRecord(): ?TableFo
    {
        Log::info('Importing FO record:', $this->data);

        try {
            $record = TableFo::firstOrNew([
                'CID' => $this->data['CID'],
            ]);

            $record->fill([
                'Provider' => $this->data['Provider'],
                'Register_Name' => $this->data['Register_Name'] ?? null,
                'Site_ID' => $this->data['Site_ID'],
                'Status' => $this->data['Status'],
            ]);

            Log::info('Processed FO record:', [
                'CID' => $record->CID,
                'Site_ID' => $record->Site_ID,
                'Status' => $record->Status
            ]);

            return $record;
        } catch (\Exception $e) {
            Log::error('Error processing FO record: ' . $e->getMessage(), [
                'data' => $this->data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your fiber optic import has completed and ' . 
                number_format($import->successful_rows) . ' ' . 
                str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . 
                    str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}