<?php

namespace Drupal\geofield_map;

use Drupal\geocoder\GeocoderInterface;
use Drupal\geocoder\DumperPluginManager;

/**
 * Class GeofieldMapGeocoderService.
 */
class GeofieldMapGeocoderService implements GeofieldMapGeocoderServiceInterface {

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
   * @param \Drupal\geocoder\GeocoderInterface $geocoder
   *   The geocoder service.
   * @param \Drupal\geocoder\DumperPluginManager $geocoder_dumper
   *   The geocoder dumper service.
   */
  public function __construct(GeocoderInterface $geocoder, DumperPluginManager $geocoder_dumper) {
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
        $geoAddressArray['addresstext'] = $this->geocoderDumper->createInstance('geofieldmap_addresstext')
          ->dump($geoAddress);
        $geoAddressArray['formatted_address'] = $this->geocoderDumper->createInstance('geofieldmap_formattedaddress')
          ->dump($geoAddress);
        $results[] = $geoAddressArray;
      }
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function googleMapsGeocode($address, $apiKey) {
    return [];
  }

}
