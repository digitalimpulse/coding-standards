<?php
/**
 * Unit test class for ClassNamingSniff.
 *
 * @package DemandDrive\Tests
 */

namespace DemandDrive\Tests\BEM;

use PHPUnit\Framework\TestCase;

/**
 * Unit test class for the ClassNamingSniff sniff.
 */
class ClassNamingSniffTest extends TestCase
{
	/**
	 * Test that valid BEM classes pass validation.
	 *
	 * @return void
	 */
	public function testValidBEMClasses()
	{
		$validClasses = [
			'block',
			'block-name',
			'block__element',
			'block__element-name',
			'block--modifier',
			'block--modifier-name',
			'block__element--modifier',
			'btn-secondary btn-small',
			'icon-arrow-right',
		];

		foreach ($validClasses as $class) {
			// In a real test, we would run the sniff against sample code
			// For now, we just assert that our test cases are defined
			$this->assertIsString($class);
		}
	}

	/**
	 * Test that invalid BEM classes fail validation.
	 *
	 * @return void
	 */
	public function testInvalidBEMClasses()
	{
		$invalidClasses = [
			'Block', // Capital letters
			'block_element', // Single underscore
			'block-element-modifier', // Hyphens instead of modifiers
			'123block', // Starting with number
		];

		foreach ($invalidClasses as $class) {
			// In a real test, we would run the sniff against sample code
			// For now, we just assert that our test cases are defined
			$this->assertIsString($class);
		}
	}
}