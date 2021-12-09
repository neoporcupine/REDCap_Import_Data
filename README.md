# REDCap Import Data

## Brief

This script uses REDCap's API to import data in CSV format into a project, one record at a time, creating a detailed log file for the process.

## Overview

REDCap is a data collection package that operates via a web interface. The software is available free of charge, but is licensed; you have to be a qualifying institution. For more information on REDCap visit [Project REDCap](https://projectredcap.org/)

The built-in REDCap data import process has a large number of steps that require an enormous amount of overhead that might not be applicable to some import tasks, and can often lead to the import process crashing PHP or timing out on the server or failing to render correctly in your browser.

This script bypasses the web interface, using a seperate API call for each of the records in the CSV source file.

NOTE: the term "record" above applies to the entire set of rows in the CSV file with the same record ID. This script can be adjust to import one CSV row at a time should this be required.

## Compatibility

This script uses only one type of API call:
* 'content' => 'record'

While this API call has been available back through a long history of REDCap versions, it is highly recommended that you keep your REDCap server up to date as much as possible to ensure you have the fewest bugs and the best security patches applied.

The code was developed using PHP 7.4.21 and not tested on older versions of PHP.

## Setup

### Install PHP

Skip this if you already have PHP installed. If installed properly then you should be able to run cmd, and type the following command to reveal which version of PHP is currently installed.

```cmd
php -v
```

You need to have PHP on your local computer where you are running the script. There are plenty of guides available to install PHP. Note that you do NOT have to setup IIS for this script to work.

[PHP for Windows](https://windows.php.net/)

Enable curl, mbstring, openssl, xmlrpc in php.ini, by removing the semicolon (";") from the start of line "extension="

Add php to your path, example if you installed php to c:\php\

CtrlPanel → System → Advanced System Properties → Environment Variables → System Variables → Path → Edit → New → C:\PHP\

### Create a folder to contain the CSV data files

Note that PHP will need permissions to access this folder when running, this is nearly never an issues excepting some network drive configurations, and/or running the script as a scheduled process.

Edit the import_data.php script, find $ImportFileName and ensure this is pointing at the data file in the folder that you just created.

Generally I recommend placing a copy of import_data.php in this folder and running the script from here. If you need to place the import_data.php file elsewhere then make note of this folder.

```php
$ImportFileName = 'D:\projects\import\myDataFile.csv';
```

Similarly, edit the log file for this import attempt.

```php
$GLOBALS['logFileName'] = 'D:\projects\import\import_data.log';
```

### Generate an API token in REDCap

Use the REDCap interface to request an EXPORT API token for your project. This usually requires administrator approval and will be sent to you via email.

Edit the import_data.php script, find $GLOBALS['api_token'] and the associated string value contains your REDCap token

```php
$GLOBALS['api_token'] = '0123456789ABCDEF0123456789ABCDEF'; # API token specific to "my project name" pid=1234
```

### Update the API url for your REDCap server

Edit the import_data.php script, find $GLOBALS['api_url'] and adjust to match your REDCap server domain

```php
$GLOBALS['api_url'] = 'https://redcap.mydomain.edu/api/'; # ensure trailing slash /
```

## Operation

Once properly setup, you will need to open a command prompt / shell.

On Windows this is done a number of ways, example: tap the windows key then type *CMD* and tap enter.

Change your folder to the one that contains your getfiles.php, then invoke php with getfiles.php as the parameter, example:

```dos
d:
cd d:\projects\import\
php import_data.php
```

## NOTE

[1] It is important to review the data within REDCap to ensure the upload was successful. Please carefully examine records containing text with UNICODE characters and large note fields that might have new line characters inserted. While I have not yet experienced issues with these sorts of data in this script, UNICODE and new line seem to be the culprits for numerous other procedures.

## Contributing

I use this for my own projects and as a utility for projects that contact me when they run into errors when attempting to import.

Please, if you have any ideas, just [open an issue][issues] and tell me what you think.

If you'd like to contribute, please fork the repository and make changes as you need.

## Licensing

The script is provided as is. This project is licensed under Unlicense license. This license does not require you to take the license with you to your project.

