/**
 * Validación de email mediante AJAX
 * Valida en tiempo real si un email ya existe en la base de datos
 */

document.addEventListener('DOMContentLoaded', function() {
    // Obtener el input de email del formulario de registro
    const emailInput = document.getElementById('email');
    const emailFeedback = document.getElementById('email-feedback');
    const emailStatus = document.getElementById('email-status');

    if (emailInput && emailFeedback) {
        let validationTimeout;

        // Validar email cuando el usuario deja de escribir
        emailInput.addEventListener('input', function() {
            const email = this.value.trim();

            // Limpiar timeout anterior
            clearTimeout(validationTimeout);

            // Si el input está vacío, limpiar feedback
            if (!email) {
                this.classList.remove('is-invalid', 'is-valid');
                emailFeedback.innerHTML = '';
                emailStatus.innerHTML = '';
                return;
            }

            // Validar formato de email básico
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
                emailFeedback.innerHTML = '<i class="fas fa-exclamation-circle"></i> Formato de correo inválido.';
                emailStatus.innerHTML = '';
                return;
            }

            // Mostrar loader
            this.classList.remove('is-invalid', 'is-valid');
            emailStatus.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verificando...';
            emailFeedback.innerHTML = '';

            // Esperar 500ms después de que el usuario deja de escribir
            validationTimeout = setTimeout(() => {
                validateEmailAjax(email, emailInput, emailFeedback, emailStatus);
            }, 500);
        });

        // Limpiar validación cuando el input pierde el foco
        emailInput.addEventListener('blur', function() {
            clearTimeout(validationTimeout);
        });
    }
});

/**
 * Realiza la validación AJAX del email
 * @param {string} email - Email a validar
 * @param {HTMLElement} emailInput - Input del email
 * @param {HTMLElement} emailFeedback - Contenedor para mensajes de error
 * @param {HTMLElement} emailStatus - Contenedor para estado de validación
 */
function validateEmailAjax(email, emailInput, emailFeedback, emailStatus) {
    fetch(`/api/check-email?email=${encodeURIComponent(email)}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
    .then(response => response.json())
    .then(data => {
        // Limpiar estado previo
        emailInput.classList.remove('is-invalid', 'is-valid');
        emailFeedback.innerHTML = '';
        emailStatus.innerHTML = '';

        if (data.exists) {
            // Email ya existe
            emailInput.classList.add('is-invalid');
            emailFeedback.innerHTML = `
                <i class="fas fa-times-circle"></i>
                ${data.message}
            `;
        } else {
            // Email disponible
            emailInput.classList.add('is-valid');
            emailStatus.innerHTML = `
                <i class="fas fa-check-circle text-success"></i>
                Correo disponible
            `;
        }
    })
    .catch(error => {
        console.error('Error validating email:', error);
        emailInput.classList.add('is-invalid');
        emailFeedback.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error al validar el correo.';
    });
}

/**
 * Validación de contraseña en tiempo real
 * Muestra requisitos cumplidos y no cumplidos
 */
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const passwordRequirements = document.getElementById('password-requirements');

    if (passwordInput && passwordRequirements) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const requirements = {
                'length': password.length >= 8,
                'uppercase': /[A-Z]/.test(password),
                'lowercase': /[a-z]/.test(password),
                'number': /\d/.test(password),
                'special': /[!@#$%^&*]/.test(password),
            };

            let html = '<div class="mt-2"><small><strong>Requisitos:</strong></div>';

            // Longitud
            html += `<div class="requirement-item">
                <i class="fas fa-${requirements.length ? 'check-circle text-success' : 'circle text-muted'}"></i>
                Al menos 8 caracteres
            </div>`;

            // Mayúscula
            html += `<div class="requirement-item">
                <i class="fas fa-${requirements.uppercase ? 'check-circle text-success' : 'circle text-muted'}"></i>
                Una letra mayúscula
            </div>`;

            // Minúscula
            html += `<div class="requirement-item">
                <i class="fas fa-${requirements.lowercase ? 'check-circle text-success' : 'circle text-muted'}"></i>
                Una letra minúscula
            </div>`;

            // Número
            html += `<div class="requirement-item">
                <i class="fas fa-${requirements.number ? 'check-circle text-success' : 'circle text-muted'}"></i>
                Un número
            </div>`;

            // Carácter especial (opcional)
            html += `<div class="requirement-item">
                <i class="fas fa-${requirements.special ? 'check-circle text-success' : 'circle text-muted'}"></i>
                Un carácter especial (!@#$%^&*) - Opcional
            </div>`;

            html += '</div>';

            passwordRequirements.innerHTML = html;
        });
    }
});
