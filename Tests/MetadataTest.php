<?php
/*
* This file is part of the Orbitale EntityMerger package.
*
* (c) Alexandre Rock Ancelet <contact@orbitale.io>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Orbitale\Component\EntityMerger\Tests;

use Orbitale\Component\EntityMerger\EmptyClassMetadata;

class MetadataTest extends \PHPUnit_Framework_TestCase
{

    public function testEmptyMetadata()
    {

        $metadata = new EmptyClassMetadata('Orbitale\Component\EntityMerger\Tests\Fixtures\TestClassicObject');

        $this->assertInstanceOf('Orbitale\Component\EntityMerger\EmptyClassMetadata', $metadata);

        $this->assertInstanceOf('ReflectionClass', $metadata->getReflectionClass());

        $this->assertTrue($metadata->hasField('id'));
        $this->assertTrue($metadata->hasField('privateField'));
        $this->assertTrue($metadata->hasField('protectedField'));

        $this->assertFalse($metadata->hasField('this_field_should_not_exist'));

        $this->assertEquals(array(
            'id',
            'commentedField',
            'defaultedField',
            'bothCommentedAndDefaulted',
            'object',
            'classField',
            'classCollection',
            'externalClass',
            'date',
            'notMapped',
            'protectedField',
            'privateField',
        ), $metadata->getFieldNames());

        $this->assertNull($metadata->isIdentifier('id'));
        $this->assertNull($metadata->hasAssociation('id'));
        $this->assertNull($metadata->isSingleValuedAssociation('id'));
        $this->assertNull($metadata->isCollectionValuedAssociation('id'));
        $this->assertNull($metadata->isAssociationInverseSide('id'));
        $this->assertNull($metadata->getAssociationTargetClass('id'));
        $this->assertNull($metadata->getAssociationMappedByTargetField('id'));
        $this->assertNull($metadata->getIdentifierValues((object) array()));
        $this->assertNull($metadata->getIdentifier());
        $this->assertNull($metadata->getIdentifierFieldNames());
        $this->assertNull($metadata->getAssociationNames());

        $this->assertEmpty($metadata->getTypeOfField('id'));

        $this->assertEquals('string', $metadata->getTypeOfField('commentedField'));
        $this->assertEquals('array', $metadata->getTypeOfField('defaultedField'));
        $this->assertEquals('integer', $metadata->getTypeOfField('bothCommentedAndDefaulted'));
        $this->assertEquals('object', $metadata->getTypeOfField('object'));
        $this->assertEquals('\Orbitale\Component\EntityMerger\Tests\Fixtures\TestClassicObject', $metadata->getTypeOfField('classField'));
        $this->assertEquals('Orbitale\Component\EntityMerger\Tests\Fixtures\TestClassicObject', $metadata->getTypeOfField('classCollection'));
        $this->assertEquals('Orbitale\Component\EntityMerger\Tests\Fixtures\DownedNs\DownedNsEntity', $metadata->getTypeOfField('externalClass'));
        $this->assertEquals('\DateTime', $metadata->getTypeOfField('date'));
        $this->assertEquals(null, $metadata->getTypeOfField('notMapped'));

    }

    public function testEntityMetadataForProperty()
    {
        $metadata = new EmptyClassMetadata('Orbitale\Component\EntityMerger\Tests\Fixtures\Entity\TestEntity');

        $this->assertNull($metadata->getTypeOfField('string'));
    }

}
