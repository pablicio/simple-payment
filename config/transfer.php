<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Transfer Authorizer Mock
    |--------------------------------------------------------------------------
    |
    | Quando true, o autorizador externo sempre retornará sucesso.
    | Usado para desenvolvimento e testes específicos.
    |
    */
    'authorizer_mock' => env('TRANSFER_AUTHORIZER_MOCK', false),

    /*
    |--------------------------------------------------------------------------
    | External Services URLs
    |--------------------------------------------------------------------------
    |
    | URLs dos serviços externos utilizados no processo de transferência.
    |
    */
    'authorizer_url' => env('TRANSFER_AUTHORIZER_URL', 'https://util.devi.tools/api/v2/authorize'),
    'notifier_url' => env('TRANSFER_NOTIFIER_URL', 'https://util.devi.tools/api/v1/notify'),
];
