<?php

declare(strict_types=1);

namespace JakubZapletal\Component\BankStatement\ABO;

use JakubZapletal\Component\BankStatement\Constants\ABOBankConstants;

class BankAccountParser implements BankAccountParserInterface
{
    private string $defaultBankCode;

    /**
     * @var array <string, string>
     */
    private array $bankAccountNumberToBankCodeMap;

    /**
     * @param string                $defaultBankCode
     * @param array<string, string> $bankAccountNumberToBankCodeMap Allows mapping bank account to bank code (to
     *                                                              construct correct full bank account for abo exports
     *                                                              that does not contain bank code)
     */
    public function __construct(
        string $defaultBankCode = '0000',
        array $bankAccountNumberToBankCodeMap = []
    ) {
        $this->defaultBankCode = $defaultBankCode;
        $this->bankAccountNumberToBankCodeMap = $bankAccountNumberToBankCodeMap;
    }


    /**
     * Takes header line from ABO format and constructs bank account number. Line looks like:
     * 0740000002100199001AA....00000170290722FIO and output looks like 2100199001/2010 or 115-214740207/0100 or
     * 973789052/0000, etc.
     *
     * @see BankAccountParserTest
     */
    public function parse(string $line): string
    {
        $bankCode = $this->getBankCode(line: $line);

        list($prefix, $number) = $this->getPrefixAndNumber(bankCode: $bankCode, line: $line);

        $bankAccount = $this->formatBankAccountNumber(prefix: $prefix, number: $number);

        if (array_key_exists($bankAccount, $this->bankAccountNumberToBankCodeMap)) {
            $bankCode = $this->bankAccountNumberToBankCodeMap[$bankAccount];
        }

        return $bankAccount . '/' . $bankCode;
    }

    public function formatBankAccountNumber(string $prefix, string $number): string
    {
        // Remove leading zeros from prefix and number and build an array of non empty string values
        $accountNumberParts = array_filter([
            ltrim($prefix, '0'),
            ltrim($number, '0')
        ], fn(string $value) => $value !== '');

        if ($accountNumberParts === []) {
            $accountNumberParts = ['0000000000'];
        }

        // Implode will add '-' separator between prefix and number only if $prefix is not and empty string
        // (built array will not contain empty prefix due the array_filter usage).
        return implode('-', $accountNumberParts);
    }

    /**
     * @param string $bankCode
     * @param string $line
     *
     * @return array{0: string, 1: string}
     */
    protected function getPrefixAndNumber(string $bankCode, string $line): array
    {
        if (array_key_exists($bankCode, ABOBankConstants::BANK_CODES_WITH_INTERNAL_FORMAT) === false) {
            return [
                substr($line, 3, 6),
                substr($line, 9, 10)
            ];
        }

        // Example: 7204021470000115
        $internalAccountNumber = substr($line, 3, 16);
        // Internal format of account number is created through permutation according to the following principle:
        // Normal format:   N01 N02 N03 N04 N05 N06 N07 N08 N09 N10 N11 N12 N13 N14 N15 N16
        // Internal format: N16 N14 N15 N12 N07 N08 N09 N10 N11 N13 N01 N02 N03 N04 N05 N06
        // @link https://mojebanka.kb.cz//file/cs/bdsk_format_abo_sk.pdf
        $internalFormatPositionToNormal = [16, 14, 15, 12, 7, 8, 9, 10, 11, 13, 1, 2, 3, 4, 5, 6];

        $prefix = '000000';
        $prefixLength = strlen($prefix);
        $number = '000000000';

        foreach ($internalFormatPositionToNormal as $index => $position) {
            $value = $internalAccountNumber[$index];

            // Convert character position to character index
            $newIndex = $position - 1;

            if ($position > $prefixLength) {
                $number[$newIndex] = $value;
            } else {
                $prefix[$newIndex - $prefixLength] = $value;
            }
        }

        // Example result: {0: '000115', 1: '0214740207'}
        return [$prefix, $number];
    }

    protected function getBankCode(string $line): string
    {
        $iban = trim(substr($line, 114, 8));

        if ($iban === 'FIO') {
            return '2010';
        }

        if (str_starts_with($iban, 'CZ') === true) {
            // Example: CZ220100 -> 0100
            return substr($iban, 4, 4);
        }

        $accountName = substr($line, 19, 20);

        if (array_key_exists($accountName, ABOBankConstants::BANK_CODES_BASED_ON_ACCOUNT_NAME) === true) {
            // Example: CZ220100 -> 0100
            return ABOBankConstants::BANK_CODES_BASED_ON_ACCOUNT_NAME[$accountName];
        }

        return $this->defaultBankCode;
    }

    /**
     * @return array <string, string>
     */
    public function getBankAccountNumberToBankCodeMap(): array
    {
        return $this->bankAccountNumberToBankCodeMap;
    }

    public function getDefaultBankCode(): string
    {
        return $this->defaultBankCode;
    }
}
