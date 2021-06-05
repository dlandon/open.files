<?PHP
/* Copyright 2015-2021, Dan Landon.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */

$docroot = $docroot ?: @$_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
$translations = file_exists("$docroot/webGui/include/Translations.php");

if ($translations) {
  /* add translations. */
  $_SERVER['REQUEST_URI'] = 'openfiles';
  require_once "$docroot/webGui/include/Translations.php";
} else {
  /* legacy support (without javascript). */
  $noscript = true;
  require_once "$docroot/plugins/$plugin/include/Legacy.php";
}

function ofiles() {
/* cd to /tmp or else lsof itself will show up as working dir on websserver home. */
$timeout	= 10;
$time		= -microtime(true); 
$res		= shell_exec("/usr/bin/timeout ".$timeout." "."cd /tmp;/usr/bin/lsof -F facn /mnt/* /dev/loop* /dev/md* 2>/dev/null");
$time		+= microtime(true);
$return = "<tr><td colspan='7' style='text-align:center;'><em>"._('Command timed out')."! "._('Cannot get list of open files').".</em></td></tr>";

if ($time < $timeout) {
	$res1 = explode("\n", $res);
	$blocked = false;
	$bcount = 0;
	$process = 0;

	$return = "";

	foreach ($res1 as $stg) {
	$c = substr($stg,0,1);
	$var = substr($stg,1);

		switch ($c) {
			case "c" :
				$name = $var;
				$prog[$process] = $name;
				$count[$process] = 0;
				$blocking[$process] = 0;
				$pnum[$process] = $process;
				break;
			case "n" :
				$count[$process]++;
				$flist[$process][$i++] = $var;
				if ($cwd) {
					$flist[$process][$i-1] .= " (working directory)";
				}
				break;
			case "a" :
				if ($var == "u" || $var == "w") {
					$blocking[$process] ++;
					$blocked = true;
					$bcount ++;
				}
				break;
			case "f" :
				if ($var == "cwd" && $prog[$process] !='smbd') {
					$blocking[$process] ++;
					$blocked = true;
					$bcount ++;
					$cwd = true;
				} else {
					$cwd = false;
				}
				break;
			case "p" :
				$process = $var;
				$i = 0;
				break;
			default :
				break;
		}
	}

	$bb="";
	if ($pnum) {
		foreach ($pnum as $pp) {
			$ss = $flist[$pnum[$pp]][0];
			$bb = "<td><input type='checkbox' onclick='$(\"#kill_button{$pnum[$pp]}\").prop(\"disabled\",!this.checked);'>";
			$bb .= "<button id='kill_button{$pnum[$pp]}' disabled onclick='openBox(\"/plugins/open.files/scripts/killprocess&arg1={$pnum[$pp]}\",\"Kill Process\",450,450,true)'>"._('Kill')."</button></td>";
			$return .= "<tr><td>$prog[$pp]</td><td style='text-align:center'>$pnum[$pp]$bb</td><td style='text-align:center'>$count[$pp]</td><td style='text-align:center'>$blocking[$pp]</td><td>";
			$truncate = 80;
			$trim = 30;
			foreach($flist[$pnum[$pp]] as $pname) {
				if (strlen($pname) > $truncate) {
					$pname =substr($pname, 0, $trim)."<strong>...</strong>".substr($pname, strlen($pname)-($truncate-$trim));
				}
				$return .= "$pname</br>";
			}
			$return .= "</td></tr>";
		}
	}

	if ((!$return)) {
		$return = "<tr><td colspan='7' style='text-align:center'><em>"._('No open files')."</em></td></tr>";
	}
}

return $return;
}
?>
