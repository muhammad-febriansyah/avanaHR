<?php

/*
|--------------------------------------------------------------------------
| HR reminders
|--------------------------------------------------------------------------
|
| Lead times for the reminders:scan command. Document reminders use the
| per-document reminder_days when set, falling back to the default here.
|
*/

return [
    // Default lead time (days) before a document's expiry to remind HR.
    'document_default_days' => 30,

    // Lead time (days) before a fixed-term contract end-date to alert HR.
    'contract_days_before' => 30,

    // Roles that receive HR reminder notifications.
    'recipient_roles' => ['hr-admin', 'tenant-admin'],
];
