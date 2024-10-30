

document.addEventListener('DOMContentLoaded', (event) => {
    const calendarEvents = document.querySelectorAll('.lbec-calendar-event a');
    calendarEvents.forEach(function(event) {
        event.addEventListener('click', function(e) {
            const target = e.target;

            if (target.classList.contains('open')) {
                return true;
            }
            e.preventDefault();
            target.classList.add('open');
        });
    });
});
