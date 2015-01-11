<?php

namespace Pierstoval\Component\EntityMerger;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class EntityMerger
{

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var string
     */
    protected $currentlyAnalyzedClass = '';

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Tries to merge array $userObject into $object
     *
     * @param object $object
     * @param array $userObject
     * @param array $mapping
     * @return object
     */
    public function merge($object, array $userObject, $mapping = array())
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('You must specify an object in order to merge the array in it.');
        }
        $this->currentlyAnalyzedClass = get_class($object);
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
            if (isset($userObject[$field])) {
                $this->mergeField($field, $object, $userObject[$field], $params);
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'If you want to specify "%s" as an editable field, then you must have it set in your data object.',
                    $field
                ));
            }
        }
        $this->currentlyAnalyzedClass = '';
        return $object;
    }

    /**
     * Will try to merge a field by automatically searching in its doctrine mapping datas.
     *
     * @param string $field The field to merge
     * @param object $object The entity you want to "hydrate"
     * @param mixed $value The datas you want to merge in the object field
     * @param array $userMapping An array containing mapping informations provided by the user. Mostly used for relationships
     * @return object
     */
    protected function mergeField($field, $object, $value, array $userMapping = array())
    {
        $mapping = array_merge(array(
            'pivot' => null,
            'objectField' => $field,
        ), $userMapping);

        $metadatas = $this->em->getClassMetadata($this->currentlyAnalyzedClass);
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
                    $relationMetadatas = $this->em->getMetadataFactory($relationClass)->getMetadataFor($relationClass);
                    $pivotValue = $relationMetadatas->getSingleIdentifierFieldName();
                }

                if ($metadatas->isSingleValuedAssociation($mapping['objectField'])) {
                    // Single valued : ManyToOne or OneToOne
                    if ($value) {
                        $newRelationObject = $this->em->getRepository($relationClass)->findOneBy(array($pivotValue => $value[$pivotValue]));
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
                        $newCollection = $this->em->getRepository($relationClass)->findBy(array($pivotValue => $value));
                    } else {
                        $newCollection = array();
                    }
                    $reflectionProperty->setValue($object, $newCollection);
                }
            }
        } else {
            throw new \InvalidArgumentException(sprintf(
                'Could not find field "%s" in class "%s"',
                $mapping['objectField'], $this->currentlyAnalyzedClass
            ));
        }
        return $object;
    }
}
