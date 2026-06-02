@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i> 2FA Ya Está Habilitado
                    </h5>
                </div>

                <div class="card-body p-5">
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle"></i>
                        <strong>¡Listo!</strong>
                        Tu autenticación de dos factores ya está activa y configurada correctamente.
                    </div>

                    <p class="lead">
                        Desde ahora, cada vez que inicies sesión necesitarás ingresar:
                    </p>

                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <strong>1.</strong> Tu correo y contraseña
                        </li>
                        <li class="mb-3">
                            <strong>2.</strong> El código de 6 dígitos de tu aplicación de autenticación
                        </li>
                    </ul>

                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i>
                        <strong>Recomendación:</strong>
                        Guarda tu clave secreta en un lugar seguro. Si pierdes acceso a tu aplicación de autenticación,
                        necesitarás esta clave para recuprar acceso a tu cuenta.
                    </div>

                    <a href="{{ route('home') }}" class="btn btn-primary w-100">
                        <i class="fas fa-home"></i> Ir a Inicio
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
