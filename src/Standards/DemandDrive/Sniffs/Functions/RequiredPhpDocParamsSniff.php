<?php
/**
 * Required PHPDoc Parameters Sniff
 *
 * Ensures functions have proper PHPDoc blocks with @param and @return tags.
 *
 * @package DemandDrive\Sniffs
 */

namespace DemandDrive\Standards\DemandDrive\Sniffs\Functions;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Required PHPDoc Parameters Sniff
 *
 * Checks that functions have proper PHPDoc documentation.
 */
class RequiredPhpDocParamsSniff implements Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return array( T_FUNCTION );
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  The position of the current token in the stack.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		// Skip closures
		$openBrace = $phpcsFile->findNext( T_OPEN_CURLY_BRACKET, $stackPtr );
		if ( $openBrace === false ) {
			return;
		}

		// Find the function name
		$namePtr = $phpcsFile->findNext( T_STRING, $stackPtr );
		if ( $namePtr === false ) {
			return;
		}

		$functionName = $tokens[ $namePtr ]['content'];

		// Skip magic methods and private functions starting with underscore
		if ( strpos( $functionName, '__' ) === 0 || strpos( $functionName, '_' ) === 0 ) {
			return;
		}

		// Look for PHPDoc comment before the function
		$commentEnd = $phpcsFile->findPrevious( array( T_WHITESPACE, T_PUBLIC, T_PRIVATE, T_PROTECTED, T_STATIC ), $stackPtr - 1, null, true );
		
		if ( $commentEnd === false || $tokens[ $commentEnd ]['code'] !== T_DOC_COMMENT_CLOSE_TAG ) {
			$error = 'Function "%s" is missing a PHPDoc comment block';
			$phpcsFile->addError( $error, $stackPtr, 'MissingPhpDoc', array( $functionName ) );
			return;
		}

		// Get the comment content
		$commentStart = $tokens[ $commentEnd ]['comment_opener'];
		$comment      = $phpcsFile->getTokensAsString( $commentStart, ( $commentEnd - $commentStart + 1 ) );

		// Check for @param tags
		$params = $this->getFunctionParameters( $phpcsFile, $stackPtr );
		$this->checkParamTags( $phpcsFile, $stackPtr, $comment, $params, $functionName );

		// Check for @return tag
		$this->checkReturnTag( $phpcsFile, $stackPtr, $comment, $functionName );
	}

	/**
	 * Get function parameters.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  The position of the function token.
	 *
	 * @return array
	 */
	private function getFunctionParameters( File $phpcsFile, $stackPtr ) {
		$tokens     = $phpcsFile->getTokens();
		$openParen  = $phpcsFile->findNext( T_OPEN_PARENTHESIS, $stackPtr );
		$closeParen = $tokens[ $openParen ]['parenthesis_closer'];
		
		$params   = array();
		$paramPtr = $openParen;
		
		while ( ( $paramPtr = $phpcsFile->findNext( T_VARIABLE, $paramPtr + 1, $closeParen ) ) !== false ) {
			$params[] = $tokens[ $paramPtr ]['content'];
		}
		
		return $params;
	}

	/**
	 * Check @param tags in PHPDoc.
	 *
	 * @param File   $phpcsFile    The file being scanned.
	 * @param int    $stackPtr     The position of the function token.
	 * @param string $comment      The PHPDoc comment.
	 * @param array  $params       Function parameters.
	 * @param string $functionName The function name.
	 *
	 * @return void
	 */
	private function checkParamTags( File $phpcsFile, $stackPtr, $comment, $params, $functionName ) {
		foreach ( $params as $param ) {
			if ( strpos( $comment, '@param' ) === false || strpos( $comment, $param ) === false ) {
				$error = 'Function "%s" is missing @param documentation for parameter %s';
				$phpcsFile->addError( $error, $stackPtr, 'MissingParamDoc', array( $functionName, $param ) );
			}
		}
	}

	/**
	 * Check @return tag in PHPDoc.
	 *
	 * @param File   $phpcsFile    The file being scanned.
	 * @param int    $stackPtr     The position of the function token.
	 * @param string $comment      The PHPDoc comment.
	 * @param string $functionName The function name.
	 *
	 * @return void
	 */
	private function checkReturnTag( File $phpcsFile, $stackPtr, $comment, $functionName ) {
		$tokens = $phpcsFile->getTokens();
		
		// Find function body to check for return statements
		$openBrace  = $phpcsFile->findNext( T_OPEN_CURLY_BRACKET, $stackPtr );
		$closeBrace = $tokens[ $openBrace ]['bracket_closer'];
		
		$hasReturn = $phpcsFile->findNext( T_RETURN, $openBrace, $closeBrace );
		
		if ( $hasReturn !== false && strpos( $comment, '@return' ) === false ) {
			$error = 'Function "%s" has return statements but is missing @return documentation';
			$phpcsFile->addError( $error, $stackPtr, 'MissingReturnDoc', array( $functionName ) );
		}
	}
}
