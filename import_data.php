<?php
/* 
Import CSV data into REDCap project using the API via PHP
Import record by record

NOTE: ALWAYS import a small amount (~ 3 full records) to test.
When working - erase those records and import your full data set.

### OPERATION

[1] Edit GLOBALS and ImportFileName in the USER SECTION below.

$GLOBALS['logFileName'] to contain the full filename for logging information to be appended to. Note this file is NOT blanked each run.
$GLOBALS['api_token'] to contain your token key value
	REDCap - create an export API token
$GLOBALS['api_url'] to contain your REDCap server domain with /api/ at the end
$ImportFileName to contain the full path and name of the CSV data file

[2] Execute the PHP script

Open cmd (tap windows key then type CMD and tap enter)
Change folder to the one that contains your import_data.php
	cd d:\projects\import\
	php import_data.php

[3] Review log file

Review the log file for any problem records.
Note that successfully imported data does not require re-uploading, you might consider editing your import CSV down to the records that generated errors importing.

*/

### USER SECTION ###

$GLOBALS['logFileName'] = 'D:\projects\import\import_data.log'; # Log file name
$GLOBALS['api_token']   = '12340B28FDA1234BE07AAC627BA45678'; # Generate API token
$GLOBALS['api_url']     = 'https://redcap.mysite.edu/api/'; # Domain and API path for site, must have trailing slash
$ImportFileName         = 'D:\projects\import\myDataFile.csv'; # All records in CSV format with header row

####################

### FUNCTIONS

function microtime_float() {
list($usec, $sec) = explode(" ", microtime()); return ((float)$usec + (float)$sec);
} # END FUNCTION


function myLog ($thisText) {
$myFileHndl = fopen($GLOBALS['logFileName'], "a");
fwrite($myFileHndl, $thisText);
fclose($myFileHndl);
} # END FUNCTION

/*
overwriteBehavior options (default = normal):
	normal - blank/empty values will be ignored [default]
	overwrite - blank/empty values are valid and will overwrite data
	'overwriteBehavior' => 'normal',
	'overwriteBehavior' => 'overwrite',
*/

function myImportLine($thisLine){
$fields = array(
	'token'   => $GLOBALS['api_token'],
	'content' => 'record',
	'format'  => 'csv',
	'type'    => 'flat',
	'data'    => $thisLine,
);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $GLOBALS['api_url']);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields, '', '&'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // Set to TRUE for production use
curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_AUTOREFERER, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
# IMPORT
echo "import"; $BenchMarkTime1 = microtime_float();
$output = curl_exec($ch);
if (strlen($output."") < 5) {echo " [".$output."]"; myLog (" result=".$output."\n");}
else {echo " [problem]"; myLog (" result=".$output."\n".$thisLine."\n\n\n");}
$BenchMarkTime2 = microtime_float();  echo " ".round($BenchMarkTime2 - $BenchMarkTime1,4) . " seconds\n";
curl_close($ch);
return 0;
} # END FUNCTION



# Rip out unicode garbage!
function stripuc($thisstring) {
return preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $thisstring);
} # END FUNCTION

# Fix crap datetime fields
function fixdatetime($thisstring) {
$localstr = preg_replace('/,(\d\d\d\d-\d\d-\d\d) (\d*:\d*),/', ',\1 \2:00,', $thisstring);
# fix d/m/y -> y-m-d
$localstr = preg_replace('/,(\d)\/(\d\d)\/(\d\d\d\d),/', ',\3-\2-0\1,', $localstr);
$localstr = preg_replace('/,(\d\d)\/(\d\d)\/(\d\d\d\d),/', ',\3-\2-\1,', $localstr);
$localstr = preg_replace('/,(\d)\/(\d\d)\/(\d\d\d\d),/', ',\3-\2-0\1,', $localstr);
$localstr = preg_replace('/,(\d\d)\/(\d\d)\/(\d\d\d\d),/', ',\3-\2-\1,', $localstr);
return $localstr;
} # END FUNCTION



### MAIN

echo "Reading the data file"; 
myLog ("##########################################\n".$GLOBALS['api_url']."\nImport ".$ImportFileName."\nStarting:".date("Y-m-d H:i:s")."\n");
$BenchMarkTime0 = microtime_float();
$BenchMarkTime1 = microtime_float();
# Read the data file
$myFileHndl = fopen($ImportFileName, "r") or die("Unable to open file!");
$myInput = fread($myFileHndl,filesize($ImportFileName));
fclose($myFileHndl);
$BenchMarkTime2 = microtime_float();  echo " ".round($BenchMarkTime2 - $BenchMarkTime1,4) . " seconds\n";
$recNr = 0;
# Note \r\n will also split on \r or \n
$SepTok = "\r\n";
$headerline = stripuc(strtok($myInput, $SepTok));
$line = strtok($SepTok); # First data line
if ($line !== false) {$line = fixdatetime($line);}
$recid = substr($line,0,strpos($line,",")); # Record ID is the first var on the data line
$thisrecid = $recid; # Cursor is at current record
$rec = $headerline; # Record starts with header
while ($line !== false) {
	while (($thisrecid == $recid) && ($line !== false)) { # If we haven't: gone into the next record or reached end of input
		$rec = $rec . "\n" . $line; # Add the row to the currect record
		$line = strtok($SepTok); # Read in the next line
		if ($line !== false) {
			$line = fixdatetime($line);
			$thisrecid = substr($line,0,strpos($line,","));
			} # If not end of input set the recid_curosr value to next line
	}
	$recNr = $recNr + 1;
	echo "$recNr $recid ";
	myLog ("$recNr $recid \n");
	myImportLine ($rec);
	if ($line !== false) {
		$recid = $thisrecid; # Set the current id to the cursor rec id
		$rec = $headerline; # Set the first line of the record as the header line
	}
}
# Done!
$BenchMarkTime2 = microtime_float();
echo "Total time ".round($BenchMarkTime2 - $BenchMarkTime0,4) . " seconds\n";
myLog ("Completed:".date("Y-m-d H:i:s")."\nTotal time ".round($BenchMarkTime2 - $BenchMarkTime0,4)." seconds\n");

?>
