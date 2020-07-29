<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Model;

use Doctrine\ORM\ORMException;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\ImportRepository;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Exception\ImportDelayedException;
use Mautic\LeadBundle\Exception\ImportFailedException;
use Mautic\LeadBundle\Helper\Progress;
use Mautic\LeadBundle\Model\ImportModel;
use Mautic\LeadBundle\Tests\StandardImportTestHelper;

class ImportModelTest extends StandardImportTestHelper
{
    public function testInitEventLog()
    {
        $userId   = 4;
        $userName = 'John Doe';
        $fileName = 'import.csv';
        $line     = 104;
        $model    = $this->initImportModel();
        $entity   = $this->initImportEntity();
        $entity->setCreatedBy($userId)
            ->setCreatedByUser($userName)
            ->setOriginalFile($fileName);
        $log = $model->initEventLog($entity, $line);

        $this->assertInstanceOf(LeadEventLog::class, $log);
        $this->assertSame($userId, $log->getUserId());
        $this->assertSame($userName, $log->getUserName());
        $this->assertSame('lead', $log->getBundle());
        $this->assertSame('import', $log->getObject());
        $this->assertSame(['line' => $line, 'file' => $fileName], $log->getProperties());
    }

    public function testProcess()
    {
        $model  = $this->initImportModel();
        $entity = $this->initImportEntity();
        $entity->start();
        $model->process($entity, new Progress());
        $entity->end();

        $this->assertEquals(100, $entity->getProgressPercentage());
        $this->assertSame(4, $entity->getInsertedCount());
        $this->assertSame(2, $entity->getIgnoredCount());
        $this->assertSame(Import::IMPORTED, $entity->getStatus());
    }

    public function testCheckParallelImportLimitWhenMore()
    {
        $entity = $this->initImportEntity();
        $model  = $this->getMockBuilder(ImportModel::class)
            ->setMethods(['getParallelImportLimit', 'getRepository'])
            ->disableOriginalConstructor()
            ->getMock();

        $model->expects($this->once())
            ->method('getParallelImportLimit')
            ->will($this->returnValue(4));

        $repository = $this->getMockBuilder(ImportRepository::class)
            ->setMethods(['countImportsWithStatuses'])
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('countImportsWithStatuses')
            ->will($this->returnValue(5));

        $model->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $result = $model->checkParallelImportLimit();

        $this->assertFalse($result);
    }

    public function testCheckParallelImportLimitWhenEqual()
    {
        $entity = $this->initImportEntity();
        $model  = $this->getMockBuilder(ImportModel::class)
            ->setMethods(['getParallelImportLimit', 'getRepository'])
            ->disableOriginalConstructor()
            ->getMock();

        $model->expects($this->once())
            ->method('getParallelImportLimit')
            ->will($this->returnValue(4));

        $repository = $this->getMockBuilder(ImportRepository::class)
            ->setMethods(['countImportsWithStatuses'])
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('countImportsWithStatuses')
            ->will($this->returnValue(4));

        $model->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $result = $model->checkParallelImportLimit();

        $this->assertFalse($result);
    }

    public function testCheckParallelImportLimitWhenLess()
    {
        $entity = $this->initImportEntity();
        $model  = $this->getMockBuilder(ImportModel::class)
            ->setMethods(['getParallelImportLimit', 'getRepository'])
            ->disableOriginalConstructor()
            ->getMock();

        $model->expects($this->once())
            ->method('getParallelImportLimit')
            ->will($this->returnValue(6));

        $repository = $this->getMockBuilder(ImportRepository::class)
            ->setMethods(['countImportsWithStatuses'])
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('countImportsWithStatuses')
            ->will($this->returnValue(5));

        $model->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $result = $model->checkParallelImportLimit();

        $this->assertTrue($result);
    }

    public function testStartImportWhenParallelLimitHit()
    {
        $model = $this->getMockBuilder(ImportModel::class)
            ->setMethods(['checkParallelImportLimit', 'setGhostImportsAsFailed', 'saveEntity', 'getParallelImportLimit', 'logDebug'])
            ->disableOriginalConstructor()
            ->getMock();

        $model->method('checkParallelImportLimit')
            ->will($this->returnValue(false));

        $model->expects($this->once())
            ->method('getParallelImportLimit')
            ->will($this->returnValue(1));

        $model->expects($this->exactly(2))
            ->method('logDebug');

        $model->setTranslator($this->getTranslatorMock());

        $entity = $this->initImportEntity(['canProceed']);

        $entity->method('canProceed')
            ->will($this->returnValue(true));

        $result = $model->startImport($entity, new Progress());

        $this->assertFalse($result);
        $this->assertEquals(0, $entity->getProgressPercentage());
        $this->assertSame(0, $entity->getInsertedCount());
        $this->assertSame(0, $entity->getIgnoredCount());
        $this->assertSame(Import::DELAYED, $entity->getStatus());
    }

    public function testBeginImportWhenParallelLimitHit()
    {
        $model = $this->getMockBuilder(ImportModel::class)
            ->setMethods(['checkParallelImportLimit', 'setGhostImportsAsFailed', 'saveEntity', 'getParallelImportLimit'])
            ->disableOriginalConstructor()
            ->getMock();

        $model->method('checkParallelImportLimit')
            ->will($this->returnValue(false));

        $model->expects($this->once())
            ->method('getParallelImportLimit')
            ->will($this->returnValue(1));

        $model->setTranslator($this->getTranslatorMock());

        $entity = $this->initImportEntity(['canProceed']);

        $entity->method('canProceed')
            ->will($this->returnValue(true));

        try {
            $model->beginImport($entity, new Progress());
            $this->fail();
        } catch (ImportDelayedException $e) {
            // This is expected
        }

        $this->assertEquals(0, $entity->getProgressPercentage());
        $this->assertSame(0, $entity->getInsertedCount());
        $this->assertSame(0, $entity->getIgnoredCount());
        $this->assertSame(Import::DELAYED, $entity->getStatus());

        $model->expects($this->never())->method('saveEntity');
    }

    public function testStartImportWhenDatabaseException()
    {
        $model = $this->getMockBuilder(ImportModel::class)
            ->setMethods(['checkParallelImportLimit', 'setGhostImportsAsFailed', 'saveEntity', 'logDebug', 'process'])
            ->disableOriginalConstructor()
            ->getMock();

        $model->expects($this->once())
            ->method('checkParallelImportLimit')
            ->will($this->returnValue(true));

        $model->expects($this->exactly(2))
            ->method('logDebug');

        $model->expects($this->once())
            ->method('process')
            ->will($this->throwException(new ORMException()));

        $model->setTranslator($this->getTranslatorMock());

        $entity = $this->initImportEntity(['canProceed']);

        $entity->method('canProceed')
            ->will($this->returnValue(true));

        $result = $model->startImport($entity, new Progress());

        $this->assertFalse($result);
        $this->assertEquals(0, $entity->getProgressPercentage());
        $this->assertSame(0, $entity->getInsertedCount());
        $this->assertSame(0, $entity->getIgnoredCount());
        $this->assertSame(Import::DELAYED, $entity->getStatus());
    }

    public function testBeginImportWhenDatabaseException()
    {
        $model = $this->getMockBuilder(ImportModel::class)
            ->setMethods(['checkParallelImportLimit', 'setGhostImportsAsFailed', 'saveEntity', 'logDebug', 'process'])
            ->disableOriginalConstructor()
            ->getMock();

        $model->expects($this->once())
            ->method('checkParallelImportLimit')
            ->will($this->returnValue(true));

        $model->expects($this->once())
            ->method('process')
            ->will($this->throwException(new ORMException()));

        $model->setTranslator($this->getTranslatorMock());

        $entity = $this->initImportEntity(['canProceed']);

        $entity->method('canProceed')
            ->will($this->returnValue(true));

        try {
            $model->beginImport($entity, new Progress());
            $this->fail();
        } catch (ImportFailedException $e) {
            // This is expected
        }

        $this->assertEquals(0, $entity->getProgressPercentage());
        $this->assertSame(0, $entity->getInsertedCount());
        $this->assertSame(0, $entity->getIgnoredCount());
        $this->assertSame(Import::DELAYED, $entity->getStatus());

        $model->expects($this->never())->method('saveEntity');
    }

    public function testIsEmptyCsvRow()
    {
        $model    = $this->initImportModel();
        $testData = [
            [
                'row' => '',
                'res' => true,
            ],
            [
                'row' => [],
                'res' => true,
            ],
            [
                'row' => [null],
                'res' => true,
            ],
            [
                'row' => [''],
                'res' => true,
            ],
            [
                'row' => ['John'],
                'res' => false,
            ],
            [
                'row' => ['John', 'Doe'],
                'res' => false,
            ],
        ];

        foreach ($testData as $test) {
            $this->assertSame(
                $test['res'],
                $model->isEmptyCsvRow($test['row']),
                'Failed on row '.var_export($test['row'], true)
            );
        }
    }

    public function testTrimArrayValues()
    {
        $model    = $this->initImportModel();
        $testData = [
            [
                'row' => ['John '],
                'res' => ['John'],
            ],
            [
                'row' => ['  John  ', ' Do  e '],
                'res' => ['John', 'Do  e'],
            ],
            [
                'row' => ['key' => '  John  ', 2 => ' Do  e '],
                'res' => ['key' => 'John', 2 => 'Do  e'],
            ],
        ];

        foreach ($testData as $test) {
            $this->assertSame(
                $test['res'],
                $model->trimArrayValues($test['row']),
                'Failed on row '.var_export($test['row'], true)
            );
        }
    }

    public function testHasMoreValuesThanColumns()
    {
        $model    = $this->initImportModel();
        $columns  = 3;
        $testData = [
            [
                'row' => ['John'],
                'mod' => ['John', '', ''],
                'res' => false,
            ],
            [
                'row' => ['John', 'Doe'],
                'mod' => ['John', 'Doe', ''],
                'res' => false,
            ],
            [
                'row' => ['key' => 'John', 2 => 'Doe', 'stuff'],
                'mod' => ['key' => 'John', 2 => 'Doe', 'stuff'],
                'res' => false,
            ],
            [
                'row' => ['key' => 'John', 2 => 'Doe', 'stuff', 'this is too much'],
                'mod' => ['key' => 'John', 2 => 'Doe', 'stuff', 'this is too much'],
                'res' => true,
            ],
        ];

        foreach ($testData as $test) {
            $res = $model->hasMoreValuesThanColumns($test['row'], $columns);
            $this->assertSame(
                $test['res'],
                $res,
                'Failed on row '.var_export($test['row'], true)
            );
            $this->assertSame($test['mod'], $test['row']);
        }
    }

    public function testLimit()
    {
        $model = $this->initImportModel();

        $import = new Import();
        $import->setFilePath(self::$largeCsvPath)
            ->setLineCount(511)
            ->setHeaders(self::$initialList[0])
            ->setParserConfig(
                [
                    'batchlimit' => 10,
                    'delimiter'  => ',',
                    'enclosure'  => '"',
                    'escape'     => '/',
                ]
            );

        $import->start();
        $progress = new Progress();
        // Each batch should have the last line imported recorded as limit + 1
        $model->process($import, $progress, 100);
        $this->assertEquals(101, $import->getLastLineImported());
        $model->process($import, $progress, 100);
        $this->assertEquals(201, $import->getLastLineImported());
        $model->process($import, $progress, 100);
        $this->assertEquals(301, $import->getLastLineImported());
        $model->process($import, $progress, 100);
        $this->assertEquals(401, $import->getLastLineImported());
        $model->process($import, $progress, 100);
        $this->assertEquals(501, $import->getLastLineImported());
        $model->process($import, $progress, 100);

        // 512 is an empty line in the CSV
        $this->assertEquals(512, $import->getLastLineImported());

        // Excluding the header but including the empty row in 512, there are 511 rows
        $this->assertEquals(511, $import->getProcessedRows());

        $import->end();
    }

    public function testMacLineEndings()
    {
        $oldCsv = self::$csvPath;

        // Generate a new CSV
        self::generateSmallCSV();

        $csv = file_get_contents(self::$csvPath);
        $csv = str_replace("\n", "\r", $csv);
        file_put_contents(self::$csvPath, $csv);

        $this->testProcess();

        @unlink(self::$csvPath);

        self::$csvPath = $oldCsv;
    }
}
