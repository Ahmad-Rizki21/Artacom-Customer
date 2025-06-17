@props(['histories'])

<div class="space-y-6 text-black dark:text-white">
    @forelse ($histories as $history)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-4">
            <!-- Header: Action, Status, and Timestamp -->
            <div class="flex justify-between items-center mb-2">
                <div class="flex items-center space-x-2">
                    <!-- Action Icon -->
                    @if ($history->action === 'created')
                        <span class="text-green-500 dark:text-green-400">
                            <!-- SVG icon -->
                        </span>
                    @elseif ($history->action === 'updated')
                        <span class="text-blue-600 dark:text-blue-400">
                            <!-- SVG icon -->
                        </span>
                    @elseif ($history->action === 'deleted')
                        <span class="text-red-600 dark:text-red-400">
                            <!-- SVG icon -->
                        </span>
                    @endif

                    <!-- Action and Status -->
                    <div>
                        <span class="font-semibold capitalize">{{ $history->action }}</span>
                        @if ($history->status)
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 dark:bg-gray-700">
                                {{ $history->status }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="text-sm">
                    {{ $history->changed_at ? $history->changed_at->format('d M Y H:i') : 'N/A' }}
                </div>
            </div>

            <!-- Note -->
            @if ($history->note)
                <div class="mt-2 text-sm">
                    <span class="font-medium">Catatan:</span> {{ $history->note }}
                </div>
            @endif

            <!-- User -->
            <div class="mt-1 text-xs">
                <span class="font-medium">Diubah Oleh:</span> {{ $history->user->name ?? 'Unknown' }}
            </div>

            <!-- Comparison Table for Old and New Values (if updated) -->
            @if ($history->action === 'updated' && is_array($history->old_values) && is_array($history->new_values))
                <div class="mt-3 overflow-x-auto">
                    <span class="font-medium">Perubahan Data:</span>
                    <table class="min-w-full border border-gray-300 dark:border-gray-600 rounded-md mt-2">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700">
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase">Field</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase">Data Lama</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase">Data Baru</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (array_intersect_key($history->new_values, $history->old_values) as $key => $newValue)
                                @php
                                    $oldValue = $history->old_values[$key] ?? null;
                                    if ($oldValue !== $newValue && !in_array($key, ['updated_at', 'created_at'])) {
                                @endphp
                                    <tr class="border-t border-gray-300 dark:border-gray-600">
                                        <td class="px-4 py-2 text-sm capitalize">{{ $key }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $oldValue ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $newValue ?? '-' }}</td>
                                    </tr>
                                @php
                                    }
                                @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @empty
        <div class="text-center py-4">
            Tidak ada riwayat perubahan.
        </div>
    @endforelse
</div>
