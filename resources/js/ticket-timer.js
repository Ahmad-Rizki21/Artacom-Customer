document.addEventListener('livewire:load', function () {
    let timerInterval;

    function updateTimers() {
        Livewire.emit('updateTimers');
    }

    // Start timer when page loads
    timerInterval = setInterval(updateTimers, 1000);

    // Clean up interval when leaving page
    window.addEventListener('beforeunload', function() {
        clearInterval(timerInterval);
    });
});