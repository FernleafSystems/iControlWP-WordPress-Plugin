<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Db_Base', false ) ):

	require_once( dirname( dirname( __FILE__ ) ).'/base.php' );

	class ICWP_APP_Api_Internal_Db_Base extends ICWP_APP_Api_Internal_Base {

		/**
		 * @param bool $bIncludeViews
		 * @return array
		 * @throws Exception
		 */
		public function getDatabaseTableStatus( $bIncludeViews = false ) {
			$oDb = $this->loadDbProcessor();

			$aTableStatusResults = $oDb->showTableStatus();
			if ( empty( $aTableStatusResults ) ) {
				throw new Exception( 'Empty results from TABLE STATUS query is not as expected.' );
			}

			$nDatabaseTotal = 0;
			$nGainTotal = 0;
			$aTables = array();
			/** @var stdClass $oTable */
			foreach( $aTableStatusResults as $oTable ) {
				$nDataLength = $oTable->Data_length;
				$nIndexLength = $oTable->Index_length;
				$nDataFree = $oTable->Data_free;

				$nTableTotal = $nDataLength + $nIndexLength;
				$nDatabaseTotal += $nTableTotal;
				$nGainTotal += $nDataFree;

				$sComment = empty( $oTable->Comment ) ? '' : $oTable->Comment;

				if ( !$oDb->isTableView( $oTable ) || $bIncludeViews ) {

					$aTable = array(
						'name'		=> $oTable->Name,
						'records'	=> $oTable->Rows,
						'size'		=> $nTableTotal,
						'gain'		=> $nDataFree,
						'comment'	=> $sComment,
						'crashed'   => 0
					);

					if ( $oDb->isTableCrashed( $oTable ) ) {
						$aTable[ 'comment' ] = sprintf( 'Table "%s" appears to be crashed', $oTable->Name );
						$aTable[ 'crashed' ] = 1;
					}
					$aTables[] = $aTable;
				}
			}

			$aData = array(
				'tables'			=> $aTables,
				'database_total'	=> $nDatabaseTotal,
				'database_gain'		=> $nGainTotal
			);
			return $aData;
		}
	}

endif;