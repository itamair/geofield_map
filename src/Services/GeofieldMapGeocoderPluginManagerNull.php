<?php

namespace Drupal\geofield_map\Services;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides a manager to provide Geocoder Plugin Managers.
 */
class GeofieldMapGeocoderPluginManagerNull implements GeofieldMapGeocoderPluginManagerInterface {

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new GeocoderPluginManager Service object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The modules handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getGeocoderProviderPluginManager() {
    return NULL;
  }

}
