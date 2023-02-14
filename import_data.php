<?php
/* 
Overview:
Use REDCap API to upload/import data.
This code imports one complete record at a time and logs the success.

Requires:
REDCap API token with IMPORT/UPDATE rights
CSV file in the exact format, ready for import
PHP installed

Before importing check:
Data is in CSV, not tab or other delimited
First row contains column headings
Data has appropriate DAG code, record id

Notes:
A record consists of all rows with the same record id.
You need a very clear understanding how the first set of columns used by REDCap are formatted and valued. Improper values in these columns will lead to errors or unusual record behaviour.
Ideally you should create a small csv file with only a few records for your initial import testing.

### OPERATION

[1] Edit GLOBALS and ImportFileName in the USER SECTION below.

$GLOBALS['csvDateFormat'] - REVIEW the csv file, examine what date format is being used and assign this global with either 'dmy','mdy','ymd'
	REDCap requires yyyy-mm-dd; this script can convert dd/mm/yyyy or mm/dd/yyyy
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
Note that successfully imported data does not require re-uploading, you might consider editing your import CSV down to contain only the records that generated logged errors during the import process.

*/

### USER SECTION ###
$GLOBALS['csvDateFormat'] = 'ymd'; # check your csv, use one of dateformat codes: 'dmy','mdy','ymd' 
$GLOBALS['logFileName'] = 'D:\projects\import\import_data.log'; # Log file name - any issues found during the import will be recorded here
$GLOBALS['api_token']   = '12340B28FDA1234BE07AAC627BA45678'; # Use REDCap to generate API token
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
$fields
overwriteBehavior options (default = normal):
	normal - blank/empty values will be ignored [default], existing values in the data will NOT be altered with blanks
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



# Rip out unicode from the start of the header line (bom)
function stripuc($thisstring) {
return preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $thisstring);
} # END FUNCTION



# Fix US datetime fields. These usually creep in if anyone has saved using MS Excel while not having their system date as ISO. Please set your operating system to yyyy-mm-dd
function fixdatetime($thisstring) {
# Microsoft Excel removes the seconds in date time, so let's artificially add this back on.
# UPDATE -> no longer required, REDCap now accepts datetime without seconds and I believe MS Excel date time now includes seconds
$localstr = $thisstring;
# $localstr = preg_replace('/,(\d\d\d\d-\d\d-\d\d) (\d*:\d*),/', ',\1 \2:00,', $thisstring); # Add fake seconds to date time that does not have seconds
if ($GLOBALS['csvDateFormat'] == 'dmy') {
	# DAY MONTH YEAR -> ymd
	# fix d/m/y -> y-m-d
	$localstr = preg_replace('/,(\d)\/(\d\d)\/(\d\d\d\d),/',   ',\3-\2-0\1,', $localstr);
	$localstr = preg_replace('/,(\d\d)\/(\d\d)\/(\d\d\d\d),/', ',\3-\2-\1,', $localstr);
	$localstr = preg_replace('/,(\d)\/(\d\d)\/(\d\d\d\d),/',   ',\3-\2-0\1,', $localstr);
	$localstr = preg_replace('/,(\d\d)\/(\d\d)\/(\d\d\d\d),/', ',\3-\2-\1,', $localstr);
	# do it twice because a replaced comma won't be used in the search for the second string so two dates next to each other will ignore the second date
	$localstr = preg_replace('/,(\d)\/(\d\d)\/(\d\d\d\d),/',   ',\3-\2-0\1,', $localstr);
	$localstr = preg_replace('/,(\d\d)\/(\d\d)\/(\d\d\d\d),/', ',\3-\2-\1,', $localstr);
	$localstr = preg_replace('/,(\d)\/(\d\d)\/(\d\d\d\d),/',   ',\3-\2-0\1,', $localstr);
	$localstr = preg_replace('/,(\d\d)\/(\d\d)\/(\d\d\d\d),/', ',\3-\2-\1,', $localstr);
	# fix datetime
	$localstr = preg_replace('/,(\d)\/(\d)\/(\d\d\d\d) /',     ',\3-0\2-0\1 ', $localstr); // Space terminated to catch DATETIME, do not have to repeat
	$localstr = preg_replace('/,(\d)\/(\d\d)\/(\d\d\d\d) /',   ',\3-0\2-\1 ', $localstr);
	$localstr = preg_replace('/,(\d\d)\/(\d\d)\/(\d\d\d\d) /', ',\3-\2-\1 ', $localstr);
	$localstr = preg_replace('/,(\d\d)\/(\d)\/(\d\d\d\d) /',   ',\3-\2-0\1 ', $localstr);
} elseif ($GLOBALS['csvDateFormat'] == 'mdy') {
	# MONTH DAY YEAR -> ymd
	# fix m/d/y -> y-m-d
	$localstr = preg_replace('/,(\d)\/(\d)\/(\d\d\d\d),/',     ',\3-0\1-0\2,', $localstr); // 1st date replace
	$localstr = preg_replace('/,(\d)\/(\d\d)\/(\d\d\d\d),/',   ',\3-0\1-\2,', $localstr);
	$localstr = preg_replace('/,(\d\d)\/(\d\d)\/(\d\d\d\d),/', ',\3-\1-\2,', $localstr);
	$localstr = preg_replace('/,(\d\d)\/(\d)\/(\d\d\d\d),/',   ',\3-\1-0\2,', $localstr);
	# do it twice because a replaced comma won't be used in the search for the second string so two dates next to each other will ignore the second date
	$localstr = preg_replace('/,(\d)\/(\d)\/(\d\d\d\d),/',     ',\3-0\1-0\2,', $localstr); // 2nd date replace
	$localstr = preg_replace('/,(\d)\/(\d\d)\/(\d\d\d\d),/',   ',\3-0\1-\2,', $localstr);
	$localstr = preg_replace('/,(\d\d)\/(\d\d)\/(\d\d\d\d),/', ',\3-\1-\2,', $localstr);
	$localstr = preg_replace('/,(\d\d)\/(\d)\/(\d\d\d\d),/',   ',\3-\1-0\2,', $localstr);
	# fix datetime
	$localstr = preg_replace('/,(\d)\/(\d)\/(\d\d\d\d) /',     ',\3-0\1-0\2 ', $localstr); // Space terminated to catch DATETIME, do not have to repeat
	$localstr = preg_replace('/,(\d)\/(\d\d)\/(\d\d\d\d) /',   ',\3-0\1-\2 ', $localstr);
	$localstr = preg_replace('/,(\d\d)\/(\d\d)\/(\d\d\d\d) /', ',\3-\1-\2 ', $localstr);
	$localstr = preg_replace('/,(\d\d)\/(\d)\/(\d\d\d\d) /',   ',\3-\1-0\2 ', $localstr);
}
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
if ($line !== false) {$line = fixdatetime($line);} # COMMENT/UNCOMMENT if dates require fixing
$recid = substr($line,0,strpos($line,",")); # Record ID is the first var on the data line
$thisrecid = $recid; # Cursor is at current record
$rec = $headerline; # Record starts with header
while ($line !== false) {
	while (($thisrecid == $recid) && ($line !== false)) { # If we haven't: gone into the next record or reached end of input
		$rec = $rec . "\n" . $line; # Add the row to the currect record
		$line = strtok($SepTok); # Read in the next line
		if ($line !== false) {
			$line = fixdatetime($line); # COMMENT/UNCOMMENT if dates require fixing
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
