<?php
/*
* This file is part of the Orbitale EntityMerger package.
*
* (c) Alexandre Rock Ancelet <contact@orbitale.io>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Orbitale\Component\EntityMerger\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="test_entity_asso")
 */
class TestEntityWithAssociation {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var TestEntity
     * @ORM\ManyToOne(targetEntity="Orbitale\Component\EntityMerger\Tests\Fixtures\Entity\TestEntity")
     */
    protected $manyToOne;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return TestEntityWithAssociation
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return TestEntity
     */
    public function getManyToOne()
    {
        return $this->manyToOne;
    }

    /**
     * @param TestEntity $manyToOne
     *
     * @return TestEntityWithAssociation
     */
    public function setManyToOne($manyToOne)
    {
        $this->manyToOne = $manyToOne;

        return $this;
    }

}
