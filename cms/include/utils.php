<?php
// Whether the requested page matches the supplied filename  -  usual usage: isDirectRequest(__FILE__)
function isDirectRequest($filename) {
	$filename = str_replace('\\', '/', $filename);
	$scriptname = $_SERVER['SCRIPT_NAME'];
	if (strlen($filename) > strlen($scriptname)) {
		if (substr($filename, strlen($filename) - strlen($scriptname)) == $scriptname) {
			return TRUE;
		}
	}
	return FALSE;
}

function matchesUriTemplate($template, $pathInfo=NULL) {
	return is_array(matchUriTemplate($template, $pathInfo));
}

function matchUriTemplate($template, $pathInfo=NULL) {
	$params = array();
	if ($pathInfo == NULL) {
		$pathInfo = $_SERVER['PATH_INFO'];
	}
	
	while (true) {
		$pos = strpos($template, "{");
		if ($pos === FALSE) {
			if ($pathInfo == $template) {
				return $params;
			} else {
				return FALSE;
			}
		}
		$extractA = substr($pathInfo, 0, $pos);
		$extractB = substr($template, 0, $pos);
		if ($extractA != $extractB) {
			return FALSE;
		}
		
		// Extract the variable name
		$template = substr($template, $pos + 1);
		$pathInfo = substr($pathInfo, $pos);

		$endPos = strpos($template, "}");
		$varName = substr($template, 0, $endPos);
		$template = substr($template, $endPos + 1);

		// Find the next section
		$nextPos = strpos($template, "{");
		$nextExtract = ($nextPos === FALSE) ? $template : substr($template, 0, $nextPos);
		
		if ($nextExtract == "") {
			$params[$varName] = $pathInfo;
			return $params;
		}
		
		$endOfValuePos = strpos($pathInfo, $nextExtract);
		if ($endOfValuePos === FALSE) {
			return FALSE;
		}
		$value = substr($pathInfo, 0, $endOfValuePos);
		$params[$varName] = $value;
		
		$pathInfo = substr($pathInfo, $endOfValuePos);
	}
}

function pointerEscape($key) {
	return str_replace("/", "~1", str_replace("~", "~0", $key));
}

function pointerExtend($base, $key) {
	return $base."/".str_replace("/", "~1", str_replace("~", "~0", $key));
}
?>