<?php

namespace JakubZapletal\Component\BankStatement\Statement;

use ArrayIterator;
use Iterator;
use JakubZapletal\Component\BankStatement\Statement\Transaction\TransactionInterface;
use Traversable;

class Statement implements StatementInterface, \Countable, \IteratorAggregate
{
    private const BankAccountNumberMaxLength = 10;

    /**
     * @var string
     */
    protected $accountNumber;

    /**
     * @var array{prefix: ?string, number: string, bankCode: string}
     */
    protected array $parsedAccountNumber;

    /**
     * @var float
     */
    protected $balance;

    /**
     * @var float
     */
    protected $debitTurnover;

    /**
     * @var float
     */
    protected $creditTurnover;

    /**
     * @var string
     */
    protected $serialNumber;

    /**
     * @var \DateTimeImmutable
     */
    protected $dateCreated;

    /**
     * @var \DateTimeImmutable
     */
    protected $dateLastBalance;

    /**
     * @var float
     */
    protected $lastBalance;

    /**
     * @var TransactionInterface[]
     */
    protected $transactions = array();

    /**
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @param $balance
     *
     * @return $this
     */
    public function setBalance($balance)
    {
        $this->balance = (float) $balance;

        return $this;
    }

    /**
     * @return float
     */
    public function getCreditTurnover()
    {
        return $this->creditTurnover;
    }

    /**
     * @param $creditTurnover
     *
     * @return $this
     */
    public function setCreditTurnover($creditTurnover)
    {
        $this->creditTurnover = (float) $creditTurnover;

        return $this;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * @param \DateTimeImmutable $dateCreated
     *
     * @return $this
     */
    public function setDateCreated(\DateTimeImmutable $dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * @return string
     */
    public function getSerialNumber()
    {
        return $this->serialNumber;
    }

    /**
     * @param $serialNumber
     *
     * @return $this
     */
    public function setSerialNumber($serialNumber)
    {
        $this->serialNumber = $serialNumber;

        return $this;
    }

    /**
     * @return float
     */
    public function getDebitTurnover()
    {
        return $this->debitTurnover;
    }

    /**
     * @param $debitTurnover
     *
     * @return $this
     */
    public function setDebitTurnover($debitTurnover)
    {
        $this->debitTurnover = (float) $debitTurnover;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccountNumber()
    {
        return $this->accountNumber;
    }

    public function setAccountNumber($accountNumber): self
    {
        $this->accountNumber = $accountNumber;

        $this->parsedAccountNumber = [
            'prefix'   => null,
            'bankCode' => '0000'
        ];

        $splitBankCode = explode('/', $accountNumber);
        if (count($splitBankCode) === 2) {
            $this->parsedAccountNumber['bankCode'] = $splitBankCode[1];
        }

        // Support format with separator or without separator
        $splitNumber = explode('-', $splitBankCode[0]);
        $firstPart = $splitNumber[0];
        if (count($splitNumber) === 2) {
            $this->parsedAccountNumber['prefix'] = $firstPart;
            $this->parsedAccountNumber['number'] = $splitNumber[1];
        } else {
            $firstPartLength = strlen($firstPart);
            if ($firstPartLength <= self::BankAccountNumberMaxLength) {
                $this->parsedAccountNumber['number'] = $firstPart;
            } else {
                $this->parsedAccountNumber['prefix'] = substr($firstPart, 0, $firstPartLength - self::BankAccountNumberMaxLength);
                $this->parsedAccountNumber['number'] = substr($firstPart, -self::BankAccountNumberMaxLength, self::BankAccountNumberMaxLength);
            }
        }

        return $this;
    }

    /**
     * Split account number to parts
     *
     * @return array{prefix: ?string, number: ?string, bankCode: ?string}
     */
    public function getParsedAccountNumber(): array
    {
        return $this->parsedAccountNumber;
    }

    /**
     * @return string|null
     */
    public function getAccountNumberPrefix()
    {
        return $this->getParsedAccountNumber()['prefix'];
    }

    /**
     * @return string
     */
    public function getAccountNumberNumber()
    {
        return $this->getParsedAccountNumber()['number'];
    }

    /**
     * @return string
     */
    public function getAccountNumberBankCode()
    {
        return $this->getParsedAccountNumber()['bankCode'];
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDateLastBalance()
    {
        return $this->dateLastBalance;
    }

    /**
     * @param \DateTimeImmutable $dateLastBalance
     *
     * @return $this
     */
    public function setDateLastBalance(\DateTimeImmutable $dateLastBalance)
    {
        $this->dateLastBalance = $dateLastBalance;

        return $this;
    }

    /**
     * @return float
     */
    public function getLastBalance()
    {
        return $this->lastBalance;
    }

    /**
     * @param float $lastBalance
     *
     * @return $this
     */
    public function setLastBalance($lastBalance)
    {
        $this->lastBalance = (float) $lastBalance;

        return $this;
    }

    /**
     * @return TransactionInterface[]
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @param TransactionInterface $transaction
     *
     * @return $this
     */
    public function addTransaction(TransactionInterface $transaction)
    {
        $added = false;

        foreach ($this->transactions as $addedTransaction) {
            if ($transaction === $addedTransaction) {
                $added = true;
                break;
            }
        }

        if ($added !== true) {
            $this->transactions[] = $transaction;
        }

        return $this;
    }

    /**
     * @param TransactionInterface $transaction
     *
     * @return $this
     */
    public function removeTransaction(TransactionInterface $transaction)
    {
        foreach ($this->transactions as $key => $addedTransaction) {
            if ($transaction === $addedTransaction) {
                unset($this->transactions[$key]);
                break;
            }
        }

        return $this;
    }

    public function count(): int
    {
        return count($this->transactions);
    }

    /**
     * @return ArrayIterator<TransactionInterface>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->transactions);
    }
}
