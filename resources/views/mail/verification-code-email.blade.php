<x-mail::message>
# Introduction

Tu código de verificación es: {{ $code }}

<x-mail::button :url="''">
Button Text
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
