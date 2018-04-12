<?php

namespace Drupal\geofield_map;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\geofield_map\Annotation\MapThemer;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;

/**
 * Provides a plugin manager for Geofield Map Themers.
 */
class MapThemerPluginManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
    TranslationInterface $translation_manager,
    EntityTypeManagerInterface $entity_manager
  ) {
    parent::__construct('Plugin/GeofieldMapThemer', $namespaces, $module_handler, MapThemerInterface::class, MapThemer::class);

    $this->alterInfo('geofield_map_themer_info');
    $this->setCacheBackend($cache_backend, 'geofield_map_themer_plugins');
    $this->entityManager = $entity_manager;
    $this->geofieldMapSettings = $config_factory->get('geofield_map.settings');
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
   * Validates the Icon Image statuses.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateIconImageStatuses(array $element, FormStateInterface $form_state) {
    $clicked_button = end($form_state->getTriggeringElement()['#parents']);
    if (!empty($element['#value']['fids'][0])) {
      $file = File::load($element['#value']['fids'][0]);
      if ($clicked_button == 'submit') {
        $file->setPermanent();
        self::fileSave($file);
      }
      if ($clicked_button == 'remove_button') {
        $file->setTemporary();
        self::fileSave($file);
      }
    }
  }

  /**
   * Save a file, handling exception.
   *
   * @param \Drupal\file\Entity\file $file
   *   The file to save.
   */
  public static function fileSave(file $file) {
    try {
      $file->save();
    }
    catch (EntityStorageException $e) {
      \Drupal::logger('Geofield Map Themer')->log('warning', t("The file couldn't be saved: @message", [
        '@message' => $e->getMessage(),
      ])
      );
    }
  }

  /**
   * Generate File Icon preview.
   *
   * @param int $fid
   *   The file to save.
   *
   * @return array
   *   The icon preview element.
   */
  protected function getFileIconElement($fid) {

    $upload_location = $this->geofieldMapSettings->get('theming.markers_location.security') . $this->geofieldMapSettings->get('theming.markers_location.rel_path');

    $element = [
      '#type' => 'managed_file',
      '#title' => t('Choose a Marker Icon file'),
      '#title_display' => 'invisible',
      '#default_value' => !empty($fid) ? [$fid] : NULL,
      '#multiple' => FALSE,
      '#error_no_message' => FALSE,
      '#upload_location' => $upload_location,
      '#upload_validators' => $this->fileUploadValidators,
      '#progress_message' => $this->t('Please wait...'),
      '#progress_indicator' => 'throbber',
      '#element_validate' => [
        [get_class($this), 'validateIconImageStatuses'],
      ],
    ];

    if (!empty($fid)) {
      $element['preview'] = $this->getIconView($fid);
    }

    return $element;
  }

  /**
   * Generate File Upload Help Message.
   *
   * @return array
   *   The field upload help element.
   */
  protected function getFileUploadHelp() {
    return [
      '#type' => 'container',
      '#tag' => 'div',
      'file_upload_help' => [
        '#theme' => 'file_upload_help',
        '#upload_validators' => $this->fileUploadValidators,
        '#cardinality' => 1,
      ],
      '#attributes' => [
        'style' => ['style' => 'font-size:0.8em; color: gray; text-transform: lowercase; font-weight: normal'],
      ],
    ];
  }

  /**
   * Generate File Icon preview.
   *
   * @param \Drupal\file\Entity\file $file
   *   The file to save.
   *
   * @return array
   *   The icon preview element.
   */
  protected function getFileIconView(file $file) {
    return [
      '#weight' => -10,
      '#theme' => 'image_style',
      '#width' => '40px',
      '#height' => '40px',
      '#style_name' => 'thumbnail',
      '#uri' => $file->getFileUri(),
    ];
  }

  /**
   * Generate File Managed Url from fid.
   *
   * @param int $fid
   *   The file identifier.
   *
   * @return string
   *   The icon preview element.
   */
  protected function getFileManagedUrl($fid = NULL) {
    if (isset($fid) && $file = File::load($fid)) {
      $uri = $file->getFileUri();
      $url = file_create_url($uri);
      return $url;
    }
    return NULL;
  }

  /**
   * Generate Icon View Element.
   *
   * @param int $fid
   *   The file identifier.
   *
   * @return array
   *   The icon view render array..
   */
  protected function getIconView($fid) {
    $icon_view_element = [];
    try {
      /* @var \Drupal\file\Entity\file $file */
      $file = $this->entityManager->getStorage('file')->load($fid);
      if ($file instanceof FileInterface) {
        $icon_view_element = $this->getFileIconView($file);
      }
    }
    catch (InvalidPluginDefinitionException $e) {
    }

    return $icon_view_element;
  }

}
