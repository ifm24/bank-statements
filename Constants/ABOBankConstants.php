<?php

declare(strict_types=1);

namespace JakubZapletal\Component\BankStatement\Constants;

class ABOBankConstants
{
    const BANK_CODES_BASED_ON_ACCOUNT_NAME = [
        'Banka Creditas, a.s.' => '2250',
    ];

    const BANK_CODES_WITH_INTERNAL_FORMAT = [
        '0100' => true,
    ];
    public const BANKS_WITH_CURRENCY_CODE_IN_TRANSACTION = [
        '0300', // ČSOB
        '2010', // FIO
    ];
    public const BANKS_WITH_ALT_POSTING_CODE = [
        '0300', // Česká spořitelna
    ];
}
