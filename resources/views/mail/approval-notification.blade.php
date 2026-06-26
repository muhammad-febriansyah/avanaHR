<x-mail::message>
# {{ $heading }}

@if ($title)
**{{ $title }}**
@endif

Silakan masuk ke AvanaHR untuk meninjau melalui **Inbox Approval**.

<x-mail::button :url="config('app.url') . '/approvals'">
Buka Inbox Approval
</x-mail::button>

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
