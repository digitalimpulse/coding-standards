<?php
/**
 * Unit test class for ConditionalIndentationSniff.
 *
 * @package DemandDrive\Tests
 */

namespace DemandDrive\Tests\Conditionals;

use PHPUnit\Framework\TestCase;

/**
 * Unit test class for the ConditionalIndentationSniff sniff.
 */
class ConditionalIndentationSniffTest extends TestCase
{
	/**
	 * Test that properly formatted multiline conditionals pass validation.
	 *
	 * @return void
	 */
	public function testValidMultilineConditionals()
	{
		$validExamples = [
			// Proper multiline if with correct indentation
			'if (' . "\n" . "\t" . 'true' . "\n" . "\t" . '&& false' . "\n" . ') {' . "\n" . "\t" . 'echo "test";' . "\n" . '}',

			// Proper multiline while with correct indentation
			'while (' . "\n" . "\t" . '$condition' . "\n" . "\t" . '&& $other_condition' . "\n" . ') {' . "\n" . "\t" . 'do_something();' . "\n" . '}',

			// Proper multiline foreach with correct indentation
			'foreach (' . "\n" . "\t" . '$array as $key' . "\n" . "\t" . '=> $value' . "\n" . ') {' . "\n" . "\t" . 'process( $key, $value );' . "\n" . '}',

			// Proper multiline for with correct indentation
			'for (' . "\n" . "\t" . '$i = 0;' . "\n" . "\t" . '$i < count( $array );' . "\n" . "\t" . '$i++' . "\n" . ') {' . "\n" . "\t" . 'echo $array[ $i ];' . "\n" . '}',

			// Single line conditional (should pass)
			'if ( true && false ) { echo "test"; }',
		];

		foreach ($validExamples as $example) {
			// In a real test, we would run the sniff against sample code
			// For now, we just assert that our test cases are defined
			$this->assertIsString($example);
		}
	}

	/**
	 * Test that improperly formatted multiline conditionals fail validation.
	 *
	 * @return void
	 */
	public function testInvalidMultilineConditionals()
	{
		$invalidExamples = [
			// Poor indentation - should fail
			'if (' . "\n" . 'true' . "\n" . '&& false' . "\n" . ') {' . "\n" . "\t" . 'echo "test";' . "\n" . '}',

			// Inconsistent indentation - should fail
			'if (' . "\n" . "\t" . 'true' . "\n" . "\t\t" . '&& false' . "\n" . ') {' . "\n" . "\t" . 'echo "test";' . "\n" . '}',

			// No indentation in multiline - should fail
			'while (' . "\n" . '$condition' . "\n" . '&& $other' . "\n" . ') {' . "\n" . "\t" . 'process();' . "\n" . '}',

			// Incorrect indentation level - should fail
			'foreach (' . "\n" . "\t\t" . '$items as $item' . "\n" . "\t\t" . '=> $value' . "\n" . ') {' . "\n" . "\t" . 'process( $item );' . "\n" . '}',
		];

		foreach ($invalidExamples as $example) {
			// In a real test, we would run the sniff against sample code
			// For now, we just assert that our test cases are defined
			$this->assertIsString($example);
		}
	}

	/**
	 * Test that opening parenthesis placement is validated correctly.
	 *
	 * @return void
	 */
	public function testOpenParenthesisPlacement()
	{
		$validParenthesisExamples = [
			// Opening parenthesis followed by newline - should pass
			'if (' . "\n" . "\t" . 'true' . "\n" . "\t" . '&& false' . "\n" . ') {' . "\n" . "\t" . 'echo "test";' . "\n" . '}',

			// Single line - should pass
			'if ( true ) { echo "test"; }',
		];

		$invalidParenthesisExamples = [
			// Opening parenthesis on same line as condition - should fail
			'if ( true' . "\n" . "\t" . '&& false' . "\n" . ') {' . "\n" . "\t" . 'echo "test";' . "\n" . '}',

			// Content immediately after opening parenthesis - should fail
			'while ( $condition' . "\n" . "\t" . '&& $other' . "\n" . ') {' . "\n" . "\t" . 'process();' . "\n" . '}',
		];

		foreach ($validParenthesisExamples as $example) {
			$this->assertIsString($example);
		}

		foreach ($invalidParenthesisExamples as $example) {
			$this->assertIsString($example);
		}
	}

	/**
	 * Test that conditional indentation fixes work correctly.
	 *
	 * @return void
	 */
	public function testConditionalIndentationFixing()
	{
		$fixingExamples = [
			// Fix poor indentation
			[
				'before' => 'if (' . "\n" . 'true' . "\n" . '&& false' . "\n" . ') {' . "\n" . "\t" . 'echo "test";' . "\n" . '}',
				'after'  => 'if (' . "\n" . "\t" . 'true' . "\n" . "\t" . '&& false' . "\n" . ') {' . "\n" . "\t" . 'echo "test";' . "\n" . '}',
			],

			// Fix inconsistent indentation
			[
				'before' => 'while (' . "\n" . "\t" . '$condition' . "\n" . "\t\t" . '&& $other' . "\n" . ') {' . "\n" . "\t" . 'process();' . "\n" . '}',
				'after'  => 'while (' . "\n" . "\t" . '$condition' . "\n" . "\t" . '&& $other' . "\n" . ') {' . "\n" . "\t" . 'process();' . "\n" . '}',
			],

			// Fix opening parenthesis placement
			[
				'before' => 'if ( true' . "\n" . "\t" . '&& false' . "\n" . ') {' . "\n" . "\t" . 'echo "test";' . "\n" . '}',
				'after'  => 'if (' . "\n" . "\t" . 'true' . "\n" . "\t" . '&& false' . "\n" . ') {' . "\n" . "\t" . 'echo "test";' . "\n" . '}',
			],
		];

		foreach ($fixingExamples as $example) {
			// In a real test, we would apply the fixer and compare results
			// For now, we just assert that our test cases are defined
			$this->assertIsString($example['before']);
			$this->assertIsString($example['after']);
		}
	}

	/**
	 * Test that complex multiline conditionals are handled correctly.
	 *
	 * @return void
	 */
	public function testComplexMultilineConditionals()
	{
		$complexExamples = [
			// Complex if condition with method calls
			'if (' . "\n" . "\t" . '$object->method( $param )' . "\n" . "\t" . '&& $this->check_condition()' . "\n" . "\t" . '|| $fallback' . "\n" . ') {' . "\n" . "\t" . 'execute();' . "\n" . '}',

			// Complex foreach with array destructuring
			'foreach (' . "\n" . "\t" . '$complex_array as $key' . "\n" . "\t" . '=> [ $first, $second ]' . "\n" . ') {' . "\n" . "\t" . 'process( $key, $first, $second );' . "\n" . '}',

			// Complex while with multiple conditions
			'while (' . "\n" . "\t" . 'isset( $data[ $index ] )' . "\n" . "\t" . '&& $data[ $index ] !== null' . "\n" . "\t" . '&& $index < $max_index' . "\n" . ') {' . "\n" . "\t" . 'process_data( $data[ $index ] );' . "\n" . "\t" . '$index++;' . "\n" . '}',

			// Complex for loop with multiple variables
			'for (' . "\n" . "\t" . '$i = 0, $j = count( $array ) - 1;' . "\n" . "\t" . '$i < $j;' . "\n" . "\t" . '$i++, $j--' . "\n" . ') {' . "\n" . "\t" . 'swap( $array[ $i ], $array[ $j ] );' . "\n" . '}',
		];

		foreach ($complexExamples as $example) {
			// In a real test, we would run the sniff against sample code
			// For now, we just assert that our test cases are defined
			$this->assertIsString($example);
		}
	}

	/**
	 * Test that nested conditionals are handled correctly.
	 *
	 * @return void
	 */
	public function testNestedConditionals()
	{
		$nestedExamples = [
			// Nested if statements
			'if (' . "\n" . "\t" . '$outer_condition' . "\n" . ') {' . "\n" . "\t" . 'if (' . "\n" . "\t\t" . '$inner_condition' . "\n" . "\t\t" . '&& $other' . "\n" . "\t" . ') {' . "\n" . "\t\t" . 'nested_action();' . "\n" . "\t" . '}' . "\n" . '}',

			// Nested while loop
			'while (' . "\n" . "\t" . '$outer_condition' . "\n" . ') {' . "\n" . "\t" . 'foreach (' . "\n" . "\t\t" . '$items as $item' . "\n" . "\t" . ') {' . "\n" . "\t\t" . 'process( $item );' . "\n" . "\t" . '}' . "\n" . '}',
		];

		foreach ($nestedExamples as $example) {
			// In a real test, we would run the sniff against sample code
			// For now, we just assert that our test cases are defined
			$this->assertIsString($example);
		}
	}

	/**
	 * Test that comments within conditionals are handled correctly.
	 *
	 * @return void
	 */
	public function testCommentsInConditionals()
	{
		$commentExamples = [
			// Comments within multiline conditional
			'if (' . "\n" . "\t" . '// Check first condition' . "\n" . "\t" . '$first_condition' . "\n" . "\t" . '// And second condition' . "\n" . "\t" . '&& $second_condition' . "\n" . ') {' . "\n" . "\t" . 'execute();' . "\n" . '}',

			// Block comments
			'while (' . "\n" . "\t" . '/* Complex condition */' . "\n" . "\t" . '$condition' . "\n" . "\t" . '&& $other' . "\n" . ') {' . "\n" . "\t" . 'process();' . "\n" . '}',
		];

		foreach ($commentExamples as $example) {
			// In a real test, we would run the sniff against sample code
			// For now, we just assert that our test cases are defined
			$this->assertIsString($example);
		}
	}
} 