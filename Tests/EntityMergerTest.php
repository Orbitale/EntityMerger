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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use stdClass;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Serializer as JmsSerializer;
use JMS\Serializer\SerializerBuilder;
use Orbitale\Component\EntityMerger\EntityMerger;
use Orbitale\Component\EntityMerger\Tests\Fixtures\TestClassicObject;
use Orbitale\Component\EntityMerger\Tests\Fixtures\Entity\TestEntity;
use Orbitale\Component\EntityMerger\Tests\Fixtures\Entity\TestEntityWithAssociation;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\CustomNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class EntityMergerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Serializer must be an instance of SerializerInterface, either Symfony native or JMS one.
     */
    public function testConstructException()
    {
        new EntityMerger(null, (object) array());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You must specify an object in order to merge the array in it.
     */
    public function testMergeInvalidException()
    {
        $merger = new EntityMerger();
        $merger->merge(1, array());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage If you want to merge an array into an entity, you must populate this array.
     */
    public function testMergeNoDataObject()
    {
        $merger = new EntityMerger();
        $merger->merge((object) array(), array());
    }

    public function testMergeEntity()
    {
        $em = $this->getEntityManager();

        $merger = new EntityMerger($em);

        /** @var TestEntity $entity */
        $entity = new TestEntity;

        $entity->setId(1)->setString('Name');
        $this->assertEquals(1, $entity->getId());

        /** @var TestEntity $merged */
        $mergedEntity = $merger->merge($entity, array('id' => 10));

        $this->assertEquals(10, $mergedEntity->getId());
    }

    public function testMergeClassicObject()
    {
        $merger = new EntityMerger();
        /** @var TestClassicObject $object */
        $object = $merger->merge(new TestClassicObject(), array('commentedField' => 'this is awesome !'));
        $this->assertEquals('this is awesome !', $object->commentedField);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Could not find field "field" in class "stdClass"
     */
    public function testUnmappedField()
    {
        $object = new stdClass();
        $object->field = null;
        $merger = new EntityMerger();
        $merger->merge($object, array('field' => 'value'));
    }

    public function testMergeSerializeNative()
    {
        if (!class_exists('Symfony\Component\Serializer\Normalizer\PropertyNormalizer')) {
            $this->markTestSkipped('Symfony 2.3 and 2.4 cannot use the serializer with the EntityMerger.');
        }
        $serializer = $this->getSerializer();

        $merger = new EntityMerger(null, $serializer);

        $serializedObject = new TestClassicObject();
        $serializedObject->commentedField = 'this field is commented';

        /** @var TestClassicObject $newItem */
        $newItem = $merger->merge(new TestClassicObject(), $serializedObject);

        $this->assertEquals($serializedObject->commentedField, $newItem->commentedField);
    }

    public function testMergeSerializeJms()
    {
        $serializer = $this->getJmsSerializer();

        $merger = new EntityMerger(null, $serializer);

        $serializedObject = new TestClassicObject();
        $serializedObject->commentedField = 'this field is commented';

        /** @var TestClassicObject $newItem */
        $newItem = $merger->merge(new TestClassicObject(), $serializedObject);

        $this->assertEquals($serializedObject->commentedField, $newItem->commentedField);
    }

    public function testMergeWithMapping()
    {
        $object = new TestClassicObject();
        $object->commentedField = 'Should never be analyzed.';
        $data = array('commentedField' => 'This field should be specified.');
        $mapping = array('commentedField' => true,);

        $merger = new EntityMerger();

        $merger->merge($object, $data, $mapping);

        $this->assertEquals($data['commentedField'], $object->commentedField);
    }

    public function testMergeWithMappingJson()
    {
        $object = new TestClassicObject();
        $object->commentedField = 'Should never be analyzed.';
        $data = array('commented_field' => 'This field should be specified.');
        $mapping = array('commented_field' => json_encode(array('objectField' => 'commentedField')));

        $merger = new EntityMerger();

        $merger->merge($object, $data, $mapping);

        $this->assertEquals($data['commented_field'], $object->commentedField);
    }

    public function testMergeWithMappingObjectField()
    {
        $object = new TestClassicObject();
        $object->commentedField = 'Should never be analyzed.';
        $data = array('commented_field' => 'This field should be specified.');
        $mapping = array('commented_field' => array('objectField' => 'commentedField'));

        $merger = new EntityMerger();

        $merger->merge($object, $data, $mapping);

        $this->assertEquals($data['commented_field'], $object->commentedField);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage If you want to specify "inexistant_field" as an mergeable field, then you must have to set it in your data object.
     */
    public function testMergeWithMappingInvalid()
    {
        $object = new TestClassicObject();
        $object->commentedField = 'Should never be analyzed.';
        $data = array('commentedField' => 'This field should be specified.');
        $mapping = array('commentedField' => true, 'inexistant_field' => true);

        $merger = new EntityMerger();

        $merger->merge($object, $data, $mapping);
    }

    public function testMergeWithMappingAssociation()
    {
        $object = new TestEntityWithAssociation();

        $em = $this->getEntityManager();

        $testEntityInDatabase = new TestEntity();
        $testEntityInDatabase->setString('This should be found for test.');
        $em->persist($testEntityInDatabase);
        $em->flush();

        $merger = new EntityMerger($em);

        $data = array('manyToOne' => array('id' => 1, 'string' => 'New string.'));
        $mapping = array('manyToOne' => true);

        $merger->merge($object, $data, $mapping);

        $this->assertEquals($data['manyToOne']['id'], $object->getManyToOne()->getId());
        $this->assertEquals($data['manyToOne']['string'], $object->getManyToOne()->getString());
    }

    /**
     * @return EntityManager
     * @throws ORMException
     */
    protected function getEntityManager()
    {
        $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/Fixtures/Entity"), true);
        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader(), array(__DIR__."/Fixtures/Entity")));

        if (file_exists(__DIR__.'/../build/test.db')) {
            unlink(__DIR__.'/../build/test.db');
        }

        $em = EntityManager::create(array('path' => __DIR__.'/../build/test.db', 'driver' => 'pdo_sqlite'), $config);

        $tool = new SchemaTool($em);
        $tool->createSchema($em->getMetadataFactory()->getAllMetadata());

        return $em;
    }

    /**
     * @return Serializer
     */
    protected function getSerializer()
    {
        $normalizers = array(new GetSetMethodNormalizer(), new CustomNormalizer());
        if (class_exists('Symfony\Component\Serializer\Normalizer\PropertyNormalizer')) {
            $normalizers[] = new PropertyNormalizer();
        }
        $encoders = array(new JsonEncoder());
        return new Serializer($normalizers, $encoders);
    }

    /**
     * @return JmsSerializer
     */
    protected function getJmsSerializer()
    {
        $builder = SerializerBuilder::create();
        $builder->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy());
        return $builder->build();
    }
}
