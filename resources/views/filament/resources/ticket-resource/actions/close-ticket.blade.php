<div>
    <p>Are you sure you want to close ticket #{{ $record->No_Ticket }}?</p>

    @if ($record->Action_Summry)
        <h3>Action Summary:</h3>
        <p>{{ $record->Action_Summry }}</p>
    @else
        <form wire:submit.prevent="updateActionSummary">
            <div>
                <label for="action_summary" class="block text-sm font-medium text-gray-700">Action Summary</label>
                <textarea
                    wire:model="actionSummary"
                    id="action_summary"
                    name="action_summary"
                    rows="3"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                    required
                ></textarea>
                @error('actionSummary') <span class="text-red-600">{{ $error }}</span> @enderror
            </div>
            <button type="submit" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Save and Close
            </button>
        </form>
    @endif
</div>