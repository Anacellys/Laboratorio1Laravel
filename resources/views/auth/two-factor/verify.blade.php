@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">Verificacion 2FA</div>

                <div class="card-body p-4">
                    @if (session('info'))
                        <div class="alert alert-info">{{ session('info') }}</div>
                    @endif

                    <form action="{{ route('two-factor.validate-code') }}" method="POST">
                        @csrf

                        <label for="code" class="form-label">Codigo de Google Authenticator</label>
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

                        <button type="submit" class="btn btn-success w-100 mt-3">Validar codigo</button>
                        <a href="{{ route('login') }}" class="btn btn-outline-secondary w-100 mt-2">Volver</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const codeInput = document.getElementById('code');

    codeInput?.addEventListener('input', function () {
        this.value = this.value.replace(/[^\d]/g, '').slice(0, 6);
    });
});
</script>
@endsection
