<?php
/**
 * Conditional Alternate Syntax Sniff
 *
 * Prevents alternate syntax usage for control structures unless PHP closing tags are present.
 *
 * @package DemandDrive\Sniffs
 */

namespace DemandDrive\Standards\DemandDrive\Sniffs\Conditionals;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Conditional Alternate Syntax Sniff
 *
 * Checks for alternate syntax usage and prevents it unless PHP closing tags are present.
 */
class ConditionalAlternateSyntaxSniff implements Sniff {

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
		$this->checkAlternateSyntax( $phpcs_file, $stack_ptr );
	}

	/**
	 * Check for alternate syntax usage (if/endif, while/endwhile, etc.).
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the current token.
	 *
	 * @return void
	 */
	private function checkAlternateSyntax( File $phpcs_file, $stack_ptr ) {
		$tokens = $phpcs_file->getTokens();

		// Find the colon that indicates alternate syntax.
		$colon_ptr = $phpcs_file->findNext( T_COLON, $stack_ptr );
		if ( false === $colon_ptr ) {
			return;
		}

		// Check if this colon is part of a ternary operator.
		$question_ptr = $phpcs_file->findPrevious( T_INLINE_THEN, $stack_ptr, $colon_ptr );
		if ( false !== $question_ptr ) {
			// This is a ternary operator, not alternate syntax.
			return;
		}

		// Check if the colon is within the same statement.
		$semicolon_ptr = $phpcs_file->findNext( T_SEMICOLON, $stack_ptr, $colon_ptr );
		if ( false !== $semicolon_ptr ) {
			// There's a semicolon before the colon, so it's not alternate syntax.
			return;
		}

		// Check if there's an opening brace before the colon.
		$open_brace_ptr = $phpcs_file->findNext( T_OPEN_CURLY_BRACKET, $stack_ptr, $colon_ptr );
		if ( false !== $open_brace_ptr ) {
			// There's an opening brace, so it's not alternate syntax.
			return;
		}

		// For elseif, check if it's part of an existing alternate syntax block.
		if ( T_ELSEIF === $tokens[ $stack_ptr ]['code'] ) {
			$parent_if_ptr = $this->findParentIfStatement( $phpcs_file, $stack_ptr );
			if ( false !== $parent_if_ptr ) {
				// Check if the parent if statement has PHP closing tags.
				if ( $this->hasPhpClosingTags( $phpcs_file, $parent_if_ptr ) ) {
					// Parent if has PHP closing tags, so elseif is allowed.
					return;
				}
			}
		} elseif ( $this->hasPhpClosingTags( $phpcs_file, $stack_ptr ) ) {
			// Check if alternate syntax is allowed (has PHP closing tags).
			// Alternate syntax is allowed when there are PHP closing tags.
			return;
		}

		// This appears to be alternate syntax without PHP closing tags.
		$error = 'Alternate syntax for control structures is not allowed. Use curly braces instead';
		$fix   = $phpcs_file->addFixableError( $error, $colon_ptr, 'AlternateSyntax' );

		if ( true === $fix ) {
			$this->fixAlternateSyntax( $phpcs_file, $stack_ptr, $colon_ptr );
		}
	}

	/**
	 * Check if alternate syntax has PHP closing tags which makes it allowed.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the current token.
	 *
	 * @return bool True if PHP closing tags are found, false otherwise.
	 */
	private function hasPhpClosingTags( File $phpcs_file, $stack_ptr ) {
		$tokens = $phpcs_file->getTokens();

		// Find the end of this control structure.
		$end_tokens = array( T_ENDIF, T_ENDWHILE, T_ENDFOR, T_ENDFOREACH );
		$end_ptr    = $phpcs_file->findNext( $end_tokens, $stack_ptr );

		if ( false === $end_ptr ) {
			return false;
		}

		// Look for PHP closing tags between the start and end.
		$close_tag_ptr = $phpcs_file->findNext( T_CLOSE_TAG, $stack_ptr, $end_ptr );

		return false !== $close_tag_ptr;
	}

	/**
	 * Find the parent if statement for an elseif.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the elseif token.
	 *
	 * @return int|false The position of the parent if statement, or false if not found.
	 */
	private function findParentIfStatement( File $phpcs_file, $stack_ptr ) {
		$tokens = $phpcs_file->getTokens();

		// Look backwards for the parent if statement.
		$current_ptr = $stack_ptr - 1;
		$if_count    = 0;
		$endif_count = 0;

		while ( $current_ptr >= 0 ) {
			$token = $tokens[ $current_ptr ];

			if ( T_IF === $token['code'] ) {
				if ( $if_count === $endif_count ) {
					// Found the matching parent if.
					return $current_ptr;
				}
				++$if_count;
			} elseif ( T_ENDIF === $token['code'] ) {
				++$endif_count;
			}

			--$current_ptr;
		}

		return false;
	}

	/**
	 * Fix alternate syntax by converting to curly brace syntax.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the control structure token.
	 * @param int  $colon_ptr  The position of the colon token.
	 *
	 * @return void
	 */
	private function fixAlternateSyntax( File $phpcs_file, $stack_ptr, $colon_ptr ) {
		$tokens = $phpcs_file->getTokens();

		// Determine the control structure type.
		$control_type = strtolower( $tokens[ $stack_ptr ]['content'] );

		// Find the end token.
		$end_tokens = array(
			'if'      => T_ENDIF,
			'elseif'  => T_ENDIF,
			'while'   => T_ENDWHILE,
			'for'     => T_ENDFOR,
			'foreach' => T_ENDFOREACH,
		);

		$end_token_type = $end_tokens[ $control_type ] ?? T_ENDIF;
		$end_ptr        = $phpcs_file->findNext( $end_token_type, $stack_ptr );

		if ( false === $end_ptr ) {
			return;
		}

		$phpcs_file->fixer->beginChangeset();

		// Replace the colon with opening brace.
		$phpcs_file->fixer->replaceToken( $colon_ptr, ' {' );

		// Handle else/elseif statements.
		$this->fixElseStatements( $phpcs_file, $stack_ptr, $end_ptr );

		// Replace the end token with closing brace.
		$phpcs_file->fixer->replaceToken( $end_ptr, '}' );

		// Remove the semicolon after the end token if it exists.
		$semicolon_ptr = $phpcs_file->findNext( T_SEMICOLON, $end_ptr, $end_ptr + 2 );
		if ( false !== $semicolon_ptr ) {
			$phpcs_file->fixer->replaceToken( $semicolon_ptr, '' );
		}

		$phpcs_file->fixer->endChangeset();
	}

	/**
	 * Fix else/elseif statements within alternate syntax.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $start_ptr  The start position.
	 * @param int  $end_ptr    The end position.
	 *
	 * @return void
	 */
	private function fixElseStatements( File $phpcs_file, $start_ptr, $end_ptr ) {
		$tokens = $phpcs_file->getTokens();

		// Find else/elseif statements.
		$else_ptr = $start_ptr;
		while ( false !== ( $else_ptr = $phpcs_file->findNext( array( T_ELSE, T_ELSEIF ), $else_ptr + 1, $end_ptr ) ) ) {
			// Find the colon after else/elseif.
			$else_colon_ptr = $phpcs_file->findNext( T_COLON, $else_ptr, $end_ptr );
			if ( false !== $else_colon_ptr ) {
				// Replace else: with } else {.
				if ( T_ELSE === $tokens[ $else_ptr ]['code'] ) {
					$phpcs_file->fixer->replaceToken( $else_ptr, '} else' );
					$phpcs_file->fixer->replaceToken( $else_colon_ptr, ' {' );
				} else {
					// For elseif, we need to handle the condition.
					$condition_start = $else_ptr + 1;
					$condition_end   = $else_colon_ptr - 1;

					// Build the elseif condition.
					$condition = '';
					for ( $i = $condition_start; $i <= $condition_end; $i++ ) {
						$condition .= $tokens[ $i ]['content'];
					}

					$phpcs_file->fixer->replaceToken( $else_ptr, '} elseif' );
					$phpcs_file->fixer->replaceToken( $else_colon_ptr, ' {' );
				}
			}
		}
	}
} 