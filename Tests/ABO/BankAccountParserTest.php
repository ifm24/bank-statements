<?php

declare(strict_types=1);

namespace JakubZapletal\Component\BankStatement\Tests\ABO;

use JakubZapletal\Component\BankStatement\ABO\BankAccountParser;
use PHPUnit\Framework\TestCase;

class BankAccountParserTest extends TestCase
{
    public function data(): array
    {
        return [
            'FIO      1' => ['0740000002100199001AAAAAAA AAAA AAAAAAA29072200000000000000+00000000000000+000000000000000000000000000000170290722FIO', '2100199001/2010'],
            'RB       1' => ['0740000000973789052AAAAAAA AAAA AAAAAAA31082200000000000000+00000000000000+000000000000000000000000000000225310822', '973789052/0000'],
            'KB       1' => ['0747204021470000115AAAAAAA AAAA AAAAAAA30082200000000000000+00000000000000+000000000000000000000000000000171310822CZ220100MB', '115-214740207/0100'],
            'KB       2' => ['0747289364750000043AAAAAAA AAAA AAAAAAA03112200000000000000+00000000000000+000000000000000000000000000000225041122CZ940100MB', '43-3647590287/0100'],
            'Creditas 1' => ['0740000000105559337Banka Creditas, a.s.01102200000000000000+00000000000000+000000000000000000000000000000006311022', '105559337/2250'],
            'CSOB     1' => ['0740000000304449456000000 000000       09112200000000000000+00000000000000+000000000025000000000000025000003151122', '304449456/0000'],
        ];
    }

    /**
     * @dataProvider data
     */
    public function testParser(string $line, string $expected): void
    {
        $result = (new BankAccountParser())->parse($line);

        $this->assertEquals($expected, $result);
    }

    public function dataWithBankAccountFix(): array
    {
        return [
            ...$this->data(),
            'RB       2' => ['0740000000923789052AAAAAAA AAAA AAAAAAA31082200000000000000+00000000000000+000000000000000000000000000000225310822', '923789052/5500', ['923789052' => '5500']],
            'RB       3' => ['0740000000923789052AAAAAAA AAAA AAAAAAA31082200000000000000+00000000000000+000000000000000000000000000000225310822', '923789052/0000', ['923789051' => '5500']],
            'FIO      2' => ['0740000002100199001AAAAAAA AAAA AAAAAAA29072200000000000000+00000000000000+000000000000000000000000000000170290722FIO', '2100199001/1234', ['2100199001' => '1234']],
        ];
    }

    /**
     * @dataProvider dataWithBankAccountFix
     */
    public function testWithBankAccountFix(string $line, string $expected, array $map = []): void
    {
        $result = (new BankAccountParser(bankAccountNumberToBankCodeMap: $map))->parse($line);

        $this->assertEquals($expected, $result);
    }

    public function dataDefaultCode(): array
    {
        return [

            'FIO      1' => ['0740000002100199001AAAAAAA AAAA AAAAAAA29072200000000000000+00000000000000+000000000000000000000000000000170290722FIO', '2100199001/2010'],
            'RB       1' => ['0740000000973789052AAAAAAA AAAA AAAAAAA31082200000000000000+00000000000000+000000000000000000000000000000225310822', '973789052/1234'],
        ];
    }

    /**
     * @dataProvider dataDefaultCode
     */
    public function testDefaultCode(string $line, string $expected): void
    {
        $result = (new BankAccountParser(defaultBankCode: '1234'))->parse($line);

        $this->assertEquals($expected, $result);
    }
}
