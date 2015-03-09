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

use Doctrine\Common\Annotations\SimpleAnnotationReader;
use ReflectionClass;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

class EmptyClassMetadata  implements ClassMetadata
{

    protected $className;

    /**
     * @var ReflectionClass
     */
    protected $reflClass;

    public function __construct($className)
    {
        $this->className = $className;
    }

    public function getName()
    {
        return $this->className;
    }

    /**
     * @return ReflectionClass
     */
    public function getReflectionClass()
    {
        if (!$this->reflClass) {
            $this->reflClass = new ReflectionClass($this->getName());
        }
        return $this->reflClass;
    }

    public function hasField($fieldName)
    {
        return $this->getReflectionClass()->hasProperty($fieldName);
    }

    public function getFieldNames()
    {
        $props = array();
        $reflProps = $this->getReflectionClass()->getProperties();
        foreach ($reflProps as $prop) {
            if ($this->hasField($prop->getName())) {
                $props[] = $prop->getName();
            }
        }
        return $props;
    }

    public function getTypeOfField($fieldName)
    {
        if ($this->getReflectionClass()->hasProperty($fieldName)) {
            $reflProp = $this->getReflectionClass()->getProperty($fieldName);
            $doc = (string) $reflProp->getDocComment();
            if (strpos($doc, '@var') === false) {
                // First, if we don't have any "@var" annotation,
                // we try to check if the property has a default value
                $defaultProps = $this->getReflectionClass()->getDefaultProperties();
                if (isset($defaultProps[$fieldName])) {
                    return gettype($defaultProps[$fieldName]);
                }
            } else {
                return $this->getTypeByAnnotation($reflProp);
            }
        }
        return null;
    }

    protected function getTypeByAnnotation(\ReflectionProperty $reflProp)
    {
            preg_match('~@var +([^\s]+)(?:\[\])?(?:.+)?\n~is', $reflProp->getDocComment(), $annotations);
            $annotations = array_map('trim', $annotations);
            if (isset($annotations[1])) {
                $annotation = str_replace('[]', '', $annotations[1]); // Trim from the potential array notation "type[]"
                if ($this->getValidType($annotation)) {
                    return $this->getValidType($annotation);
                } elseif (class_exists($annotation)) {
                    return $annotation;
                } elseif (class_exists($this->getReflectionClass()->getNamespaceName().'\\'.$annotation)) {
                    return $this->getReflectionClass()->getNamespaceName().'\\'.$annotation;
                }
                $reader = new SimpleAnnotationReader();
                $reader->addNamespace($this->getReflectionClass()->getNamespaceName());

                $readAnnotations = $reader->getPropertyAnnotations($reflProp);
                foreach ($readAnnotations as $readAnnotation) {
                    if (strpos($readAnnotation, $annotation) === strlen($readAnnotation) - strlen($annotation)) {
                        return $readAnnotation;
                    }
                }

                $file = $this->getReflectionClass()->getFileName();
                $content = file_get_contents($file);

                // In case PHP5.5+ and traits
                $content = preg_replace('~(class [^{]+\{)(?:\s*use [^;]+;)*~isu', '$1', $content);

                preg_match_all('~^\s*use ([^;]+);~m', $content, $matches);
                $matches = isset($matches[1]) ? $matches[1] : array();
                foreach ($matches as $fqcn) {
                    if (strpos($fqcn, $annotation) === strlen($fqcn) - strlen($annotation)) {
                        return $fqcn;
                    }
                }
            }
    }

    public function isIdentifier($fieldName)
    {
        return null;
    }

    public function hasAssociation($fieldName)
    {
        return null;
    }

    public function isSingleValuedAssociation($fieldName)
    {
        return null;
    }

    public function isCollectionValuedAssociation($fieldName)
    {
        return null;
    }

    public function isAssociationInverseSide($assocName)
    {
        return null;
    }

    public function getAssociationTargetClass($assocName)
    {
        return null;
    }

    public function getAssociationMappedByTargetField($assocName)
    {
        return null;
    }

    public function getIdentifierValues($object)
    {
        return null;
    }

    public function getIdentifier()
    {
        return null;
    }

    public function getIdentifierFieldNames()
    {
        return null;
    }

    public function getAssociationNames()
    {
        return null;
    }

    private function getValidType($type = null)
    {
        $types = array(
            'integer'  => 'integer',
            'number'   => 'integer',
            'int'      => 'integer',
            'float'    => 'float',
            'double'   => 'float',
            'decimal'  => 'float',
            'string'   => 'string',
            'boolean'  => 'boolean',
            'bool'     => 'boolean',
            'array'    => 'array',
            'resource' => 'resource',
            'object'   => 'object',
            'null'     => 'null',
            'callable' => 'callable',
        );

        if (isset($types[$type])) {
            return $types[$type];
        }
        return null;
    }
}
