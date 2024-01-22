<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendRequest;
use App\Http\Requests\VerifyRequest;
use App\Models\TwoFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use OTPHP\TOTP;

class TwoFactorController extends Controller
{
    private function generateTwoFactorRecord($user, $email, $platform) {
        $otp = TOTP::generate();
        $record = TwoFactor::where([
            'df_usuario' => $user,
            'df_correo' => $email,
            'df_plataforma' => $platform,
        ])->first();

        if (!$record) {
            return TwoFactor::create([
                'df_codigo' => $otp->now(),
                'df_correo' => $email,
                'df_usuario' => $user,
                'df_plataforma' => $platform,
                'df_fecha_vencimiento' => now()->addMinutes(config('auth.otp.expire')),
            ]);
        }

        $record->update([
            'df_codigo' => $otp->now(),
            'df_fecha_vencimiento' => now()->addMinutes(config('auth.otp.expire')),
        ]);

        return $record;
    }

    public function send(SendRequest $request) {
        try {
            $record = $this->generateTwoFactorRecord($request->user, $request->email, $request->platform);
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
        $record = TwoFactor::where([
            'df_usuario' => $request->user,
            'df_plataforma' => $request->platform,
        ])->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un problema al intentar validar',
            ]);
        }

        if ($record->df_fecha_vencimiento > now()) {
            $record->update(['df_intentos' => $record->df_intentos + 1]);

            return response()->json([
                'success' => false,
                'message' => 'Su codigo de verificación ha expirado',
            ]);
        }

        if ($record->df_codigo != $request->code) {
            $record->update(['df_intentos' => $record->df_intentos + 1]);

            return response()->json([
                'success' => false,
                'message' => 'Su codigo de verificación es incorrecto',
            ]);
        }

        if ($record->df_intentos > config('auth.otp.limit')) {
            return response()->json([
                'success' => false,
                'message' => 'Ha superado el limite de intentos, por favor espere 5 minutos para volver a intentarlo',
            ]);
        }

        $record->delete();

        return response()->json([
            'success' => true,
            'message' => 'Su código de verificación es valido'
        ]);
    }
}
