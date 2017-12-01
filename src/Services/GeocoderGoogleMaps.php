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
  public function googleMapsGeocode($address, $apiKey, array $options) {

    // Build the Google Maps Geocoding request, with the apiKey.
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . '&key=' . $apiKey;

    // Add additional options to the request (besides the apiKey already in)
    foreach ($options['googlemaps'] as $k => $option) {
      if ($k != 'apiKey') {
        $url .= '&' . rawurlencode($k) . '=' . rawurlencode($option);
      }
      if ($k == 'locale') {
        $url .= '&language=' . rawurlencode($option);
      }
    }

    /* @var \GuzzleHttp\Client $client */
    $client = $this->httpClient;
    $data = Json::decode($client->get($url)->getBody()->getContents());
    return $data['results'];
  }

}
