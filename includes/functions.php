<?php
declare(strict_types=1);

//==================================================================================================
//  Format bytes into other units
//==================================================================================================
function formatBytes($bytes, $precision = 2)
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}


//==================================================================================================
//  Return the number of bytes from a text value
//==================================================================================================
function return_bytes($val)
{
    $val = trim($val);
    $int = (int)$val;
    $last = strtolower($val[strlen((string)$int) - 1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
            break;
        case 'm':
            $val *= 1024;
            break;
        case 'k':
            $val *= 1024;
            break;
    }

    return $val;
}


//==================================================================================================
//  Show miliseconds on a human readable message
//==================================================================================================
function miliseconds2human($ss) {

    $ms = $ss % 1000;
    $s = floor(($ss % 60000) / 1000);
    $m = floor(($ss % 3600000) / 60000);
    $h = floor($ss / 3600000);

    $out = '<small>' . ($ms < 100 ? '0' : '') . ($ms < 10 ? '0' : '') . $ms . '</small>';
    $out = ($s < 10 ? '0' : '').($s ? $s : '0').'.'.$out;
    $out = ($m < 10 ? '0' : '').($m ? $m : '0').':'.$out;
    $out = ($h < 10 ? '0' : '').($h ? $h : '0').':'.$out;

    return $out;
}


// generate unique GUI as share key
function getGUID(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }
    else {
        mt_srand((int)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid((string)rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = "" // chr(123)// "{"
            .substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
            // .chr(125);// "}"
        return $uuid;
    }
}

// primary key from the settings
function getPrimaryKeyFromSettings($dbStr, $theTable) {

	global $_databases;

	$pks = [];

	// Check if path exists
	if (!isset($_databases[$dbStr]['tables'][$theTable]['properties'][0]['columns'])) {
		return $pks;
	}

	$columns = $_databases[$dbStr]['tables'][$theTable]['properties'][0]['columns'];

	// Decode if it's a JSON string
	if (is_string($columns)) {
		$columns = json_decode($columns, true);
	}

	// Ensure it's an array
	if (!is_array($columns)) {
		return $pks;
	}

	foreach($columns as $col) {
		if(isset($col['pk']))
			$pks[] = $col['name'];
	}

	return $pks;
}

// generate random characters
function randomPassword($len = 8) {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < $len; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}