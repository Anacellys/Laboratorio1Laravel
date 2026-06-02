@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-shield-alt"></i> Verificación de Dos Factores
                    </h5>
                </div>

                <div class="card-body p-5">
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i>
                        <strong>Paso adicional de seguridad</strong>
                        Por favor ingresa el código de 6 dígitos de tu aplicación de autenticación.
                    </div>

                    <form action="{{ route('two-factor.validate-code') }}" method="POST" id="2fa-form">
                        @csrf

                        <div class="form-group mb-4">
                            <label for="code" class="form-label h5">
                                <strong>Código de autenticación:</strong>
                            </label>
                            <input
                                type="text"
                                id="code"
                                name="code"
                                class="form-control form-control-lg text-center font-monospace @error('code') is-invalid @enderror"
                                placeholder="000000"
                                maxlength="6"
                                pattern="\d{6}"
                                required
                                autofocus
                                autocomplete="off"
                                style="font-size: 1.5rem; letter-spacing: 0.3rem;"
                            >
                            @error('code')
                                <div class="invalid-feedback d-block mt-2">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                            <small class="form-text text-muted d-block mt-2">
                                Abre tu aplicación de autenticación y copia el código de 6 dígitos.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-success w-100 btn-lg mb-2">
                            <i class="fas fa-check-circle"></i> Verificar
                        </button>

                        <a href="{{ route('login') }}" class="btn btn-secondary w-100">
                            <i class="fas fa-arrow-left"></i> Volver a Login
                        </a>
                    </form>

                    <div class="mt-4 pt-3 border-top text-center">
                        <small class="text-muted">
                            <i class="fas fa-lock"></i>
                            Tu código es privado y seguro
                        </small>
                    </div>
                </div>
            </div>

            <!-- Card adicional con instrucciones -->
            <div class="card mt-4 border-0 bg-light">
                <div class="card-body p-3">
                    <h6 class="card-title mb-3">
                        <i class="fas fa-question-circle"></i> ¿No tienes acceso a tu código?
                    </h6>
                    <ul class="small mb-0">
                        <li>Verifica que tu dispositivo con la aplicación de autenticación esté sincronizado</li>
                        <li>Intenta con el siguiente código que aparecerá en 30 segundos</li>
                        <li>Si olvidaste tu clave, contacta al administrador</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validar entrada del código (solo dígitos)
    const codeInput = document.getElementById('code');
    if (codeInput) {
        codeInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^\d]/g, '').slice(0, 6);

            // Auto-submit si tiene 6 dígitos
            if (this.value.length === 6) {
                // Opcional: auto-submit después de 500ms
                setTimeout(() => {
                    document.getElementById('2fa-form').submit();
                }, 500);
            }
        });
    }
});
</script>
@endsection
