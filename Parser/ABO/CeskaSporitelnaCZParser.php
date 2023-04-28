<?php

namespace JakubZapletal\Component\BankStatement\Parser\ABO;

use JakubZapletal\Component\BankStatement\Parser\ABOParser;

class CeskaSporitelnaCZParser extends ABOParser
{
    private const POSTING_CODE_DEBIT           = 1;
    private const POSTING_CODE_CREDIT          = 2;
    private const POSTING_CODE_DEBIT_REVERSAL  = 3;
    private const POSTING_CODE_CREDIT_REVERSAL = 4;
}
