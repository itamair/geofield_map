<?php

namespace Drupal\geofield_map\Services;

use GuzzleHttp\ClientInterface;
use Drupal\Component\Serialization\Json;

/**
 * Class GeocoderGoogleMaps.
 */
abstract class GeocoderGoogleMaps implements GeofieldMapGeocoderServiceInterface {

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a new GeofieldMapGeocoderService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Http Client.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function googleMapsGeocode($address, $apiKey) {

    $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . '&key=' . $apiKey;

    /* @var \GuzzleHttp\Client $client */
    $client = $this->httpClient;
    $data = Json::decode($client->get($url)->getBody()->getContents());
    return $data['results'];
  }

}
