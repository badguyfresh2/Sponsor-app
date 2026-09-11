// assets/js/app.js
document.addEventListener('DOMContentLoaded', () => {
    // General app initialization
    console.log('Sponsorship App Initialized');

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
