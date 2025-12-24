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

    /*
    |--------------------------------------------------------------------------
    | SSL Verification
    |--------------------------------------------------------------------------
    |
    | Desabilite a verificação SSL APENAS em desenvolvimento.
    | NUNCA desabilite em produção por questões de segurança.
    |
    | Isso resolve o erro "cURL error 60: SSL certificate problem"
    | comum em ambientes Windows de desenvolvimento.
    |
    */
    'notifier_verify_ssl' => env('TRANSFER_NOTIFIER_VERIFY_SSL', true),
    'authorizer_verify_ssl' => env('TRANSFER_AUTHORIZER_VERIFY_SSL', true),
    ];
