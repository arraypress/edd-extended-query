# Easy Digital Downloads Extended Query Class

This class significantly enhances
the [Easy Digital Downloads (EDD)](https://github.com/awesomemotive/easy-digital-downloads) querying capabilities by
introducing the ability to
perform advanced SQL aggregate operations such as SUM, AVG, MAX, MIN, and more. Designed to complement the ORM
functionality provided by [BerlinDB](https://github.com/berlindb/core) through the base EDD Query Class, it facilitates
complex mathematical queries,
enriching
data analysis and manipulation tasks.

## Features ##

* **Advanced SQL Aggregation:** Incorporates SQL aggregate functions such as SUM, AVG, MAX, MIN, GROUP_CONCAT, STDDEV,
  VAR_SAMP, and VAR_POP into EDD queries, enabling in-depth data summarization and analysis.

* **Arithmetic Operation Support:** Allows for direct arithmetic operations within queries using operators like
  addition, subtraction, multiplication, and division, facilitating complex calculations directly within database
  queries.

* **Numerical Column Type Support:** Provides a comprehensive list of supported numeric SQL column types for precise
  data handling, including tinyint, smallint, mediumint, int, bigint, decimal, numeric, float, double, bit, and real.

* **Enhanced Grouping Capabilities:** Enables the use of GROUP BY clauses to aggregate multiple sums (SUM), averages (
  AVG), and other calculations for distinct fields in a single query request. This feature allows for streamlined data
  grouping and summarization, providing powerful insights into datasets with minimal overhead.

* **Comprehensive Documentation:** Accompanied by detailed documentation and usage examples, making it straightforward
  for developers to implement and leverage its advanced features in their projects.

## Minimum Requirements ##

* **PHP:** 7.4

## Installation ##

Extended Query is a developer library, not a plugin, which means you need to include it somewhere in your own
project.

You can use Composer:

```bash
composer require arraypress/edd-extended-query
```

#### Basic Usage

```php
// Require the Composer autoloader to enable class autoloading.
require_once __DIR__ . '/vendor/autoload.php';
```

This class is specifically designed to serve as a foundational base, enabling developers to extend and tailor their own
custom query classes according to specific data retrieval and manipulation needs within their Easy Digital Downloads (
EDD) extensions or related projects. By inheriting from the `Extended_Query` class, you can create specialized query
handlers for different aspects of your application, such as managing custom task requests, with ease and precision.

Here's an illustrative example of how to extend the Extended_Query class to construct a custom query handler for task
requests.

```php
use ArrayPress\Utils\EDD\Database\Extended_Query;

/**
 * Extends the `Extended_Query` class to manage custom task requests.
 */
class Task_Requests extends Extended_Query {

    /**
     * Database table name.
     *
     * @var string
     */
    protected $table_name = 'task_requests';

    /**
     * Database table alias.
     *
     * @var string
     */
    protected $table_alias = 'tr';

    /**
     * Schema class for database structure.
     *
     * @var string
     */
    protected $table_schema = '\\ArrayPress\\EDD\\Tasks\\Database\\Schemas\\Task_Requests';

    /**
     * Singular item name.
     *
     * @var string
     */
    protected $item_name = 'task_request';

    /**
     * Plural items name.
     *
     * @var string
     */
    protected $item_name_plural = 'task_requests';

    /**
     * Class for item objects.
     *
     * @var string
     */
    protected $item_shape = '\\ArrayPress\\EDD\\Tasks\\Objects\\Task_Request';

    /**
     * Cache group name.
     *
     * @var string
     */
    protected $cache_group = 'task_requests';

    /**
     * Constructor to set up query parameters.
     *
     * @param array|string $query Query parameters.
     */
    public function __construct( $query = [] ) {
        parent::__construct( $query );
    }
}
```

## Example 1: Simple Aggregate Query

Calculating the total sales amount for a specific product ID.

```php
$query = new Task_Transactions([
    'function'   => 'SUM',
    'fields'     => 'amount',
    'product_id' => 123 // Assuming product ID is 123
]);

$total_sales = $query->get_result();
```

##### Supported Aggregate Functions

* **SUM:** Calculates the sum of a set of values. Useful for finding total amounts, like total sales.
* **AVG:** Calculates the average of a set of values. Ideal for determining the average transaction size or average
  discount applied.
* **MAX:** Finds the maximum value in a set of values. Can be used to find the largest transaction amount or highest
  discount.
* **MIN:** Finds the minimum value in a set of values. Useful for identifying the smallest transaction or lowest price
  item sold.
* **GROUP_CONCAT:** Concatenates values from multiple rows into a single string. This is particularly useful for
  aggregating text-based data, such as combining tags or categories.
* **STDDEV:** Calculates the standard deviation of a set of values, which helps in understanding the variance in data
  points, like transaction amounts.
* **VAR_SAMP:** Calculates the sample variance of a set of values, providing insight into the variability of a sample
  from a population.
* **VAR_POP:** Calculates the population variance of a set of values, offering a view into the variability of the entire
  population.

## Example 2: Aggregate Function with Arithmetic Operation

Calculating the total profit by subtracting tax from sales amounts.

```php
$query = new Task_Transactions([
    'function' => 'SUM',
    'fields'   => ['amount', 'tax'],
    'operator' => '-' // Subtracting discount from amount
]);

$total_profit = $query->get_result();
```

##### Supported Aggregate Functions

+, -, *, /, %

## Example 3: Grouping by Product ID

Calculating total sales amount grouped by product ID.

```php
$query = new Task_Transactions([
    'function' => 'SUM',
    'fields'   => 'amount',
    'groupby'  => ['product_id']
]);

$sales_by_product = $query->get_result();
```

## Example 4: Grouping by Multiple Columns

Calculating total sales amount grouped by product ID.

```php
$query = new Task_Transactions([
    'function' => 'AVG',
    'fields'   => ['amount', 'discount']
    'groupby'  => ['product_id']
]);

$sales_by_product = $query->get_result();
```

## Contributions

Contributions to this library are highly appreciated. Raise issues on GitHub or submit pull requests for bug
fixes or new features. Share feedback and suggestions for improvements.

## License: GPLv2 or later

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.