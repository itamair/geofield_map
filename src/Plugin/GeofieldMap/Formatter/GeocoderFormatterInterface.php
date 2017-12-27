<?php

namespace Drupal\geofield_map\Plugin\GeofieldMap\Formatter;

use Geocoder\Model\Address;

/**
 * Provides an interface for geofield_map formatter plugins.
 *
 * Dumpers are plugins that knows to format geographical data into an industry
 * standard format.
 */
interface GeocoderFormatterInterface {

  /**
   * Dumps the argument into a specific format.
   *
   * @param \Geocoder\Model\Address $address
   *   The address to be formatted.
   *
   * @return string
   *   The formatted address.
   */
  public function format(Address $address);

}
