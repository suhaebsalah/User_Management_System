document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.login-form');
    if (!form) {
        return;
    }

    const usernameInput = form.querySelector('#username');
    const passwordInput = form.querySelector('#password');
    const submitButton = form.querySelector('.login-btn');
    const formAlert = form.querySelector('.login-form-alert');

    function setFieldError(input, message) {
        const group = input.closest('.form-group');
        if (!group) {
            return;
        }

        const errorEl = group.querySelector('.error-message');
        if (!errorEl) {
            return;
        }

        errorEl.textContent = message || '';
        errorEl.classList.toggle('is-hidden', !message);
    }

    function clearFormAlert() {
        if (!formAlert) {
            return;
        }

        formAlert.textContent = '';
        formAlert.style.display = 'none';
    }

    function showFormAlert(message) {
        if (!formAlert) {
            return;
        }

        formAlert.textContent = message || '';
        formAlert.style.display = message ? 'block' : 'none';
    }

    function clearErrors() {
        setFieldError(usernameInput, '');
        setFieldError(passwordInput, '');
        clearFormAlert();
    }

    function validateEmptyFields() {
        const username = (usernameInput.value || '').trim();
        const password = (passwordInput.value || '').trim();
        let hasErrors = false;

        if (!username) {
            setFieldError(usernameInput, 'Please, the input is empty.');
            hasErrors = true;
        } else {
            setFieldError(usernameInput, '');
        }

        if (!password) {
            setFieldError(passwordInput, 'Please, the input is empty.');
            hasErrors = true;
        } else {
            setFieldError(passwordInput, '');
        }

        return !hasErrors;
    }

    usernameInput.addEventListener('input', function () {
        setFieldError(usernameInput, '');
        clearFormAlert();
    });

    passwordInput.addEventListener('input', function () {
        setFieldError(passwordInput, '');
        clearFormAlert();
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearFormAlert();

        if (!validateEmptyFields()) {
            return;
        }

        const formData = new FormData(form);
        formData.append('login_admin', '1');

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.classList.add('is-loading');
        }

        try {
            const response = await fetch(form.action || window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success' && data.redirect) {
                window.location.href = data.redirect;
                return;
            }

            if (data.errors) {
                setFieldError(usernameInput, data.errors.username || '');
                setFieldError(passwordInput, data.errors.password || '');
                if (data.errors.form) {
                    showFormAlert(data.errors.form);
                }
                return;
            }

            showFormAlert(data.message || 'Login failed.');
        } catch (error) {
            showFormAlert('Could not submit the form. Please try again.');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.classList.remove('is-loading');
            }
        }
    });
});