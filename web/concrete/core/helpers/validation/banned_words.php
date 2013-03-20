<?
defined('C5_EXECUTE') or die("Access Denied.");

/**
 * Helper functions for catching or stripping dirty words from content
 * @package Helpers
 * @category Concrete
 * @subpackage Validation
 * @author  Ben / Tony Trupp <tony@concretecms.com>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */

define("STRING_UTILS_CASE_FIRST_LOWER", 0);
define("STRING_UTILS_CASE_FIRST_UPPER", 1);
define("STRING_UTILS_CASE_HAS_LOWER",   2);
define("STRING_UTILS_CASE_HAS_UPPER",   4);
define("STRING_UTILS_CASE_HAS_NONALPH", 8);
define("STRING_UTILS_CASE_MIXED",       6);

define("STRING_UTILS_TRUNCATE_CHARS",   '');
define("STRING_UTILS_TRUNCATE_WORDS",   '\s+');
define("STRING_UTILS_TRUNCATE_SENTS",   '[!.?]\s+');
define("STRING_UTILS_TRUNCATE_PARS",    '\n{2,}');

class Concrete5_Helper_Validation_BannedWords {

    public $bannedWords;

    function getCSV_simple($file){}

    function loadBannedWords(){
		$bw = new BannedWords();
		$this->bannedWords = $bw->get();
    }

    function wordCase($word){
		$lower = "abcdefghijklmnopqrstuvwxyz";
		$UPPER = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$i = 0;
		$case = 0;
		while ($c = $word[$i]) {
			if (strpos($lower,$c)!==FALSE) {
			if ($i==0) {
				$case |= STRING_UTILS_CASE_FIRST_LOWER;
			} else {
				$case |= STRING_UTILS_CASE_HAS_LOWER;
			}
			} else if (strpos($UPPER, $c)!==FALSE) {
			if ($i==0) {
				$case |= STRING_UTILS_CASE_FIRST_UPPER;
			} else {
				$case |= STRING_UTILS_CASE_HAS_UPPER;
			}
			} else {
			$case |= STRING_UTILS_CASE_HAS_NONALPH;
			}
			$i++;
		}
		return $case;
    }

    function forceCase($case, &$word){
		$word = strtolower($word);
		if ($case & STRING_UTILS_CASE_FIRST_UPPER)
			$word = ucfirst($word);
		$c = $case & STRING_UTILS_CASE_MIXED;
		$i = 1;
		while ($word[$i]) {
			if ($c==STRING_UTILS_CASE_HAS_UPPER ||
			($c==STRING_UTILS_CASE_MIXED && !round(mt_rand(0,2)))
			   ) {
			$word[$i] = strtoupper($word[$i]);
			}
			$i++;
		}
    }

    function isBannedWord(&$word){
		return in_array(strtolower($word), $this->bannedWords);
    }

    function hasBannedWords(&$string){
    	return (strlen($string) == str_replace($this->bannedWords, array_pad(array(), count($this->bannedWords), ''), strtolower($string)));
    }

    function hasBannedPart($string){
		$string = strtolower($string);
		foreach ($this->bannedWords as $bw => $replacement) {
			if (strpos($string, $bw)!==FALSE) return TRUE;
		}
		return FALSE;
    }

    function truncate($string, $num, $which=STRING_UTILS_TRUNCATE_CHARS, $ellipsis="&#8230;"){
		$parts = preg_split("/($which)/", $string, -1, PREG_SPLIT_DELIM_CAPTURE);
		$i = 0;
		$out = "";
		while (count($parts) && ++$i < $num ) {
			$out .= array_shift($parts).array_shift($parts);
		}
		if (count($parts)) $out = trim($out).$ellipsis;
		return $out;
    }


	public function getBannedKeys($inputArray) {
		$error_keys = array();
		if(is_array($inputArray) && count($inputArray)) {
			foreach(array_keys($inputArray) as $k) {
				 if(is_string($inputArray[$k]) && $this->hasBannedWords( $inputArray[$k])) {
				 	$error_keys[] = $k;
				 }	elseif (is_array($inputArray[$k]) && count($inputArray[$k])) {
				 	foreach($inputArray[$k] as $v) {
						if($this->hasBannedWords($v)) {
							$error_keys[] = $k;
							break;
						}
					}
				 }
			}
		}
		return $error_keys;
	}

}