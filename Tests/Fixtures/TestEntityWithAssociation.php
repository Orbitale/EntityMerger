<?php
/*
* This file is part of the Orbitale EntityMerger package.
*
* (c) Alexandre Rock Ancelet <contact@orbitale.io>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Orbitale\Component\EntityMerger\Tests\Fixtures;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class TestEntityWithAssociation {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToMany(targetEntity="Orbitale\Component\EntityMerger\Tests\Fixtures\TestEntity")
     */
    protected $oneToMany;

    /**
     * @ORM\ManyToOne(targetEntity="Orbitale\Component\EntityMerger\Tests\Fixtures\TestEntityWithAssociation")
     */
    protected $manyToOne;
}