<?php

namespace App\Filament\AlfaLawson\Resources\TicketResource\Pages;

use App\Filament\AlfaLawson\Resources\TicketResource;
use App\Filament\Exports\TicketExport;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Builder;
use App\Models\AlfaLawson\Ticket;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    public bool $hasActiveFilters = false;

    public function getDefaultActiveTab(): ?string
    {
        return 'Active';
    }

    public function getTabs(): array
    {
        return [
            'Active' => Tab::make('Tiket Aktif')
                ->badge(fn () => Ticket::whereIn('Status', ['OPEN', 'PENDING'])->count())
                ->icon('heroicon-o-bell-alert')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('Status', ['OPEN', 'PENDING']))
                ->extraAttributes(['title' => 'Menampilkan tiket dengan status OPEN dan PENDING']),

            'Open' => Tab::make('Tiket Terbuka')
                ->badge(fn () => Ticket::where('Status', 'OPEN')->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-exclamation-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('Status', 'OPEN'))
                ->extraAttributes(['title' => 'Menampilkan tiket dengan status OPEN']),

            'Pending' => Tab::make('Tiket Pending')
                ->badge(fn () => Ticket::where('Status', 'PENDING')->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('Status', 'PENDING'))
                ->extraAttributes(['title' => 'Menampilkan tiket dengan status PENDING']),

            'Closed' => Tab::make('Tiket Selesai')
                ->badge(fn () => Ticket::where('Status', 'CLOSED')->count())
                ->badgeColor('success')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('Status', 'CLOSED'))
                ->extraAttributes(['title' => 'Menampilkan tiket yang sudah selesai (CLOSED)']),

            'All' => Tab::make('Semua Tiket')
                ->badge(fn () => Ticket::count())
                ->icon('heroicon-o-ticket')
                ->extraAttributes(['title' => 'Menampilkan semua tiket tanpa memandang status']),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create')
                ->label('Buat Tiket')
                ->url(static::getResource()::getUrl('create'))
                ->icon('heroicon-o-plus')
                ->iconPosition(IconPosition::Before)
                ->color('primary')
                ->tooltip('Buat tiket baru untuk melaporkan masalah'), // Tooltip untuk tombol "Buat Tiket"

            Actions\Action::make('export')
                ->label(fn () => $this->hasActiveFilters ? 'Ekspor Data Terfilter' : 'Ekspor Semua Data')
                ->color('success')
                ->icon('heroicon-o-arrow-down-tray')
                ->iconPosition(IconPosition::Before)
                ->tooltip(fn () => $this->hasActiveFilters 
                    ? 'Ekspor data tiket berdasarkan filter yang diterapkan' 
                    : 'Ekspor semua data tiket ke file Excel') // Tooltip dinamis untuk tombol "Ekspor"
                ->action(function () {
                    return $this->hasActiveFilters 
                        ? $this->exportFilteredData() 
                        : $this->exportAllData();
                }),
        ];
    }

    public function exportFilteredData()
    {
        $query = $this->getFilteredQuery();

        if ($query->count() === 0) {
            Notification::make()
                ->title('Tidak ada data yang difilter.')
                ->warning()
                ->send();
            return null;
        }

        $tickets = $query->get();

        $fileName = "laporan_tickets";
        
        $periodeFilter = $this->tableFilters['periode'] ?? [];
        $startDate = $periodeFilter['start_date'] ?? null;
        $endDate = $periodeFilter['end_date'] ?? null;
        
        if ($startDate) {
            $fileName .= "_from_" . Carbon::parse($startDate)->format('Y-m-d');
        }
        if ($endDate) {
            $fileName .= "_to_" . Carbon::parse($endDate)->format('Y-m-d');
        }
        
        $statusFilter = $this->tableFilters['Status'] ?? [];
        $status = $statusFilter['value'] ?? null;
        
        $problemFilter = $this->tableFilters['Problem'] ?? [];
        $problemType = is_array($problemFilter) ? ($problemFilter['value'] ?? null) : $problemFilter;
        
        if ($status) {
            $fileName .= "_{$status}";
        }
        if ($problemType) {
            $fileName .= "_{$problemType}";
        }
        $fileName .= ".xlsx";

        return Excel::download(new TicketExport($tickets), $fileName);
    }

    public function exportAllData()
    {
        $tickets = Ticket::all();
        return Excel::download(new TicketExport($tickets), 'laporan_tickets_all.xlsx');
    }

    public function mount(): void
    {
        parent::mount();
        $this->updateFiltersStatus();
    }

    public function updatedTableFilters(): void
    {
        parent::updatedTableFilters();
        $this->updateFiltersStatus();
    }

    protected function updateFiltersStatus(): void
    {
        $this->hasActiveFilters = false;

        if (isset($this->tableFilters['periode'])) {
            $periodeFilter = $this->tableFilters['periode'];
            if (!empty($periodeFilter['start_date']) || !empty($periodeFilter['end_date'])) {
                $this->hasActiveFilters = true;
            }
        }

        if (isset($this->tableFilters['Status']) && !empty($this->tableFilters['Status']['value'])) {
            $this->hasActiveFilters = true;
        }

        if (isset($this->tableFilters['Problem'])) {
            $problemFilter = $this->tableFilters['Problem'];
            if (is_array($problemFilter) && !empty($problemFilter['value'])) {
                $this->hasActiveFilters = true;
            } elseif (!is_array($problemFilter) && !empty($problemFilter)) {
                $this->hasActiveFilters = true;
            }
        }

        if (isset($this->tableFilters['Catagory']) && !empty($this->tableFilters['Catagory']['value'])) {
            $this->hasActiveFilters = true;
        }
    }

    protected function getFilteredQuery(): Builder
    {
        $query = $this->getTable()->getQuery();

        if (isset($this->tableFilters['Status']) && !empty($this->tableFilters['Status']['value'])) {
            $status = $this->tableFilters['Status']['value'];
            $query->whereIn('Status', (array) $status);
        }

        if (isset($this->tableFilters['Problem'])) {
            $problemFilter = $this->tableFilters['Problem'];
            if (is_array($problemFilter) && !empty($problemFilter['value'])) {
                $query->whereIn('Problem', (array) $problemFilter['value']);
            } elseif (!is_array($problemFilter) && !empty($problemFilter)) {
                $query->where('Problem', $problemFilter);
            }
        }

        if (isset($this->tableFilters['Catagory']) && !empty($this->tableFilters['Catagory']['value'])) {
            $catagory = $this->tableFilters['Catagory']['value'];
            $query->whereIn('Catagory', (array) $catagory);
        }

        if (isset($this->tableFilters['periode'])) {
            $periodeFilter = $this->tableFilters['periode'];
            
            if (!empty($periodeFilter['start_date'])) {
                $query->whereDate('created_at', '>=', $periodeFilter['start_date']);
            }
            
            if (!empty($periodeFilter['end_date'])) {
                $query->whereDate('created_at', '<=', $periodeFilter['end_date']);
            }
        }

        return $query;
    }

    protected function getTableFiltersFormColumns(): int
    {
        return 3;
    }

    protected function getTableFiltersFormWidth(): string
    {
        return '4xl';
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-ticket';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        $activeTab = $this->getActiveTab();
        
        if ($activeTab === 'Open') {
            return 'Tidak ada tiket terbuka';
        } elseif ($activeTab === 'Pending') {
            return 'Tidak ada tiket pending';
        } elseif ($activeTab === 'Closed') {
            return 'Tidak ada tiket yang selesai';
        } else {
            return 'Tidak ada tiket ditemukan';
        }
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        $activeTab = $this->getActiveTab();
        
        if ($activeTab === 'Open') {
            return 'Semua tiket sudah ditangani dengan baik.';
        } elseif ($activeTab === 'Pending') {
            return 'Tidak ada tiket yang sedang pending saat ini.';
        } elseif ($activeTab === 'Closed') {
            return 'Belum ada tiket yang diselesaikan.';
        } else {
            return 'Tiket tidak ditemukan. Silakan coba filter lain atau buat tiket baru.';
        }
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Actions\Action::make('create')
                ->label('Buat Tiket Baru')
                ->url(static::getResource()::getUrl('create'))
                ->icon('heroicon-o-plus')
                ->button()
                ->tooltip('Buat tiket baru untuk melaporkan masalah'), // Tooltip untuk aksi empty state
        ];
    }
}