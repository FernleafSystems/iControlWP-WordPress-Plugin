<?php
/**
 * Copyright (c) 2015 iControlWP <support@icontrolwp.com>
 * All rights reserved.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

if ( !class_exists( 'ICWP_APP_FeatureHandler_Statistics_V1', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'base.php' );

	class ICWP_APP_FeatureHandler_Statistics_V1 extends ICWP_APP_FeatureHandler_Base {

		/**
		 * @return string
		 */
		protected function getProcessorClassName() {
			return 'ICWP_APP_Processor_Statistics';
		}

		/**
		 * @return array
		 */
		public function retrieveDailyStats() {
			/** @var ICWP_APP_Processor_Statistics $oFp */
			$oFp = $this->getProcessor();
			return $oFp->getDailyTotals();
		}

		/**
		 * @return array
		 */
		public function retrieveMonthlyStats() {
			/** @var ICWP_APP_Processor_Statistics $oFp */
			$oFp = $this->getProcessor();
			return $oFp->getMonthlyTotals();
		}

		/**
		 * @return string
		 */
		public function getStatisticsTableName() {
			return $this->doPluginPrefix( $this->getOpt( 'statistics_table_name' ), '_' );
		}
	}

endif;

class ICWP_APP_FeatureHandler_Statistics extends ICWP_APP_FeatureHandler_Statistics_V1 { }