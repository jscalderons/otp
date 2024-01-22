<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendRequest;
use App\Http\Requests\VerifyRequest;
use App\Models\TwoFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use OTPHP\TOTP;

class TwoFactorController extends Controller
{
    private function registerTwoFactor(Request $request) {
        $otp = TOTP::generate();
        $result = TwoFactor::find($request->user);

        if (!$result) {
            return TwoFactor::create([
                'df_codigo' => $otp->now(),
                'df_correo' => $request->email,
                'df_usuario' => $request->user,
                'df_plataforma' => $request->plataforma,
                'df_fecha_vencimiento' => now()->addMinutes(5),
            ]);
        }

        $result->update([
            'df_codigo' => $otp->now(),
            'df_intentos' => $result->df_intentos + 1,
            'df_fecha_vencimiento' => now()->addMinutes(5),
        ]);

        return $result;
    }

    public function send(SendRequest $request) {
        try {
            $record = $this->registerTwoFactor($request);

            $result = Mail::to($request->email)->send(new \App\Mail\VerificationCodeEmail($record));

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ha ocurrido un error al enviar el correo electrónico',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Su código de verificación ha sido enviado a su correo electrónico',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error al enviar el código de verificación',
                'error' => $th->getMessage(),
            ]);
        }
    }

    /**
     * Valida el código de verificación enviado por correo electrónico
     *
     * @param VerifyRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(VerifyRequest $request) {
        $result = TwoFactor::find($request->user);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Validación invalida',
            ]);
        }

        if ($result->df_fecha_vencimiento < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Su codigo de verificación ha expirado',
            ]);
        }

        if ($result->df_codigo != $request->code) {
            return response()->json([
                'success' => false,
                'message' => 'Su codigo de verificación es incorrecto o ha expirado',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Su código de verificación es valido'
        ]);
    }
}
