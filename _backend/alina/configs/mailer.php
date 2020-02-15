<?php
switch (ALINA_ENV) {
    case  'HOME':
    case  'DA':
    default:
        return [
            'admin' => [
                'Host'     => 'smtp.yandex.ru',
                'Port'     => 587,
                'Username' => 'my-customer-mailbox@yandex.ru',
                'Password' => 'qwerty123qwerty',
                'FromName' => 'Alina service',
            ],
        ];
        break;
}
