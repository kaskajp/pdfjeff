# PDF Jeff
Feed Jeff a PDF and he'll turn it into images!

## Installation
1. Update the `config.php` file in `/utils` with your own settings.
2. Chuck Jeff into your Apache server and you're good to go! Jeff will create a folder called `data` in the root directory and store the PDFs there.

## Dependencies
Jeff requires the following dependencies:
* [ImageMagick](http://www.imagemagick.org/script/index.php)
* [PHP](http://php.net/)
* [Apache](http://httpd.apache.org/)
* [SleekDB](https://sleekdb.github.io)

Jeff uses SleekDB (a lightweight PHP database) to store conversion processes.

## Usage
There are two available requests, one for feeding Jeff a PDF and one for getting the images back and checking the processing status.

The intended use goes like this:
1. POST a PDF to Jeff.
2. Take the returned ID from the POST request and do a GET request to the same endpoint with the ID as a parameter.
3. The GET request will return the status of the conversion process. If the conversion is done, the images will be returned as well.
4. Repeat step 3 until the conversion is done (status will be set to "done" and images will go from null to an array of images than you can download).

### Feed Jeff a PDF
To feed Jeff a PDF, send a `POST` request to with the following `JSON` body:
```json
{
  "pdf": "https://example.com/example.pdf",
  "resolution": 300
}
```
The `resolution` parameter (DPI) is optional and defaults to 150.

Jeff will return a `JSON` object with the following structure:
```json
{
  "status": "processing",
  "message": "Images are being generated.",
  "id": "0cc62ed6e1ddd65cad328014f0802e138ffff39d"
}
```
### Get the images back (and check the status)
To get the images back, send a GET request to with the following query parameters:
* `id`: The ID of the conversion process

E.g. `https://pdfjeff.test/?id=0cc62ed6e1ddd65cad328014f0802e138ffff39d`

Jeff will initially return a `JSON` object with the following structure:
```json
{
  "status": "processing",
  "statusCode": 200,
  "message": "This file is currently being processed.",
  "images": null
}
```
If the status is still "processing", do another request with a delay of a few seconds. When Jeff has finished processing, the returned `JSON` object will look something like this:
```json
{
  "status": "done",
  "statusCode": 200,
  "message": "This file has finished processing. Images will automatically expire after 30 minutes.",
  "images": [
    "data/12a86ceed46ed2049fd9396edfaa1ec5b813f1e9/images/0.jpg",
    "data/12a86ceed46ed2049fd9396edfaa1ec5b813f1e9/images/1.jpg",
    "data/12a86ceed46ed2049fd9396edfaa1ec5b813f1e9/images/2.jpg",
    "data/12a86ceed46ed2049fd9396edfaa1ec5b813f1e9/images/3.jpg",
    "data/12a86ceed46ed2049fd9396edfaa1ec5b813f1e9/images/4.jpg",
    "data/12a86ceed46ed2049fd9396edfaa1ec5b813f1e9/images/5.jpg",
    "data/12a86ceed46ed2049fd9396edfaa1ec5b813f1e9/images/6.jpg",
    "data/12a86ceed46ed2049fd9396edfaa1ec5b813f1e9/images/7.jpg"
  ]
}
```
In case of a failure, Jeff will return a `JSON` object with the following structure:
```json
{
  "status": "error",
  "statusCode": 500,
  "message": "This file failed to process, please try again.",
  "images": null
}
```

## Issues
If you find anything that doesn't work as expected, feel free to open an issue using the GitHub issues tracker. Keep in mind that this is a hobby project and I'm not actively maintaining it. I don't intend for Jeff to be something he's not, so I (most likely) won't be adding any new features.

## License
Jeff is licensed under the MIT license. See the [LICENSE](LICENSE) file for more information.

![jeff](https://user-images.githubusercontent.com/1626458/202286502-1eb3abf0-73ff-46bb-b039-00e28a6b3317.gif)