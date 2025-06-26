<?php

namespace App\Filament\Imports;

use App\Models\AlfaLawson\Maipu;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MaipuImportImporter extends Importer
{
    protected static ?string $model = Maipu::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('SN')
                ->label('Serial Number')
                ->rules(['required', 'string', 'max:32', 'unique:table_maipu,SN']),
            ImportColumn::make('Model')
                ->rules(['required', 'string', 'max:32']),
            ImportColumn::make('Kepemilikan')
                ->label('Ownership')
                ->rules(['required', 'string', 'max:32']),
            ImportColumn::make('tgl_beli')
                ->label('Purchase Date')
                ->rules(['nullable', 'date']),
            ImportColumn::make('garansi')
                ->label('Warranty Period')
                ->rules(['nullable', 'string', 'max:16']),
            ImportColumn::make('Site_ID')
                ->label('Site ID')
                ->rules(['nullable', 'string', 'max:16']),
            ImportColumn::make('Status')
                ->rules(['required', 'string', 'in:Operasional,Rusak,Sparepart,Perbaikan,Tidak Bisa Diperbaiki']),
            ImportColumn::make('Deskripsi')
                ->label('Description')
                ->rules(['nullable', 'string']),
        ];
    }

    public function resolveRecord(): ?Maipu
    {
        Log::info('Importing Maipu row:', $this->data);

        try {
            $sn = $this->data['SN'];
            // Normalisasi SN: hapus karakter non alphanumeric, lalu format dengan tanda '-'
            $snClean = preg_replace('/[^A-Za-z0-9]/', '', $sn);
            $snLength = strlen($snClean);

            if ($snLength >= 12) {
                $formattedSn = '';
                for ($i = 0; $i < $snLength; $i += 4) {
                    $chunk = substr($snClean, $i, 4);
                    $formattedSn .= $chunk;
                    if ($i + 4 < $snLength) {
                        $formattedSn .= '-';
                    }
                }
                $sn = $formattedSn;
            } else {
                $sn = $snClean;
            }

            if (Maipu::where('SN', $sn)->exists()) {
                Log::warning('Duplicate SN found, skipping: ' . $sn);
                return null;
            }

            $record = new Maipu();

            $purchaseDate = null;
            if (!empty($this->data['tgl_beli'])) {
                try {
                    $purchaseDate = Carbon::parse($this->data['tgl_beli']);
                } catch (\Exception $e) {
                    Log::warning('Could not parse purchase date: ' . $this->data['tgl_beli']);
                }
            }

            $status = trim($this->data['Status']);
            $validStatuses = ['Operasional', 'Rusak', 'Sparepart', 'Perbaikan', 'Tidak Bisa Diperbaiki'];
            if (!in_array($status, $validStatuses)) {
                $status = 'Operasional';
                Log::warning('Invalid Status value, defaulting to Operasional: ' . $this->data['Status']);
            }

            $record->fill([
                'SN' => $sn,
                'Model' => trim($this->data['Model']),
                'Kepemilikan' => strtoupper(trim($this->data['Kepemilikan'])),
                'tgl_beli' => $purchaseDate,
                'garansi' => $this->data['garansi'] ?? null,
                'Site_ID' => trim($this->data['Site_ID']),
                'Status' => $status,
                'Deskripsi' => $this->data['Deskripsi'] ?? null,
            ]);

            Log::info('Processed Maipu record:', [
                'SN' => $record->SN,
                'Site_ID' => $record->Site_ID,
                'Kepemilikan' => $record->Kepemilikan,
                'Status' => $record->Status,
                'Deskripsi' => $record->Deskripsi,
            ]);

            return $record;
        } catch (\Exception $e) {
            Log::error('Error processing Maipu record: ' . $e->getMessage(), [
                'data' => $this->data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function mutateBeforeCreate(array $data): array
    {
        // Normalisasi SN
        $snClean = preg_replace('/[^A-Za-z0-9]/', '', $data['SN']);
        $snLength = strlen($snClean);

        if ($snLength >= 12) {
            $formattedSn = '';
            for ($i = 0; $i < $snLength; $i += 4) {
                $chunk = substr($snClean, $i, 4);
                $formattedSn .= $chunk;
                if ($i + 4 < $snLength) {
                    $formattedSn .= '-';
                }
            }
            $data['SN'] = $formattedSn;
        } else {
            $data['SN'] = $snClean;
        }

        $data['Kepemilikan'] = strtoupper(trim($data['Kepemilikan']));

        $validStatuses = ['Operasional', 'Rusak', 'Sparepart', 'Perbaikan', 'Tidak Bisa Diperbaiki'];
        $data['Status'] = trim($data['Status']);
        if (!in_array($data['Status'], $validStatuses)) {
            $data['Status'] = 'Operasional';
        }

        $data['Model'] = trim($data['Model']);
        $data['Site_ID'] = trim($data['Site_ID']);

        return $data;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import perangkat Maipu selesai dengan ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diimpor.';
        }

        return $body;
    }
}
