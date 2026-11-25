document.querySelectorAll('[data-password-toggle]').forEach(function (toggle) {
    toggle.addEventListener('click', function () {
        const input = document.getElementById(toggle.getAttribute('aria-controls'));
        const isHidden = input.type === 'password';

        input.type = isHidden ? 'text' : 'password';
        toggle.textContent = isHidden ? 'Hide password' : 'Show password';
        toggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
    });
});
