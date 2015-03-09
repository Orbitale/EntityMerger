[![SensioLabsInsight](https://insight.sensiolabs.com/projects/fb6bd829-fda7-4b4e-b759-6cab39c5614a/mini.png)](https://insight.sensiolabs.com/projects/fb6bd829-fda7-4b4e-b759-6cab39c5614a)
[![Build Status](https://travis-ci.org/Pierstoval/EntityMerger.svg)](https://travis-ci.org/Pierstoval/EntityMerger)
[![Coverage Status](https://coveralls.io/repos/Pierstoval/EntityMerger/badge.svg)](https://coveralls.io/r/Pierstoval/EntityMerger)

Entity Merger
===============

Entity merger for Doctrine-based apps.

Installation
===============

Install with [Composer](https://getcomposer.org/), it's the best packages manager you can have :

```shell
composer require pierstoval/entity-merger
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
