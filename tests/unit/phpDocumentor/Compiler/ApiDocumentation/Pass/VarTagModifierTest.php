<?php

declare(strict_types=1);

namespace phpDocumentor\Compiler\ApiDocumentation\Pass;

use phpDocumentor\Descriptor\Collection;
use phpDocumentor\Faker\Faker;
use phpDocumentor\Reflection\Fqsen;
use PHPUnit\Framework\TestCase;

final class VarTagModifierTest extends TestCase
{
    use Faker;

    public function testVarTagWithoutNameIsNotFiltered(): void
    {
        $constantDescriptor = $this->faker()->constantDescriptor(new Fqsen('\\MyClass::MY_CONSTANT'));
        $constantDescriptor->getTags()->set('var', new Collection([$this->faker()->varTagDescriptor()]));

        $classDescriptor = $this->faker()->classDescriptor(new Fqsen('\\MyClass'));
        $classDescriptor->setConstants(new Collection([$constantDescriptor]));

        $apiSetDescriptor = $this->faker()->apiSetDescriptor();
        $apiSetDescriptor->getIndex('classes')->set('\\MyClass', $classDescriptor);

        $subject = new VarTagModifier();
        $subject($apiSetDescriptor);

        self::assertCount(1, $constantDescriptor->getTags()['var']);
    }

    public function testVarTagWithNameIsFilteredWhenNotMatching(): void
    {
        $constantDescriptor = $this->faker()->constantDescriptor(new Fqsen('\\MyClass::MY_CONSTANT'));
        $constantDescriptor->getTags()->set('var', new Collection([$this->faker()->varTagDescriptor('OTHER_CONST')]));

        $classDescriptor = $this->faker()->classDescriptor(new Fqsen('\\MyClass'));
        $classDescriptor->setConstants(new Collection([$constantDescriptor]));

        $apiSetDescriptor = $this->faker()->apiSetDescriptor();
        $apiSetDescriptor->getIndex('classes')->set('\\MyClass', $classDescriptor);

        $subject = new VarTagModifier();
        $subject($apiSetDescriptor);

        self::assertCount(0, $constantDescriptor->getTags()['var']);
    }

    public function testVarTagWithNameIsNotFilteredWhenNameMatches(): void
    {
        $constantDescriptor = $this->faker()->constantDescriptor(new Fqsen('\\MyClass::MY_CONSTANT'));
        $constantDescriptor->getTags()->set('var', new Collection([$this->faker()->varTagDescriptor('MY_CONSTANT')]));

        $classDescriptor = $this->faker()->classDescriptor(new Fqsen('\\MyClass'));
        $classDescriptor->setConstants(new Collection([$constantDescriptor]));

        $apiSetDescriptor = $this->faker()->apiSetDescriptor();
        $apiSetDescriptor->getIndex('classes')->set('\\MyClass', $classDescriptor);

        $subject = new VarTagModifier();
        $subject($apiSetDescriptor);

        self::assertCount(1, $constantDescriptor->getTags()['var']);
    }

    public function testVarTagForClassPropertiesAreFiltered(): void
    {
        $propertyDescriptor = $this->faker()->propertyDescriptor(new Fqsen('\\MyClass::$myProperty'));
        $propertyDescriptor->getTags()->set('var', new Collection([
            $this->faker()->varTagDescriptor('$foo'),
            $this->faker()->varTagDescriptor('myProperty'),
        ]));

        $classDescriptor = $this->faker()->classDescriptor(new Fqsen('\\MyClass'));
        $classDescriptor->setProperties(new Collection([$propertyDescriptor]));

        $apiSetDescriptor = $this->faker()->apiSetDescriptor();
        $apiSetDescriptor->getIndex('classes')->set('\\MyClass', $classDescriptor);

        $subject = new VarTagModifier();
        $subject($apiSetDescriptor);

        self::assertCount(1, $propertyDescriptor->getTags()['var']);
    }

    public function testVarTagsForEnumConstantsAreFiltered(): void
    {
        $constantDescriptor = $this->faker()->constantDescriptor(new Fqsen('\\MyEnum::MY_CONSTANT'));
        $constantDescriptor->getTags()->set('var', new Collection([
            $this->faker()->varTagDescriptor('$foo'),
            $this->faker()->varTagDescriptor('MY_CONSTANT'),
        ]));

        $enumDescriptor = $this->faker()->enumDescriptor(new Fqsen('\\MyEnum'));
        $enumDescriptor->setConstants(new Collection([$constantDescriptor]));

        $apiSetDescriptor = $this->faker()->apiSetDescriptor();
        $apiSetDescriptor->getIndex('enums')->set('\\MyEnum', $enumDescriptor);

        $subject = new VarTagModifier();
        $subject($apiSetDescriptor);

        self::assertCount(1, $constantDescriptor->getTags()['var']);
    }

    public function testVarTagsForTraitConstantsAreFiltered(): void
    {
        $constantDescriptor = $this->faker()->constantDescriptor(new Fqsen('\\MyTrait::MY_CONSTANT'));
        $constantDescriptor->getTags()->set('var', new Collection([
            $this->faker()->varTagDescriptor('$foo'),
            $this->faker()->varTagDescriptor('MY_CONSTANT'),
        ]));

        $traitDescriptor = $this->faker()->traitDescriptor(new Fqsen('\\MyTrait'));
        $traitDescriptor->setConstants(new Collection([$constantDescriptor]));

        $apiSetDescriptor = $this->faker()->apiSetDescriptor();
        $apiSetDescriptor->getIndex('traits')->set('\\MyTrait', $traitDescriptor);

        $subject = new VarTagModifier();
        $subject($apiSetDescriptor);

        self::assertCount(1, $constantDescriptor->getTags()['var']);
    }

    public function testVarTagsForTraitPropertiesAreFiltered(): void
    {
        $propertyDescriptor = $this->faker()->propertyDescriptor(new Fqsen('\\MyTrait::$myProperty'));
        $propertyDescriptor->getTags()->set('var', new Collection([
            $this->faker()->varTagDescriptor('$foo'),
            $this->faker()->varTagDescriptor('myProperty'),
        ]));

        $traitDescriptor = $this->faker()->traitDescriptor(new Fqsen('\\MyTrait'));
        $traitDescriptor->setProperties(new Collection([$propertyDescriptor]));

        $apiSetDescriptor = $this->faker()->apiSetDescriptor();
        $apiSetDescriptor->getIndex('traits')->set('\\MyTrait', $traitDescriptor);

        $subject = new VarTagModifier();
        $subject($apiSetDescriptor);

        self::assertCount(1, $propertyDescriptor->getTags()['var']);
    }
}
