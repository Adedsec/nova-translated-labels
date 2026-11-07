<?php

declare(strict_types=1);

/*
| Translations for your Nova admin panel. Keys are the English text your code
| uses; values are what admins see. Anything missing keeps its English text.
| Run `php artisan nova-translated-labels:missing --json` to list what is left.
*/

return [

    // Menu groups, keyed by the resource's $group.
    'groups' => [
        // 'Billing' => 'صورتحساب',
    ],

    // Resources, keyed by their uriKey.
    'resources' => [
        // 'users' => ['label' => 'کاربران', 'singular' => 'کاربر'],
    ],

    // Field labels, keyed by the name passed to make().
    'fields' => [
        // 'Created At' => 'تاریخ ایجاد',
    ],

    // Panel and tab headings.
    'panels' => [],

    // Filter names.
    'filters' => [],

    // Action names.
    'actions' => [],

    // Select, Badge, BooleanGroup and filter option labels.
    'options' => [
        // 'Pending' => 'در انتظار',
    ],

    // Per-resource exceptions, keyed by uriKey, then by one of the groups above.
    'overrides' => [
        // 'orders' => ['fields' => ['Status' => 'وضعیت سفارش']],
    ],

];
