<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link https://phpdoc.org
 */

namespace phpDocumentor\Transformer\Writer;

use InvalidArgumentException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use phpDocumentor\Faker\Faker;
use phpDocumentor\Transformer\Template;
use phpDocumentor\Transformer\Transformation;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \phpDocumentor\Transformer\Writer\FileIo
 * @covers \phpDocumentor\Transformer\Writer\IoTrait
 */
final class FileIoTest extends TestCase
{
    use Faker;

    private vfsStreamDirectory $templatesFolder;
    private vfsStreamDirectory $sourceFolder;
    private vfsStreamDirectory $destinationFolder;
    private Template $template;

    protected function setUp(): void
    {
        $root = vfsStream::setup();
        $this->templatesFolder = vfsStream::newDirectory('templates');
        $root->addChild($this->templatesFolder);
        $this->sourceFolder = vfsStream::newDirectory('source');
        $root->addChild($this->sourceFolder);
        $this->destinationFolder = vfsStream::newDirectory('destination');
        $root->addChild($this->destinationFolder);

        $mountManager = new MountManager(
            [
                'templates' => new Filesystem(new Local($this->templatesFolder->url())),
                'template' => new Filesystem(new Local($this->sourceFolder->url())),
                'destination' => new Filesystem(new Local($this->destinationFolder->url())),
            ],
        );
        $this->template = new Template('My Template', $mountManager);
    }

    public function testCopiesFileFromCustomTemplateToDestination(): void
    {
        $this->sourceFolder->addChild(new vfsStreamFile('index.html.twig'));

        $writer = new FileIo();

        $apiSet = self::faker()->apiSetDescriptor();
        $project = self::faker()->projectDescriptor([self::faker()->versionDescriptor([$apiSet])]);

        $this->assertFalse($this->destinationFolder->hasChild('index.html'));
        $writer->transform(
            new Transformation(
                $this->template,
                'copy',
                'fileio',
                'index.html.twig',
                'index.html',
            ),
            $project,
            $apiSet,
        );
        $this->assertTrue($this->destinationFolder->hasChild('index.html'));
    }

    public function testCopiesFileFromGlobalTemplateToDestination(): void
    {
        $this->templatesFolder->addChild(new vfsStreamFile('templateName/images/image.png'));

        $writer = new FileIo();

        $apiSet = self::faker()->apiSetDescriptor();
        $project = self::faker()->projectDescriptor([self::faker()->versionDescriptor([$apiSet])]);

        $this->assertFalse($this->destinationFolder->hasChild('images/destination.png'));
        $writer->transform(
            new Transformation(
                $this->template,
                'copy',
                'fileio',
                'templates/templateName/images/image.png',
                'images/destination.png',
            ),
            $project,
            $apiSet,
        );
        $this->assertTrue($this->destinationFolder->hasChild('images/destination.png'));
    }

    public function testCopiedFileOverwritesExistingFile(): void
    {
        $apiSet = self::faker()->apiSetDescriptor();
        $project = self::faker()->projectDescriptor([self::faker()->versionDescriptor([$apiSet])]);
        $this->sourceFolder->addChild(vfsStream::newFile('index.html.twig')->withContent('new content'));
        $this->destinationFolder->addChild(vfsStream::newFile('index.html')->withContent('original content'));

        $writer = new FileIo();

        $writer->transform(
            new Transformation(
                $this->template,
                'copy',
                'fileio',
                'index.html.twig',
                'index.html',
            ),
            $project,
            $apiSet,
        );
        $this->assertTrue($this->destinationFolder->hasChild('index.html'));
        $this->assertStringEqualsFile($this->destinationFolder->getChild('index.html')->url(), 'new content');
    }

    public function testCopiesDirectoryFromCustomTemplateToDestination(): void
    {
        $apiSet = self::faker()->apiSetDescriptor();
        $project = self::faker()->projectDescriptor([self::faker()->versionDescriptor([$apiSet])]);
        $sourceDirectory = new vfsStreamDirectory('images');
        $sourceDirectory->addChild(new vfsStreamFile('image1.png'));
        $this->sourceFolder->addChild($sourceDirectory);

        $writer = new FileIo();

        $this->assertFalse($this->destinationFolder->hasChild('images'));
        $writer->transform(
            new Transformation(
                $this->template,
                'copy',
                'fileio',
                'images',
                'images',
            ),
            $project,
            $apiSet,
        );
        $this->assertTrue($this->destinationFolder->hasChild('images'));
        $this->assertTrue($this->destinationFolder->hasChild('images/image1.png'));
    }

    public function testCopiesDirectoryFromGlobalTemplateToDestination(): void
    {
        $apiSet = self::faker()->apiSetDescriptor();
        $project = self::faker()->projectDescriptor([self::faker()->versionDescriptor([$apiSet])]);
        $sourceDirectory = new vfsStreamDirectory('images');
        $sourceDirectory->addChild(new vfsStreamFile('image1.png'));
        $templateDirectory = new vfsStreamDirectory('templateName');
        $templateDirectory->addChild($sourceDirectory);
        $this->templatesFolder->addChild($templateDirectory);

        $writer = new FileIo();

        $this->assertFalse($this->destinationFolder->hasChild('images'));
        $writer->transform(
            new Transformation(
                $this->template,
                'copy',
                'fileio',
                'templates/templateName/images',
                'images',
            ),
            $project,
            $apiSet,
        );
        $this->assertTrue($this->destinationFolder->hasChild('images'));
        $this->assertTrue($this->destinationFolder->hasChild('images/image1.png'));
    }

    public function testCopiesDirectoryRecursivelyFromCustomTemplateToDestination(): void
    {
        $apiSet = self::faker()->apiSetDescriptor();
        $project = self::faker()->projectDescriptor([self::faker()->versionDescriptor([$apiSet])]);
        $subfolder = new vfsStreamDirectory('subfolder');
        $subfolder->addChild(new vfsStreamFile('image2.png'));
        $sourceDirectory = new vfsStreamDirectory('images');
        $sourceDirectory->addChild($subfolder);
        $this->sourceFolder->addChild($sourceDirectory);

        $writer = new FileIo();

        $this->assertFalse($this->destinationFolder->hasChild('images'));
        $writer->transform(
            new Transformation(
                $this->template,
                'copy',
                'fileio',
                'images',
                'images',
            ),
            $project,
            $apiSet,
        );
        $this->assertTrue($this->destinationFolder->hasChild('images'));
        $this->assertTrue($this->destinationFolder->hasChild('images/subfolder/image2.png'));
    }

    public function testCopiesDirectoryRecursivelyFromGlobalTemplateToDestination(): void
    {
        $apiSet = self::faker()->apiSetDescriptor();
        $project = self::faker()->projectDescriptor([self::faker()->versionDescriptor([$apiSet])]);
        $subfolder = new vfsStreamDirectory('subfolder');
        $subfolder->addChild(new vfsStreamFile('image2.png'));
        $sourceDirectory = new vfsStreamDirectory('images');
        $sourceDirectory->addChild($subfolder);
        $templateDirectory = new vfsStreamDirectory('templateName');
        $templateDirectory->addChild($sourceDirectory);
        $this->templatesFolder->addChild($templateDirectory);

        $writer = new FileIo();

        $this->assertFalse($this->destinationFolder->hasChild('images'));
        $writer->transform(
            new Transformation(
                $this->template,
                'copy',
                'fileio',
                'templates/templateName/images',
                'images',
            ),
            $project,
            $apiSet,
        );
        $this->assertTrue($this->destinationFolder->hasChild('images'));
        $this->assertTrue($this->destinationFolder->hasChild('images/subfolder/image2.png'));
    }

    public function testExceptionOccursIfSourceFileCannotBeFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        $writer = new FileIo();

        $transformation = new Transformation(
            $this->template,
            'copy',
            'fileio',
            'unknown_file',
            'nah.png',
        );
        $apiSetDescriptor = self::faker()->apiSetDescriptor();
        $projectDescriptor = self::faker()->projectDescriptor(
            [self::faker()->versionDescriptor([$apiSetDescriptor])],
        );

        $writer->transform($transformation, $projectDescriptor, $apiSetDescriptor);
    }

    public function testExceptionOccursIfQueryIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $writer = new FileIo();

        $apiSetDescriptor = self::faker()->apiSetDescriptor();
        $projectDescriptor = self::faker()->projectDescriptor(
            [self::faker()->versionDescriptor([$apiSetDescriptor])],
        );
        $transformation = new Transformation(
            $this->template,
            'not-a-copy',
            'fileio',
            'unknown_file',
            'nah.png',
        );

        $writer->transform($transformation, $projectDescriptor, $apiSetDescriptor);
    }
}
