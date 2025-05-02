<?php

namespace App\Filament\AlfaLawson\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class AlfaLawsonDCMapWidget extends Widget
{
    protected static string $view = 'filament.widgets.alfa-lawson-dc-map-widget';
    
    protected static ?int $sort = 1;
    protected static bool $isLazy = false;
    protected int | string | array $columnSpan = 'full';
    
    public string $selectedFilter = 'Semua';
    public string $searchTerm = '';
    
    public function getDcLocations(): array
    {
        $locations = $this->getAllDcLocations();
        
        if ($this->selectedFilter !== 'Semua') {
            $locations = array_filter($locations, fn($loc) => $loc['type'] === $this->selectedFilter);
        }
        
        if (!empty($this->searchTerm)) {
            $searchTerm = strtolower($this->searchTerm);
            $locations = array_filter($locations, function($loc) use ($searchTerm) {
                return str_contains(strtolower($loc['name']), $searchTerm) || 
                       str_contains(strtolower($loc['type']), $searchTerm) ||
                       str_contains(strtolower($loc['remote']), $searchTerm);
            });
        }
        
        return array_values($locations);
    }
    
    private function getAllDcLocations(): array
    {
        return [
            [
                'name' => 'DC Jakarta',
                'type' => 'Alfamart',
                'remote' => 120,
                'lat' => -6.2088,
                'lng' => 106.8456,
            ],
            [
                'name' => 'DC Surabaya',
                'type' => 'Alfamart',
                'remote' => 85,
                'lat' => -7.2575,
                'lng' => 112.7521,
            ],
            [
                'name' => 'DC Bandung',
                'type' => 'Lawson',
                'remote' => 65,
                'lat' => -6.9175,
                'lng' => 107.6191,
            ],
            [
                'name' => 'DC Medan',
                'type' => 'Alfamart',
                'remote' => 50, 
                'lat' => 3.5952,
                'lng' => 98.6722,
            ],
            [
                'name' => 'DC Makassar',
                'type' => 'Lawson',
                'remote' => 45,
                'lat' => -5.1477,
                'lng' => 119.4327,
            ],
        ];
    }
    
    public function filterByType(string $type): void
    {
        $this->selectedFilter = $type;
    }
    
    public function render(): View
    {
        return view(static::$view, [
            'dcLocations' => $this->getDcLocations(),
        ]);
    }
}