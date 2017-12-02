<?php

namespace Drupal\geofield_map\Services;

use GuzzleHttp\ClientInterface;
use Drupal\geocoder\GeocoderInterface;
use Drupal\geocoder\DumperPluginManager;

/**
 * Class GeocoderGeocoderService.
 */
class GeocoderGeocoderService extends GeocoderGoogleMaps implements GeofieldMapGeocoderServiceInterface {

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Drupal\geocoder\Geocoder definition.
   *
   * @var \Drupal\geocoder\Geocoder
   */
  protected $geocoder;

  /**
   * Drupal\geocoder\DumperPluginManager definition.
   *
   * @var \Drupal\geocoder\DumperPluginManager
   */
  protected $geocoderDumper;

  /**
   * Constructs a new GeofieldMapGeocoderService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Http Client.
   * @param \Drupal\geocoder\GeocoderInterface $geocoder
   *   The geocoder service.
   * @param \Drupal\geocoder\DumperPluginManager $geocoder_dumper
   *   The geocoder dumper service.
   */
  public function __construct(ClientInterface $http_client, GeocoderInterface $geocoder, DumperPluginManager $geocoder_dumper) {
    parent::__construct($http_client);
    $this->geocoder = $geocoder;
    $this->geocoderDumper = $geocoder_dumper;

  }

  /**
   * {@inheritdoc}
   */
  public function geocoderGeocode($address, array $plugins, array $plugin_options = []) {
    $results = [];
    /* @var \Geocoder\Model\Address $addressesCollection */
    $addressesCollection = $this->geocoder->geocode($address, $plugins, $plugin_options)
      ->all();
    if (!empty($addressesCollection)) {
      /* @var \Geocoder\Model\Address $geoAddress */
      foreach ($addressesCollection as $geoAddress) {
        $geoAddressArray = $geoAddress->toArray();
        // It a formatted_address property is not defined
        // (as Google Maps Geocoding does), then create it with our own dumper.
        if (!isset($geoAddressArray['formatted_address'])) {
          $geoAddressArray['formatted_address'] = $this->geocoderDumper->createInstance('geofieldmap_formattedaddress')
            ->dump($geoAddress);
        }
        // It a formatted_address property is not defined
        // (as Google Maps Geocoding does), then create it with our own dumper.
        if (!isset($geoAddressArray['geometry'])) {
          $geoAddressArray['geometry'] = $this->geocoderDumper->createInstance('geofieldmap_geometry')
            ->dump($geoAddress);
        }
        $results[] = $geoAddressArray;
      }
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetDebugMessage() {

    $output_message = [];

    // Define an output Debug Message only for the User.
    if ($this->currentUser->hasPermission('configure geofield_map')) {


    }

    return $output_message;
  }

}
