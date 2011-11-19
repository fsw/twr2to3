#!/usr/bin/php
<?php

//require_once('File/Archive.php');     


error_reporting( 0 ); //E_ALL ); 

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
	if( $xml->description )
	  $element_xml->addChild('description')->addChild('short',$xml->description);
	
	
	if( $xml->thumbnail )
	  $element_xml->addChild( 'thumbnail' )->addAttribute( 'src', $xml->thumbnail );
	
	
	$baseCost = (int)$xml->baseCost;
	$costPerUnit = (int)$xml->costPerUnit;

	$xml->addChild( 'cost', $baseCost + $costPerUnit )->addAttribute( 'id', 'pts' );
	//this is most important change from TWR 2 to 3 so this can be faulty 
	if( $costPerUnit )
	  $xml->addAttribute( 'size', 'inherit' );
	
	
	if($xml->extraFields){
		$stats = $element_xml->addChild('stats');
		foreach($xml->extraFields->field as $field){
			$stats->addChild('stat',$field->value)->addAttribute('id',ereg_replace("[^A-Za-z0-9]", "",$field->name));
		}	
	}

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

$info_out_xml = new SimpleXMLElement( "<ruleset></ruleset>" );
//TODO xsi is missing!
$info_out_xml->addAttribute( 'xmlns', 'armycalc' );
$info_out_xml->addAttribute( 'xsi:schemaLocation', 'armycalc http://armycalc.com/xmls/twr3info.xsd', 'http://www.w3.org/2001/XMLSchema-instance' );
$info_out_xml->addAttribute( 'version', '3.0' );
$identity = $info_out_xml->addChild( 'identity' );
$identity->addChild( 'uid', 'armycalc.twr3.autoconvert.'.$info_xml->uid );
$identity->addChild( 'revision', $info_xml->revision );
$identity->addChild( 'netid', 'unknown' );
$identity->addChild( 'origin', 'unknown' );
//$identity->addChild( 'md5', 'unknown' );


$langs = $info_out_xml->addChild('languages');
$lang1 = $langs->addChild('language');
$lang1->addAttribute('id','en');
$lang1->addAttribute('default','true');
$lang1->addChild('transtab')->addAttribute('src','english_transtab.xml');
$lang1->addChild('name','English');

$lang2 = $langs->addChild('language');
$lang2->addAttribute('id','xx');
$lang2->addChild('transtab')->addAttribute('src','example_transtab.xml');
$lang2->addChild('name','Example Language');


$english_transtab = new SimpleXMLElement("<transtab></transtab>");
$example_transtab = new SimpleXMLElement("<transtab></transtab>");
$example_transtab->addChild('text',"Example translation 1")->addAttribute('id','SHORTDESC');
$example_transtab->addChild('text',"Example translation 2")->addAttribute('id','LONGDESC');
$example_transtab->addChild('text',"Example translation 3")->addAttribute('id','AUTO_CONVERTED_ERROR');




$info_out_xml->addChild('name',$info_xml->name);

$author = $info_out_xml->addChild('author');
  $author->addChild('name', $info_xml->author);
  $author->addChild('email', '');
  $author->addChild('netid', 'unknown');
  $author->addChild('login', 'unknown');
  $author->addChild('url', 'unknown');


$description = $info_out_xml->addChild('description',$info_xml->description);
$info_out_xml->addChild('full',$info_xml->description)->addAttribute('tid','LONGDESC');
$info_out_xml->addChild('short', 
  (strlen($info_xml->description)>100 ? substr($info_xml->description,0,100)."..." : $info_xml->description ))->addAttribute('tid','SHORTDESC');

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

$default_error = $info_out_xml->addChild('errors')->addChild('error');
  $default_error->addAttribute('class','warning');
  $default_error->addAttribute('id','auto_converted');
  $default_error->addChild('message','This ruleset was auto converted and does not provide a validator')->addAttribute('tid','AUTO_CONVERTED_ERROR');





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

//appendElements have also populated menu with categories
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

function ac_put_contents($path,$contents){

  global $output_dir;
  file_put_contents( $output_dir.$path, $contents );
  echo "written ".strlen($contents)." bytes to $path\n";

}

ac_put_contents("info.xml", formatXml($info_out_xml));
ac_put_contents("elements.xml", formatXml($elements_xml));
ac_put_contents("english_transtab.xml", formatXml($english_transtab));
ac_put_contents("example_transtab.xml", formatXml($example_transtab));
ac_put_contents("validator.js", "//unfortunatell auto conversion does not support validation scripts and you have to revrite it :(\narmy.toggleError('auto_converted',true);");





