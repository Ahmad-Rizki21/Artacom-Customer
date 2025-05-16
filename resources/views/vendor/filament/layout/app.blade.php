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