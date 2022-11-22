<?php

declare(strict_types=1);

namespace JakubZapletal\Component\BankStatement\ABO;

interface BankAccountParserInterface
{
    /**
     * Takes header line from ABO format and constructs bank account number. Line looks like:
     * 0740000002100199001AA....00000170290722FIO and output looks like 2100199001/2010 or 115-214740207/0100 or
     * 973789052/0000, etc.
     *
     * @see BankAccountParserTest
     */
    public function parse(string $line): string;

    public function formatBankAccountNumber(string $prefix, string $number): string;
}
