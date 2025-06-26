<?php
/**
 * Array To Link Usage Sniff
 *
 * Ensures array_to_link() function is used for ACF link fields.
 *
 * @package DemandDrive\Sniffs
 */

namespace DemandDrive\Standards\DemandDrive\Sniffs\Arrays;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Array To Link Usage Sniff
 *
 * Checks that array_to_link() function is used when working with ACF link field arrays.
 */
class ArrayToLinkUsageSniff implements Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return array( T_INLINE_HTML, T_ECHO );
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

		// Look for <a> tags in HTML content.
		if ( T_INLINE_HTML === $tokens[ $stack_ptr ]['code'] ) {
			$this->check_html_content( $phpcs_file, $stack_ptr, $content );
		}

		// Look for echo statements that might contain <a> tags.
		if ( T_ECHO === $tokens[ $stack_ptr ]['code'] ) {
			$this->check_echo_statement( $phpcs_file, $stack_ptr );
		}
	}

	/**
	 * Check HTML content for <a> tags with manual link building.
	 *
	 * @param File   $phpcs_file The file being scanned.
	 * @param int    $stack_ptr  The position of the token.
	 * @param string $content    The HTML content.
	 *
	 * @return void
	 */
	private function check_html_content( File $phpcs_file, $stack_ptr, $content ) {
		// Look for <a> tags with href attributes.
		if ( preg_match( '/<a\s+[^>]*href\s*=/', $content ) ) {
			$this->check_for_manual_link_building( $phpcs_file, $stack_ptr, $content );
		}
	}

	/**
	 * Check echo statements for <a> tags with manual link building.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the echo token.
	 *
	 * @return void
	 */
	private function check_echo_statement( File $phpcs_file, $stack_ptr ) {
		$tokens = $phpcs_file->getTokens();

		// Get the content of the echo statement.
		$end_of_statement = $phpcs_file->findNext( array( T_SEMICOLON, T_CLOSE_TAG ), $stack_ptr );
		if ( false === $end_of_statement ) {
			return;
		}

		$echo_content = '';
		for ( $i = $stack_ptr; $i < $end_of_statement; $i++ ) {
			$echo_content .= $tokens[ $i ]['content'];
		}

		// Look for <a> tags in echo content.
		if ( preg_match( '/<a\s+[^>]*href\s*=/', $echo_content ) ) {
			$this->check_for_manual_link_building( $phpcs_file, $stack_ptr, $echo_content );
		}
	}

	/**
	 * Check content for manual link building patterns.
	 *
	 * @param File   $phpcs_file The file being scanned.
	 * @param int    $stack_ptr  The position of the token.
	 * @param string $content    The content to check.
	 *
	 * @return void
	 */
	private function check_for_manual_link_building( File $phpcs_file, $stack_ptr, $content ) {
		// Check if array_to_link is already being used.
		if ( false !== strpos( $content, 'array_to_link' ) ) {
			return;
		}

		// Look for patterns that suggest manual link building within <a> tags.
		$url_patterns = array(
			'/\$\w+\[\'url\'\]/',      // $link['url']
			'/\$\w+\["url"\]/',        // $link["url"]
		);

		$title_patterns = array(
			'/\$\w+\[\'title\'\]/',    // $link['title']
			'/\$\w+\["title"\]/',      // $link["title"]
		);

		$has_url_pattern   = false;
		$has_title_pattern = false;

		// Check for URL patterns.
		foreach ( $url_patterns as $pattern ) {
			if ( preg_match( $pattern, $content ) ) {
				$has_url_pattern = true;
				break;
			}
		}

		// Check for title patterns.
		foreach ( $title_patterns as $pattern ) {
			if ( preg_match( $pattern, $content ) ) {
				$has_title_pattern = true;
				break;
			}
		}

		// If we found both URL and title patterns in an <a> tag, suggest array_to_link.
		if ( $has_url_pattern && $has_title_pattern ) {
			$error = 'Manual link building detected in <a> tag. Consider using array_to_link() function for ACF link fields';
			$phpcs_file->addWarning( $error, $stack_ptr, 'ManualLinkBuilding' );
		} elseif ( $has_url_pattern ) {
			$error = 'Manual URL building detected in <a> tag. Consider using array_to_link() function for ACF link fields';
			$phpcs_file->addWarning( $error, $stack_ptr, 'ManualUrlBuilding' );
		}
	}
}
