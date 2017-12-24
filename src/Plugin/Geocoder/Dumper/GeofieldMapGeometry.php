<?php

namespace Drupal\geofield_map\Plugin\Geocoder\Dumper;

use Drupal\geocoder\DumperBase;
use Geocoder\Model\Address;

/**
 * Provides an address string geocoder dumper plugin.
 *
 * @GeocoderDumper(
 *   id = "geofieldmap_geometry",
 *   name = "GeofieldMap Geometry"
 * )
 */
class GeofieldMapGeometry extends DumperBase {

  /**
   * {@inheritdoc}
   *
   * @return string
   *   The formatted address.
   */
  public function dump(Address $address) {
    $values = [];
    foreach ($address->toArray() as $key => $value) {
      if (!is_array($value)) {
        $values[$key] = $value;
      }
    }

    /* @var array $geometry */
    $geometry = [
      'location' => [
        'lat' => $address->toArray()['latitude'],
        'lng' => $address->toArray()['longitude'],
      ],
      'viewport' => [
        'northeast' => [
          'lat' => $address->toArray()['bounds']['north'],
          'lng' => $address->toArray()['bounds']['east'],
        ],
        'southwest' => [
          'lat' => $address->toArray()['bounds']['south'],
          'lng' => $address->toArray()['bounds']['west'],
        ],
      ],
    ];

    return $geometry;
  }

}
