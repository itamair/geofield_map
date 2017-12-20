<?php

namespace Drupal\geofield_map\Services;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\geocoder\ProviderPluginManager;

/**
 * Provides a manager to provide Geocoder Plugin Managers.
 */
class GeocoderPluginManager implements GeocoderPluginManagerInterface {

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The provider plugin manager service.
   *
   * @var \Drupal\geocoder\ProviderPluginManager
   */
  protected $geocoderProviderPluginManager;

  /**
   * Constructs a new GeocoderPluginManager Service object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The modules handler.
   * @param \Drupal\geocoder\ProviderPluginManager $provider_plugin_manager
   *   The provider plugin manager service.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    ProviderPluginManager $provider_plugin_manager
  ) {
    $this->moduleHandler = $module_handler;
    $this->geocoderProviderPluginManager = $provider_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getGeocoderProviderPluginManager() {
    return $this->geocoderProviderPluginManager;
  }

}
