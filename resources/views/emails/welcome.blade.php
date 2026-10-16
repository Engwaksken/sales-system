@component('mail::message')
@php($brandName = $siteSettings['site_name'] ?? config('mail.from.name', config('app.name', 'Sales System')))
# Welcome, {{ $user->name }}!

An account has been created for you on **{{ $brandName }}**.

- **Email:** {{ $user->email }}
- **Role:** {{ ucfirst($user->role) }}

@component('mail::button', ['url' => $loginUrl])
Log in now
@endcomponent

If you weren't expecting this, please contact your administrator.

Thanks,<br>
{{ $brandName }}
@endcomponent
