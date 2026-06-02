<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller para validaciones durante el registro
 *
 * Proporciona endpoints para:
 * - Validar disponibilidad de email (AJAX)
 * - Validar disponibilidad de username
 */
class RegistrationApiController extends Controller
{
    /**
     * Verifica si un email está disponible (no existe en BD)
     *
     * GET /api/check-email?email=usuario@ejemplo.com
     *
     * @param Request $request Debe contener 'email' en query string
     * @return JsonResponse JSON con disponibilidad
     */
    public function checkEmailExists(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->query('email');

        // Buscar si el email existe en la BD
        $exists = User::where('email', $email)->exists();

        return response()->json([
            'available' => !$exists,
            'exists' => $exists,
            'email' => $email,
            'message' => $exists ? 'Este correo ya está registrado.' : 'Correo disponible.',
        ]);
    }

    /**
     * Valida un email en tiempo real
     *
     * POST /api/validate-email
     *
     * @param Request $request Debe contener 'email'
     * @return JsonResponse JSON con resultado de validación
     */
    public function validateEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->input('email');

        // Validar que sea un email real
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'valid' => false,
                'message' => 'El formato del correo no es válido.',
            ], 422);
        }

        // Verificar si ya existe
        $exists = User::where('email', $email)->exists();

        if ($exists) {
            return response()->json([
                'valid' => false,
                'message' => 'Este correo ya está registrado en el sistema.',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Correo válido y disponible.',
        ]);
    }
}
