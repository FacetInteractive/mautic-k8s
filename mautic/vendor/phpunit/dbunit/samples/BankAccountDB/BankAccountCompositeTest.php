<?php
/*
 * This file is part of DBUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once 'BankAccount.php';

/**
 * Tests for the BankAccount class.
 *
 * @since      Class available since Release 1.0.0
 */
class BankAccountCompositeTest extends PHPUnit_Framework_TestCase
{
    protected $pdo;

    protected $tester;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->pdo = new PDO('sqlite::memory:');
        BankAccount::createTable($this->pdo);
        $this->tester = $this->getDatabaseTester();
    }

    /**
     * @return PHPUnit_Extensions_Database_DefaultTester
     */
    protected function getDatabaseTester()
    {
        $connection = new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($this->pdo, 'sqlite');
        $tester     = new PHPUnit_Extensions_Database_DefaultTester($connection);
        $tester->setSetUpOperation(PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT());
        $tester->setTearDownOperation(PHPUnit_Extensions_Database_Operation_Factory::NONE());
        $tester->setDataSet(new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__) . '/_files/bank-account-seed.xml'));

        return $tester;
    }

    protected function setUp()
    {
        parent::setUp();
        $this->tester->onSetUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->tester->onTearDown();
    }

    public function testNewAccountBalanceIsInitiallyZero()
    {
        $bank_account = new BankAccount('12345678912345678', $this->pdo);
        $this->assertEquals(0, $bank_account->getBalance());
    }

    public function testOldAccountInfoInitiallySet()
    {
        $bank_account = new BankAccount('15934903649620486', $this->pdo);
        $this->assertEquals(100, $bank_account->getBalance());
        $this->assertEquals('15934903649620486', $bank_account->getAccountNumber());

        $bank_account = new BankAccount('15936487230215067', $this->pdo);
        $this->assertEquals(1216, $bank_account->getBalance());
        $this->assertEquals('15936487230215067', $bank_account->getAccountNumber());

        $bank_account = new BankAccount('12348612357236185', $this->pdo);
        $this->assertEquals(89, $bank_account->getBalance());
        $this->assertEquals('12348612357236185', $bank_account->getAccountNumber());
    }

    public function testAccountBalanceDeposits()
    {
        $bank_account = new BankAccount('15934903649620486', $this->pdo);
        $bank_account->depositMoney(100);

        $bank_account = new BankAccount('15936487230215067', $this->pdo);
        $bank_account->depositMoney(230);

        $bank_account = new BankAccount('12348612357236185', $this->pdo);
        $bank_account->depositMoney(24);

        $xml_dataset = new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__) . '/_files/bank-account-after-deposits.xml');
        PHPUnit_Extensions_Database_TestCase::assertDataSetsEqual($xml_dataset, $this->tester->getConnection()->createDataSet());
    }

    public function testAccountBalanceWithdrawals()
    {
        $bank_account = new BankAccount('15934903649620486', $this->pdo);
        $bank_account->withdrawMoney(100);

        $bank_account = new BankAccount('15936487230215067', $this->pdo);
        $bank_account->withdrawMoney(230);

        $bank_account = new BankAccount('12348612357236185', $this->pdo);
        $bank_account->withdrawMoney(24);

        $xml_dataset = new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__) . '/_files/bank-account-after-withdrawals.xml');
        PHPUnit_Extensions_Database_TestCase::assertDataSetsEqual($xml_dataset, $this->tester->getConnection()->createDataSet());
    }

    public function testNewAccountCreation()
    {
        $bank_account = new BankAccount('12345678912345678', $this->pdo);

        $xml_dataset = new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__) . '/_files/bank-account-after-new-account.xml');
        PHPUnit_Extensions_Database_TestCase::assertDataSetsEqual($xml_dataset, $this->tester->getConnection()->createDataSet());
    }
}
