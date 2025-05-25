<div class="space-y-4">
    @if($evidences && $evidences->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($evidences as $evidence)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-shadow">
                    {{-- File Preview --}}
                    <div class="mb-3">
                        @if($evidence->isImage())
                            <div class="relative group">
                                <img 
                                    src="{{ Storage::url($evidence->file_path) }}" 
                                    alt="{{ $evidence->file_name }}"
                                    class="w-full h-32 object-cover rounded cursor-pointer hover:opacity-75 transition-opacity"
                                    onclick="openImageModal('{{ Storage::url($evidence->file_path) }}', '{{ $evidence->file_name }}')"
                                >
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all rounded flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                    </svg>
                                </div>
                            </div>
                        @elseif($evidence->isVideo())
                            <div class="flex items-center justify-center h-32 bg-gray-100 dark:bg-gray-700 rounded">
                                <div class="text-center">
                                    <svg class="w-12 h-12 text-gray-400 dark:text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Video File</p>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-32 bg-gray-100 dark:bg-gray-700 rounded">
                                <div class="text-center">
                                    <svg class="w-12 h-12 text-gray-400 dark:text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Document</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- File Info --}}
                    <div class="space-y-2">
                        <h4 class="font-medium text-sm text-gray-900 dark:text-gray-100 truncate" title="{{ $evidence->file_name }}">
                            {{ $evidence->file_name }}
                        </h4>
                        
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ $evidence->formatted_file_size }}</span>
                            <span class="px-2 py-1 bg-{{ $evidence->upload_stage_color }}-100 dark:bg-{{ $evidence->upload_stage_color }}-900 text-{{ $evidence->upload_stage_color }}-800 dark:text-{{ $evidence->upload_stage_color }}-200 rounded-full">
                                {{ $evidence->upload_stage_label }}
                            </span>
                        </div>

                        @if($evidence->description)
                            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $evidence->description }}</p>
                        @endif

                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <div>Uploaded by: {{ $evidence->uploader->name ?? 'Unknown' }}</div>
                            <div>{{ $evidence->created_at->format('d/m/Y H:i') }}</div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-2 pt-2">
                            <a 
                                href="{{ Storage::url($evidence->file_path) }}" 
                                target="_blank" 
                                class="flex-1 text-center px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 transition-colors"
                            >
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                View
                            </a>
                            <a 
                                href="{{ Storage::url($evidence->file_path) }}" 
                                download="{{ $evidence->file_name }}"
                                class="flex-1 text-center px-3 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600 transition-colors"
                            >
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Download
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Evidence Statistics --}}
        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Evidence Summary</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div class="text-center">
                    <div class="font-semibold text-blue-600 dark:text-blue-400">{{ $evidences->where('file_type', 'image')->count() }}</div>
                    <div class="text-gray-600 dark:text-gray-300">Images</div>
                </div>
                <div class="text-center">
                    <div class="font-semibold text-purple-600 dark:text-purple-400">{{ $evidences->where('file_type', 'video')->count() }}</div>
                    <div class="text-gray-600 dark:text-gray-300">Videos</div>
                </div>
                <div class="text-center">
                    <div class="font-semibold text-green-600 dark:text-green-400">{{ $evidences->where('file_type', 'document')->count() }}</div>
                    <div class="text-gray-600 dark:text-gray-300">Documents</div>
                </div>
                <div class="text-center">
                    <div class="font-semibold text-gray-600 dark:text-gray-300">{{ $evidences->count() }}</div>
                    <div class="text-gray-600 dark:text-gray-300">Total Files</div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <svg class="w-12 h-12 text-gray-400 dark:text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="text-gray-500 dark:text-gray-400">No evidence uploaded yet</p>
        </div>
    @endif
</div>

{{-- Image Modal --}}
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 dark:bg-opacity-85 hidden z-50 flex items-center justify-center" onclick="closeImageModal()">
    <div class="max-w-4xl max-h-screen p-4">
        <img id="modalImage" src="" alt="" class="max-w-full max-h-full object-contain">
        <div class="text-center mt-4">
            <p id="modalTitle" class="text-white dark:text-gray-100 text-lg"></p>
            <button onclick="closeImageModal()" class="mt-2 px-4 py-2 bg-white dark:bg-gray-700 text-black dark:text-gray-100 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Close</button>
        </div>
    </div>
</div>

<script>
window.openImageModal = function(src, title) {
    document.getElementById('modalImage').src = src;
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('imageModal').classList.remove('hidden');
}

window.closeImageModal = function() {
    document.getElementById('imageModal').classList.add('hidden');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        window.closeImageModal();
    }
});
</script>