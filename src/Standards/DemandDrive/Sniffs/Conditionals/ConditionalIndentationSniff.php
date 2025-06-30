<?php
/**
 * Conditional Indentation Sniff
 *
 * Ensures proper formatting of multiline conditionals including indentation and parenthesis placement.
 *
 * @package DemandDrive\Sniffs
 */

namespace DemandDrive\Standards\DemandDrive\Sniffs\Conditionals;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Conditional Indentation Sniff
 *
 * Checks for proper formatting of multiline conditionals including indentation and parenthesis placement.
 */
class ConditionalIndentationSniff implements Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return array( T_IF, T_ELSEIF, T_WHILE, T_FOR, T_FOREACH );
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
		$this->checkMultilineFormatting( $phpcs_file, $stack_ptr );
	}

	/**
	 * Check multiline formatting for conditional statements.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the current token.
	 *
	 * @return void
	 */
	private function checkMultilineFormatting( File $phpcs_file, $stack_ptr ) {
		$tokens = $phpcs_file->getTokens();

		// Find the opening parenthesis.
		$open_paren_ptr = $phpcs_file->findNext( T_OPEN_PARENTHESIS, $stack_ptr );
		if ( false === $open_paren_ptr ) {
			return;
		}

		// Get the closing parenthesis.
		$close_paren_ptr = $tokens[ $open_paren_ptr ]['parenthesis_closer'];

		// Check if this is a multiline conditional.
		$open_line  = $tokens[ $open_paren_ptr ]['line'];
		$close_line = $tokens[ $close_paren_ptr ]['line'];

		if ( $open_line === $close_line ) {
			// Single line conditional, no multiline rules to check.
			return;
		}

		// This is a multiline conditional, check formatting.
		$this->checkOpenParenthesisPlacement( $phpcs_file, $open_paren_ptr );
		$this->checkConditionalIndentation( $phpcs_file, $open_paren_ptr, $close_paren_ptr );
	}

	/**
	 * Check that opening parenthesis is on its own line for multiline conditionals.
	 *
	 * @param File $phpcs_file     The file being scanned.
	 * @param int  $open_paren_ptr The position of the opening parenthesis.
	 *
	 * @return void
	 */
	private function checkOpenParenthesisPlacement( File $phpcs_file, $open_paren_ptr ) {
		$tokens = $phpcs_file->getTokens();

		// Find the first non-whitespace token after the opening parenthesis.
		$next_token = $phpcs_file->findNext( T_WHITESPACE, $open_paren_ptr + 1, null, true );
		if ( false === $next_token ) {
			return;
		}

		// Check if the next non-whitespace token is on the same line as the opening parenthesis.
		if ( $tokens[ $open_paren_ptr ]['line'] === $tokens[ $next_token ]['line'] ) {
			$error = 'Opening parenthesis of multiline conditional should be followed by a newline';
			$fix   = $phpcs_file->addFixableError( $error, $open_paren_ptr, 'OpenParenthesisPlacement' );

			if ( true === $fix ) {
				$this->fixOpenParenthesisPlacement( $phpcs_file, $open_paren_ptr, $next_token );
			}
		}
	}

	/**
	 * Check indentation within multiline conditionals.
	 *
	 * @param File $phpcs_file     The file being scanned.
	 * @param int  $open_paren_ptr The position of the opening parenthesis.
	 * @param int  $close_paren_ptr The position of the closing parenthesis.
	 *
	 * @return void
	 */
	private function checkConditionalIndentation( File $phpcs_file, $open_paren_ptr, $close_paren_ptr ) {
		$tokens = $phpcs_file->getTokens();

		// Get the base indentation of the conditional statement.
		$base_indent      = 0;
		$conditional_line = $tokens[ $open_paren_ptr ]['line'];

		// Find the start of the line containing the conditional.
		for ( $i = $open_paren_ptr; $i >= 0; $i-- ) {
			if ( $tokens[ $i ]['line'] < $conditional_line ) {
				$line_start = $i + 1;
				break;
			}
		}

		if ( isset( $line_start ) ) {
			// Calculate base indentation.
			for ( $j = $line_start; $j < $open_paren_ptr; $j++ ) {
				if ( T_WHITESPACE === $tokens[ $j ]['code'] ) {
					// Count tabs as 4 spaces for consistency.
					$content      = str_replace( "\t", '    ', $tokens[ $j ]['content'] );
					$base_indent += strlen( $content );
				} else {
					break;
				}
			}
		}

		$expected_indent = $base_indent + 4; // One level of indentation.

		// Check each line within the conditional.
		for ( $i = $open_paren_ptr + 1; $i < $close_paren_ptr; $i++ ) {
			if ( $tokens[ $i ]['line'] !== $tokens[ $i - 1 ]['line'] ) {
				// This is the start of a new line.
				$line_indent = 0;
				$j           = $i;

				// Calculate indentation for this line.
				while ( $j < $close_paren_ptr && T_WHITESPACE === $tokens[ $j ]['code'] ) {
					if ( false === strpos( $tokens[ $j ]['content'], "\n" ) ) {
						// Count tabs as 4 spaces for consistency.
						$content      = str_replace( "\t", '    ', $tokens[ $j ]['content'] );
						$line_indent += strlen( $content );
					}
					++$j;
				}

				// Skip empty lines and comments.
				if (
					$j < $close_paren_ptr &&
					T_COMMENT !== $tokens[ $j ]['code'] &&
					$tokens[ $j ]['line'] !== $tokens[ $close_paren_ptr ]['line']
				) {
					if ( $line_indent !== $expected_indent ) {
						$error = 'Conditional statement content not indented correctly; expected %s spaces but found %s';
						$fix   = $phpcs_file->addFixableError(
							$error,
							$j,
							'ConditionalIndentation',
							array( $expected_indent, $line_indent )
						);

						if ( true === $fix ) {
							$this->fixIndentation( $phpcs_file, $j, $expected_indent, $line_indent );
						}
					}
				}
			}
		}
	}

	/**
	 * Fix opening parenthesis placement by moving content to the next line.
	 *
	 * @param File $phpcs_file     The file being scanned.
	 * @param int  $open_paren_ptr The position of the opening parenthesis.
	 * @param int  $next_token     The position of the first content token.
	 *
	 * @return void
	 */
	private function fixOpenParenthesisPlacement( File $phpcs_file, $open_paren_ptr, $next_token ) {
		$tokens = $phpcs_file->getTokens();

		// Calculate the base indentation.
		$base_indent = 0;
		$paren_line  = $tokens[ $open_paren_ptr ]['line'];

		// Find the start of the line containing the opening parenthesis.
		for ( $i = $open_paren_ptr; $i >= 0; $i-- ) {
			if ( $tokens[ $i ]['line'] < $paren_line ) {
				$line_start = $i + 1;
				break;
			}
		}

		if ( isset( $line_start ) ) {
			// Calculate base indentation.
			for ( $j = $line_start; $j < $open_paren_ptr; $j++ ) {
				if ( T_WHITESPACE === $tokens[ $j ]['code'] ) {
					// Count tabs as 4 spaces for consistency.
					$content      = str_replace( "\t", '    ', $tokens[ $j ]['content'] );
					$base_indent += strlen( $content );
				} else {
					break;
				}
			}
		}

		$expected_indent = $base_indent + 4; // One level of indentation.

		$phpcs_file->fixer->beginChangeset();

		// Add a newline and proper indentation after the opening parenthesis.
		$indent_string = "\n" . str_repeat( ' ', $expected_indent );
		$phpcs_file->fixer->addContentBefore( $next_token, $indent_string );

		// Remove any existing whitespace between the opening parenthesis and the first token.
		for ( $i = $open_paren_ptr + 1; $i < $next_token; $i++ ) {
			if ( T_WHITESPACE === $tokens[ $i ]['code'] ) {
				$phpcs_file->fixer->replaceToken( $i, '' );
			}
		}

		$phpcs_file->fixer->endChangeset();
	}

	/**
	 * Fix indentation issues in conditional statements.
	 *
	 * @param File $phpcs_file      The file being scanned.
	 * @param int  $token_ptr       The position of the incorrectly indented token.
	 * @param int  $expected_indent The expected indentation level.
	 * @param int  $current_indent  The current indentation level.
	 *
	 * @return void
	 */
	private function fixIndentation( File $phpcs_file, $token_ptr, $expected_indent, $current_indent ) {
		$tokens = $phpcs_file->getTokens();

		// Find the start of the line.
		$line_start = $token_ptr;
		while ( $line_start > 0 && $tokens[ $line_start ]['line'] === $tokens[ $token_ptr ]['line'] ) {
			--$line_start;
		}
		++$line_start;

		// Find the first non-whitespace token on the line.
		$first_content = $phpcs_file->findNext( T_WHITESPACE, $line_start, null, true );
		if ( false === $first_content || $tokens[ $first_content ]['line'] !== $tokens[ $token_ptr ]['line'] ) {
			return;
		}

		// Calculate the correct indentation.
		$indent_difference = $expected_indent - $current_indent;
		$phpcs_file->fixer->beginChangeset();

		if ( $indent_difference > 0 ) {
			// Add indentation.
			$spaces_to_add = str_repeat( ' ', $indent_difference );
			$phpcs_file->fixer->addContentBefore( $first_content, $spaces_to_add );
		} elseif ( $indent_difference < 0 ) {
			// Remove indentation from whitespace tokens before the first content.
			$spaces_to_remove = abs( $indent_difference );
			for ( $i = $line_start; $i < $first_content; $i++ ) {
				if ( T_WHITESPACE === $tokens[ $i ]['code'] ) {
					$content = $tokens[ $i ]['content'];
					// Remove spaces/tabs from the beginning.
					$content = preg_replace( '/^[ \t]{1,' . $spaces_to_remove . '}/', '', $content );
					$phpcs_file->fixer->replaceToken( $i, $content );
					$spaces_to_remove -= ( strlen( $tokens[ $i ]['content'] ) - strlen( $content ) );
					if ( $spaces_to_remove <= 0 ) {
						break;
					}
				}
			}
		}

		$phpcs_file->fixer->endChangeset();
	}
} 