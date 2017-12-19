<?php

namespace Drupal\geofield_map\Annotation;

use Drupal\geocoder\Annotation\GeocoderPluginBase;

/**
 * Defines a geofield map dumper plugin annotation object.
 *
 * @Annotation
 */
class GeofieldMapDumper extends GeocoderPluginBase {

  /**
   * The plugin handler.
   *
   * This is the fully qualified class name of the plugin handler.
   *
   * @var string
   */
  public $handler = NULL;

}
