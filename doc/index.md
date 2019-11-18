# Amenophis Elasticsearch ![](https://github.com/amenophis/elasticsearch/workflows/CI/badge.svg)

## Features

- Provide declarative Elasticsearch Client / Index configuration
- Provide autowireable Client / Index services
- Provide IndexBuilder to simplify index settings / mapping migration
- Provide 1 command to debug configuration
- Provide 1 command to migration elasticsearch index settings / mapping

## Installation

```bash
$ composer require amenophis/elasticsearch ^1.0@dev
```
## Configuration
Register the bundle in `config/bundles.php`:
```php
<?php

return [
    //...
    Amenophis\Elasticsearch\Bridge\Symfony\AmenophisElasticsearchBundle::class => ['all' => true],
    //...
];
```

Create bundle configuration file `config/packages/amenophis_elasticsearch.yaml`:
```yaml
amenophis_elasticsearch:
  clients:
    main:
      hosts: localhost:9200
  indices:
    posts:
      settings:
        number_of_shards: 1
        number_of_replicas: 1
      mappings:
        dynamic: "false" # Required to be a string because ES client return boolean as string
        properties:
          title:
            type: text
          content:
            type: text

```

## Usage
### Configuration debug
Use `bin/console amenophis:debug:client` command to show configured clients and the status of the connection:
```bash
$ bin/console amenophis:debug:client
Elasticsearch clients
=====================

------ ---------------
name   info
------ ---------------
main   Not connected
------ ---------------

$ bin/console amenophis:debug:client
Elasticsearch clients
=====================

------ -----------
name   info
------ -----------
main   Connected
```

You can also add the client name as argument of the previous command to show more details:
```bash
$ bin/console amenophis:debug:client main

Elasticsearch "main"
====================

 ---------------- ------------------------------------------------------------------------------------------
  version          7.3.2
  lucene version   8.1.0
  cluster uuid     0Zz4OznCSqC1L0x98oK9Lw
  indices          ".kibana_1", ".kibana_2", ".kibana_task_manager", ".tasks"
 ---------------- ------------------------------------------------------------------------------------------
```

### Index migration
Use `bin/console amenophis:index:migrate` command to run index migration.
The command takes two arguments `client` and `index`:
```bash
$ bin/console amenophis:index:migrate main posts


 [OK] Index has been created !

```

> The command will create the index if it is not already configured.

> The created index will be named like `posts_2019-11-17-141354` (the index name followed by a datetime) and an alias `posts` will be configured on the index.

> If index settings / mapping changed on the configuration side, the next run will create a new index, migrate the data from the old index to the new one, move the alias to the new index and close the previous index.

> If you run multiple times with configuration changed, old closed indices will be removed.

> Your application should always the index name `posts` to ensure using the latest data.

### Service autowiring
The sample configuration will autoconfigure 3 services: a Client, an Index and an IndexBuilder.
You can use them with autowiring like this:
```php
<?php

namespace App\Controller;

use Amenophis\Elasticsearch\Index;
use Amenophis\Elasticsearch\IndexBuilder;
use Elasticsearch\Client;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController
{
    /**
     * @Route("/")
     */
    public function __invoke(Index $postsIndex, IndexBuilder $mainIndexBuilder, Client $mainClient)
    {
        return new Response('OK');
    }
}
```


