<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Kay Strobach (typo3@kay-strobach.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * tools to get tracking code
 *
 * @author Kay Strobach <typo3@kay-strobach.de>
 */
class tx_piwikintegration_tracking {
	/**
	 * @param $params
	 * @param $reference
	 * @return void
	 */
	public function init(&$params, &$reference) {
		// process the page with these options
		$this->extConf = $params['pObj']->config['config']['tx_piwik.'];
		//read base url
		$this->baseUrl = $params['pObj']->config['config']['baseURL'];
	}

	/**
	 * handler for non cached output processing to insert piwik tracking code
	 * if in independent mode
	 *
	 * @param $params
	 * @param pointer $reference : to the parent object
	 * @internal param $pointer $$params: passed params from the hook
	 * @return void
	 */
	public function contentPostProc_output(&$params, &$reference) {
		$this->init($params, $reference);
		$content = $params['pObj']->content;
		$beUserLogin = $params['pObj']->beUserLogin;

		//check wether there is a BE User loggged in, if yes avoid to display the tracking code!
		if ($beUserLogin == 1) {
			return;
		}

		//check wether needed parameters are set properly
		if (!($this->extConf['piwik_idsite']) || !($this->extConf['piwik_host'])) {
			return;
		}

		$piwikCode = $this->piwikHelper->getPiwikJavaScriptCodeForSite($this->extConf['piwik_idsite']);
		$piwikCode = str_replace('&gt;', '>', $piwikCode);
		$piwikCode = str_replace('&lt;', '<', $piwikCode);
		$piwikCode = str_replace('&quot;', '"', $piwikCode);
		$piwikCode = str_replace('<br />', '', $piwikCode);

		$params['pObj']->content = str_replace(
			'</body>',
			'<!-- EXT:piwikintegration independent mode, disable independent mode, if you have 2 trackingcode snippets! -->' . $piwikCode . '<!-- /EXT:piwikintegration --></body>',
			$params['pObj']->content
		);
	}
	/**
	 * handler for cached output processing to assure that the siteid is created
	 * in piwik	 
	 *
	 * @param	pointer $$params: passed params from the hook
	 * @param	pointer $reference: to the parent object
	 * @return	void
	 */
	public function contentPostProc_all(&$params, &$reference) {
		$this->init($params, $reference);
		if ($this->extConf['piwik_idsite'] != 0) {
			$erg = $GLOBALS['TYPO3_DB']->exec_SELECTquery (
				'*',
				tx_piwikintegration_div::getTblName('site'),
				'idsite=' . intval($this->extConf['piwik_idsite'])
			);
			$numRows = $GLOBALS['TYPO3_DB']->sql_num_rows($erg);
			//check wether siteid exists
			if ($numRows === 0) {
				//if not -> create
				//FIX currency for current Piwik version, since 0.6.3
				// $currency = Piwik_GetOption('SitesManager_DefaultCurrency') ? Piwik_GetOption('SitesManager_DefaultCurrency') : 'USD';
				//FIX timezone for current Piwik version, since 0.6.3
				// $timezone = Piwik_GetOption('SitesManager_DefaultTimezone') ? Piwik_GetOption('SitesManager_DefaultTimezone') : 'UTC';

				$GLOBALS['TYPO3_DB']->exec_INSERTquery(
					tx_piwikintegration_div::getTblName('site'),
					array(
						'idsite'   => intval($this->extConf['piwik_idsite']),
						'name'     => 'ID ' . intval($this->extConf['piwik_idsite']),
						'main_url' => $this->baseUrl,
						// 'timezone'   => $timezone,
						// 'currency'   => $currency,
						'ts_created' => date('Y-m-d H:i:s', time()),
					)
				);
			} elseif ($numRows > 1) {
				//more than once -> error
				die('piwik idsite table is inconsistent, please contact server administrator');
			}
		}
	}
	/**
	 * returns js trackingcode for a given idsite
	 *
	 * @param	integer		$siteId: idsite of piwik
	 * @return	string		trackingcode
	 * @return string
	 */
	public function getPiwikJavaScriptCodeForSite($siteId) {
		tx_piwikintegration_install::getInstaller()->getConfigObject()->initPiwikFrameWork();
		$content = \Piwik\Piwik::getJavascriptCode($siteId, $this->getPiwikBaseURL());
		return $content;
	}

	/**
	 * returns js trackingcode for a given pid
	 *
	 * @param	integer		$uid: uid of a page in TYPO3
	 * @return	string
	 */
	public function getPiwikJavaScriptCodeForPid($uid) {
		return $this->getPiwikJavaScriptCodeForSite(1);
	}

	/**
	 * returns piwikBaseURL
	 *
	 * @return	string
	 */
	public function getPiwikBaseURL() {
		if (TYPO3_MODE == 'BE') {
			tx_piwikintegration_install::getInstaller()->getConfigObject()->initPiwikFrameWork();
			$path  = \Piwik\Url::getCurrentUrlWithoutFileName();
			$path  = dirname($path);
			$path .= '/typo3conf/piwik/piwik/';
		} else {
			$path = 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/typo3conf/piwik/piwik/';
		}
		return $path;
	}
}
