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
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </span>
                    @elseif ($history->action === 'updated')
                        <span class="text-blue-500 dark:text-blue-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </span>
                    @elseif ($history->action === 'deleted')
                        <span class="text-red-500 dark:text-red-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </span>
                    @endif

                    <!-- Action and Status -->
                    <div>
                        <span class="font-semibold capitalize">{{ $history->action }}</span>
                        @if ($history->status)
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700">
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
                    <span class="font-medium">Note:</span> {{ $history->note }}
                </div>
            @endif

            <!-- User -->
            <div class="mt-1 text-xs">
                <span class="font-medium">Updated by:</span> {{ $history->user->name ?? 'Unknown' }} ({{ $history->user->role ?? 'Admin' }})
            </div>

            <!-- Old Description (if updated and Deskripsi changed) -->
            @if ($history->action === 'updated' && isset($history->old_values['Deskripsi']) && array_key_exists('Deskripsi', $history->new_values) && $history->old_values['Deskripsi'] !== $history->new_values['Deskripsi'])
                <div class="mt-3 text-sm">
                    <span class="font-medium">Deskripsi Lama:</span>
                    <p class="mt-1 p-2 bg-gray-50 dark:bg-gray-700 rounded-md">
                        {{ $history->old_values['Deskripsi'] ?? 'Tidak ada deskripsi' }}
                    </p>
                </div>
            @endif

            <!-- Comparison Table for Old and New Values (if updated) -->
            @if ($history->action === 'updated' && is_array($history->old_values) && is_array($history->new_values))
                <div class="mt-3 overflow-x-auto">
                    <span class="font-medium">Perubahan Data:</span>
                    <table class="min-w-full border border-gray-200 dark:border-gray-700 rounded-md mt-2">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700">
                                <th class="px-4 py-2 text-left text-xs font-medium uppercase">Field</th>
                                <th class="px-4 py-2 text-left text-xs font-medium uppercase">Data Lama</th>
                                <th class="px-4 py-2 text-left text-xs font-medium uppercase">Data Baru</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (array_intersect_key($history->new_values, $history->old_values) as $key => $newValue)
                                @php
                                    $oldValue = $history->old_values[$key] ?? null;
                                    if ($key === 'SN') {
                                        $oldValue = strlen($oldValue) === 12 ? substr($oldValue, 0, 4) . '-' . substr($oldValue, 4, 4) . '-' . substr($oldValue, 8, 4) : $oldValue;
                                        $newValue = strlen($newValue) === 12 ? substr($newValue, 0, 4) . '-' . substr($newValue, 4, 4) . '-' . substr($newValue, 8, 4) : $newValue;
                                    }
                                    if ($key === 'tgl_beli') {
                                        $oldValue = $oldValue ? \Carbon\Carbon::parse($oldValue)->format('d M Y') : '-';
                                        $newValue = $newValue ? \Carbon\Carbon::parse($newValue)->format('d M Y') : '-';
                                    }
                                    if ($oldValue !== $newValue && !in_array($key, ['updated_at', 'created_at'])) {
                                @endphp
                                    <tr class="border-t border-gray-200 dark:border-gray-600">
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
