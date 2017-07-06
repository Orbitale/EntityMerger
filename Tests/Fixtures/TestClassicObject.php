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

use Orbitale\Component\EntityMerger\Tests\Fixtures\DownedNs\DownedNsEntity;
use Symfony\Component\Validator\Constraints\DateTime;

class TestClassicObject
{
    public $id;

    /**
     * @author Piers
     *
     * @var string
     */
    public $commentedField;

    public $defaultedField = [];

    /**
     * @var int
     */
    public $bothCommentedAndDefaulted = 0;

    /**
     * @var object
     */
    public $object;

    /**
     * @var \Orbitale\Component\EntityMerger\Tests\Fixtures\TestClassicObject
     */
    public $classField;

    /**
     * @var TestClassicObject[]
     */
    public $classCollection;

    /**
     * @var DownedNsEntity It's considered as an external class because of the "use" statement
     */
    public $externalClass;

    /**
     * @DateTime() annotation just to say
     *
     * @var \DateTime
     */
    public $date;

    public $notMapped;

    protected $protectedField;

    private $privateField;
}
