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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOSqlite\Driver;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\ORMException;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Serializer as JmsSerializer;
use JMS\Serializer\SerializerBuilder;
use Orbitale\Component\EntityMerger\EntityMerger;
use Orbitale\Component\EntityMerger\Tests\Fixtures\TestClassicObject;
use Orbitale\Component\EntityMerger\Tests\Fixtures\TestEntity;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class EntityMergerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @return EntityManager
     * @throws ORMException
     */
    protected function getEntityManager()
    {
        $config = new Configuration();
        $config->setProxyDir(__DIR__.'/../build/proxies/');
        $config->setProxyNamespace(__NAMESPACE__.'\\__PROXY__');
        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $conn = new Connection(array('pdo' => true), new Driver());
        $em = EntityManager::create($conn, $config);

        return $em;
    }

    /**
     * @return Serializer
     */
    protected function getSerializer()
    {
        return new Serializer(array(new GetSetMethodNormalizer(), new PropertyNormalizer()), array(new JsonEncoder()));
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

    public function testMerge()
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

    public function testMergeSerializeNative()
    {
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

}
