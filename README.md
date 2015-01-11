Entity Merger
===============

Entity merger for Doctrine-based apps.

Installation
===============

Install with [Composer](https://getcomposer.org/), it's the best packages manager you can have :

```shell
composer require pierstoval/entity-merger:dev-master
```

Requirements
===============

* PHP 5.3.3 or more
* Doctrine ORM

Usage
===============

```php

use Pierstoval\Component\EntityMerger\EntityMerger;

$merger = new EntityMerger($entityManager);

$newObject = $merger->merge(new Entity(), $postedDatas, $customMapping);

```