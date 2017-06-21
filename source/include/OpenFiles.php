<?PHP
/* Copyright 2015-2017, Dan Landon.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */

function ofiles() {
//* cd to /tmp or else lsof itself will show up as working dir on websserver home
$res = shell_exec("cd /tmp;lsof -F facn /mnt/disk* /mnt/user* /dev/loop* /dev/md* /mnt/cache 2>/dev/null");
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
		$bb = "&nbsp;<button onclick='openBox(\"/plugins/open.files/scripts/killprocess&arg1={$pnum[$pp]}\",\"Kill Process\",450,450,true)'>Kill</button>";
		$return .= "<tr><td>$prog[$pp]</td><td style='text-align:center'>$pnum[$pp]$bb</td><td style='text-align:center'>$count[$pp]</td><td style='text-align:center'>$blocking[$pp]</td><td>";
		$truncate = 85;
		foreach($flist[$pnum[$pp]] as $pname) {
			if (strlen($pname) > $truncate) {
				$pname = substr($pname, 0, $truncate);
				$pname .= " <strong>...</strong>";
			}
			$return .= "$pname</br>";
		}
		$return .= "</td></tr>";
	}
}

if (!($return)) {
	$return = "<tr><td colspan='7' style='text-align:center'><em>No open files</em></td></tr>";
}

return $return;
}
?>
