@props(['histories'])

<div class="space-y-6">
    @forelse ($histories as $history)
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 dark:bg-gray-900 dark:border-gray-700">
            <div class="flex justify-between items-center mb-2">
                <div class="flex items-center space-x-2">
                    @if ($history->action === 'created')
                        <span class="text-green-600 dark:text-green-400" title="Created">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </span>
                    @elseif ($history->action === 'updated')
                        <span class="text-blue-600 dark:text-blue-400" title="Updated">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </span>
                    @elseif ($history->action === 'deleted')
                        <span class="text-red-600 dark:text-red-400" title="Deleted">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </span>
                    @endif

                    <div>
                        <span class="font-semibold text-black dark:text-white capitalize">{{ ucfirst($history->action) }}</span>
                        @if ($history->status)
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 dark:bg-gray-600 text-black dark:text-white">
                                {{ strtoupper($history->status) }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $history->changed_at ? $history->changed_at->format('d M Y') : 'N/A' }}
                </div>
            </div>

            @if ($history->note)
                <div class="mt-2 text-sm text-gray-700 dark:text-white">
                    <span class="font-medium text-black dark:text-white">Catatan:</span> {{ $history->note }}
                </div>
            @endif

            <div class="mt-1 text-xs text-gray-600 dark:text-white">
                <span class="font-medium text-black dark:text-white">Diubah Oleh:</span> {{ $history->user->name ?? 'Unknown' }}
            </div>

            @if ($history->action === 'updated' && is_array($history->old_values) && is_array($history->new_values))
                <div class="mt-3 overflow-x-auto">
                    <span class="font-medium text-black dark:text-white">Perubahan Data:</span>
                    <table class="min-w-full border border-gray-300 dark:border-gray-600 rounded-md mt-2">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-800">
                                <th class="px-4 py-2 text-left text-xs font-medium text-black dark:text-white uppercase">Field</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-black dark:text-white uppercase">Data Lama</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-black dark:text-white uppercase">Data Baru</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (array_intersect_key($history->new_values, $history->old_values) as $key => $newValue)
                                @php
                                    $oldValue = $history->old_values[$key] ?? null;
                                @endphp
                                @if ($oldValue !== $newValue && !in_array($key, ['updated_at', 'created_at']))
                                    <tr class="border-t border-gray-300 dark:border-gray-600">
                                        <td class="px-4 py-2 text-sm text-gray-700 dark:text-white capitalize">{{ str_replace('_', ' ', $key) }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-white">
                                            {{ strpos($key, 'Online_Date') !== false && $oldValue ? \Carbon\Carbon::parse($oldValue)->format('d M Y') : ($oldValue ?? '-') }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-white">
                                            {{ strpos($key, 'Online_Date') !== false && $newValue ? \Carbon\Carbon::parse($newValue)->format('d M Y') : ($newValue ?? '-') }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @empty
        <div class="text-center text-gray-600 dark:text-white py-4">
            Tidak ada riwayat perubahan.
        </div>
    @endforelse
</div>