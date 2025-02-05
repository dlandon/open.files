<?PHP
/* Copyright 2015-2025, Dan Landon.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */
?>

<?
/* Define the docroot path. */
if (!defined('DOCROOT')) {
	define('DOCROOT', $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp');
}

/* Get the Unraid Wrappers and Helpers files. */
require_once(DOCROOT."/webGui/include/Wrappers.php");
require_once(DOCROOT."/webGui/include/Helpers.php");

/* Get translations. */
require_once(DOCROOT."/webGui/include/Translations.php");

switch ($_POST['action']) {
	case 'open_files':
		/* cd to /tmp or else lsof itself will show up as working dir on websserver home. */
		$timeout	= 30;
		$time		= -microtime(true); 
		$res		= shell_exec("/usr/bin/timeout ".escapeshellarg($timeout)." "."cd /tmp;/usr/bin/lsof -F facn /mnt/* /dev/loop* /dev/md* 2>/dev/null");
		$time		+= microtime(true);

		if ($time < $timeout) {
			$res1 = isset($res) ? explode("\n", $res) : array();
			$blocked = false;
			$bcount = 0;
			$process = 0;

			$return = "";

			foreach ($res1 as $stg) {
				$c		= substr($stg,0,1);
				$var	= substr($stg,1);

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
			if ((isset($pnum)) && ($pnum)) {
				foreach ($pnum as $pp) {
					$ss			= $flist[$pnum[$pp]][0];
					$bb			= "<input title='"._('Enable Kill Button').".' type='checkbox' onclick='$(\"#kill_button{$pnum[$pp]}\").prop(\"disabled\",!this.checked);'>";
					$bb			.= "<button class='kill-button' title='"._('Kill this Process holding files open').".' id='kill_button{$pnum[$pp]}' disabled onclick='openBox(\"/plugins/open.files/scripts/killprocess&arg1={$pnum[$pp]}\",\"Kill Process\",450,450,true)'>"._('Kill')."</button>";
					$return		.= "<tr><td>$prog[$pp]</td><td>$pnum[$pp]</td><td>$bb</td><td>$count[$pp]</td><td>$blocking[$pp]</td>";

					$truncate	= 80;
					$trim		= 40;
					$return		.= "<td>";
					foreach($flist[$pnum[$pp]] as $pname) {
						if (strlen($pname) > $truncate) {
							$pname =substr($pname, 0, $trim)."<strong>...</strong>".substr($pname, strlen($pname)-($truncate-$trim));
						}
						$return .= "$pname<br />";
					}
					$return .= "</td></tr>";
				}
			}

			if ((!$return)) {
				$return		= "<tr><td colspan='6' style='text-align:center'><em>"._('No open files')."</em></td></tr>";
			}
		} else {
			$return		= "<tr><td colspan='6' style='text-align:center;'><em>"._('Command timed out')."! "._('Cannot get list of open files').".</em></td></tr>";
		}

		echo json_encode($return);
		break;

		default:
			break;
	}
?>
