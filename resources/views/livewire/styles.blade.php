@livewireScripts
<script>
    window.addEventListener('livewire:load', function () {
        Livewire.on('notify', param => {
            window.$wireui.notify({
                title: param.message,
                type: param.type,
            });
        });
    });
</script>