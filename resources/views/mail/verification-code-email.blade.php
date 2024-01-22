<x-mail::message>
<img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="{{ config('app.name') }}" width="360px">

Su código de verificación es:

# {{ $record->df_codigo }}

Si no intentaste iniciar sesión, no se requiere ninguna acción adicional.

Atentamente,<br>
{{ config('app.name') }} <br>
</x-mail::message>
