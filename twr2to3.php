#!/usr/bin/php
<?php

//require_once('File/Archive.php');     


error_reporting( 0 ); 

$input_file = "input.twr";

echo "warning: removing input and output dirs\n";
rmdir("input");
rmdir("output");
mkdir("input");
mkdir("output");

echo "execing tar\n";
exec("tar -C input/ -xzvf $input_file");

/*function getFileContentsFromArchive( $file, $path ){

$source = File_Archive::read($file.$path);
if(PEAR::isError($source))
	die('Error reading archive. Are you sure it is of tar.gz format?');
    
//echo htmlspecialchars($source->getData());

return (string)$source->getData();

}*/


$info_xml = new SimpleXMLElement( file_get_contents( "input/info.xml" ) );
    
//echo htmlspecialchars($source->getData());
if($info_xml->getName()!='ruleset')
	die('Error parsing info.xml First element has to be "ruleset" not '.$xml->getName().'.');
    
if((!$info_xml->name) || (!$info_xml->uid))
	die('lack of one or more of required fields: name,uid');



    
