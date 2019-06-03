<?php

return array(
    'dsn' => 'https://ad293356dda74a53a9b593414ae40993:f1a56140630e48418aed07de88637faf@sentry.io/229212',

    // capture release as git sha
    // 'release' => trim(exec('git --git-dir ' . base_path('.git') . ' log --pretty="%h" -n1 HEAD')),

    // Capture bindings on SQL queries
    'breadcrumbs.sql_bindings' => true,

    // Capture default user context
    'user_context' => false,
);
