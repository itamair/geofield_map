<?php

namespace Drupal\geofield_map\Plugin\GeofieldMap\Formatter;

use Geocoder\Model\Address;

/**
 * Provides a GeofieldMap Default Formatter plugin.
 *
 * @GeofieldMapFormatter(
 *   id = "default_address_formatter",
 *   name = "Default Address Formatter"
 * )
 */
class GeofieldMapFormattedAddress extends GeocoderFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function format(Address $address) {
    $formatted_address = $this->formatter->format($address, '%S %n, %z %L %c, %C');
    $this->cleanFormattedAddress($formatted_address);
    return $formatted_address;
  }

}
