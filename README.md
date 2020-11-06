# MongoCute - A QueryBuilder for MongoDB

By using this builder you can easily make your database queries without writing raw queries.

## Installation

```bash
composer require payamjafari/mongocute
```

## Usage

Just call method "query" in QueryBuilder class and you are ready.

```php
<?php

use MongoCute\MongoCute\QueryBuilder;

$cards = QueryBuilder::query()->table( 'cards' )->get();

// use $cards for your needs
```

## Environment config

You can set your configuration in your project root folder by creating a file named `".env"`
```dotenv
MGCUTE_DB_ADDRESS=127.0.0.1
MGCUTE_DB_PORT=27017
MGCUTE_DB_NAME=mytestdb
MGCUTE_DB_USERNAME=
MGCUTE_DB_PASSWORD=
```
