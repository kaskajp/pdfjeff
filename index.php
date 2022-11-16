<?php
require_once "vendor/SleekDB/src/Store.php";

// Set headers
header('Content-Type: application/json; charset=utf-8');

// Database
$databaseDirectory = __DIR__ . "/database";
$processes = new \SleekDB\Store("processes", $databaseDirectory);

// Clear expired processes
$expiredProcesses = $processes->deleteBy(["timestamp", "<", (strtotime("-30 seconds"))], 2);
for ($i = 0; $i < count($expiredProcesses); $i++) {
  // Delete directory and files for expired process
  exec('rm -rf data/' . $expiredProcesses[$i]["id"]);
}

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

  // Save process to database
  $process = [
    "id" => $id,
    "resolution" => $resolution,
    "status" => "processing",
    "timestamp" => strtotime("now")
  ];
  $results = $processes->insert($process);

  // Generate images
  $info = shell_exec("php utils/generate-images.php '" . $id . "' ". $resolution . " " . $results['_id'] . " >/dev/null 2>&1 &");
  //$info = shell_exec("php utils/generate-images.php '" . $id . "' ". $resolution . " " . $results['_id'] . " &");
  //var_dump($info);

  header("HTTP/1.1 200 OK");
  echo json_encode(array('status' => 'Processing', 'message' => 'Images are being generated.', 'id' => $id));

  return;
}
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  // Fail with error if no ID is given
  $id = isset($_GET['id']) ? $_GET['id'] : null;
  if ($id == null) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(array('status' => 'Error', 'statusCode' => 400, 'message' => 'Mandatory parameters missing: id.'));
    return;
  }

  // Find process in database
  $process = $processes->findOneBy(["id", "=", $id]);

  // Fail with error if process is not found
  if ($process == null) {
    header("HTTP/1.1 404 Not Found");
    echo json_encode(array('status' => 'Error', 'statusCode' => 404, 'message' => 'Process not found.'));
    return;
  }

  header("HTTP/1.1 200 OK");
  if($process['status'] === 'processing') {
    echo json_encode(array('status' => $process['status'], 'statusCode' => 200, 'message' => 'This file is currently being processed.', 'images' => isset($process['images']) ? $process['images'] : null));
  }
  elseif($process['status'] === 'done') {
    echo json_encode(array('status' => $process['status'], 'statusCode' => 200, 'message' => 'This file has finished processing. Images will automatically expire after 30 minutes.', 'images' => isset($process['images']) ? $process['images'] : null));
  }
  elseif($process['status'] === 'failed') {
    echo json_encode(array('status' => $process['status'], 'statusCode' => 200, 'message' => 'This file failed to process, please try again.', 'images' => isset($process['images']) ? $process['images'] : null));
  }
}

?>