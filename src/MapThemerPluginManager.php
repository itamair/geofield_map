<?php

namespace Drupal\geofield_map;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\geofield_map\Annotation\MapThemer;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Plugin\Exception\PluginException;

/**
 * Provides a plugin manager for Geofield Map Themers.
 */
class MapThemerPluginManager extends DefaultPluginManager {

  /**
   * Constructor of the a Geofield Map Themers plugin manager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/GeofieldMapThemer', $namespaces, $module_handler, MapThemerInterface::class, MapThemer::class);

    $this->alterInfo('geofield_map_themer_info');
    $this->setCacheBackend($cache_backend, 'geofield_map_themer_plugins');
  }

  /**
   * Generate an Options array for all the MapThemers plugins.
   *
   * @return mixed[]
   *   An array of MapThemers plugins Options. Keys are plugin IDs.
   */
  public function getMapThemersList() {
    $options = [];
    foreach ($this->getDefinitions() as $k => $map_themer) {
      /* @var \Drupal\Core\StringTranslation\TranslatableMarkup $map_themer_name */
      $map_themer_name = $map_themer['name'];
      $options[$k] = $map_themer_name->render();
    }
    return $options;
  }

  /**
   * Retrieve the icon for theming definition.
   *
   * @param string $plugin_id
   *   The Map Themer plugin id.
   * @param array|string $values
   *   The theming definition.
   *
   * @return mixed
   *   The icon definition.
   */
  public function getIcon($plugin_id, $values) {
    try {
      $plugin = $this->createInstance($plugin_id);
      if (isset($plugin) && $plugin instanceof MapThemerInterface) {
        $plugin_type = $plugin->getPluginDefinition()['type'];
        switch ($plugin_type) {
          case 'single_value':
            $icon = $values;
            break;
        }
      }
      return $icon;
    }
    catch (PluginException $e) {
    }
    return NULL;

  }

}