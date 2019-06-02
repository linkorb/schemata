LinkORB Schema Annotation Tool
==============================

## Installation

You can use the included generator tools using the following steps

* Make you have `composer` is installed (https://getcomposer.org/download/)
* Add the dependency: `composer require linkorb/schemata`
* Install the dependencies by running `composer install`. This will download all libraries we need in the `vendor/` subdirectory

## Usage

### General CLI Usage

`vendor/bin/schemata <command> <arguments>`

### Schema HTML documentation

Type the following command to generate HTML documentation based on the schema files:

    `vendor/bin/schemata generate:html-doc /path/to/schema /path/to/build/html-doc`

This will parse the schema defined in the `schema/` directory, and generate a complete set of HTML documentation in to the `build/html-doc` directory.

You can browse the documentation by opening the `index.html` file in a web-browser.

    open `build/html-doc/index.html`

### GraphQL schema definitions

Type the following command to generate [GraphQL schema definitions](https://graphql.org/learn/schema/) based on the schema files:

    `vendor/bin/schemata generate:graphql-schema /path/to/schema /path/to/build/graphql [--bundle]`

This will parse the schema defined in the `schema/` directory, and generate a complete set of GraphQL types in to the `build/graphql` directory. Passing the `--bundle` flag will create a single bundled file instead of one per type

### Context schema definitions

Type the following command to generate [context schema definitions](https://github.com/linkorb/context) based on the schema files:

    `vendor/bin/schemata generate:context-schema /path/to/schema /path/to/build/context [--bundle]`

This will parse the schema defined in the `schema/` directory, and generate a complete set of GraphQL types in to the `build/context` directory. Passing the `--bundle` flag will create a single bundled file instead of one per type

### Schema Validation

    `vendor/bin/schemata schemata:validate /path/to/schema`

A service that scans through all tables and columns, performs validation. The console command returns `0` if no issues, returns `-1` if issues exist.

### Schema Diff

    `vendor/bin/schemata schemata:diff /path/to/schemaOne /path/to/schemaTwo`

A console command that:

* loads 2 schemas;
* scans throught all tables+columns, and build an array of differences (added+removed tables and columns);
* outputs the list of differences to the console.

### Inline Usage Example

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use LinkORB\Schemata\Service\SchemaProviderPath;
use LinkORB\Schemata\Service\SchemaService;

$schemaProvider = new SchemaProviderPath('/workspace/schema');

$service = new SchemaService($schemaProvider->getSchema());

$service->parseSchema();

$schema = $service->getSchema();

echo
    'Number of tables: ' . count($schema->getTables()) . PHP_EOL,
    'Number of codelists: ' . count($schema->getCodelists()) . PHP_EOL
;

```
