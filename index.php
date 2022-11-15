<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
error_reporting(E_ALL);

require_once "vendor/SleekDB/src/Store.php";
//phpinfo();
//exit();

// Set headers
header('Content-Type: application/json; charset=utf-8');

// Datbase
$databaseDirectory = __DIR__ . "/database";
$processes = new \SleekDB\Store("processes", $databaseDirectory);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Decode JSON from request body
  try {
    $json = json_decode(file_get_contents('php://input'), false, 512, JSON_THROW_ON_ERROR);
    if (!isset($json->pdf)) {
      header("HTTP/1.1 400 Bad Request");
      echo json_encode(array('status' => 'Error', 'statusCode' => 400, 'message' => 'Mandatory parameters missing: pdf.'));
      return;
    }
  }
  catch (\JsonException $exception) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('status' => 'Error', 'statusCode' => 400, 'message' => $exception->getMessage()));
    return;
  }

  // Parameters
  $resolution = isset($json->resolution) ? $json->resolution : 150; // Resolution in DPI
  $pdf = isset($json->pdf) ? $json->pdf : null; // PDF file
  $id = bin2hex(random_bytes(20)); // Unique ID

  // Create directories
  if(!is_dir('data')) {
    mkdir('data');
  }
  if(!is_dir('data/' . $id)) {
    mkdir('data/' . $id);
    mkdir('data/' . $id . '/images');
  }

  // Fetch PDF from URL with curl, save it to disk and check for errors
  $ch = curl_init($pdf);
  $fp = fopen('data/' . $id . '/jeff.pdf', 'wb');
  curl_setopt($ch, CURLOPT_FILE, $fp);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_exec($ch);
  curl_close($ch);
  fclose($fp);
  if(!file_exists('data/' . $id . '/jeff.pdf')) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(array('status' => 'Error', 'message' => 'Could not fetch PDF.'));
    return;
  }

  //var_dump(exec("/Applications/MAMP/bin/php/php7.4.16/bin /Volumes/TRON1/htdocs/pdfjeff/utils/generate-images.php '".$id."' ".$resolution." &"));
  //var_dump(exec('/Applications/MAMP/bin/php/php7.4.16/bin /Volumes/TRON1/htdocs/pdfjeff/utils/generate-images.php', $id, $resolution));
  //var_dump("php /Volumes/TRON1/htdocs/pdfjeff/utils/generate-images.php '".$id."' ".$resolution." &");
  //exec("php utils/generate-images.php '" . $id . "' ". $resolution . " &");

  // Save process to database
  $process = [
    "id" => $id,
    "resolution" => $resolution,
    "status" => "processing"
  ];
  $results = $processes->insert($process);

  // Generate images
  $info = shell_exec("php utils/generate-images.php '" . $id . "' ". $resolution . " " . $results['_id'] . " >/dev/null 2>&1 &");
  //$info = shell_exec("php utils/generate-images.php '" . $id . "' ". $resolution . " " . $results['_id'] . " &");
  //var_dump($info);


  //$updateddbprocess = $processes->updateById($dbprocess["_id"], ["status" => "done"]);

  //print_r($updateddbprocess);

  //var_dump($info);
  //exec("/Applications/MAMP/bin/php/php7.4.16/bin /Volumes/TRON1/htdocs/pdfjeff/utils/generate-images.php '" . $id . "' ". $resolution . " &");

  /*$image = new Imagick();
  $image->pingImage('data/' . $id . '/jeff.pdf');
  $pages = $image->getNumberImages();
  $images = array();
  for($i=0; $i < $pages; $i++) {
    $imagick = new Imagick();
    $imagick->setResolution($resolution,$resolution);
    $imagick->readImage('data/' . $id . '/jeff.pdf[' . $i . ']');
    $imagick->writeImage('data/' . $id . '/images/' . $i . '.jpg');
    $images[] = 'data/' . $id . '/images/' . $i . '.jpg';
  }*/

  // header ok
  header("HTTP/1.1 200 OK");
  //echo json_encode(array('status' => 'Success', 'message' => 'Images generated.', 'id' => $id, 'images' => $images));
  echo json_encode(array('status' => 'Processing', 'message' => 'Images are being generated.', 'id' => $id));

  return;

  // Delete directory
  //exec('rm -rf data/' . $id);
}
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $id = isset($_GET['id']) ? $_GET['id'] : null;
  if ($id == null) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(array('status' => 'Error', 'statusCode' => 400, 'message' => 'Mandatory parameters missing: id.'));
    return;
  }

  $process = $processes->findOneBy(["id", "=", $id]);

  //$updateddbprocess = $processes->updateById($process["_id"], ["images" => "dis"]);
  //var_dump($process);

  if ($process == null) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(array('status' => 'Error', 'statusCode' => 400, 'message' => 'Process not found.'));
    return;
  }

  header("HTTP/1.1 200 OK");
  echo json_encode(array('status' => $process['status'], 'statusCode' => 200, 'message' => 'Doing something', 'images' => $process['images']));

  //header("HTTP/1.1 400 Bad Request");
  //echo json_encode(array('status' => 'Error', 'statusCode' => 400, 'message' => 'Only POST requests are allowed.'));
  //return;
}

?>