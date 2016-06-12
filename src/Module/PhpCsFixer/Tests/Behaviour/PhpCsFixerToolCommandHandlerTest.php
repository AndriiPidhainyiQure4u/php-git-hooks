<?php

namespace Module\PhpCsFixer\Tests\Behaviour;

use Module\Configuration\Service\HookQuestions;
use Module\Configuration\Tests\Stub\ConfigurationDataResponseStub;
use Module\Files\Contract\Query\PhpFilesExtractorQuery;
use Module\Files\Tests\Stub\PhpFilesResponseStub;
use Module\Git\Contract\Response\BadJobLogoResponse;
use Module\Git\Service\PreCommitOutputWriter;
use Module\Git\Tests\Stub\FilesCommittedStub;
use Module\PhpCsFixer\Contract\Command\PhpCsFixerToolCommand;
use Module\PhpCsFixer\Contract\CommandHandler\PhpCsFixerToolCommandHandler;
use Module\PhpCsFixer\Contract\Exception\PhpCsFixerViolationsException;
use Module\PhpCsFixer\Service\PhpCsFixerTool;
use Module\PhpCsFixer\Service\PhpCsFixerToolExecutor;
use Module\PhpCsFixer\Tests\Infrastructure\PhpCsFixerUnitTestCase;

class PhpCsFixerToolCommandHandlerTest extends PhpCsFixerUnitTestCase
{
    /**
     * @var PhpCsFixerToolCommandHandler
     */
    private $phpCsFixerToolCommandHandler;

    protected function setUp()
    {
        $this->phpCsFixerToolCommandHandler = new PhpCsFixerToolCommandHandler(
            new PhpCsFixerTool($this->getQueryBus(), new PhpCsFixerToolExecutor(
                $this->getOutputInterface(),
                $this->getPhpCsFixerToolProcessor()
            ))
        );
    }

    /**
     * @test
     */
    public function itShouldNotExecuteTool()
    {
        $files = FilesCommittedStub::createWithoutPhpFiles();
        $configurationData = ConfigurationDataResponseStub::createAllEnabled();

        $this->shouldHandleQuery(new PhpFilesExtractorQuery($files), PhpFilesResponseStub::create([]));

        $this->phpCsFixerToolCommandHandler->handle(
            new PhpCsFixerToolCommand(
                $files,
                $configurationData->isPhpCsFixerPsr0(),
                $configurationData->isPhpCsFixerPsr1(),
                $configurationData->isPhpCsFixerPsr2(),
                $configurationData->isPhpCsFixerSymfony(),
                $configurationData->getErrorMessage()
            )
        );
    }

    /**
     * @test
     */
    public function itShouldThrowsException()
    {
        $this->expectException(PhpCsFixerViolationsException::class);

        $files = FilesCommittedStub::createAllFiles();
        $configurationData = ConfigurationDataResponseStub::createAllEnabled();
        $phpFiles = FilesCommittedStub::createOnlyPhpFiles();

        $this->shouldHandleQuery(
            new PhpFilesExtractorQuery($files),
            PhpFilesResponseStub::create($phpFiles)
        );

        $outputMessage = new PreCommitOutputWriter('Checking PSR0 code style with PHP-CS-FIXER');
        $this->shouldWriteOutput($outputMessage->getMessage());

        $errors = null;
        foreach ($phpFiles as $file) {
            $errorText = 'ERROR';
            $this->shouldProcessPhpCsFixerTool($file, 'PSR0', $errorText);
            $errors .= $errorText;
        }

        $this->shouldWriteLnOutput($outputMessage->setError($errors));
        $this->shouldWriteLnOutput(BadJobLogoResponse::paint($configurationData->getErrorMessage()));

        $this->phpCsFixerToolCommandHandler->handle(
            new PhpCsFixerToolCommand(
                $files,
                $configurationData->isPhpCsFixerPsr0(),
                $configurationData->isPhpCsFixerPsr1(),
                $configurationData->isPhpCsFixerPsr2(),
                $configurationData->isPhpCsFixerSymfony(),
                $configurationData->getErrorMessage()
            )
        );
    }

    /**
     * @test
     */
    public function itShouldWorksFine()
    {
        $files = FilesCommittedStub::createAllFiles();
        $configurationData = ConfigurationDataResponseStub::createAllEnabled();
        $phpFiles = FilesCommittedStub::createOnlyPhpFiles();

        $this->shouldHandleQuery(
            new PhpFilesExtractorQuery($files),
            PhpFilesResponseStub::create($phpFiles)
        );

        $outputMessagePsr0 = new PreCommitOutputWriter('Checking PSR0 code style with PHP-CS-FIXER');
        $this->shouldWriteOutput($outputMessagePsr0->getMessage());

        foreach ($phpFiles as $file) {
            $this->shouldProcessPhpCsFixerTool($file, 'PSR0', null);
        }

        $this->shouldWriteLnOutput($outputMessagePsr0->getSuccessfulMessage());

        $outputMessagePsr1 = new PreCommitOutputWriter('Checking PSR1 code style with PHP-CS-FIXER');
        $this->shouldWriteOutput($outputMessagePsr1->getMessage());

        foreach ($phpFiles as $file) {
            $this->shouldProcessPhpCsFixerTool($file, 'PSR1', null);
        }

        $this->shouldWriteLnOutput($outputMessagePsr1->getSuccessfulMessage());

        $outputMessagePsr2 = new PreCommitOutputWriter('Checking PSR2 code style with PHP-CS-FIXER');
        $this->shouldWriteOutput($outputMessagePsr2->getMessage());

        foreach ($phpFiles as $file) {
            $this->shouldProcessPhpCsFixerTool($file, 'PSR2', null);
        }

        $this->shouldWriteLnOutput($outputMessagePsr2->getSuccessfulMessage());

        $outputMessageSymfony = new PreCommitOutputWriter('Checking SYMFONY code style with PHP-CS-FIXER');
        $this->shouldWriteOutput($outputMessageSymfony->getMessage());

        foreach ($phpFiles as $file) {
            $this->shouldProcessPhpCsFixerTool($file, 'SYMFONY', null);
        }

        $this->shouldWriteLnOutput($outputMessageSymfony->getSuccessfulMessage());

        $this->phpCsFixerToolCommandHandler->handle(
            new PhpCsFixerToolCommand(
                $files,
                $configurationData->isPhpCsFixerPsr0(),
                $configurationData->isPhpCsFixerPsr1(),
                $configurationData->isPhpCsFixerPsr2(),
                $configurationData->isPhpCsFixerSymfony(),
                $configurationData->getErrorMessage()
            )
        );
    }
}
