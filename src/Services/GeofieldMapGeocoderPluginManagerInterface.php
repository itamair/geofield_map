<?php

namespace Drupal\geofield_map\Services;

/**
 * Interface GeocoderPluginManagerInterface.
 */
interface GeofieldMapGeocoderPluginManagerInterface {

  /**
   * Return Null or geocoder\ProviderPluginManager if geocoder module exists.
   *
   * @return null|\Drupal\geocoder\ProviderPluginManager
   *   The result.
   */
  public function getGeocoderProviderPluginManager();

}
