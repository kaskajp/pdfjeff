<?php
// Database and configuration
require_once "config.php";
require_once dirname(__DIR__) . "/vendor/SleekDB/src/Store.php";
$databaseDirectory = dirname(__DIR__) . "/database";
$processes = new \SleekDB\Store("processes", $databaseDirectory);

try {
  // Arguments
  $id = $argv[1];
  $resolution = $argv[2];
  $processID = $argv[3];

  // Generate images with Imagick
  $image = new Imagick();
  $image->pingImage(DATA_PATH . $id . '/jeff.pdf');
  $pages = $image->getNumberImages();
  $images = array();
  for($i=0; $i < $pages; $i++) {
    $imagick = new Imagick();
    $imagick->setResolution($resolution,$resolution);
    $imagick->readImage(DATA_PATH . $id . '/jeff.pdf[' . $i . ']');
    $imagick->writeImage(DATA_PATH . $id . '/images/' . $i . '.jpg');
    $images[] = 'data/' . $id . '/images/' . $i . '.jpg';
  }

  // Update process in database with images and new status
  $updateddbprocess = $processes->updateById($processID, ["status" => "done", "timestamp" => strtotime("now"), "images" => $images]);
}
catch (ImagickException $e) {
  // Update process in database with failed status
  $updateddbprocess = $processes->updateById($processID, ["status" => "failed", "timestamp" => strtotime("now")]);
}
