<?php

namespace Drupal\geofield_map\Plugin\Geocoder\Dumper;

use Drupal\geocoder\DumperBase;
use Geocoder\Model\Address;

/**
 * Provides an address string geocoder dumper plugin.
 *
 * @GeocoderDumper(
 *   id = "geofieldmap_addresstext",
 *   name = "GeofieldMap Address string"
 * )
 */
class GeofieldMapAddressText extends DumperBase {

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

    return implode(', ', array_filter($values));
  }

}
