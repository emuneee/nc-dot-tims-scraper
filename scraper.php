/*
 * Copyright 2013 Evan Halley
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
<?php

$base_url = "http://tims.ncdot.gov/TIMS/RSS/CameraGeoRSS.aspx";
$reverse = "http://maps.googleapis.com/maps/api/geocode/json";
$t_lat_par = "TLatitude";
$t_lon_par = "TLongitude";
$b_lat_par = "BLatitude";
$b_lon_par = "BLongitude";
$latlon_par = "latlng";
$sensor = "sensor";
$limit = 10;

if($_SERVER['REQUEST_METHOD'] == 'GET') {
  $t_lat = 37.02;//$_GET['tlat'];
  $t_lon = -87.82;//$_GET['tlon'];
  $b_lat = 31.58;//$_GET['blat'];
  $b_lon = -69.77;//$_GET['blon'];
  // build query parameters 
  $params = array($t_lat_par => $t_lat,
    $t_lon_par => $t_lon,
    $b_lat_par => $b_lat,
    $b_lon_par => $b_lon);
   
  $r = new HttpRequest($base_url, HttpRequest::METH_GET);
  $r->addQueryData($params);

  $r->send();
  if($r->getResponseCode() == 200) {
    $xml = $r->getResponseBody();
    $cameras_xml = new SimpleXMLElement($xml);

    $i = 0;
    $cameras = array();
    foreach($cameras_xml->channel->item as $camera) {
        if($i === $limit) {
          //break;
        }
        // parse the url
        $raw_desc = $camera->description;
        $src_start = strpos($raw_desc, "src='", 0);
        $src_end = strpos($raw_desc, "'", $src_start + 5);
        $url = substr($raw_desc, $src_start + 5, $src_end - $src_start - 5);
        // parse the coordinates
        $coords = split(" ", $camera->children("http://www.georss.org/georss"));
        $latitude = $coords[0];
        $longitude = $coords[1];
        // get metadata from google maps
        $r2 = new HttpRequest($reverse, HttpRequest::METH_GET);
        $query = array($latlon_par => $latitude . ',' . $longitude,
          $sensor => 'false');
        $r2->addQueryData($query);
        $r2->send();
        // wait for 2 seconds
        usleep(250000);
        $address = "";
        if($r2->getResponseCode() == 200) {
          $results = json_decode($r2->getResponseBody(), true);
          //print_r($results);
          foreach ($results['results'] as $result) {
          
          //print_r($result);
            if(strcmp($result['types'][0], 'postal_code') == 0) {
              $address = $result['formatted_address'];
            }
          }
        }

        $camera_obj = array(
          "index" => $i,
          "guid" => hash("md5", $url),
          "title" => (string)$camera->title,
          "address" => $address,
          "latitude" => $latitude,
          "longitude" => $longitude,
          "url" => $url);
        array_push($cameras, $camera_obj);
        $i++;
    }
    echo json_encode($cameras);
  }
}
?>
