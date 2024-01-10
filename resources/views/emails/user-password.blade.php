<x-mail::message>
    # Sua nova senha foi gerada!

    Use essa senha para ter acesso ao sistema Parthenon.

    Sua senha é: {{ $password }}

    Obrigado,<br>
    {{ config('app.name') }}
</x-mail::message>