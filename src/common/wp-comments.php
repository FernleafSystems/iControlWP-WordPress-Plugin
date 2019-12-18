<?php

class ICWP_APP_WpComments extends ICWP_APP_Foundation {

	/**
	 * @var ICWP_APP_WpComments
	 */
	protected static $oInstance = null;

	private function __construct() {
	}

	/**
	 * @return ICWP_APP_WpComments
	 */
	public static function GetInstance() {
		if ( is_null( self::$oInstance ) ) {
			self::$oInstance = new self();
		}
		return self::$oInstance;
	}

	/**
	 * @param array $aLookupParams
	 * @return array[]
	 */
	public function getComments( $aLookupParams = [] ) {
		$aResults = get_comments( wp_parse_args( $aLookupParams, $this->getDefaultLookupParams() ) );
		foreach ( $aResults as $nKey => $oComment ) {
			$aResults[ $nKey ] = (array)$oComment;
		}
		return $aResults;
	}

	/**
	 * @param string $sType
	 * @param array  $aLookupParams
	 * @return array[]
	 */
	public function getCommentsOfType( $sType, $aLookupParams = [] ) {
		$aLookupParams[ 'type' ] = $sType;
		return $this->getComments( $aLookupParams );
	}

	/**
	 * @param array $aCommentTypes
	 * @param array $aLookupParams
	 * @return array[]
	 */
	public function getCommentsOfTypes( $aCommentTypes, $aLookupParams = [] ) {
		$aResults = [];
		foreach ( $aCommentTypes as $sType ) {
			$aResults = array_merge( $aResults, $this->getCommentsOfType( $sType, $aLookupParams ) );
		}
		return $aResults;
	}

	/**
	 * @param $nCommentId
	 * @return false|string
	 */
	public function getCommentStatus( $nCommentId ) {
		return wp_get_comment_status( $nCommentId );
	}

	/**
	 * @param string $nCommentId
	 * @param string $sNewStatus
	 * @return bool|WP_Error
	 */
	public function setCommentStatus( $nCommentId, $sNewStatus ) {
		$mResult = false;
		if ( in_array( $sNewStatus, [ 'hold', 'approve', 'spam', 'trash', 'delete' ] ) ) {
			$mResult = wp_set_comment_status( $nCommentId, $sNewStatus );
		}
		return is_wp_error( $mResult ) ? false : $mResult;
	}

	/**
	 * @return bool
	 */
	public function getIfCommentsMustBePreviouslyApproved() {
		return ( $this->loadWP()->getOption( 'comment_whitelist' ) == 1 );
	}

	/**
	 * @param WP_Post|null $oPost - queries the current post if null
	 * @return bool
	 */
	public function isCommentsOpen( $oPost = null ) {
		if ( is_null( $oPost ) || !is_a( $oPost, 'WP_Post' ) ) {
			global $post;
			$oPost = $post;
		}
		return ( is_a( $oPost, 'WP_Post' ) ? ( $oPost->comment_status == 'open' ) : $this->isCommentsOpenByDefault() );
	}

	/**
	 * @return bool
	 */
	public function isCommentsOpenByDefault() {
		return ( $this->loadWP()->getOption( 'default_comment_status' ) == 'open' );
	}

	/**
	 * @param string $sAuthorEmail
	 * @return bool
	 */
	public function isCommentAuthorPreviouslyApproved( $sAuthorEmail ) {

		if ( empty( $sAuthorEmail ) || !is_email( $sAuthorEmail ) ) {
			return false;
		}

		$oDb = $this->loadDbProcessor();
		$sQuery = "
				SELECT comment_approved
				FROM %s
				WHERE
					comment_author_email = '%s'
					AND comment_approved = '1'
					LIMIT 1
			";

		$sQuery = sprintf(
			$sQuery,
			$oDb->getTable_Comments(),
			esc_sql( $sAuthorEmail )
		);
		return $oDb->getVar( $sQuery ) == 1;
	}

	/**
	 * @return bool
	 */
	public function isCommentPost() {
		return $this->loadDP()->GetIsRequestPost() && $this->loadWP()->getIsCurrentPage( 'wp-comments-post.php' );
	}

	/**
	 * http://codex.wordpress.org/Function_Reference/get_comments
	 * @return array
	 */
	protected function getDefaultLookupParams() {
		return [
			'orderby' => 'comment_date_gmt', //comment_post_ID, comment_approved, comment_ID
			'order'   => 'DESC',
			'number'  => '10', //set blank to get unlimited
			'count'   => false,
		];
	}
}