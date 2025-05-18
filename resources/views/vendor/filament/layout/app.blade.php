@livewireStyles
@livewireScripts
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@pushOnce('scripts')
    @if(request()->routeIs('filament.resources.tickets.view'))
    <script>
        document.addEventListener('livewire:initialized', () => {
            setInterval(() => {
                Livewire.dispatch('refresh');
            }, 1000);
        });
    </script>
    @endif
@endPushOnce

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('trigger-edit-action', (data) => {
            @this.triggerEditAction(data.actionId);
        });
    });
</script>