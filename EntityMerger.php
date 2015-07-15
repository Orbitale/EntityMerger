<?php
/*
* This file is part of the Orbitale's EntityMerger package.
*
* (c) Alexandre Rock Ancelet <contact@orbitale.io>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Orbitale\Component\EntityMerger;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\Serializer\SerializerInterface;
use JMS\Serializer\SerializerInterface as JMSSerializerInterface;

class EntityMerger
{

    /**
     * When merging objects, the merger will use the same "merge" for the associated fields,
     * doing every merge recursively for each object involved.
     * Be careful: if the merged object also have associations, it can be risky.
     * Be sure to have the whole object mapped recursively.
     */
    const ASSOCIATIONS_MERGE = 1;

    /**
     * For associations, the merger will search for an entity in the Database, depending on the mapping.
     * It will search for the primary key identifier, and make a basic $repo->find($id),
     * only if the specified identifier is mapped in the "dataObject"
     */
    const ASSOCIATIONS_FIND = 2;

    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var integer
     */
    protected $associationStrategy;

    public function __construct(ObjectManager $om = null, $serializer = null, $associationStrategy = null)
    {
        $this->om = $om;

        if ($serializer && !($serializer instanceof SerializerInterface || $serializer instanceof JMSSerializerInterface)) {
            throw new \InvalidArgumentException('Serializer must be an instance of SerializerInterface, either Symfony native or JMS one.');
        }
        $this->serializer = $serializer;
        $this->associationStrategy = $associationStrategy ?: (self::ASSOCIATIONS_MERGE | self::ASSOCIATIONS_FIND);
    }

    /**
     * @param integer $strategy
     * @return $this
     */
    public function setAssociationStrategy($strategy)
    {
        $this->associationStrategy = (int) $strategy;
        return $this;
    }

    /**
     * @return integer
     */
    public function getAssociationStrategy()
    {
        return $this->associationStrategy;
    }

    /**
     * Tries to merge array $dataObject into $object
     *
     * @param object       $object
     * @param array|object $dataObject
     * @param array        $mapping
     * @return object
     */
    public function merge($object, $dataObject, $mapping = array())
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('You must specify an object in order to merge the array in it.');
        }
        if (is_object($dataObject) && $this->serializer) {
            // Serialize/deserialize object into array
            // This allows to merge two objects together
            $dataObject = json_decode($this->serializer->serialize($dataObject, 'json'), true);
        }
        if (!count($dataObject)) {
            throw new \InvalidArgumentException('If you want to merge an array into an entity, you must populate this array.');
        }
        return $this->doMerge($object, $dataObject, $mapping);
    }

    /**
     * @param $object
     * @param array $dataObject
     * @param array $mapping
     * @return mixed
     */
    protected function doMerge($object, array $dataObject, $mapping = array())
    {
        if (count($mapping)) {
            foreach ($mapping as $field => $params) {
                if (is_string($params)) {
                    // Tries to decode if params is a string, so we can have a mapping information stringified in JSON
                    $params = json_decode($params, true);
                }
                if (!is_array($params)) {
                    // Allows anything to be transformed into an array
                    // If we used json_decode, "null" will be returned and then it'll become an empty array
                    // Although $params should be either "1", "true" or an array, even empty
                    $params = array();
                }
                if (array_key_exists($field, $dataObject)) {
                    $this->mergeField($field, $object, $dataObject[$field], $params);
                } else {
                    throw new \InvalidArgumentException(sprintf(
                        'If you want to specify "%s" as an mergeable field, then you must have to set it in your data object.',
                        $field
                    ));
                }
            }
        } else {
            foreach ($dataObject as $field => $value) {
                $this->mergeField($field, $object, $value, array());
            }
        }
        return $object;
    }

    /**
     * Will try to merge a field by automatically searching in its doctrine mapping datas.
     *
     * @param string $field The field to merge
     * @param object $object The entity you want to "hydrate"
     * @param mixed $value The datas you want to merge in the object field
     * @param array $userMapping An array containing mapping informations provided by the user. Mostly used for relationships
     */
    protected function mergeField($field, $object, $value, array $userMapping = array())
    {
        $currentlyAnalyzedClass = get_class($object);

        $mapping = array_merge(array(
            'pivot' => null,
            'objectField' => $field,
        ), $userMapping);

        if ($this->om) {
            $metadatas = $this->om->getClassMetadata($currentlyAnalyzedClass);
        } else {
            $metadatas = new EmptyClassMetadata($currentlyAnalyzedClass);
        }

        $hasMapping = $metadatas->hasField($mapping['objectField']) ?: $metadatas->hasAssociation($mapping['objectField']);

        $reflectionProperty = null;

        if ($hasMapping) {
            if ($metadatas->hasField($mapping['objectField'])) {
                // Handles a field
                $reflectionProperty = $metadatas->getReflectionClass()->getProperty($mapping['objectField']);
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($object, $value);
            } elseif ($metadatas->hasAssociation($mapping['objectField'])) {
                // Handles a relation
                $relationClass = $metadatas->getAssociationTargetClass($mapping['objectField']);
                $reflectionProperty = $metadatas->getReflectionClass()->getProperty($mapping['objectField']);
                $reflectionProperty->setAccessible(true);

                $pivotValue = isset($value[$mapping['pivot']]) ? $value[$mapping['pivot']] : null;

                if (null === $pivotValue) {
                    // If no pivot value is specified, we'll get automatically the Entity's Primary Key
                    /** @var ClassMetadataInfo $relationMetadatas */
                    $relationMetadatas = $this->om->getMetadataFactory($relationClass)->getMetadataFor($relationClass);
                    $pivotValue = $relationMetadatas->getSingleIdentifierFieldName();
                }

                if ($pivotValue && ($this->associationStrategy & self::ASSOCIATIONS_FIND)) {
                    // "find" strategy.
                    if ($metadatas->isSingleValuedAssociation($mapping['objectField'])) {
                        // Single valued : ManyToOne or OneToOne
                        if ($value) {
                            $newRelationObject = $this->om->getRepository($relationClass)->findOneBy(array($pivotValue => $value[$pivotValue]));
                        } else {
                            $newRelationObject = null;
                        }
                        $reflectionProperty->setValue($object, $newRelationObject);
                    } elseif ($metadatas->isCollectionValuedAssociation($mapping['objectField'])) {
                        // Collection : OneToMany or ManyToMany
                        if ($value) {
                            if (!is_array($value)) {
                                $value = array($value);
                            }
                            $newCollection = $this->om->getRepository($relationClass)->findBy(array($pivotValue => $value));
                        } else {
                            $newCollection = array();
                        }
                        $reflectionProperty->setValue($object, $newCollection);
                    }
                }

                if ($value && ($this->associationStrategy & self::ASSOCIATIONS_MERGE)) {
                    // "merge" strategy
                    if ($metadatas->isSingleValuedAssociation($mapping['objectField'])) {
                        $reflectionProperty->setValue($object, $this->merge($reflectionProperty->getValue($object) ?: new $relationClass, $value));
                    }
                }
            }
        } else {
            throw new \InvalidArgumentException(sprintf(
                'Could not find field "%s" in class "%s"',
                $mapping['objectField'], $currentlyAnalyzedClass
            ));
        }
    }

}
