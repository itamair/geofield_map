<?php

namespace Drupal\geofield_map\Plugin\Geocoder\Dumper;

use Drupal\geocoder\DumperBase;
use Geocoder\Model\Address;

/**
 * Provides a formatted address string geocoder dumper plugin.
 *
 * @GeocoderDumper(
 *   id = "geofieldmap_formattedaddress",
 *   name = "GeofieldMap Formatted Address string"
 * )
 */
class GeofieldMapFormattedAddress extends DumperBase {

  /**
   * {@inheritdoc}
   */
  public function dump(Address $address) {
    $values = [];
    foreach ($address->toArray() as $key => $value) {
      if (!is_array($value)) {
        $values[$key] = $value;
      }
    }
    unset($values['latitude'], $values['longitude']);

    $formatted_address = $values['streetName'] .
      ', ' . $values['streetNumber'] .
      ', ' . $values['postalCode'] .
      ' ' . $values['locality'] .
      ' ' . $values['countryCode'] .
      ', ' . $values['country'];

    return $formatted_address;
  }

}
