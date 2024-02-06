<?php
/**
 * Easy Digital Downloads Extended Query Class
 *
 * Extends the functionality of the 'EDD\Database\Query' class to enhance query operations in WordPress and PHP
 * projects. This class adds support for SQL aggregate functions, additional hooks for item add, update, and delete
 * operations, and more. It provides a flexible way to work with database queries and aggregate data while maintaining
 * code modularity and usability.
 *
 * @package     arraypress/edd-extended-query
 * @version     1.0.0
 * @author      David Sherlock
 * @license     GPL2+
 *
 * @see         https://github.com/arraypress/edd-extended-query for more information and usage examples.
 */

namespace ArrayPress\EDD\Database;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\Extended_Query' ) && class_exists( '\EDD\Database\Query' ) ) :
	/**
	 * Query base class.
	 *
	 * This class exists solely to add action hooks to BerlinDB.
	 *
	 * @since 1.0
	 */
	class Extended_Query extends \EDD\Database\Query {

		/**
		 * @var array List of valid SQL aggregate functions
		 * - SUM: Calculates the sum of a set of values.
		 * - AVG: Calculates the average of a set of values.
		 * - MAX: Finds the maximum value in a set of values.
		 * - MIN: Finds the minimum value in a set of values.
		 * - GROUP_CONCAT: Concatenates values from multiple rows into a single string.
		 * - STDDEV: Calculates the standard deviation of a set of values.
		 * - VAR_SAMP: Calculates the sample variance of a set of values.
		 * - VAR_POP: Calculates the population variance of a set of values.
		 */
		private array $aggregate_functions = [
			'SUM',
			'AVG',
			'MAX',
			'MIN',
			'GROUP_CONCAT',
			'STDDEV',
			'VAR_SAMP',
			'VAR_POP'
		];

		/**
		 * @var array List of valid SQL arithmetic operators for calculations.
		 * - -: Subtracts one value from another.
		 * - *: Multiplies two values.
		 * - /: Divides one value by another.
		 * - %: Computes the remainder of the division of one value by another.
		 */
		private array $valid_aggregate_operators = [
			'-',
			'*',
			'/',
			'%'
		];

		/**
		 * @var array List of valid numeric SQL column types
		 * - tinyint: Tiny integer, capable of storing values typically from -128 to 127.
		 * - smallint: Small integer, capable of storing values typically from -32,768 to 32,767.
		 * - mediumint: Medium-sized integer, capable of storing values typically from -8,388,608 to 8,388,607.
		 * - int: Standard integer, capable of storing values typically from -2,147,483,648 to 2,147,483,647.
		 * - bigint: Large integer, capable of storing values typically from -9,223,372,036,854,775,808 to 9,223,372,036,854,775,807.
		 * - decimal: Fixed-point number, used when it is important to preserve exact precision, e.g., for monetary data.
		 * - numeric: Exact numeric value with a fixed precision and scale (synonym for decimal).
		 * - float: Single precision floating-point number. Can be used to store large numbers with fractional components.
		 * - double: Double precision floating-point number. Provides greater precision than float.
		 * - bit: A bit field that can store zero or more bits, useful for storing binary data compactly.
		 * - real: Synonym for double in MySQL, though it can differ in other databases. Used for double precision floating-point numbers.
		 */
		private array $numeric_column_types = [
			'tinyint',
			'smallint',
			'mediumint',
			'int',
			'bigint',
			'decimal',
			'numeric',
			'float',
			'double',
			'bit',
			'real',
		];

		/**
		 * @var array List of integer SQL column types
		 * - tinyint: Tiny integer, capable of storing values typically from -128 to 127.
		 * - smallint: Small integer, capable of storing values typically from -32,768 to 32,767.
		 * - mediumint: Medium-sized integer, capable of storing values typically from -8,388,608 to 8,388,607.
		 * - int: Standard integer, capable of storing values typically from -2,147,483,648 to 2,147,483,647.
		 * - bigint: Large integer, capable of storing values typically from -9,223,372,036,854,775,808 to 9,223,372,036,854,775,807.
		 */
		private array $int_column_types = [
			'tinyint',
			'smallint',
			'mediumint',
			'int',
			'bigint',
		];

		/**
		 * Constructs the Query class instance with specific query parameters.
		 * Initializes the instance by parsing aggregate query parameters and setting up necessary hooks.
		 *
		 * @param array $query Associative array of query parameters, including 'function' and 'fields'.
		 */
		public function __construct( array $query = [] ) {
			if ( ! empty( $query ) ) {
				$query = $this->prepare_aggregate_query( $query );
			}

			// Call the parent constructor with the modified query parameters.
			parent::__construct( $query );

			// Perform any necessary setup after constructing the parent object.
			$this->setup_hooks();
		}

		/**
		 * Parses the provided query array to standardize and validate the 'function' and 'fields' parameters.
		 * Incorporates the new sanitize methods for both function and fields.
		 *
		 * @param array $query The original query parameters.
		 *
		 * @return array Modified query parameters with validated and standardized 'function' and 'fields'.
		 */
		private function prepare_aggregate_query( array $query ): array {
			if ( ! empty( $query['function'] ) && $this->is_valid_aggregate_function( $query['function'] ) ) {
				$query['function'] = $this->sanitize_aggregate_function( $query['function'] );

				// Sanitize the operator, defaulting to '+' if not set or invalid
				$query['operator'] = ! empty( $query['operator'] ) ? $this->sanitize_aggregate_operator( $query['operator'] ) : '+';

				// Process 'fields' to ensure they are in a consistent format and unique.
				if ( ! empty( $query['fields'] ) ) {
					$query['aggregate_fields'] = $this->sanitize_aggregate_fields( $query['fields'] );

					$query['count'] = true; // Hijack the 'COUNT(*)' query by forcing the count query
					unset( $query['fields'] ); // Unset fields to allow counts
				} else {
					// If 'fields' are not provided, there's no need to aggregate; remove 'aggregate_fields'.
					unset( $query['aggregate_fields'] );
				}
			} else {
				// Remove 'function' if it's invalid or not provided to prevent incorrect usage.
				unset( $query['function'] );
			}

			return $query;
		}

		/**
		 * Sets up hooks related to the class functionality.
		 * This method is responsible for adding all the necessary action and filter hooks
		 * that the class relies on for its operation. It's typically called during the
		 * initialization phase of the class instance, ensuring that all hooks are registered
		 * before they're needed.
		 *
		 * @return void
		 */
		private function setup_hooks() {
			add_filter( $this->apply_prefix( "{$this->item_name_plural}_query_clauses" ), [
				$this,
				'maybe_filter_query_clauses'
			], 10, 2 );
		}

		/**
		 * Modifies query clauses based on the provided aggregation function and fields.
		 * Dynamically adjusts SQL clauses to incorporate aggregate functions, handling grouping and field combination as needed.
		 *
		 * @param array  $clauses Existing SQL clauses to be modified.
		 * @param object $query   Query object containing original parameters for aggregation.
		 *
		 * @return array Modified array of SQL clauses incorporating aggregation logic.
		 */
		public function maybe_filter_query_clauses( array $clauses, object $query ): array {
			if ( ! empty( $query->query_var_originals['function'] ) && ! empty( $query->query_var_originals['aggregate_fields'] ) ) {
				$function = $this->sanitize_aggregate_function( $query->query_var_originals['function'] );
				$operator = $this->sanitize_aggregate_operator( $query->query_var_originals['operator'] );

				// Extract 'GROUP BY' names and cleanup
				$groupby_names = $this->get_groupby_names( $clauses['fields'] );

				// Process fields for aggregation
				$fields = $this->sanitize_aggregated_fields( $query->query_var_originals['aggregate_fields'] );

				// Bail if fields are empty
				if ( empty( $fields ) ) {
					return $clauses;
				}

				$aggregate_clauses = [];

				// Modify the part where aggregate clauses are built
				if ( empty( $groupby_names ) ) {
					if ( $function === 'GROUP_CONCAT' ) {
						$fields_string       = "CONCAT(" . implode( ", '|', ", $fields ) . ")";
						$aggregate_clauses[] = "GROUP_CONCAT(DISTINCT {$fields_string} SEPARATOR '|') AS concatenated_fields";
					} else {
						$fields_string       = implode( " {$operator} ", $fields );
						$aggregate_clauses[] = "{$function}({$fields_string}) as total_amount";
					}
				} else {
					foreach ( $fields as $field ) {
						if ( $function === 'GROUP_CONCAT' ) {
							$aggregate_clauses[] = "{$function}(DISTINCT {$field}) as {$field}";
						} else {
							$aggregate_clauses[] = "{$function}({$field}) as {$field}";
						}
					}
				}

				// Join aggregate clauses with a comma
				$aggregate_clause = implode( ', ', $aggregate_clauses );

				// Rebuild the 'fields' clause
				if ( ! empty( $groupby_names ) ) {
					$clauses['fields'] = "{$groupby_names}, {$aggregate_clause}";
				} else {
					$clauses['fields'] = $aggregate_clause;
				}
			}

			return $clauses;
		}

		/**
		 * Executes the constructed query and retrieves the result.
		 * Depending on query configuration, may return a collection of items, a single item, or a count.
		 *
		 * @return mixed Result of the query execution, format based on query's purpose and configuration.
		 */
		public function get_result() {
			if ( ! empty( $this->query_vars['count'] ) ) {
				if ( empty( $this->query_vars['groupby'] ) ) {
					if ( ! empty( $this->query_vars['aggregate_fields'] ) && $this->is_decimal_result( $this->query_vars['aggregate_fields'] ) ) {
						return absint( $this->found_items );
					} else {
						return $this->get_db()->get_var( $this->request );
					}
				} else {
					return $this->items;
				}
			}

			return null;
		}

		/**
		 * Extracts group by names from the given clause.
		 *
		 * @param string $clause The SQL clause from which to extract group by names.
		 *
		 * @return string The extracted group by names, if any.
		 */
		private function get_groupby_names( string $clause ): string {

			// Detect "COUNT(*) as count" and remove it, keeping preceding group names if any
			$count_pos     = strpos( $clause, ', COUNT(*) as count' );
			$groupby_names = $count_pos !== false ? substr( $clause, 0, $count_pos ) : $clause;

			// Ensure there's no trailing comma
			$groupby_names = rtrim( $groupby_names, ', ' );

			// Remove "COUNT(*)" from the groupbyNames
			$groupby_names = str_replace( 'COUNT(*)', '', $groupby_names );

			// Ensure any leading/trailing spaces are removed
			return trim( $groupby_names );
		}

		/** Sanitization **************************************************************/

		/**
		 * Sanitizes the aggregate function by trimming whitespace and converting to uppercase.
		 *
		 * @param string $function The aggregate function to sanitize.
		 *
		 * @return string The sanitized aggregate function.
		 */
		private function sanitize_aggregate_function( string $function ): string {
			return strtoupper( trim( $function ) );
		}

		/**
		 * Validates and sanitizes the arithmetic operator for SQL queries.
		 *
		 * This function checks if the provided operator is within the allowed list
		 * of arithmetic operators to ensure safe construction of SQL queries. It returns
		 * a default '+' operator if the provided operator is not in the valid list.
		 *
		 * @param string $operator The arithmetic operator to validate and sanitize.
		 *
		 * @return string The sanitized operator if valid, or '+' if not valid.
		 */
		private function sanitize_aggregate_operator( string $operator ): string {
			$operator = trim( $operator );
			if ( in_array( $operator, $this->valid_aggregate_operators, true ) ) {
				return $operator;
			}

			// Return '+' if the operator is not valid
			return '+';
		}

		/**
		 * Sanitizes the aggregate fields, ensuring each field is trimmed and unique.
		 * Handles both string and array inputs, converting strings to arrays.
		 *
		 * @param mixed $fields The aggregate fields to sanitize.
		 *
		 * @return array The sanitized array of unique aggregate fields.
		 */
		private function sanitize_aggregate_fields( $fields ): array {
			if ( is_string( $fields ) ) {
				$fields = [ trim( $fields ) ]; // Convert to array and trim
			} elseif ( is_array( $fields ) ) {
				$fields = array_map( 'trim', $fields ); // Trim each field
			}

			return array_unique( $fields ); // Ensure fields are unique
		}

		/**
		 * Validates the provided fields, ensuring each exists and is numeric.
		 * Fields not meeting these criteria are omitted from the returned array,
		 * effectively filtering out invalid fields for aggregation purposes.
		 *
		 * @param array $fields An array of fields to validate for aggregation.
		 *
		 * @return array An array of validated field names suitable for inclusion in an SQL query.
		 */
		private function sanitize_aggregated_fields( array $fields ): array {
			$validated_fields = [];

			foreach ( $fields as $field ) {
				$field = trim( $field );

				// Check if the field exists and is numeric. Skip the field if either check fails.
				if ( $this->column_exists( $field ) && $this->is_column_numeric( $field ) ) {
					$validated_fields[] = $field;
				}
			}

			return $validated_fields;
		}

		/**
		 * Validates if the provided function name is a supported SQL aggregate function.
		 *
		 * @param string $function The name of the function to check.
		 *
		 * @return bool True if the function is supported, false otherwise.
		 */
		private function is_valid_aggregate_function( string $function ): bool {
			return in_array( strtoupper( $function ), $this->aggregate_functions, true );
		}

		/** Helpers *******************************************************************/

		/**
		 * Check if a column exists in the schema.
		 *
		 * @param string $column_name The name of the column to check.
		 *
		 * @return bool Returns true if the column exists, false otherwise.
		 */
		public function column_exists( string $column_name ): bool {
			return in_array( $column_name, array_flip( $this->get_column_names() ), true );
		}

		/**
		 * Check if a column is numeric.
		 *
		 * @param string $column_name The name of the column to check.
		 *
		 * @return bool Returns true if the column is numeric, false otherwise.
		 */
		public function is_column_numeric( string $column_name ): bool {
			$column = $this->get_column_by( [ 'name' => $column_name ] );

			// Check if the column's type is in the array of numeric types
			return isset( $column->type ) && in_array( strtolower( $column->type ), $this->numeric_column_types, true );
		}

		/**
		 * Checks if all specified aggregate fields are of integer type.
		 *
		 * This method iterates over each field in the 'aggregate_fields' array and checks if the field's type is listed
		 * in the '$int_types' array, indicating it is an integer type. This is used to determine if the result of an
		 * aggregation can be returned directly without rerunning the query when not using 'groupby'.
		 *
		 * @param array $fields The fields to check.
		 *
		 * @return bool Returns true if all fields are of integer type, false otherwise.
		 */
		private function is_decimal_result( array $fields ): bool {
			if ( ! empty( $fields ) ) {
				foreach ( $fields as $field ) {
					$column = $this->get_column_by( [ 'name' => $field ] );

					// Check if the column's type is an integer type
					if ( ! isset( $column->type ) || ! in_array( strtolower( $column->type ), $this->int_column_types, true ) ) {
						return false; // If any field is not an integer type, return false
					}
				}
			}

			return true; // All fields are of integer type
		}

	}
endif;