<x-mail::message>
    # Olá, {{ $user->name }}!

    Seu usuário foi criado no sistema. Seguem seus dados de acesso:

    **Login:** {{ $user->email }}  
    **Senha:** {{ $password }}

    Obrigado,
    {{ config('app.name') }}
</x-mail::message>