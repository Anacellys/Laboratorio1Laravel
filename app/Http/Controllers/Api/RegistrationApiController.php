<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints AJAX para validar el correo durante el registro.
 */
class RegistrationApiController extends Controller
{
    /**
     * GET /api/check-email?correo=usuario@dominio.com
     */
    public function checkEmailExists(Request $request): JsonResponse
    {
        $request->validate([
            'correo' => ['required', 'email'],
        ]);

        $correo = (string) $request->query('correo');
        $exists = User::where('correo', $correo)->exists();

        return response()->json([
            'available' => !$exists,
            'exists' => $exists,
            'correo' => $correo,
            'message' => $exists ? 'Este correo ya esta registrado.' : 'Correo disponible.',
        ]);
    }

    /**
     * POST /api/validate-email, util si se prefiere validar con CSRF.
     */
    public function validateEmail(Request $request): JsonResponse
    {
        $request->validate([
            'correo' => ['required', 'email'],
        ]);

        $correo = (string) $request->input('correo');
        $exists = User::where('correo', $correo)->exists();

        if ($exists) {
            return response()->json([
                'valid' => false,
                'message' => 'Este correo ya esta registrado en el sistema.',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Correo valido y disponible.',
        ]);
    }
}
