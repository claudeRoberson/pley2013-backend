<?php
return [
    'environment' => 'local',
    'dsn' => 'https://ad293356dda74a53a9b593414ae40993:f1a56140630e48418aed07de88637faf@sentry.io/229212',
    'breadcrumbs.sql_bindings' => false,
    'user_context' => false,
    'ignored_exceptions' => [
        'Pley\Exception\User\RegistrationExistingUserException',
        'Pley\Exception\Auth\NotAuthenticatedException',
        'Pley\Repository\Exception\EntityNotFoundException'
    ]
];