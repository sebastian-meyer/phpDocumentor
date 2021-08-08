<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @link      https://phpdoc.org
 */

namespace phpDocumentor\Console\Command\Project;

use League\Pipeline\PipelineInterface;
use phpDocumentor\Console\Application;
use phpDocumentor\Descriptor\ProjectDescriptorBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_keys;

/**
 * @coversDefaultClass \phpDocumentor\Console\Command\Project\RunCommand
 * @covers ::__construct
 * @covers ::<private>
 */
class RunCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @covers ::execute
     */
    public function testPipelineIsInvokedWithTheNecessaryOptions(): void
    {
        $input = new StringInput('--force -f abc');
        $output = new BufferedOutput();

        $pipeline = $this->prophesize(PipelineInterface::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $pipeline->__invoke(Argument::that(static function (array $options) {
            return $options['force'] === true && $options['filename'] === ['abc'];
        }))
            ->shouldBeCalledTimes(1);

        $descriptor = $this->prophesize(ProjectDescriptorBuilder::class);

        $command = new RunCommand($descriptor->reveal(), $pipeline->reveal(), $eventDispatcher->reveal());
        $application = $this->prophesize(Application::class);
        $application->getVersion()->willReturn('3.0');
        $application->getHelperSet()->willReturn(new HelperSet());
        $application->getDefinition()->willReturn(new InputDefinition());
        $command->setApplication($application->reveal());

        $this->assertSame(0, $command->run($input, $output));
    }

    /**
     * @covers ::configure
     */
    public function testCommandIsConfiguredWithTheRightOptions(): void
    {
        $descriptor = $this->prophesize(ProjectDescriptorBuilder::class);
        $pipeline = $this->prophesize(PipelineInterface::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $command = new RunCommand($descriptor->reveal(), $pipeline->reveal(), $eventDispatcher->reveal());
        $options = $command->getDefinition()->getOptions();
        $this->assertEquals(
            [
                'target',
                'cache-folder',
                'filename',
                'directory',
                'encoding',
                'extensions',
                'ignore',
                'ignore-tags',
                'hidden',
                'ignore-symlinks',
                'markers',
                'title',
                'force',
                'validate',
                'visibility',
                'defaultpackagename',
                'sourcecode',
                'template',
                'examples-dir',
                'setting',
                'list-settings',
                'parseprivate',
            ],
            array_keys($options)
        );
    }
}
