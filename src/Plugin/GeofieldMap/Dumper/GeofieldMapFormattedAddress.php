<?php

namespace Drupal\geofield_map\Plugin\GeofieldMap\Dumper;

use Drupal\geocoder\DumperBase;
use Geocoder\Model\Address;

/**
 * Provides a formatted address string geocoder dumper plugin.
 *
 * @GeofieldMapDumper(
 *   id = "geofieldmap_default_formatter",
 *   name = "GeofieldMap Default Formatter"
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

    $formatted_address = '';
    $formatted_address .= !empty($values['streetName']) ? $values['streetName'] . ', ' : '';
    $formatted_address .= !empty($values['streetNumber']) ? $values['streetNumber'] . ', ' : '';
    $formatted_address .= !empty($values['postalCode']) ? $values['postalCode'] . ' ' : '';
    $formatted_address .= !empty($values['subLocality']) && !empty($values['locality']) ? $values['subLocality'] . ' - ' . $values['locality'] . ' ' : '';
    $formatted_address .= empty($values['subLocality']) && !empty($values['locality']) ? $values['locality'] . ' ' : '';
    $formatted_address .= !empty($values['countryCode']) ? $values['countryCode'] : '';
    $formatted_address .= !empty($values['country']) ? ', ' . $values['country'] : '';

    return $formatted_address;
  }

}
