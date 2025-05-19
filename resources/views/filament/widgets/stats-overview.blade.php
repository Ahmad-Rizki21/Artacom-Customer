<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @foreach ($this->getStats() as $stat)
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="text-sm font-medium text-gray-600">{{ $stat->label }}</h3>
            <p class="text-2xl font-bold mt-2">{{ $stat->value }}</p>
            <p class="text-sm mt-2 flex items-center text-gray-500">
                @if ($stat->descriptionIcon)
                    <span class="mr-1">
                        <x-dynamic-component :component="$stat->descriptionIcon" class="w-4 h-4" />
                    </span>
                @endif
                <span class="{{ $stat->color === 'danger' ? 'text-red-500' : ($stat->color === 'warning' ? 'text-yellow-500' : 'text-green-500') }}">
                    {{ $stat->description }}
                </span>
            </p>
        </div>
    @endforeach
</div>