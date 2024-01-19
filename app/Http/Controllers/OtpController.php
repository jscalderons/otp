<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use OTPHP\TOTP;

class OtpController extends Controller
{
    public function send() {
        $otp = TOTP::generate();

        $to = "jhonathan.calderon@synlab.co";
        $code = $otp->now();

        // TODO: Guardar en la base de datos.

        $result = Mail::to($to)->send(new \App\Mail\VerificationCodeEmail($code));

        return response()->json([
            'success' => true,
            'message' => 'Se ha enviado un nuevo código de verificación a su correo electrónico',
        ]);
    }

    public function verify(Request $request) {
        $otp = $request->code;

        if ($otp == "123456") {
            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'OTP verification failed',
                'code' => $otp,
                'request' => $request->input(),
            ]);
        }
    }
}
