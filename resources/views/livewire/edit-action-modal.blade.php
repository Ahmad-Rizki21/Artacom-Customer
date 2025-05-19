<div>
    @if (session()->has('message'))
        <div class="fi-notification fi-notification-success pointer-events-auto relative flex w-80 rounded-xl bg-white p-4 shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-notification-icon flex flex-shrink-0 items-center justify-center rounded-full bg-success-500/10 p-2 text-success-500 dark:bg-success-500/20">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ml-3 w-0 flex-1">
                <p class="text-sm font-medium text-gray-950 dark:text-white">{{ session('message') }}</p>
            </div>
            <div class="ml-4 flex flex-shrink-0">
                <button type="button" class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="fi-notification fi-notification-danger pointer-events-auto relative flex w-80 rounded-xl bg-white p-4 shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-notification-icon flex flex-shrink-0 items-center justify-center rounded-full bg-danger-500/10 p-2 text-danger-500 dark:bg-danger-500/20">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <div class="ml-3 w-0 flex-1">
                <p class="text-sm font-medium text-gray-950 dark:text-white">{{ session('error') }}</p>
            </div>
            <div class="ml-4 flex flex-shrink-0">
                <button type="button" class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <!-- Modal -->
    @if($isOpen)
    <div 
        x-data="{}"
        x-on:keydown.escape.window="$wire.closeModal()"
        class="fi-modal-window fixed inset-0 z-50 grid h-screen w-screen place-items-center bg-black/50 dark:bg-white/10"
        role="dialog"
        aria-modal="true"
    >
        <div
            x-trap.inert.noscroll="true"
            class="fi-modal flex w-full max-h-[90vh] max-w-md flex-col bg-white shadow-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 rounded-xl overflow-hidden"
        >
            <div class="fi-modal-header flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-white/10">
                <h3 class="fi-modal-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Edit Action
                </h3>

                <button 
                    wire:click="closeModal"
                    type="button" 
                    class="fi-modal-close-btn -m-3 p-3 text-gray-400 hover:text-gray-500"
                >
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="save" class="flex flex-col gap-y-4">
                <div class="fi-modal-content space-y-4 p-6">
                    <div class="fi-form-component">
                        <div class="fi-form-field-wrp">
                            <div class="grid gap-y-2">
                                <div class="flex items-center justify-between gap-x-3">
                                    <label for="action_taken" class="fi-form-field-label inline-flex items-center gap-x-3 text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                        <span>Action Status</span>
                                    </label>
                                </div>

                                <div class="grid gap-y-2">
                                    <div>
                                        <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white focus-within:ring-2 dark:bg-white/5 ring-gray-950/10 focus-within:ring-primary-600 dark:ring-white/20 dark:focus-within:ring-primary-500">
                                            <div class="min-w-0 flex-1">
                                                <select 
                                                    wire:model="actionTaken" 
                                                    id="action_taken"
                                                    class="fi-select-input block w-full border-0 bg-transparent py-1.5 text-base text-gray-950 focus:ring-0 dark:text-white sm:text-sm sm:leading-6 px-3"
                                                >
                                                    <option value="Start Clock">Start Clock</option>
                                                    <option value="Pending Clock">Pending Clock</option>
                                                    <option value="Closed">Closed</option>
                                                    <option value="Note">Note</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="fi-form-component">
                        <div class="fi-form-field-wrp">
                            <div class="grid gap-y-2">
                                <div class="flex items-center justify-between gap-x-3">
                                    <label for="action_description" class="fi-form-field-label inline-flex items-center gap-x-3 text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                        <span>Description</span>
                                    </label>
                                </div>

                                <div class="grid gap-y-2">
                                    <div>
                                        <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white focus-within:ring-2 dark:bg-white/5 ring-gray-950/10 focus-within:ring-primary-600 dark:ring-white/20 dark:focus-within:ring-primary-500">
                                            <div class="min-w-0 flex-1">
                                                <textarea
                                                    wire:model="actionDescription"
                                                    id="action_description"
                                                    class="fi-textarea block w-full resize-y border-0 bg-transparent px-3 py-1.5 text-base text-gray-950 focus:ring-0 dark:text-white sm:text-sm sm:leading-6"
                                                    rows="4"
                                                    required
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fi-modal-footer flex items-center justify-end gap-x-3 bg-gray-50 px-6 py-3 dark:bg-white/5">
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="fi-btn relative flex items-center justify-center rounded-lg font-semibold outline-none transition duration-75 focus:ring-2 dark:focus:ring-offset-0 fi-color-custom fi-btn-color-secondary fi-size-md gap-1.5 px-3 py-2 text-sm inline-flex bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20"
                    >
                        <span class="fi-btn-label">
                            Cancel
                        </span>
                    </button>

                    <button
                        type="submit"
                        class="fi-btn relative flex items-center justify-center rounded-lg font-semibold outline-none transition duration-75 focus:ring-2 dark:focus:ring-offset-0 fi-color-custom fi-btn-color-primary fi-size-md gap-1.5 px-3 py-2 text-sm inline-flex bg-primary-600 text-white hover:bg-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400"
                    >
                        <span class="fi-btn-label">
                            Save
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>