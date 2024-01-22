<?php

namespace App\Http\Controllers;

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

    public function send(Request $request) {
        try {
            $record = $this->registerTwoFactor($request);

            Mail::to($request->email)->send(new \App\Mail\VerificationCodeEmail($record));

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

    // TODO: Realizar las validaciones
    public function verify(Request $request) {
        $result = TwoFactor::find($request->user);

        if ($result->df_codigo == $request->code && $result->df_fecha_vencimiento > now()) {
            return response()->json([
                'success' => true,
                'message' => 'Su código de verificación es valido'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Su codigo de verificación es incorrecto o ha expirado',
        ]);
    }
}
