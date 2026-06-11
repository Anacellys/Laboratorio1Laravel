/**
 * Valida por AJAX si el correo ya existe antes de enviar el registro.
 */
$(function () {
    const $correo = $('#correo');
    const $feedback = $('#correo-feedback');
    const $status = $('#correo-status');
    const $button = $('#register-button');

    if (!$correo.length || !$('#register-form').length) {
        return;
    }

    let timer = null;

    $correo.on('input', function () {
        const correo = $correo.val().trim();
        clearTimeout(timer);

        $correo.removeClass('is-valid is-invalid');
        $feedback.text('');
        $status.text('');
        $button.prop('disabled', false);

        if (!correo) {
            return;
        }

        timer = setTimeout(function () {
            $status.html('<span class="spinner-border spinner-border-sm me-1"></span>Verificando correo...');

            $.ajax({
                url: '/api/check-email',
                method: 'GET',
                dataType: 'json',
                data: { correo: correo },
                success: function (response) {
                    $status.text('');

                    if (response.exists) {
                        $correo.addClass('is-invalid');
                        $feedback.text(response.message);
                        $button.prop('disabled', true);
                        return;
                    }

                    $correo.addClass('is-valid');
                    $status.addClass('text-success').text('Correo disponible.');
                    $button.prop('disabled', false);
                },
                error: function () {
                    $status.text('');
                    $correo.addClass('is-invalid');
                    $feedback.text('No fue posible validar el correo.');
                },
            });
        }, 450);
    });
});
