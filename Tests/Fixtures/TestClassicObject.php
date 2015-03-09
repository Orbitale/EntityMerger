<?php
/*
* This file is part of the Pierstoval's EntityMerger package.
*
* (c) Alexandre "Pierstoval" Rock Ancelet <pierstoval@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Pierstoval\Component\EntityMerger\Tests\Fixtures;

use Pierstoval\Component\EntityMerger\Tests\Fixtures\DownedNs\DownedNsEntity;
use Symfony\Component\Validator\Constraints\DateTime;

class TestClassicObject
{

    public $id;

    /**
     * @author Piers
     * @var string
     */
    public $commentedField;

    public $defaultedField = array();

    /**
     * @var integer
     */
    public $bothCommentedAndDefaulted = 0;

    /**
     * @var object
     */
    public $object;

    /**
     * @var \Pierstoval\Component\EntityMerger\Tests\Fixtures\TestClassicObject
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
     * @var \DateTime
     */
    public $date;

    public $notMapped;

    protected $protectedField;

    private $privateField;

}
