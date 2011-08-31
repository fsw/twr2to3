#!/usr/bin/php
<?php

//require_once('File/Archive.php');     


error_reporting( E_ALL ); 

$input_file = "input.twr";
$output_dir = "output/";
$input_dir = "input/";


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

//append elements to file using old xml format
function appendElement( $elements_xml, $xml ){

	//we will add element to default menu
	global $mainmenu_array;

	$element_xml = $elements_xml->addChild('element');
	
	$element_xml->addChild('uid',$xml->uid);
	$element_xml->addChild('name',$xml->name);
	
	if($xml->categories)
		foreach($xml->categories->category as $category){
			
			$id = ereg_replace("[^A-Za-z0-9\ ]", "",(string)$category);

			if(!isset($mainmenu_array[$id]))
				$mainmenu_array[$id] = (string)$category;
		
			$element_xml->addChild('menu')->addAttribute('id',$id);
		
		}


	foreach($xml->bonus as $bonus)
		appendElement( $element_xml->addChild('elements'), $bonus );

	foreach($xml->bonuses as $bonuses)
		appendElements( $element_xml->addChild('elements'), $bonuses );

}


function appendElements( $elements_xml, $xml ){
	
	foreach($xml->unit as $unit)
		appendElement( $elements_xml, $unit );
	
	foreach($xml->bonus as $bonus)
		appendElement( $elements_xml, $bonus );


}


$info_xml = new SimpleXMLElement( file_get_contents( $input_dir."info.xml" ) );
    
//echo htmlspecialchars($source->getData());
if($info_xml->getName()!='ruleset')
	die('Error parsing info.xml First element has to be "ruleset" not '.$xml->getName().'.');
    
if((!$info_xml->name) || (!$info_xml->uid))
	die('lack of one or more of required fields: name,uid');

$info_out_xml = new SimpleXMLElement("<ruleset></ruleset>");
//TODO xsi is missing!
$info_out_xml->addAttribute('xsi:schemaLocation', 'armycalc http://armycalc.com/xmls/twr3info.xsd');
$info_out_xml->addAttribute('version', '3.0');
$info_out_xml->addChild('uid','armycalc.twr3.autoconvert.'.$info_xml->uid);
$info_out_xml->addChild('revision','1');

$lang = $info_out_xml->addChild('languages')->addChild('language');
$lang->addAttribute('id','en');
$lang->addAttribute('default','true');
$lang->addChild('transtab')->addAttribute('src','english.xml');
$lang->addChild('name','English');

$english = new SimpleXMLElement("<transtab></transtab>");

$info_out_xml->addChild('name',$info_xml->name);
$info_out_xml->addChild('author',$info_xml->author);
$info_out_xml->addChild('description',$info_xml->description);
$info_out_xml->addChild('icon')->addAttribute('src','icon.png');


$stats_xml = $info_out_xml->addChild('stats');
$stats_array = array();
foreach($info_xml->displayFields->field as $field){
	$stats_array[ereg_replace("[^A-Za-z0-9]", "",$field->name)] = array();
	$stats_array[ereg_replace("[^A-Za-z0-9]", "",$field->name)]['default'] = (string)$field->default;

}

$default_cost = $info_out_xml->addChild('costs')->addChild('cost');
$default_cost->addAttribute('id',"pts");
$default_cost->addAttribute('default',"true");
$default_cost->addChild('name','Points');
$default_cost->addChild('shortname','pts');
$default_cost->addChild('unit','pts');
$default_cost->addChild('default',0);

$info_out_xml->addChild('defaultArmyName',$info_xml->defaultArmyName);
$info_out_xml->addChild('defaultArmySize')->addChild('cost',$info_xml->defaultArmySize)->addAttribute('id','pts');

$info_out_xml->addChild('errors');
$mainmenu_xml = $info_out_xml->addChild('mainmenu');
$mainmenu_array = array();

$model_xml = $info_out_xml->addChild('models')->addChild('model');
$model_xml->addAttribute('id',"model1");
$model_xml->addAttribute('default',"true");
$model_xml->addChild('name','Default Model');
$model_xml->addChild('elements')->addAttribute('src','elements.xml');
$model_xml->addChild('validator')->addAttribute('src','validator.js');

$elements_xml = new SimpleXMLElement("<elements></elements>");
//TODO xsi is missing!
$elements_xml->addAttribute('xsi:schemaLocation', 'armycalc http://armycalc.com/xmls/twr3elements.xsd');
$elements_xml->addAttribute('version', '3.0');

foreach($info_xml->files->units->file as $file){
	appendElements( $elements_xml, new SimpleXMLElement( file_get_contents( $input_dir.(string)$file->path )));
}
//<units>
//<file><name></name><path>units.xml</path></file>
//</units>

foreach( $mainmenu_array as $id=>$name){
	$menu_xml = $mainmenu_xml->addChild('menu');
	$menu_xml->addAttribute('id',$id);
	$menu_xml->addChild('name',$name);
	
}

foreach($stats_array as $id=>$stat){
	$stat_xml = $stats_xml->addChild('stat');
	$stat_xml->addAttribute('id',$id);
	$stat_xml->addAttribute('display','true');
	$stat_xml->addChild('name',$id);
	$stat_xml->addChild('shortname',$id);
	$stat_xml->addChild('default',$stat['default']);

}

function formatXml($simpleXml){

	$dom = new DOMDocument('1.0');
	$dom->preserveWhiteSpace = false;
	$dom->formatOutput = true;
	$dom->loadXML($simpleXml->asXML());
	return $dom->saveXML();

}

file_put_contents($output_dir."info.xml", formatXml($info_out_xml));
file_put_contents($output_dir."elements.xml", formatXml($elements_xml));
file_put_contents($output_dir."english.xml", formatXml($english));
file_put_contents($output_dir."validator.js", "//unfortunatell auto conversion does not support validation scripts and you have to revrite it :(");





