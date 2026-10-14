@component('mail::message')
# Welcome, {{ $user->name }}!

An account has been created for you on **{{ config('app.name') }}**.

- **Email:** {{ $user->email }}
- **Role:** {{ ucfirst($user->role) }}

@component('mail::button', ['url' => $loginUrl])
Log in now
@endcomponent

If you weren't expecting this, please contact your administrator.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
