document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contactForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        const loadingDiv = form.querySelector('.loading');
        const errorDiv = form.querySelector('.error-message');
        const successDiv = form.querySelector('.sent-message');

        // Reset messages
        loadingDiv.style.display = 'block';
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';
        submitButton.disabled = true;

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            loadingDiv.style.display = 'none';
            submitButton.disabled = false;

            if (data.status === 'success') {
                successDiv.style.display = 'block';
                form.reset();
            } else {
                errorDiv.textContent = data.message;
                errorDiv.style.display = 'block';
            }
        })
        .catch(error => {
            loadingDiv.style.display = 'none';
            submitButton.disabled = false;
            errorDiv.textContent = 'An error occurred. Please try again later.';
            errorDiv.style.display = 'block';
        });
    });
});
