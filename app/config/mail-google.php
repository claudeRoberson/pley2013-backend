<?php
return array(
    'driver'     => 'smtp',
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'from'       => array('address' => 'billing@pley.com', 'name' => 'Billing Pley'),
    'encryption' => 'tls',
    'username'   => 'billing@pley.com',
    'password'   => 'Billing1984',
    'sendmail'   => '/usr/sbin/sendmail -bs',
    'pretend'    => false,
);
