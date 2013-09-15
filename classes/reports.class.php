<?php
class Reports {
	/**
	 * This function formats a string containing a torrent's remaster information
	 * to be used in Reports v2.
	 *
	 * @param boolean  $Remastered - whether the torrent contains remaster information
	 * @param string   $RemasterTitle - the title of the remaster information
	 * @param string   $RemasterYear - the year of the remaster information
	 */
	public static function format_reports_remaster_info($Remastered, $RemasterTitle, $RemasterYear) {
		if ($Remastered) {
			$RemasterDisplayString = ' &lt;';
			if ($RemasterTitle != '' && $RemasterYear != '') {
				$RemasterDisplayString .= "$RemasterTitle - $RemasterYear";
			} elseif ($RemasterTitle != '' && $RemasterYear == '') {
				$RemasterDisplayString .= $RemasterTitle;
			} elseif ($RemasterTitle == '' && $RemasterYear != '') {
				$RemasterDisplayString .= $RemasterYear;
			}
			$RemasterDisplayString .= '&gt;';
		} else {
			$RemasterDisplayString = '';
		}
		return $RemasterDisplayString;
	}
}
