---
    title: Db
---

# Database Abstraction Layer

`ManaPHP\Db` is the component behind `ManaPHP\Mvc\Model` that powers the model layer in the framework.

This component allows for a lower level database manipulation than using traditional models.

.. highlights::

    This guide is not intended to be a complete documentation of available methods and their arguments. Please visit the `API`
    for a complete reference.

## Database Adapters
This component makes use of adapters to encapsulate specific database system details. ManaPHP uses [PDO] to connect to databases. The following
database engines are supported:

| Name       | Description                                                                                                                                                                                                                          | API                                                                                     |
|------------|:-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|:----------------------------------------------------------------------------------------|
| MySQL      | Is the world's most used relational database management system (RDBMS) that runs as a server providing multi-user access to a number of databases                                                                                    | `ManaPHP\Db\Adapter\Pdo\Mysql <../api/ManaPHP_Db_Adapter_Pdo_Mysql>`           |
| PostgreSQL | PostgreSQL is a powerful, open source relational database system. It has more than 15 years of active development and a proven architecture that has earned it a strong reputation for reliability, data integrity, and correctness. | `ManaPHP\Db\Adapter\Pdo\Postgresql <../api/ManaPHP_Db_Adapter_Pdo_Postgresql>` |
| SQLite     | SQLite is a software library that implements a self-contained, serverless, zero-configuration, transactional SQL database engine                                                                                                     | `ManaPHP\Db\Adapter\Pdo\Sqlite <../api/ManaPHP_Db_Adapter_Pdo_Sqlite>`         |
| Oracle     | Oracle is an object-relational database management system produced and marketed by Oracle Corporation.                                                                                                                               | `ManaPHP\Db\Adapter\Pdo\Oracle <../api/ManaPHP_Db_Adapter_Pdo_Oracle>`         |

## Implementing your own adapters

The `ManaPHP\Db\AdapterInterface` interface must be implemented in order to create your own database adapters or extend the existing ones.

## Connecting to Databases
To create a connection it's necessary instantiate the adapter class. It only requires an array with the connection parameters. The example
below shows how to create a connection passing both required and optional parameters:

```php
    <?php

    // Required
    $config = array(
        "host"     => "127.0.0.1",
        "username" => "mike",
        "password" => "sigma",
        "dbname"   => "test_db"
    );

    // Optional
    $config["persistent"] = false;

    // Create a connection
    $connection = new \ManaPHP\Db\Adapter\Mysql($config);

.. code-block:: php

    <?php

    // Required
    $config = array(
        "host"     => "localhost",
        "username" => "postgres",
        "password" => "secret1",
        "dbname"   => "template"
    );

    // Optional
    $config["schema"] = "public";

    // Create a connection
    $connection = new \ManaPHP\Db\Adapter\Postgresql($config);
```
```php
    <?php

    // Required
    $config = array(
        "dbname" => "/path/to/database.db"
    );

    // Create a connection
    $connection = new \ManaPHP\Db\Adapter\Sqlite($config);
```
```php
    <?php

    // Basic configuration
    $config = array(
        'username' => 'scott',
        'password' => 'tiger',
        'dbname'   => '192.168.10.145/orcl'
    );

    // Advanced configuration
    $config = array(
        'dbname'   => '(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521)))(CONNECT_DATA=(SERVICE_NAME=xe)(FAILOVER_MODE=(TYPE=SELECT)(METHOD=BASIC)(RETRIES=20)(DELAY=5))))',
        'username' => 'scott',
        'password' => 'tiger',
        'charset'  => 'AL32UTF8'
    );

    // Create a connection
    $connection = new \ManaPHP\Db\Adapter\Oracle($config);
```
## Setting up additional PDO options
You can set PDO options at connection time by passing the parameters 'options':

```php

    <?php

    // Create a connection with PDO options
    $connection = new \ManaPHP\Db\Adapter\Pdo\Mysql(
        array(
            "host"     => "localhost",
            "username" => "root",
            "password" => "sigma",
            "dbname"   => "test_db",
            "options"  => array(
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES \'UTF8\'",
                PDO::ATTR_CASE               => PDO::CASE_LOWER
            )
        )
    );
```

## Finding Rows
`ManaPHP\Db` provides several methods to query rows from tables. The specific SQL syntax of the target database engine is required in this case:

```php
    <?php

    $sql = "SELECT id, name FROM city ORDER BY id";

    // Send a SQL statement to the database system
    $result = $connection->query($sql);

    // Print each robot name
    while ($city = $result->fetch()) {
       echo $city["name"];
    }

    // Get all rows in an array
    $cities = $connection->fetchAll($sql);
    foreach ($cities as $city) {
       echo $robot["name"];
    }

    // Get only the first row
    $city = $connection->fetchOne($sql);
```
## Binding Parameters
Bound parameters is also supported in `ManaPHP\Db`. Although there is a minimal performance impact by using
bound parameters, you are encouraged to use this methodology so as to eliminate the possibility of your code being subject to SQL
injection attacks. Both string and positional placeholders are supported. Binding parameters can simply be achieved as follows:

```php
    <?php

    // Binding with numeric placeholders
    $sql    = "SELECT * FROM city WHERE name = ? ORDER BY name";
    $result = $connection->query($sql, array("Wall-E"));

    // Binding with named placeholders
    $sql     = "INSERT INTO `city`(name`, year) VALUES (:name, :year)";
    $success = $connection->query($sql, array("name" => "Astro Boy", "year" => 1952));
```

When using numeric placeholders, you will need to define them as integers i.e. 1 or 2. In this case "1" or "2"
are considered strings and not numbers, so the placeholder could not be successfully replaced. With any adapter
data are automatically escaped using `PDO Quote <http://www.php.net/manual/en/pdo.quote.php>`_.

This function takes into account the connection charset, so its recommended to define the correct charset
in the connection parameters or in your database server configuration, as a wrong charset will produce undesired effects when storing or retrieving data.

Also, you can pass your parameters directly to the execute/query methods. In this case bound parameters are directly passed to PDO:

```php
    <?php

    // Binding with PDO placeholders
    $sql    = "SELECT * FROM robots WHERE name = ? ORDER BY name";
    $result = $connection->query($sql, array(1 => "Wall-E"));
```
## Inserting/Updating/Deleting Rows
To insert, update or delete rows, you can use raw SQL or use the preset functions provided by the class:

```php
    <?php

    // Inserting data with a raw SQL statement
    $sql     = "INSERT INTO `city`(`name`, `year`) VALUES ('Astro Boy', 1952)";
    $success = $connection->execute($sql);

    // With placeholders
    $sql     = "INSERT INTO `city`(`name`, `year`) VALUES (?, ?)";
    $success = $connection->execute($sql, array('Astro Boy', 1952));

    // Generating dynamically the necessary SQL
    $success = $connection->insert(
       "city",
       array("Astro Boy", 1952),
       array("name", "year")
    );

    // Generating dynamically the necessary SQL (another syntax)
    $success = $connection->insertAsDict(
       "city",
       array(
          "name" => "Astro Boy",
          "year" => 1952
       )
    );

    // Updating data with a raw SQL statement
    $sql     = "UPDATE `city` SET `name` = 'Astro boy' WHERE `id` = 101";
    $success = $connection->execute($sql);

    // With placeholders
    $sql     = "UPDATE `city` SET `name` = ? WHERE `id` = ?";
    $success = $connection->execute($sql, array('Astro Boy', 101));

    // Generating dynamically the necessary SQL
    $success = $connection->update(
       "city",
       array("name"),
       array("New Astro Boy"),
       "id = 101" // Warning! In this case values are not escaped
    );

    // Generating dynamically the necessary SQL (another syntax)
    $success = $connection->updateAsDict(
       "city",
       array(
          "name" => "New Astro Boy"
       ),
       "id = 101" // Warning! In this case values are not escaped
    );

    // With escaping conditions
    $success = $connection->update(
       "city",
       array("name"),
       array("New Astro Boy"),
       array(
          'conditions' => 'id = ?',
          'bind' => array(101),
          'bindTypes' => array(PDO::PARAM_INT) // Optional parameter
       )
    );
    $success = $connection->updateAsDict(
       "city",
       array(
          "name" => "New Astro Boy"
       ),
       array(
          'conditions' => 'id = ?',
          'bind' => array(101),
          'bindTypes' => array(PDO::PARAM_INT) // Optional parameter
       )
    );

    // Deleting data with a raw SQL statement
    $sql     = "DELETE `city` WHERE `id` = 101";
    $success = $connection->execute($sql);

    // With placeholders
    $sql     = "DELETE `city` WHERE `id` = ?";
    $success = $connection->execute($sql, array(101));

    // Generating dynamically the necessary SQL
    $success = $connection->delete("city", "id = ?", array(101));
```
## Transactions

Working with transactions is supported as it is with PDO. Perform data manipulation inside transactions
often increase the performance on most database systems:

```php
    <?php

    try {

        // Start a transaction
        $connection->begin();

        // Execute some SQL statements
        $connection->execute("DELETE `city` WHERE `id` = 101");
        $connection->execute("DELETE `city` WHERE `id` = 102");
        $connection->execute("DELETE `city` WHERE `id` = 103");

        // Commit if everything goes well
        $connection->commit();

    } catch (Exception $e) {
        // An exception has occurred rollback the transaction
        $connection->rollback();
    }
```

## Database Events

`ManaPHP\Db` is able to send events to a `EventsManager <events>` if it's present. Some events when returning boolean false could stop the active operation. The following events are supported:

| Event Name          | Triggered                                                 | Can stop operation? |
|---------------------|:----------------------------------------------------------|:--------------------|
| afterConnect        | After a successfully connection to a database system      | No                  |
| beforeQuery         | Before send a SQL statement to the database system        | Yes                 |
| afterQuery          | After send a SQL statement to database system             | No                  |
| beforeDisconnect    | Before close a temporal database connection               | No                  |
| beginTransaction    | Before a transaction is going to be started               | No                  |
| rollbackTransaction | Before a transaction is rollbacked                        | No                  |
| commitTransaction   | Before a transaction is committed                         | No                  |

Bind an EventsManager to a connection is simple, `ManaPHP\Db` will trigger the events with the type "db":

```php
    <?php

    use ManaPHP\Events\Manager as EventsManager;
    use ManaPHP\Db\Adapter\Mysql as Connection;

    // Listen all the database events
    $connection->attachEvent('db',$dbLister);
```
Stop SQL operations are very useful if for example you want to implement some last-resource SQL injector checker:

```php
    <?php

    $connection->attachEvent('db:beforeQuery', function ($event, $connection) {

        // Check for malicious words in SQL statements
        if (preg_match('/DROP|ALTER/i', $connection->getSQLStatement())) {
            // DROP/ALTER operations aren't allowed in the application,
            // this must be a SQL injection!
            return false;
        }

        // It's OK
        return true;
    });
```
## Logging SQL Statements
Using high-level abstraction components such as `ManaPHP\Db` to access a database, it is difficult to understand which statements are sent to the database system. `ManaPHP\Logger <../api/ManaPHP_Logger>` interacts with `ManaPHP\Db <../api/ManaPHP_Db>`, providing logging capabilities on the database abstraction layer.

```php
    <?php

    use ManaPHP\Log\Logger;
    use ManaPHP\Events\Manager as EventsManager;
    use ManaPHP\Log\Logger\Adapter\File as FileLogger;

    $logger = new FileLogger("app/logs/db.log");

    // Listen all the database events
    $connection->attachEvent('db', function ($event, $connection) use ($logger) {
        if ($event->getType() == 'beforeQuery') {
            $logger->log($connection->getSQLStatement(), Logger::INFO);
        }
    });


    // Execute some SQL statement
    $connection->insert(
        "products",
        array("Hot pepper", 3.50),
        array("name", "price")
    );
```
As above, the file *app/logs/db.log* will contain something like this:

```sql

    [Sun, 29 Apr 12 22:35:26 -0500][DEBUG][Resource Id #77] INSERT INTO products
    (name, price) VALUES ('Hot pepper', 3.50)
```

[PDO]: http://www.php.net/manual/en/book.pdo.php
