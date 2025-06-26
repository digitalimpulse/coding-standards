<?php
/**
 * Conditional Multiline Sniff
 *
 * Ensures proper formatting of multiline conditionals and prevents alternate syntax usage.
 *
 * @package DemandDrive\Sniffs
 */

namespace DemandDrive\Standards\DemandDrive\Sniffs\Conditionals;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Conditional Multiline Sniff
 *
 * Checks for proper formatting of multiline conditionals and prevents alternate syntax.
 */
class ConditionalMultilineSniff implements Sniff {

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
		$tokens = $phpcs_file->getTokens();

		// Check for alternate syntax.
		$this->checkAlternateSyntax( $phpcs_file, $stack_ptr );

		// Check multiline formatting.
		$this->checkMultilineFormatting( $phpcs_file, $stack_ptr );
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
