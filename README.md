LinkORB Schema
==============

This repository contains the working directory for the LinkORB Database schema.

The `schema/` directory contains a set of `.xml` files that define the database schema used in LinkORB-based applications (Onatal EHR specifically)

## Tooling

You can use the included generator tools using the following steps

    1. Make you have `composer` is installed (https://getcomposer.org/download/)
    2. Install the dependencies by running `composer install`. This will download all libraries we need in the `vendor/` subdirectory
    3. Type `bin/console list` to get a list of available commands.

### Schema HTML documentation

Type the following command to generate HTML documentation based on the schema files:

    ./bin/console generate:html-doc

This will parse the schema defined in the `schema/` directory, and generate a complete set of HTML documentation in to the `build/html-doc` directory.

You can browse the documentation by opening the `index.html` file in a web-browser.

    open build/html-doc/index.html

If you'd like to watch the schema/ directory for changes, and automatically rebuild, use a command like the following:

    ag -l | entr ./bin/console generate:html-doc

### GraphQL schema definitions

Type the following command to generate [GraphQL schema definitions](https://graphql.org/learn/schema/) based on the schema files:

    ./bin/console generate:graphql-schema [--bundle]

This will parse the schema defined in the `schema/` directory, and generate a complete set of GraphQL types in to the `build/graphql` directory. Passing the `--bundle` flag will create a single bundled file instead of one per type

### Context schema definitions

Type the following command to generate [context schema definitions](https://github.com/linkorb/context) based on the schema files:

    ./bin/console generate:context-schema [--bundle]

This will parse the schema defined in the `schema/` directory, and generate a complete set of GraphQL types in to the `build/context` directory. Passing the `--bundle` flag will create a single bundled file instead of one per type

## Scripts

* `pull.sh`: Pulls the latest version of schema xml files and documentation from a local working copy of the LinkORB source-code into this repo
* `push.sh`: Pushes the local updates in this repo to xml files back into the LinkORB sourcecode

## Git, GitHub, Fork, Pull Requests

To work on this repository, please [fork this repository](https://help.github.com/en/articles/fork-a-repo) and submit a [pull request](https://help.github.com/en/articles/about-pull-requests) to propose your changes back into this main repo.

Please don't modify files in the `schema/` directory unnecessarily (i.e. white-space, line ends, xml formatting, etc), because they will need to be merged back into an upstream project. Clean pull request with exclusively functional changes will be better for code reviews.


