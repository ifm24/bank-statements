<?php

namespace JakubZapletal\Component\BankStatement\Tests\Parser;

use DateTimeImmutable;
use JakubZapletal\Component\BankStatement\Parser\ABOParser;
use JakubZapletal\Component\BankStatement\Statement\Statement;
use JakubZapletal\Component\BankStatement\Statement\Transaction\Transaction;
use LogicException;
use PHPUnit\Framework\TestCase;

class ABOParserTest extends TestCase
{
    /**
     * @var string
     */
    protected $parserClassName = ABOParser::class;

    public function testParseFile()
    {
        $fileObject = new \SplFileObject(tempnam(sys_get_temp_dir(), 'test_'), 'w+');

        $parserMock = $this->createPartialMock($this->parserClassName, array('parseFileObject'));
        $parserMock
            ->expects($this->once())
            ->method('parseFileObject')
            ->with($this->equalTo($fileObject))
            ->will($this->returnArgument(0));

        $this->assertSame(
            $fileObject->getRealPath(),
            $parserMock->parseFile($fileObject->getRealPath())->getRealPath()
        );
    }

    public function testParseFileException()
    {
        $this->expectException(\RuntimeException::class);
        $parser = new ABOParser();
        $parser->parseFile('test.file');
    }

    public function testParseContent()
    {
        $content = 'test';

        $parserMock = $this->createPartialMock($this->parserClassName, array('parseFileObject'));
        $parserMock
            ->expects($this->once())
            ->method('parseFileObject')
            ->with($this->isInstanceOf(\SplFileObject::class))
            ->will($this->returnValue($content));

        $this->assertEquals(
            $content,
            $parserMock->parseContent($content)
        );
    }

    public function testParseContentException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $parser = new ABOParser();
        $parser->parseContent(123);
    }

    public function testParseFileObject()
    {
        $parser = new ABOParser();

        $reflectionParser = new \ReflectionClass($this->parserClassName);
        $method = $reflectionParser->getMethod('parseFileObject');
        $method->setAccessible(true);

        # Positive statement
        $fileObject = new \SplFileObject(tempnam(sys_get_temp_dir(), 'test_'), 'w+');
        $fileObject->fwrite(
            '0741234561234567890Test s.r.o.         01011400000000100000+00000000080000+00000000060000' .
            '+00000000040000+002010214              ' . PHP_EOL
        );
        $fileObject->fwrite(
            '0750000000000012345000000000015678900000000020010000000400002000000001100100000120000000013050114' .
            'Tran 1              00203050114' . PHP_EOL
        );
        $fileObject->fwrite(
            '07600000000000000000000002001050114Protistrana s.r.o.' . PHP_EOL
        );
        $fileObject->fwrite(
            '078First line' . PHP_EOL
        );
        $fileObject->fwrite(
            '079Second line' . PHP_EOL
        );
        $fileObject->fwrite(
            '0750000000000012345000000000025678900000000020020000000600001000000002100200000220000000023070114' .
            'Tran 2              00203070114' . PHP_EOL
        );
        $statement = $method->invokeArgs($parser, array($fileObject));

        $this->assertInstanceOf(
            Statement::class,
            $statement
        );

        # Statement
        $this->assertSame($statement, $parser->getStatement());
        $this->assertEquals('123456-1234567890/0000', $statement->getAccountNumber());
        $this->assertEquals(new \DateTimeImmutable('2014-01-01 12:00:00'), $statement->getDateLastBalance());
        $this->assertSame(1000.00, $statement->getLastBalance());
        $this->assertSame(800.00, $statement->getBalance());
        $this->assertSame(400.00, $statement->getCreditTurnover());
        $this->assertSame(600.00, $statement->getDebitTurnover());
        $this->assertEquals(2, $statement->getSerialNumber());
        $this->assertEquals(new \DateTimeImmutable('2014-02-01 12:00:00'), $statement->getDateCreated());

        # Transactions
        $this->assertCount(2, $statement);

        $transactions = $statement->getIterator();

        /** @var Transaction $transaction */
        $transaction = $transactions->current();
        $this->assertEquals('156789/1000', $transaction->getCounterAccountNumber());
        $this->assertEquals(2001, $transaction->getReceiptId());
        $this->assertSame(400.00, $transaction->getCredit());
        $this->assertNull($transaction->getDebit());
        $this->assertEquals(11, $transaction->getVariableSymbol());
        $this->assertEquals(12, $transaction->getConstantSymbol());
        $this->assertEquals(13, $transaction->getSpecificSymbol());
        $this->assertEquals('Tran 1', $transaction->getNote());
        $this->assertEquals(new \DateTimeImmutable('2014-01-05 12:00:00'), $transaction->getDateCreated());


        $this->assertEquals(2001, $transaction->getAdditionalInformation()->getTransferIdentificationNumber());
        $this->assertEquals(
            new DateTimeImmutable('2014-01-05 12:00:00'),
            $transaction->getAdditionalInformation()->getDeductionDate()
        );
        $this->assertEquals(
            'Protistrana s.r.o.',
            $transaction->getAdditionalInformation()->getCounterPartyName()
        );

        $this->assertEquals('First line', $transaction->getMessageStart());
        $this->assertEquals('Second line', $transaction->getMessageEnd());

        $transactions->next();
        $transaction = $transactions->current();
        $this->assertNull($transaction->getCredit());
        $this->assertSame(600.00, $transaction->getDebit());

        # Negative statement
        $fileObject = new \SplFileObject(tempnam(sys_get_temp_dir(), 'test_'), 'w+');
        $fileObject->fwrite(
            '0740000000000012345Test s.r.o.         01011400000000100000-00000000080000-00000000060000-00000000040000' .
            '-002010214              ' . PHP_EOL
        );
        $fileObject->fwrite(
            '0750000000000012345000000000015678900000000020010000000400005000000001100100000120000000013050114' .
            'Tran 1              00203050114' . PHP_EOL
        );
        $fileObject->fwrite(
            '0750000000000012345000000000025678900000000020020000000600004000000002100200000220000000023070114' .
            'Tran 2              00203070114' . PHP_EOL
        );
        $statement = $method->invokeArgs($parser, array($fileObject));

        # Statement
        $this->assertSame(-1000.00, $statement->getLastBalance());
        $this->assertSame(-800.00, $statement->getBalance());
        $this->assertSame(-400.00, $statement->getCreditTurnover());
        $this->assertSame(-600.00, $statement->getDebitTurnover());

        # Transactions
        $transactions = $statement->getIterator();

        $transaction = $transactions->current();
        $this->assertSame(-400.00, $transaction->getCredit());
        $this->assertEquals(null, $transaction->getCurrency());

        $transactions->next();
        $transaction = $transactions->current();
        $this->assertSame(-600.00, $transaction->getDebit());
        $this->assertEquals(null, $transaction->getCurrency());
    }

    public function testCreditas(): void
    {
        $lines = [
            '0740000000105559337Banka Creditas, a.s.01102200000000306479+00000000310551+000000003000000000000003040720006311022',
            '0750000000105559337000000010555957210000007183070000003000001000000000000225000000000000000061022SSSSSSSS SSSSSS     00203061022',
            '0750000000105559337000000000000000010000013352860000003040722000000000000000000000000000000011122Banka CREDITAS      00203311022',
            '078Kapitalizace term. vkladu 105559417'
        ];

        $statement = $this->parse($lines);

        $this->assertSame(3105.51, $statement->getBalance());
        $this->assertSame(3064.79, $statement->getLastBalance());
        $this->assertCount(2, $statement->getTransactions());
        $this->assertSame('105559337', $statement->getAccountNumberNumber());
        $this->assertSame(null, $statement->getAccountNumberPrefix());
        $this->assertSame('105559337/2250', $statement->getAccountNumber());
        $this->assertSame('2250', $statement->getAccountNumberBankCode());

        $transaction = $statement->getTransactions()[0];
        $this->assertSame('105559572/2250', $transaction->getCounterAccountNumber());
        $this->assertSame('1000000718307', $transaction->getReceiptId());
        $this->assertSame(3000.0, $transaction->getDebit());
        $this->assertSame(null, $transaction->getCredit());
        $this->assertSame('', $transaction->getVariableSymbol());
        $this->assertSame('', $transaction->getConstantSymbol());
        $this->assertSame('', $transaction->getSpecificSymbol());
        $this->assertSame('SSSSSSSS SSSSSS', $transaction->getNote());
        $this->assertSame('2022-10-06 12:00:00', $transaction->getDateCreated()->format('Y-m-d H:i:s'));
        $this->assertSame(null, $transaction->getCurrency());
        $this->assertSame(null, $transaction->getAdditionalInformation());
        $this->assertSame(null, $transaction->getMessageStart());
        $this->assertSame(null, $transaction->getMessageEnd());

        $transaction = $statement->getTransactions()[1];
        $this->assertSame('0000000000/0000', $transaction->getCounterAccountNumber());
        $this->assertSame('1000001335286', $transaction->getReceiptId());
        $this->assertSame(null, $transaction->getDebit());
        $this->assertSame(3040.72, $transaction->getCredit());
        $this->assertSame('', $transaction->getVariableSymbol());
        $this->assertSame('', $transaction->getConstantSymbol());
        $this->assertSame('', $transaction->getSpecificSymbol());
        $this->assertSame('Banka CREDITAS', $transaction->getNote());
        $this->assertSame('2022-10-31 12:00:00', $transaction->getDateCreated()->format('Y-m-d H:i:s'));
        $this->assertSame(null, $transaction->getCurrency());
        $this->assertSame(null, $transaction->getAdditionalInformation());
        $this->assertSame('Kapitalizace term. vkladu 105559417', $transaction->getMessageStart());
        $this->assertSame(null, $transaction->getMessageEnd());
    }

    public function testFIOExport(): void
    {
        $statement = $this->parseFile('fio.gpc');

        $this->assertSame(769264.95, $statement->getBalance());
        $this->assertSame(747782.95, $statement->getLastBalance());
        $this->assertCount(2, $statement->getTransactions());
        $this->assertSame('2000012001', $statement->getAccountNumberNumber());
        $this->assertSame(null, $statement->getAccountNumberPrefix());
        $this->assertSame('2000012001/2010', $statement->getAccountNumber());
        $this->assertSame('2010', $statement->getAccountNumberBankCode());

        $transaction = $statement->getTransactions()[0];
        $this->assertSame('166111143/0800', $transaction->getCounterAccountNumber());
        $this->assertSame('24225767018', $transaction->getReceiptId());
        $this->assertSame(null, $transaction->getDebit());
        $this->assertSame(14813.0, $transaction->getCredit());
        $this->assertSame('64627', $transaction->getVariableSymbol());
        $this->assertSame('', $transaction->getConstantSymbol());
        $this->assertSame('', $transaction->getSpecificSymbol());
        $this->assertSame('Sewerin Martin', $transaction->getNote());
        $this->assertSame('2022-07-29 12:00:00', $transaction->getDateCreated()->format('Y-m-d H:i:s'));
        $this->assertSame('CZK', $transaction->getCurrency());
        $this->assertSame(null, $transaction->getAdditionalInformation());
        $this->assertSame(null, $transaction->getMessageStart());
        $this->assertSame(null, $transaction->getMessageEnd());

        $transaction = $statement->getTransactions()[1];
        $this->assertSame('11000-1734129003/0800', $transaction->getCounterAccountNumber());
        $this->assertSame('24226476315', $transaction->getReceiptId());
        $this->assertSame(null, $transaction->getDebit());
        $this->assertSame(6669.0, $transaction->getCredit());
        $this->assertSame('65519', $transaction->getVariableSymbol());
        $this->assertSame('', $transaction->getConstantSymbol());
        $this->assertSame('', $transaction->getSpecificSymbol());
        $this->assertSame('Eclerová Pavla', $transaction->getNote());
        $this->assertSame('2022-07-29 12:00:00', $transaction->getDateCreated()->format('Y-m-d H:i:s'));
        $this->assertSame('CZK', $transaction->getCurrency());
        $this->assertSame(null, $transaction->getAdditionalInformation());
        $this->assertSame(null, $transaction->getMessageStart());
        $this->assertSame(null, $transaction->getMessageEnd());
    }

    public function testCSOBExport(): void
    {
        $statement = $this->parseFile('csob.gpc');

        $this->assertSame(3423.58, $statement->getBalance());
        $this->assertSame(3423.58, $statement->getLastBalance());
        $this->assertCount(2, $statement->getTransactions());
        $this->assertSame('304449456', $statement->getAccountNumberNumber());
        $this->assertSame(null, $statement->getAccountNumberPrefix());
        $this->assertSame('304449456/0000', $statement->getAccountNumber());
        $this->assertSame('0000', $statement->getAccountNumberBankCode());

        $transaction = $statement->getTransactions()[0];
        $this->assertSame('14816-712950020/0300', $transaction->getCounterAccountNumber());
        $this->assertSame('1000000000201', $transaction->getReceiptId());
        $this->assertSame(null, $transaction->getDebit());
        $this->assertSame(25.0, $transaction->getCredit());
        $this->assertSame('304449456', $transaction->getVariableSymbol());
        $this->assertSame('', $transaction->getConstantSymbol());
        $this->assertSame('', $transaction->getSpecificSymbol());
        $this->assertSame('Popl.za pojištění in', $transaction->getNote());
        $this->assertSame('2022-11-15 12:00:00', $transaction->getDateCreated()->format('Y-m-d H:i:s'));
        $this->assertSame(null, $transaction->getCurrency());
        $this->assertSame('SPO FROM 71295002                  Příchozí úhrada', $transaction->getAdditionalInformation()->getCounterPartyName());
        $this->assertSame('Internetová rizika na rok zdarma', $transaction->getMessageStart());
        $this->assertSame(null, $transaction->getMessageEnd());

        $transaction = $statement->getTransactions()[1];
        $this->assertSame('14816-712950020/0300', $transaction->getCounterAccountNumber());
        $this->assertSame('202', $transaction->getReceiptId());
        $this->assertSame(25.0, $transaction->getDebit());
        $this->assertSame(null, $transaction->getCredit());
        $this->assertSame('304449456', $transaction->getVariableSymbol());
        $this->assertSame('', $transaction->getConstantSymbol());
        $this->assertSame('', $transaction->getSpecificSymbol());
        $this->assertSame('Popl.za pojištění in', $transaction->getNote());
        $this->assertSame('2022-11-15 12:00:00', $transaction->getDateCreated()->format('Y-m-d H:i:s'));
        $this->assertSame(null, $transaction->getCurrency());
        $this->assertSame('Trvalý příkaz k Inkasu číslo', $transaction->getAdditionalInformation()->getCounterPartyName());
        $this->assertSame('Pojištění internetových rizik', $transaction->getMessageStart());
        $this->assertSame(null, $transaction->getMessageEnd());
    }

    public function parseFile(string $fileName): Statement
    {
        $dir = __DIR__ . '/../ABO/' . $fileName;
        $path = realpath($dir);

        if ($path === false) {
            throw new LogicException('Failed to load file at '.$dir);
        }

        $parser = new ABOParser();
        return $parser->parseFile($path);
    }

    protected function parse(array $lines): Statement
    {
        $parser = new ABOParser();

        $reflectionParser = new \ReflectionClass($this->parserClassName);
        $method = $reflectionParser->getMethod('parseFileObject');
        $method->setAccessible(true);

        # Positive statement
        $fileObject = new \SplFileObject(tempnam(sys_get_temp_dir(), 'test_'), 'w+');
        foreach ($lines as $line) {
            $fileObject->fwrite($line . PHP_EOL);
        }

        return $method->invokeArgs($parser, array($fileObject));
    }
}
