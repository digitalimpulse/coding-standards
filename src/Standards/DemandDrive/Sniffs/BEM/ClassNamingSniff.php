<?php
/**
 * BEM Class Naming Sniff
 *
 * Ensures CSS class names follow BEM methodology.
 *
 * @package DemandDrive\Sniffs
 */

namespace DemandDrive\Standards\DemandDrive\Sniffs\BEM;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * BEM Class Naming Sniff
 *
 * Checks that CSS class names follow BEM (Block__Element--Modifier) methodology.
 */
class ClassNamingSniff implements Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return array( T_CONSTANT_ENCAPSED_STRING, T_DOUBLE_QUOTED_STRING, T_INLINE_HTML );
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the current token in the stack.
	 *
	 * @return void
	 */
	public function process( File $phpcs_file, $stack_ptr ) {
		$tokens  = $phpcs_file->getTokens();
		$content = $tokens[ $stack_ptr ]['content'];

		// Handle inline HTML differently than strings.
		if ( T_INLINE_HTML === $tokens[ $stack_ptr ]['code'] ) {
			$this->processHtmlContent( $phpcs_file, $stack_ptr, $content );
			return;
		}

		// Remove quotes from string content.
		$content = trim( $content, '"\'' );

		// Check if this looks like a class attribute.
		if ( ! $this->isClassAttribute( $phpcs_file, $stack_ptr ) ) {
			return;
		}

		// Split classes and check each one.
		$classes = preg_split( '/\s+/', $content );

		foreach ( $classes as $class ) {
			if ( empty( $class ) ) {
				continue;
			}

			// Allow container class as exception.
			if ( 'container' === $class ) {
				continue;
			}

			// Allow predefined button classes.
			if ( preg_match( '/^btn-(primary|secondary)(\s+btn-(small|large))?$/', $class ) ) {
				continue;
			}

			// Allow icon classes.
			if ( preg_match( '/^icon-[a-z-]+$/', $class ) ) {
				continue;
			}

			// Check BEM naming convention.
			if ( ! $this->isValidBEMClass( $class ) ) {
				$error = 'CSS class "%s" does not follow BEM naming convention. Expected format: block__element--modifier or block--modifier';
				$phpcs_file->addWarning( $error, $stack_ptr, 'InvalidBEMNaming', array( $class ) );
			}
		}
	}

	/**
	 * Process HTML content for class attributes.
	 *
	 * @param File   $phpcs_file The file being scanned.
	 * @param int    $stack_ptr  The position of the current token.
	 * @param string $content    The HTML content.
	 *
	 * @return void
	 */
	private function processHtmlContent( File $phpcs_file, $stack_ptr, $content ) {
		// Find all class attributes in HTML.
		if ( preg_match_all( '/class\s*=\s*["\']([^"\']*)["\']/', $content, $matches, PREG_OFFSET_CAPTURE ) ) {
			foreach ( $matches[1] as $match ) {
				$class_values = $match[0];
				$classes      = preg_split( '/\s+/', $class_values );

				foreach ( $classes as $class ) {
					if ( empty( $class ) ) {
						continue;
					}

					// Allow container class as exception.
					if ( 'container' === $class ) {
						continue;
					}

					// Allow predefined button classes.
					if ( preg_match( '/^btn-(primary|secondary)(\s+btn-(small|large))?$/', $class ) ) {
						continue;
					}

					// Allow icon classes.
					if ( preg_match( '/^icon-[a-z-]+$/', $class ) ) {
						continue;
					}

					// Check BEM naming convention.
					if ( ! $this->isValidBEMClass( $class ) ) {
						$error = 'CSS class "%s" does not follow BEM naming convention. Expected format: block__element--modifier or block--modifier';
						$phpcs_file->addWarning( $error, $stack_ptr, 'InvalidBEMNaming', array( $class ) );
					}
				}
			}
		}
	}

	/**
	 * Check if the current token is likely a class attribute.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the current token.
	 *
	 * @return bool
	 */
	private function isClassAttribute( File $phpcs_file, $stack_ptr ) {
		$tokens  = $phpcs_file->getTokens();
		$content = $tokens[ $stack_ptr ]['content'];

		// Skip strings that contain HTML comment syntax or are clearly not class values.
		if ( strpos( $content, '<!--' ) !== false ||
			strpos( $content, '-->' ) !== false ||
			strpos( $content, 'wp:' ) !== false ||
			strpos( $content, '<' ) !== false ||
			strpos( $content, '>' ) !== false ) {
			return false;
		}

		// Look for 'class=' before this string.
		for ( $i = $stack_ptr - 3; $i < $stack_ptr; $i++ ) {
			if ( $i < 0 ) {
				continue;
			}

			if ( isset( $tokens[ $i ]['content'] ) ) {
				$prev_content = $tokens[ $i ]['content'];
				if ( preg_match( '/class\s*=\s*["\']?$/', $prev_content ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if a class name follows BEM convention.
	 *
	 * @param string $class The class name to check.
	 *
	 * @return bool
	 */
	private function isValidBEMClass( $class ) {
		// Block with modifier: block--modifier.
		if ( preg_match( '/^[a-z][a-z0-9-]*--[a-z][a-z0-9-]*$/', $class ) ) {
			return true;
		}

		// Block with element: block__element.
		if ( preg_match( '/^[a-z][a-z0-9-]*__[a-z][a-z0-9-]*$/', $class ) ) {
			return true;
		}

		// Block with element and modifier: block__element--modifier.
		if ( preg_match( '/^[a-z][a-z0-9-]*__[a-z][a-z0-9-]*--[a-z][a-z0-9-]*$/', $class ) ) {
			return true;
		}

		// Simple block: block.
		if ( preg_match( '/^[a-z][a-z0-9-]*$/', $class ) ) {
			return true;
		}

		return false;
	}
}
