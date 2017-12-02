<?php

namespace Drupal\geofield_map\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Class GeocoderGoogleMaps.
 */
abstract class GeocoderGoogleMaps implements GeofieldMapGeocoderServiceInterface {

  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The current user making the request.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The Gmap Api Key.
   *
   * @var string
   */
  protected $gmapApiKey;

  /**
   * The Url to Geofield Map settings/configuration page.
   *
   * @var string
   */
  protected $geofieldMapSettingsPageUrl;

  /**
   * Constructs a new GeofieldMapGeocoderService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Http Client.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The modules handler.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ClientInterface $http_client,
    AccountProxyInterface $current_user,
    ModuleHandlerInterface $module_handler,
    LinkGeneratorInterface $link_generator,
    TranslationInterface $string_translation
  ) {
    $this->config = $config_factory;
    $this->httpClient = $http_client;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->link = $link_generator;

    $this->gmapApiKey = $this->config->get('geofield_map.settings')->get('gmap_api_key');

    $this->geofieldMapSettingsPageUrl = Url::fromRoute('geofield_map.settings', [], [
      'query' => [
        'destination' => Url::fromRoute('<current>')
          ->toString(),
      ],
    ]);

  }

  /**
   * {@inheritdoc}
   */
  public function geocoderGeocode($address, array $plugins, array $plugin_options = []) {
    $results = [];
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function googleMapsGeocode($address, $apiKey, array $options) {
    $results = [];
    return $results;
  }

}
