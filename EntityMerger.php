<?php
/*
* This file is part of the Pierstoval's EntityMerger package.
*
* (c) Alexandre "Pierstoval" Rock Ancelet <pierstoval@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Pierstoval\Component\EntityMerger;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\Serializer\SerializerInterface;
use JMS\Serializer\SerializerInterface as JMSSerializerInterface;

class EntityMerger
{

    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    public function __construct(ObjectManager $om, $serializer = null)
    {
        $this->om = $om;

        if ($serializer && !($serializer instanceof SerializerInterface || $serializer instanceof JMSSerializerInterface)) {
            throw new \InvalidArgumentException('Serializer must be an instance of SerializerInterface, either Symfony native or JMS oneS.');
        }
        $this->serializer = $serializer;
    }

    /**
     * Tries to merge array $dataObject into $object
     *
     * @param object $object
     * @param array $dataObject
     * @param array $mapping
     * @return object
     */
    public function merge($object, array $dataObject, $mapping = array())
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('You must specify an object in order to merge the array in it.');
        }
        if (is_object($dataObject) && $this->serializer) {
            // Serialize/deserialize object into array
            $dataObject = $this->serializer->deserialize($this->serializer->serialize($dataObject, 'json'), 'array', 'json');
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
            if (isset($dataObject[$field])) {
                $this->mergeField($field, $object, $dataObject[$field], $params);
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'If you want to specify "%s" as an mergeable field, then you must have to set it in your data object.',
                    $field
                ));
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

        $metadatas = $this->om->getClassMetadata($currentlyAnalyzedClass);
        $hasMapping = $metadatas->hasField($mapping['objectField']) ? true : $metadatas->hasAssociation($mapping['objectField']);

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
        } else {
            throw new \InvalidArgumentException(sprintf(
                'Could not find field "%s" in class "%s"',
                $mapping['objectField'], $currentlyAnalyzedClass
            ));
        }
    }
}
