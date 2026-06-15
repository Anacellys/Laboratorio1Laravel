@extends('layouts.app')

@section('content')

{{-- Estilos de la vista home --}}
<style>
    .home-wrapper {
        min-height: calc(100vh - 72px);
        background: #0d1117;
        background-image:
            radial-gradient(ellipse at 20% 20%, rgba(0,255,136,0.04) 0%, transparent 50%),
            radial-gradient(ellipse at 80% 80%, rgba(0,200,255,0.04) 0%, transparent 50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
        font-family: 'Courier New', 'Consolas', monospace;
        position: relative;
        overflow: hidden;
    }

    /* Fondo de matrix animado */
    .matrix-bg {
        position: absolute;
        inset: 0;
        pointer-events: none;
        overflow: hidden;
        opacity: 0.07;
        font-size: 13px;
        color: #00ff88;
        line-height: 1.4;
        word-break: break-all;
        padding: 1rem;
        white-space: pre-wrap;
        user-select: none;
    }

    /* Tarjeta principal */
    .home-card {
        position: relative;
        z-index: 10;
        background: rgba(13, 22, 30, 0.85);
        border: 1px solid rgba(0, 255, 136, 0.2);
        border-radius: 16px;
        padding: 2.5rem 3rem;
        width: 100%;
        max-width: 680px;
        box-shadow:
            0 0 0 1px rgba(0,255,136,0.05),
            0 20px 60px rgba(0,0,0,0.6),
            inset 0 1px 0 rgba(255,255,255,0.04);
        backdrop-filter: blur(12px);
    }

    /* Encabezado de bienvenida */
    .welcome-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid rgba(0,255,136,0.12);
    }

    .welcome-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(0,255,136,0.08);
        border: 1px solid rgba(0,255,136,0.25);
        color: #00ff88;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        padding: 4px 12px;
        border-radius: 20px;
        margin-bottom: 1rem;
    }

    .welcome-badge .dot {
        width: 6px;
        height: 6px;
        background: #00ff88;
        border-radius: 50%;
        animation: blink 1.5s ease-in-out infinite;
    }

    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.2; }
    }

    .welcome-title {
        color: #e6edf3;
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0 0 0.4rem;
        letter-spacing: -0.5px;
    }

    .welcome-title span {
        color: #00ff88;
    }

    .welcome-subtitle {
        color: #7d8590;
        font-size: 0.85rem;
        margin: 0;
    }

    /* Alertas de sesión */
    .session-alert {
        margin-bottom: 1.5rem;
        background: rgba(0,255,136,0.06);
        border: 1px solid rgba(0,255,136,0.2);
        border-radius: 8px;
        padding: 0.75rem 1rem;
        color: #00cc70;
        font-size: 0.82rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Tabla de info de sesión */
    .session-card {
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 1.75rem;
    }

    .session-card-header {
        background: rgba(0,255,136,0.06);
        border-bottom: 1px solid rgba(0,255,136,0.1);
        padding: 0.6rem 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .session-card-header span {
        color: #00cc70;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
    }

    .session-table {
        width: 100%;
        border-collapse: collapse;
    }

    .session-table tr {
        border-bottom: 1px solid rgba(255,255,255,0.04);
        transition: background 0.15s;
    }

    .session-table tr:last-child {
        border-bottom: none;
    }

    .session-table tr:hover {
        background: rgba(255,255,255,0.02);
    }

    .session-table td {
        padding: 0.65rem 1rem;
        font-size: 0.82rem;
        vertical-align: middle;
    }

    .session-table td:first-child {
        color: #7d8590;
        width: 40%;
        font-weight: 500;
        letter-spacing: 0.3px;
    }

    .session-table td:last-child {
        color: #cdd9e5;
        font-family: 'Courier New', monospace;
    }

    /* Badges de estado */
    .badge-verified {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(0,255,136,0.1);
        border: 1px solid rgba(0,255,136,0.3);
        color: #00cc70;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 4px;
    }

    .badge-active {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(0,180,255,0.1);
        border: 1px solid rgba(0,180,255,0.3);
        color: #38bdf8;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 4px;
    }

    .badge-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: currentColor;
        animation: blink 1.5s ease-in-out infinite;
    }

    /* Botón cerrar sesión */
    .btn-logout {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 0.75rem 1.5rem;
        background: transparent;
        border: 1px solid rgba(255, 80, 80, 0.35);
        color: #f87171;
        font-family: 'Courier New', monospace;
        font-size: 0.82rem;
        font-weight: 600;
        letter-spacing: 1px;
        text-transform: uppercase;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-logout:hover {
        background: rgba(255,80,80,0.08);
        border-color: rgba(255,80,80,0.6);
        color: #fca5a5;
        text-decoration: none;
    }

    .btn-logout:active {
        transform: scale(0.98);
    }

    /* Pie de tarjeta */
    .home-footer {
        text-align: center;
        margin-top: 1.75rem;
        color: rgba(125,133,144,0.5);
        font-size: 0.72rem;
        letter-spacing: 0.5px;
    }

    /* Corners decorativos */
    .corner {
        position: absolute;
        width: 14px;
        height: 14px;
        border-color: rgba(0,255,136,0.4);
        border-style: solid;
    }
    .corner-tl { top: -1px; left: -1px; border-width: 2px 0 0 2px; border-radius: 3px 0 0 0; }
    .corner-tr { top: -1px; right: -1px; border-width: 2px 2px 0 0; border-radius: 0 3px 0 0; }
    .corner-bl { bottom: -1px; left: -1px; border-width: 0 0 2px 2px; border-radius: 0 0 0 3px; }
    .corner-br { bottom: -1px; right: -1px; border-width: 0 2px 2px 0; border-radius: 0 0 3px 0; }
</style>

<div class="home-wrapper">

    {{-- Fondo decorativo de texto --}}
    <div class="matrix-bg" aria-hidden="true">{{ str_repeat("01001100 10110010 11001011 00101101 AUTH_OK 2FA_VERIFIED SESSION_ACTIVE CSRF_PROTECTED BCRYPT_12 TOTP_VALID ", 60) }}</div>

    <div class="home-card">

        {{-- Esquinas decorativas --}}
        <div class="corner corner-tl"></div>
        <div class="corner corner-tr"></div>
        <div class="corner corner-bl"></div>
        <div class="corner corner-br"></div>

        {{-- Encabezado --}}
        <div class="welcome-header">
            <div class="welcome-badge">
                <span class="dot"></span>
                Sesion activa con autenticacion de dos factores verificada
            </div>
            <h1 class="welcome-title">
                Bienvenido, <span>{{ Auth::user()->nombre }}</span>
            </h1>
            <p class="welcome-subtitle">
                Universidad Tecnológica de Panamá &mdash; SecureAuth 2FA
            </p>
        </div>

        {{-- Alerta de éxito de sesión --}}
        @if (session('success'))
            <div class="session-alert">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Información de sesión --}}
        <div class="session-card">
            <div class="session-card-header">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#00cc70" stroke-width="2.5" aria-hidden="true">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <span>Informacion de sesion</span>
            </div>
            <table class="session-table">
                <tr>
                    <td>Usuario</td>
                    <td>{{ Auth::user()->nombre }} {{ Auth::user()->apellido }}</td>
                </tr>
                <tr>
                    <td>Correo</td>
                    <td>{{ Auth::user()->correo }}</td>
                </tr>
                <tr>
                    <td>2FA</td>
                    <td>
                        <span class="badge-verified">
                            <span class="badge-dot"></span>
                            Verificado
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>CSRF</td>
                    <td>
                        <span class="badge-active">
                            <span class="badge-dot"></span>
                            Activo
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>Contrasena (Hash)</td>
                    <td style="font-size:0.70rem; color:#7ee787; word-break:break-all; line-height:1.6;">
                        {{ Auth::user()->HashMagic }}
                    </td>
                </tr>
                <tr>
                    <td>Ultimo acceso</td>
                    <td>{{ now()->format('d/m/Y H:i:s') }}</td>
                </tr>
            </table>
        </div>

        {{-- Botón cerrar sesión --}}
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn-logout">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Cerrar sesion
            </button>
        </form>

        {{-- Pie --}}
        <div class="home-footer">
            Desarrollo de Software VII &bull; UTP &bull; Junio 2026
        </div>

    </div>
</div>

@endsectionM