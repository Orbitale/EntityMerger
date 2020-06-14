:warning: This package is unmaintained. Please look at better solutions, like serializers.

Entity Merger
===============

Entity/object merger for PHP apps.

Installation
===============

Install with [Composer](https://getcomposer.org/), it's the best packages manager you can have:

```shell
composer require orbitale/entity-merger
```

Requirements
===============

* PHP 5.3.3 or more
* Any Doctrine `ObjectManager` (optional)
* [Symfony Serializer](https://github.com/symfony/serializer) (optional)
* [JMS Serializer](https://github.com/schmittjoh/serializer) (optional)

Basic usage
===============

**Note:** The `EntityMerger` only works with **mapped objects**. The properties must exist, and it cannot use magic
methods to retrieve datas.

```php
use Orbitale\Component\EntityMerger\EntityMerger;

$merger = new EntityMerger();

$object = new Object();
$object->setField(null);

$postedDatas = array('field' => 'value');

$merger->merge($object, $postedDatas);

echo $object->getField(); // Shows "value"
```

Using the Doctrine ORM ObjectManager
===============

The `EntityMerger` accepts a `Doctrine\ORM\ObjectManager` as first argument, like the `EntityManager` for instance.
This allows better and deeper merging when using ORM-mapped entities, by automatically using the Doctrine Metadatas 
to detect property types.
If you are using the `EntityMerger` in an application that uses Doctrine, it is highly recommended to inject the 
`ObjectManager` to use your mapping as type reference for each of your class properties.
 
If no `ObjectManager` is used, the `EntityMerger` will perform its own checks with its own metadatas manager, but it is
obviously less performant than the Doctrine one, because it cannot assume that you are attempting to merge an entity.
It instead makes its checks based on PHPDoc, class imports and default parameters, so be sure to perfectly write your
PHPDoc in your class if you do not want to use the `ObjectManager`!

Using the Serializer
===============

The `EntityMerger` accepts a `Serializer` as second argument.
It can both use the native [Symfony Serializer](https://github.com/symfony/serializer)
and the powerful [JMS Serializer](https://github.com/schmittjoh/serializer).

**Note:** If you are using Symfony <2.5 and still want to use the serializer, then you'll have to switch to the JMS'
one, or you may have some unexpected behavior because of the lack of the `PropertyNormalizer` class.

Using the `Serializer` allows you to merge two objects in the specified one by serializing the `dataObject` parameter
into an array, and using it as values to merge in your object/entity.

**Note:** Of course you can use your own serialization method to inject the `dataObject` as array in your merge process.

Advanced usage
===============

Merge two objects in the first one (must use any serializer):

```php
use Orbitale\Component\EntityMerger\EntityMerger;

$merger = new EntityMerger(null, $serializer);

$baseObject = new Object;
$baseObject->field = null;

$anotherObject = new Object();
$anotherObject->field = 'value';

$merger->merge($baseObject, $anotherObject);

echo $baseObject->field; // Shows "value"
```
