<?php
// Datbase
require_once dirname(__DIR__) . "/vendor/SleekDB/src/Store.php";
$databaseDirectory = dirname(__DIR__) . "/database";
//var_dump(dirname(__DIR__) . "/database");
$processes = new \SleekDB\Store("processes", $databaseDirectory);

//echo "doing " .$argv[1] ;
$id = $argv[1];
$resolution = $argv[2];
$processID = $argv[3];
$image = new Imagick();
$image->pingImage('/Users/jonas/Sites/pdfjeff/data/' . $id . '/jeff.pdf');
$pages = $image->getNumberImages();
$images = array();
for($i=0; $i < $pages; $i++) {
  $imagick = new Imagick();
  $imagick->setResolution($resolution,$resolution);
  $imagick->readImage('/Users/jonas/Sites/pdfjeff/data/' . $id . '/jeff.pdf[' . $i . ']');
  $imagick->writeImage('/Users/jonas/Sites/pdfjeff/data/' . $id . '/images/' . $i . '.jpg');
  $images[] = 'data/' . $id . '/images/' . $i . '.jpg';
}
$updateddbprocess = $processes->updateById($processID, ["images" => $images]);
