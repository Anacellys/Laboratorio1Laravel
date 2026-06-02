@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-lock"></i> Configurar Autenticación de Dos Factores (2FA)
                    </h5>
                </div>

                <div class="card-body p-5">
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i>
                        <strong>¿Qué es 2FA?</strong>
                        La autenticación de dos factores proporciona seguridad adicional. Además de tu contraseña,
                        necesitarás un código temporal generado por Google Authenticator.
                    </div>

                    <div class="row">
                        <!-- Sección del QR Code -->
                        <div class="col-md-6 text-center mb-4">
                            <h6 class="mb-3">
                                <strong>1. Escanea este código QR</strong>
                            </h6>
                            <div class="border p-3 bg-light" style="display: inline-block;">
                                @if($qrCode)
                                    <img src="{{ $qrCode }}" alt="QR Code" class="img-fluid" style="max-width: 250px;">
                                @else
                                    <p class="text-danger">Error al generar QR code</p>
                                @endif
                            </div>
                            <p class="text-muted mt-3 small">
                                Usa Google Authenticator, Microsoft Authenticator o cualquier app compatible
                            </p>
                        </div>

                        <!-- Sección del Secret Manual -->
                        <div class="col-md-6">
                            <h6 class="mb-3">
                                <strong>2. O ingresa este código manualmente</strong>
                            </h6>
                            <div class="form-group">
                                <label for="secret-key" class="form-label">Clave secreta (guardar en lugar seguro):</label>
                                <div class="input-group">
                                    <input
                                        type="text"
                                        id="secret-key"
                                        class="form-control font-monospace bg-light"
                                        value="{{ $secret }}"
                                        readonly
                                    >
                                    <button
                                        class="btn btn-outline-secondary"
                                        type="button"
                                        id="copy-secret"
                                        title="Copiar al portapapeles"
                                    >
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted d-block mt-2">
                                    ⚠️ Guarda esta clave en un lugar seguro. La necesitarás si pierdes acceso a tu aplicación.
                                </small>
                            </div>

                            <h6 class="mt-4 mb-3">
                                <strong>3. Verifica ingresando un código</strong>
                            </h6>

                            <!-- Formulario de verificación -->
                            <form action="{{ route('two-factor.confirm') }}" method="POST">
                                @csrf

                                <div class="form-group mb-3">
                                    <label for="code" class="form-label">Código de verificación (6 dígitos):</label>
                                    <input
                                        type="text"
                                        id="code"
                                        name="code"
                                        class="form-control form-control-lg font-monospace text-center @error('code') is-invalid @enderror"
                                        placeholder="000000"
                                        maxlength="6"
                                        pattern="\d{6}"
                                        required
                                        autofocus
                                    >
                                    @error('code')
                                        <div class="invalid-feedback d-block">
                                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                        </div>
                                    @enderror
                                    <small class="form-text text-muted d-block mt-2">
                                        Ingresa el código de 6 dígitos que ves en tu aplicación de autenticación.
                                    </small>
                                </div>

                                <button type="submit" class="btn btn-primary btn-block w-100">
                                    <i class="fas fa-check-circle"></i> Verificar y Habilitar 2FA
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-4" role="alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Importante:</strong>
                        Si pierdes acceso a tu aplicación de autenticación, no podrás acceder a tu cuenta.
                        Por favor, guarda la clave secreta en un lugar seguro.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copiar clave secreta al portapapeles
    const copyBtn = document.getElementById('copy-secret');
    const secretInput = document.getElementById('secret-key');

    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            secretInput.select();
            document.execCommand('copy');

            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="fas fa-check"></i>';
            copyBtn.classList.add('btn-success');
            copyBtn.classList.remove('btn-outline-secondary');

            setTimeout(() => {
                copyBtn.innerHTML = originalText;
                copyBtn.classList.remove('btn-success');
                copyBtn.classList.add('btn-outline-secondary');
            }, 2000);
        });
    }

    // Validar entrada del código (solo dígitos)
    const codeInput = document.getElementById('code');
    if (codeInput) {
        codeInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^\d]/g, '').slice(0, 6);
        });
    }
});
</script>
@endsection
