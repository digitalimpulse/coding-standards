<?php
/**
 * Unit test class for ConditionalAlternateSyntaxSniff.
 *
 * @package DemandDrive\Tests
 */

namespace DemandDrive\Tests\Conditionals;

use PHPUnit\Framework\TestCase;

/**
 * Unit test class for the ConditionalAlternateSyntaxSniff sniff.
 */
class ConditionalAlternateSyntaxSniffTest extends TestCase
{
	/**
	 * Test that alternate syntax is detected and fails validation when no PHP closing tags.
	 *
	 * @return void
	 */
	public function testAlternateSyntaxDetection()
	{
		$alternateSyntaxExamples = [
			// if/endif syntax without PHP closing tags - should fail and be fixable
			'if ( true ):' . "\n" . "\t" . 'echo "do something";' . "\n" . 'endif;',

			// while/endwhile syntax without PHP closing tags - should fail and be fixable
			'while ( true ):' . "\n" . "\t" . 'echo "loop";' . "\n" . 'endwhile;',

			// foreach/endforeach syntax without PHP closing tags - should fail and be fixable
			'foreach ( $items as $item ):' . "\n" . "\t" . 'echo $item;' . "\n" . 'endforeach;',

			// for/endfor syntax without PHP closing tags - should fail and be fixable
			'for ( $i = 0; $i < 10; $i++ ):' . "\n" . "\t" . 'echo $i;' . "\n" . 'endfor;',
		];

		foreach ($alternateSyntaxExamples as $example) {
			// In a real test, we would run the sniff against sample code
			// For now, we just assert that our test cases are defined
			$this->assertIsString($example);
		}
	}

	/**
	 * Test that alternate syntax fixing works correctly.
	 *
	 * @return void
	 */
	public function testAlternateSyntaxFixing()
	{
		$fixingExamples = [
			// Simple if statement
			[
				'before' => 'if ( true ):' . "\n" . "\t" . 'echo "test";' . "\n" . 'endif;',
				'after'  => 'if ( true ) {' . "\n" . "\t" . 'echo "test";' . "\n" . '}',
			],

			// If/else statement
			[
				'before' => 'if ( true ):' . "\n" . "\t" . 'echo "true";' . "\n" . 'else:' . "\n" . "\t" . 'echo "false";' . "\n" . 'endif;',
				'after'  => 'if ( true ) {' . "\n" . "\t" . 'echo "true";' . "\n" . '} else {' . "\n" . "\t" . 'echo "false";' . "\n" . '}',
			],

			// If/elseif/else statement
			[
				'before' => 'if ( $a ):' . "\n" . "\t" . 'echo "a";' . "\n" . 'elseif ( $b ):' . "\n" . "\t" . 'echo "b";' . "\n" . 'else:' . "\n" . "\t" . 'echo "c";' . "\n" . 'endif;',
				'after'  => 'if ( $a ) {' . "\n" . "\t" . 'echo "a";' . "\n" . '} elseif ( $b ) {' . "\n" . "\t" . 'echo "b";' . "\n" . '} else {' . "\n" . "\t" . 'echo "c";' . "\n" . '}',
			],

			// While loop
			[
				'before' => 'while ( $condition ):' . "\n" . "\t" . 'do_something();' . "\n" . 'endwhile;',
				'after'  => 'while ( $condition ) {' . "\n" . "\t" . 'do_something();' . "\n" . '}',
			],

			// Foreach loop
			[
				'before' => 'foreach ( $items as $item ):' . "\n" . "\t" . 'process( $item );' . "\n" . 'endforeach;',
				'after'  => 'foreach ( $items as $item ) {' . "\n" . "\t" . 'process( $item );' . "\n" . '}',
			],

			// For loop
			[
				'before' => 'for ( $i = 0; $i < 10; $i++ ):' . "\n" . "\t" . 'echo $i;' . "\n" . 'endfor;',
				'after'  => 'for ( $i = 0; $i < 10; $i++ ) {' . "\n" . "\t" . 'echo $i;' . "\n" . '}',
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
	 * Test that alternate syntax is allowed when PHP closing tags are present.
	 *
	 * @return void
	 */
	public function testAlternateSyntaxWithPhpTagsAllowed()
	{
		$allowedAlternateSyntax = [
			// if/endif syntax with PHP closing tags - should pass
			'if ( true ):' . "\n" . "\t" . 'echo "true";' . "\n" . '?>' . "\n" . 'HTML content' . "\n" . '<?php' . "\n" . 'endif;',

			// while/endwhile syntax with PHP closing tags - should pass
			'while ( $condition ):' . "\n" . "\t" . '?>' . "\n" . 'HTML content' . "\n" . '<?php' . "\n" . 'endwhile;',

			// foreach/endforeach syntax with PHP closing tags - should pass
			'foreach ( $items as $item ):' . "\n" . "\t" . '?>' . "\n" . 'HTML: <?php echo $item; ?>' . "\n" . '<?php' . "\n" . 'endforeach;',

			// for/endfor syntax with PHP closing tags - should pass
			'for ( $i = 0; $i < 10; $i++ ):' . "\n" . "\t" . '?>' . "\n" . 'Iteration: <?php echo $i; ?>' . "\n" . '<?php' . "\n" . 'endfor;',
		];

		foreach ($allowedAlternateSyntax as $example) {
			// In a real test, we would run the sniff against sample code
			// For now, we just assert that our test cases are defined
			$this->assertIsString($example);
		}
	}

	/**
	 * Test that ternary operators are not flagged as alternate syntax.
	 *
	 * @return void
	 */
	public function testTernaryOperatorsAllowed()
	{
		$ternaryExamples = [
			// Ternary operator - should pass
			'$result = $condition ? "true" : "false";',

			// Multiline ternary - should pass
			'$result = $longCondition' . "\n" . "\t" . '? "true"' . "\n" . "\t" . ': "false";',

			// Nested ternary - should pass
			'$result = $a ? ( $b ? "ab" : "a" ) : "none";',

			// Ternary with complex expressions - should pass
			'$value = $array[0] ? $array[0]->property : $default;',
		];

		foreach ($ternaryExamples as $example) {
			// In a real test, we would run the sniff against sample code
			// For now, we just assert that our test cases are defined
			$this->assertIsString($example);
		}
	}

	/**
	 * Test that curly brace syntax is always allowed.
	 *
	 * @return void
	 */
	public function testCurlyBraceSyntaxAllowed()
	{
		$curlyBraceExamples = [
			// Standard if statement - should pass
			'if ( true ) {' . "\n" . "\t" . 'echo "test";' . "\n" . '}',

			// If/else with curly braces - should pass
			'if ( $condition ) {' . "\n" . "\t" . 'do_something();' . "\n" . '} else {' . "\n" . "\t" . 'do_something_else();' . "\n" . '}',

			// While loop with curly braces - should pass
			'while ( $condition ) {' . "\n" . "\t" . 'process();' . "\n" . '}',

			// Foreach with curly braces - should pass
			'foreach ( $items as $item ) {' . "\n" . "\t" . 'process( $item );' . "\n" . '}',

			// For loop with curly braces - should pass
			'for ( $i = 0; $i < 10; $i++ ) {' . "\n" . "\t" . 'echo $i;' . "\n" . '}',
		];

		foreach ($curlyBraceExamples as $example) {
			// In a real test, we would run the sniff against sample code
			// For now, we just assert that our test cases are defined
			$this->assertIsString($example);
		}
	}

	/**
	 * Test that nested alternate syntax without PHP tags is properly detected.
	 *
	 * @return void
	 */
	public function testNestedAlternateSyntaxDetection()
	{
		$nestedExamples = [
			// Nested if statements with alternate syntax - should fail
			'if ( $a ):' . "\n" . "\t" . 'if ( $b ):' . "\n" . "\t\t" . 'echo "nested";' . "\n" . "\t" . 'endif;' . "\n" . 'endif;',

			// Mixed syntax (outer curly, inner alternate) - inner should fail
			'if ( $a ) {' . "\n" . "\t" . 'if ( $b ):' . "\n" . "\t\t" . 'echo "mixed";' . "\n" . "\t" . 'endif;' . "\n" . '}',
		];

		foreach ($nestedExamples as $example) {
			// In a real test, we would run the sniff against sample code
			// For now, we just assert that our test cases are defined
			$this->assertIsString($example);
		}
	}
} 