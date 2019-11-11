<?php
if ( !class_exists( 'ICWP_APP_WpDb', false ) ):

	class ICWP_APP_WpDb {

		/**
		 * @var ICWP_APP_WpDb
		 */
		protected static $oInstance = NULL;

		/**
		 * @var wpdb
		 */
		protected $oWpdb;

		/**
		 * @return ICWP_APP_WpDb
		 */
		public static function GetInstance() {
			if ( is_null( self::$oInstance ) ) {
				self::$oInstance = new self;
			}
			return self::$oInstance;
		}

		public function __construct() {}

		/**
		 * @param string $sSQL
		 * @return array
		 */
		public function dbDelta( $sSQL ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			return dbDelta( $sSQL );
		}

		/**
		 * @param string $sTable
		 * @param array $aWhere - delete where (associative array)
		 *
		 * @return false|int
		 */
		public function deleteRowsFromTableWhere( $sTable, $aWhere ) {
			$oDb = $this->loadWpdb();
			return $oDb->delete( $sTable, $aWhere );
		}

		/**
		 * Will completely remove this table from the database
		 *
		 * @param string $sTable
		 *
		 * @return bool|int
		 */
		public function doDropTable( $sTable ) {
			$sQuery = sprintf( 'DROP TABLE IF EXISTS `%s`', $sTable ) ;
			return $this->doSql( $sQuery );
		}

		/**
		 * Alias for doTruncateTable()
		 *
		 * @param string $sTable
		 *
		 * @return bool|int
		 */
		public function doEmptyTable( $sTable ) {
			return $this->doTruncateTable( $sTable );
		}

		/**
		 * Given any SQL query, will perform it using the WordPress database object.
		 *
		 * @param string $sSqlQuery
		 * @return integer|boolean (number of rows affected or just true/false)
		 */
		public function doSql( $sSqlQuery ) {
			$mResult = $this->loadWpdb()->query( $sSqlQuery );
			return $mResult;
		}

		/**
		 * @param string $sTable
		 *
		 * @return bool|int
		 */
		public function doTruncateTable( $sTable ) {
			if ( !$this->getIfTableExists( $sTable ) ) {
				return false;
			}
			$sQuery = sprintf( 'TRUNCATE TABLE `%s`', $sTable );
			return $this->doSql( $sQuery );
		}

		public function getCharCollate() {
			return $this->getWpdb()->get_charset_collate();
		}

		/**
		 * @param string $sTable
		 * @return bool
		 */
		public function getIfTableExists( $sTable ) {
			$oDb = $this->loadWpdb();
			$sQuery = "SHOW TABLES LIKE '%s'";
			$sQuery = sprintf( $sQuery, $sTable );
			$mResult = $oDb->get_var( $sQuery );
			return !is_null( $mResult );
		}

		/**
		 * @param string $sTableName
		 * @param string $sArrayMapCallBack
		 *
		 * @return array
		 */
		public function getColumnsForTable( $sTableName, $sArrayMapCallBack = '' ) {
			$oDb = $this->loadWpdb();
			$aColumns = $oDb->get_col( "DESCRIBE " . $sTableName, 0 );

			if ( !empty( $sArrayMapCallBack ) && function_exists( $sArrayMapCallBack ) ) {
				return array_map( $sArrayMapCallBack, $aColumns );
			}
			return $aColumns;
		}

		/**
		 * @return string
		 */
		public function getPrefix() {
			$oDb = $this->loadWpdb();
			return $oDb->prefix;
		}

		/**
		 * @return string
		 */
		public function getTable_Comments() {
			$oDb = $this->loadWpdb();
			return $oDb->comments;
		}

		/**
		 * @return string
		 */
		public function getTable_Posts() {
			$oDb = $this->loadWpdb();
			return $oDb->posts;
		}

		/**
		 * @param $sSql
		 *
		 * @return null|mixed
		 */
		public function getVar( $sSql ) {
			return $this->loadWpdb()->get_var( $sSql );
		}

		/**
		 * @param string $sTable
		 * @param array $aData
		 *
		 * @return int|boolean
		 */
		public function insertDataIntoTable( $sTable, $aData ) {
			$oDb = $this->loadWpdb();
			return $oDb->insert( $sTable, $aData );
		}

		/**
		 * @param string $sTable
		 * @param string $nFormat
		 *
		 * @return mixed
		 */
		public function selectAllFromTable( $sTable, $nFormat = ARRAY_A ) {
			$oDb = $this->loadWpdb();
			$sQuery = sprintf( "SELECT * FROM `%s` WHERE `deleted_at` = '0'", $sTable );
			return $oDb->get_results( $sQuery, $nFormat );
		}

		/**
		 * @param string $sQuery
		 * @param $nFormat
		 * @return array|boolean
		 */
		public function selectCustom( $sQuery, $nFormat = ARRAY_A ) {
			$oDb = $this->loadWpdb();
			return $oDb->get_results( $sQuery, $nFormat );
		}

		/**
		 * @param $sQuery
		 * @param string $nFormat
		 *
		 * @return null|object|array
		 */
		public function selectRow( $sQuery, $nFormat = ARRAY_A ) {
			$oDb = $this->loadWpdb();
			return $oDb->get_row( $sQuery, $nFormat );
		}

		/**
		 * @return array|null|object
		 * @throws Exception
		 */
		public function showTableStatus() {
			if ( !defined( 'DB_NAME' ) ) {
				throw new Exception( 'DB_NAME constant not defined.' );
			}
			$sQuery = sprintf( "SHOW TABLE STATUS FROM `%s`", DB_NAME );
			return $this->getWpdb()->get_results( $sQuery );
		}

		/**
		 * @param $sTableName
		 * @return false|int
		 * @throws Exception
		 */
		public function optimizeTable( $sTableName ) {
			if ( empty( $sTableName ) ) {
				throw new Exception( 'Database table name to optimize cannot be empty.' );
			}
			$sQuery = sprintf( 'OPTIMIZE TABLE `%s`', $sTableName );
			return $this->doSql( $sQuery );
		}

		/**
		 * @param string $sTable
		 * @param array $aData - new insert data (associative array, column=>data)
		 * @param array $aWhere - insert where (associative array)
		 *
		 * @return integer|boolean (number of rows affected)
		 */
		public function updateRowsFromTableWhere( $sTable, $aData, $aWhere ) {
			$oDb = $this->loadWpdb();
			return $oDb->update( $sTable, $aData, $aWhere );
		}

		/**
		 * @param stdClass $oTable
		 * @return bool
		 */
		public function isTableView( $oTable ) {
			return isset( $oTable->Comment ) && preg_match( '/view/i', $oTable->Comment );
		}

		/**
		 * @param stdClass $oTable - as retrieved from "show tables"
		 * @return bool
		 */
		public function isTableCrashed( $oTable ) {
			return ( !$this->isTableView( $oTable ) && is_null( $oTable->Rows ) );
		}

		/**
		 * Loads our WPDB object if required.
		 *
		 * @return wpdb
		 */
		protected function loadWpdb() {
			if ( is_null( $this->oWpdb ) ) {
				$this->oWpdb = $this->getWpdb();
			}
			return $this->oWpdb;
		}

		/**
		 */
		private function getWpdb() {
			global $wpdb;
			return $wpdb;
		}
	}
endif;