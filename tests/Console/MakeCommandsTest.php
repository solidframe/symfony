<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Tests\Console;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SolidFrame\Symfony\Console\MakeAggregateRootCommand;
use SolidFrame\Symfony\Console\MakeCqrsCommandCommand;
use SolidFrame\Symfony\Console\MakeDomainEventCommand;
use SolidFrame\Symfony\Console\MakeEntityCommand;
use SolidFrame\Symfony\Console\MakeEventListenerCommand;
use SolidFrame\Symfony\Console\MakeQueryCommand;
use SolidFrame\Symfony\Console\MakeSagaCommand;
use SolidFrame\Symfony\Console\MakeValueObjectCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class MakeCommandsTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/solidframe_make_test_' . uniqid();
        mkdir($this->tmpDir, 0o755, true);
        chdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
    }

    #[Test]
    public function makeEntityCreatesFile(): void
    {
        $tester = $this->runCommand(new MakeEntityCommand(), ['name' => 'Order']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertFileExists($this->tmpDir . '/src/Domain/Order.php');
    }

    #[Test]
    public function makeValueObjectCreatesFile(): void
    {
        $tester = $this->runCommand(new MakeValueObjectCommand(), ['name' => 'Email']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertFileExists($this->tmpDir . '/src/Domain/Email.php');
    }

    #[Test]
    public function makeAggregateRootCreatesFile(): void
    {
        $tester = $this->runCommand(new MakeAggregateRootCommand(), ['name' => 'Order']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertFileExists($this->tmpDir . '/src/Domain/Order.php');
    }

    #[Test]
    public function makeCqrsCommandCreatesFile(): void
    {
        $tester = $this->runCommand(new MakeCqrsCommandCommand(), ['name' => 'PlaceOrder']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertFileExists($this->tmpDir . '/src/Application/Command/PlaceOrder.php');
    }

    #[Test]
    public function makeQueryCreatesFile(): void
    {
        $tester = $this->runCommand(new MakeQueryCommand(), ['name' => 'GetOrder']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertFileExists($this->tmpDir . '/src/Application/Query/GetOrder.php');
    }

    #[Test]
    public function makeDomainEventCreatesFile(): void
    {
        $tester = $this->runCommand(new MakeDomainEventCommand(), ['name' => 'OrderPlaced']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertFileExists($this->tmpDir . '/src/Domain/Event/OrderPlaced.php');
    }

    #[Test]
    public function makeEventListenerCreatesFile(): void
    {
        $tester = $this->runCommand(new MakeEventListenerCommand(), ['name' => 'SendConfirmation']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertFileExists($this->tmpDir . '/src/Application/Listener/SendConfirmation.php');
    }

    #[Test]
    public function makeSagaCreatesFile(): void
    {
        $tester = $this->runCommand(new MakeSagaCommand(), ['name' => 'OrderSaga']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertFileExists($this->tmpDir . '/src/Application/Saga/OrderSaga.php');
    }

    #[Test]
    public function generatedEntityHasCorrectContent(): void
    {
        $this->runCommand(new MakeEntityCommand(), ['name' => 'Order']);

        $content = file_get_contents($this->tmpDir . '/src/Domain/Order.php');

        self::assertStringContainsString('final class Order extends AbstractEntity', $content);
    }

    #[Test]
    public function failsIfFileAlreadyExists(): void
    {
        $this->runCommand(new MakeEntityCommand(), ['name' => 'Order']);
        $tester = $this->runCommand(new MakeEntityCommand(), ['name' => 'Order']);

        self::assertSame(1, $tester->getStatusCode());
    }

    #[Test]
    public function supportsSubdirectories(): void
    {
        $tester = $this->runCommand(new MakeEntityCommand(), ['name' => 'Order/OrderItem']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertFileExists($this->tmpDir . '/src/Domain/Order/OrderItem.php');
    }

    /**
     * @param array<string, mixed> $input
     */
    private function runCommand(\Symfony\Component\Console\Command\Command $command, array $input): CommandTester
    {
        $application = new Application();
        $application->add($command);

        $tester = new CommandTester($command);
        $tester->execute($input);

        return $tester;
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
