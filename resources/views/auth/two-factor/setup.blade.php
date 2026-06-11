@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">Configurar autenticacion de dos factores</div>

                <div class="card-body p-4">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <div class="row g-4 align-items-start">
                        <div class="col-md-6 text-center">
                            <h5 class="mb-3">Escanea este codigo QR</h5>
                            <div class="border rounded p-3 bg-light d-inline-block">
                                <img src="{{ $qrCode }}" alt="QR 2FA Google Authenticator" class="img-fluid" style="max-width: 250px;">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5 class="mb-3">Clave manual secret_2fa</h5>
                            <div class="input-group mb-3">
                                <input id="secret-key" type="text" class="form-control font-monospace" value="{{ $secret }}" readonly>
                                <button id="copy-secret" class="btn btn-outline-secondary" type="button">Copiar</button>
                            </div>

                            <form action="{{ route('two-factor.confirm') }}" method="POST">
                                @csrf

                                <label for="code" class="form-label">Codigo temporal de 6 digitos</label>
                                <input
                                    id="code"
                                    name="code"
                                    type="text"
                                    maxlength="6"
                                    pattern="\d{6}"
                                    autocomplete="one-time-code"
                                    class="form-control form-control-lg text-center font-monospace @error('code') is-invalid @enderror"
                                    required
                                    autofocus
                                >

                                @error('code')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror

                                <button type="submit" class="btn btn-primary w-100 mt-3">Verificar y activar 2FA</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const copyButton = document.getElementById('copy-secret');
    const secretInput = document.getElementById('secret-key');
    const codeInput = document.getElementById('code');

    copyButton?.addEventListener('click', function () {
        secretInput.select();
        document.execCommand('copy');
        copyButton.textContent = 'Copiado';
        setTimeout(function () {
            copyButton.textContent = 'Copiar';
        }, 1500);
    });

    codeInput?.addEventListener('input', function () {
        this.value = this.value.replace(/[^\d]/g, '').slice(0, 6);
    });
});
</script>
@endsection
