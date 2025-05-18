<div>
    @if (session()->has('message'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
            {{ session('error') }}
        </div>
    @endif

    <!-- Modal -->
    @if($isOpen)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Edit Action</h2>
                <button wire:click="closeModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit.prevent="save">
                <div class="mb-4">
                    <label for="action_taken" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Action Status</label>
                    <select wire:model="actionTaken" id="action_taken" class="w-full p-2 border rounded-md bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="Start Clock">Start Clock</option>
                        <option value="Pending Clock">Pending Clock</option>
                        <option value="Closed">Closed</option>
                        <option value="Note">Note</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="action_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea wire:model="actionDescription" id="action_description" class="w-full p-2 border rounded-md bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" rows="4" required></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:ring-2 focus:ring-blue-400">Save</button>
                    <button type="button" wire:click="closeModal" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:ring-2 focus:ring-gray-400">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>