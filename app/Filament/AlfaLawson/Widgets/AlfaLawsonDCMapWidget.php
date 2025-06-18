<?php

namespace App\Filament\AlfaLawson\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use App\Models\AlfaLawson\TableRemote;
use Illuminate\Support\Collection;

class AlfaLawsonDCMapWidget extends Widget
{
    protected static string $view = 'filament.widgets.alfa-lawson-dc-map-widget';
    
    protected static ?int $sort = 2;
    protected static bool $isLazy = false;
    protected int | string | array $columnSpan = 'full';
    
    public string $selectedFilter = 'Semua';
    public string $searchTerm = '';
    
    public function filterByType(string $type): void
    {
        $this->selectedFilter = $type;
        $this->dispatch('refreshMap');
    }

    public function updatedSearchTerm(): void
    {
        $this->dispatch('refreshMap');
    }
    
    /**
     * Ambil data lokasi DC unik dari database dengan jumlah remote, tipe customer, lat, lng
     * @return array
     */
    public function getDcLocations(): array
    {
        // Ambil data dari DB, group by DC, customer, lat, lng
        $query = TableRemote::query()
            ->selectRaw('DC as name, Customer as type, latitude as lat, longitude as lng, COUNT(*) as remote')
            ->groupBy('DC', 'Customer', 'latitude', 'longitude');

        // Filter berdasarkan tipe customer jika dipilih selain 'Semua'
        if ($this->selectedFilter !== 'Semua') {
            $query->where('Customer', $this->selectedFilter);
        }

        $locations = $query->get();

        // Filter search term jika ada
        if (!empty($this->searchTerm)) {
            $searchTerm = strtolower($this->searchTerm);
            $locations = $locations->filter(function ($loc) use ($searchTerm) {
                return str_contains(strtolower($loc->name), $searchTerm) ||
                       str_contains(strtolower($loc->type), $searchTerm) ||
                       str_contains((string)$loc->remote, $searchTerm);
            });
        }

        return $locations->values()->toArray();
    }
    
    public function render(): View
    {
        return view(static::$view, [
            'dcLocations' => $this->getDcLocations(),
        ]);
    }
}
