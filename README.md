LinkORB Schema Annotation Tool
==============================

## Installation

You can use the included generator tools using the following steps

* Make you have `composer` is installed (https://getcomposer.org/download/)
* Add the dependency: `composer require linkorb/schemata`
* Install the dependencies by running `composer install`. This will download all libraries we need in the `vendor/` subdirectory

## Usage

### General CLI Usage

* Create console command file (for example `main`):
    ```php
    #!/usr/bin/env php

    <?php

    require_once __DIR__ . '/vendor/autoload.php';

    use LinkORB\Schemata\Command\GenerateContextSchemaCommand;
    use LinkORB\Schemata\Command\GenerateGraphQLSchemaCommand;
    use LinkORB\Schemata\Command\GenerateHtmlDocCommand;
    use Symfony\Component\Console\Application;

    $app = new Application();

    $app->add(new GenerateHtmlDocCommand());
    $app->add(new GenerateContextSchemaCommand());
    $app->add(new GenerateGraphQLSchemaCommand());

    $app->run();

    ```
* Make the file executable: `chmod +x main`

`./main <command> <inputPath> <outputPath>`

### Schema HTML documentation

Type the following command to generate HTML documentation based on the schema files:

    `./main generate:html-doc /path/to/schema /path/to/build/html-doc`

This will parse the schema defined in the `schema/` directory, and generate a complete set of HTML documentation in to the `build/html-doc` directory.

You can browse the documentation by opening the `index.html` file in a web-browser.

    open `build/html-doc/index.html`

### GraphQL schema definitions

Type the following command to generate [GraphQL schema definitions](https://graphql.org/learn/schema/) based on the schema files:

    `./main generate:graphql-schema /path/to/schema /path/to/build/graphql [--bundle]`

This will parse the schema defined in the `schema/` directory, and generate a complete set of GraphQL types in to the `build/graphql` directory. Passing the `--bundle` flag will create a single bundled file instead of one per type

### Context schema definitions

Type the following command to generate [context schema definitions](https://github.com/linkorb/context) based on the schema files:

    `./main generate:context-schema /path/to/schema /path/to/build/context [--bundle]`

This will parse the schema defined in the `schema/` directory, and generate a complete set of GraphQL types in to the `build/context` directory. Passing the `--bundle` flag will create a single bundled file instead of one per type

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
