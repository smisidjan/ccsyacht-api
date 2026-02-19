<x-mail::message>
# Hello!

You have been invited to join {{ config('app.name') }} by **{{ $invitedByEmail }}**.

You have been assigned the role: **{{ $role }}**

This invitation will expire on {{ $expiresAt }}.

<x-mail::button :url="$acceptUrl" color="success">
Accept Invitation
</x-mail::button>

<x-mail::button :url="$declineUrl" color="error">
Decline Invitation
</x-mail::button>

If you did not expect this invitation, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
