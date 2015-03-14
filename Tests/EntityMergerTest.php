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
use Orbitale\Component\EntityMerger\EntityMerger;
use Orbitale\Component\EntityMerger\Tests\Fixtures\TestEntity;

class EntityMergerTest extends \PHPUnit_Framework_TestCase
{

    protected function getEmMock()
    {
        $config = new Configuration();
        $config->setProxyDir(__DIR__.'/../../build/proxies/');
        $config->setProxyNamespace(__NAMESPACE__.'\\__PROXY__');
        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $conn = new Connection(array('pdo' => true), new Driver());
        $em = EntityManager::create($conn, $config);

        return $em;
    }

    public function testMerge()
    {
        $em = $this->getEmMock();

        $merger = new EntityMerger($em);

        /** @var TestEntity $entity */
        $entity = new TestEntity;

        $entity->setId(1)->setString('Name');
        $this->assertEquals(1, $entity->getId());

        /** @var TestEntity $merged */
        $mergedEntity = $merger->merge($entity, array('id' => 10));

        $this->assertEquals(10, $mergedEntity->getId());
    }

}
