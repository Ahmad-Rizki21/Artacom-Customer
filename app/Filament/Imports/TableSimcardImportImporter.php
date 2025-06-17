<?php

namespace App\Filament\Imports;

use App\Models\AlfaLawson\TableSimcard;
use App\Models\AlfaLawson\TableRemote;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Log;

class TableSimcardImportImporter extends Importer
{
    protected static ?string $model = TableSimcard::class;

    // Daftar status yang valid di sistem Anda (tambahkan sesuai kebutuhan)
    protected static array $validStatuses = [
        'active',
        'inactive',
        'gudang',  // Tambahkan status Gudang di sini (huruf kecil)
    ];

    // Mendefinisikan kolom-kolom yang diharapkan dari file CSV
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('Sim_Number')
                ->label('Nomor SIM')
                ->rules(['required', 'string', 'max:16', 'unique:table_simcard,Sim_Number']),
            ImportColumn::make('Provider')
                ->label('Provider')
                ->rules(['required', 'string', 'in:Telkomsel,Indosat,XL,Axis,Tri']),
            ImportColumn::make('Site_ID')
                ->label('Lokasi Toko')
                ->rules([
                    'nullable',
                    'string',
                    'max:255',
                    function (string $attribute, $value, \Closure $fail) {
                        // Abaikan validasi jika value kosong atau tanda '-'
                        if ($value !== null && $value !== '' && $value !== '-' && !TableRemote::where('Site_ID', $value)->exists()) {
                            $fail("Site_ID '$value' tidak ditemukan di tabel Remote.");
                        }
                    }
                ]),
            ImportColumn::make('Informasi_Tambahan')
                ->label('Catatan')
                ->rules(['nullable', 'string']),
            ImportColumn::make('SN_Card')
                ->label('Nomor Seri Kartu')
                ->rules(['nullable', 'string', 'max:25']),
            ImportColumn::make('Status')
                ->label('Status')
                ->rules(['required', 'string']), // Hilangkan rule 'in' supaya bisa terima status lain
        ];
    }

    // Logika untuk memproses setiap baris data dari CSV
    public function resolveRecord(): ?TableSimcard
    {
        Log::info('Importing SIM Card row:', $this->data);

        try {
            $simNumber = trim($this->data['Sim_Number']);

            // Cek apakah Sim_Number sudah ada (duplikat)
            if (TableSimcard::where('Sim_Number', $simNumber)->exists()) {
                Log::warning('Duplicate SIM Number found, skipping: ' . $simNumber);
                return null;
            }

            $record = new TableSimcard();

            // Normalisasi Status: lowercase dan cek validitas, default ke 'active'
            $statusRaw = trim($this->data['Status'] ?? '');
            $status = strtolower($statusRaw);
            if (!in_array($status, self::$validStatuses)) {
                Log::warning("Invalid Status value '{$statusRaw}', defaulting to active.");
                $status = 'active';
            }

            // Normalisasi Provider (jika perlu)
            $providerRaw = trim($this->data['Provider'] ?? '');
            $validProviders = ['Telkomsel', 'Indosat', 'XL', 'Axis', 'Tri'];
            $provider = in_array($providerRaw, $validProviders) ? $providerRaw : 'Telkomsel';

            // Normalisasi Site_ID: ubah '-' atau kosong jadi null
            $siteIdRaw = trim($this->data['Site_ID'] ?? '');
            $siteId = ($siteIdRaw === '' || $siteIdRaw === '-') ? null : $siteIdRaw;

            // Isi data ke record
            $record->fill([
                'Sim_Number' => $simNumber,
                'Provider' => $provider,
                'Site_ID' => $siteId,
                'Informasi_Tambahan' => $this->data['Informasi_Tambahan'] ?? null,
                'SN_Card' => $this->data['SN_Card'] ?? 'Tidak ada SN',
                'Status' => $status,
            ]);

            Log::info('Processed SIM Card record:', [
                'Sim_Number' => $record->Sim_Number,
                'Provider' => $record->Provider,
                'Site_ID' => $record->Site_ID,
                'Status' => $record->Status,
            ]);

            return $record;
        } catch (\Exception $e) {
            Log::error('Error processing SIM Card record: ' . $e->getMessage(), [
                'data' => $this->data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // Notifikasi setelah impor selesai
    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Impor data SIM Card telah selesai dan ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diimpor.';
        }

        return $body;
    }
}
