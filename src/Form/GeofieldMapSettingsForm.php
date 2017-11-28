<?php

namespace Drupal\geofield_map\Form;

use Drupal\geofield_map\GeofieldMapFormElementsValidationTrait;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Implements the GeofieldMapSettingsForm controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class GeofieldMapSettingsForm extends ConfigFormBase {

  use GeofieldMapFormElementsValidationTrait;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * GeofieldMapSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LinkGeneratorInterface $link_generator,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($config_factory);
    $this->link = $link_generator;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('link_generator'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('geofield_map.settings');

    $form['#tree'] = TRUE;

    $form['gmap_api_key'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('gmap_api_key'),
      '#title' => $this->t('Gmap Api Key (@link)', [
        '@link' => $this->link->generate(t('Get a Key/Authentication for Google Maps Javascript Library'), Url::fromUri('https://developers.google.com/maps/documentation/javascript/get-api-key', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
      '#description' => $this->t('Geofield Map requires a valid Google API key for his main features based on Google & Google Maps APIs.'),
      '#placeholder' => $this->t('Input a valid Gmap API Key'),
    ];

    // If the Geocoder Module exists and is enabled.
    if ($this->moduleHandler->moduleExists('geocoder')) {

      $form['geocoder'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Geofield Map Geocoder Settings'),
        '#description' => $this->t('Geofield Map uses the enabled Geocoder Module to integrate external geocoders. Use the following settings to choose and tune the geocoders you want to use and their working weights.'),
      ];
      $form['geocoder']['min_terms'] = [
        '#type' => 'number',
        '#default_value' => !empty($config->get('geocoder.min_terms')) ? $config->get('geocoder.min_terms') : 4,
        '#title' => $this->t('The minimum terms number for the Geocoder to start processing'),
        '#description' => $this->t('A too low value (<= 3) will affect the application Geocode Quota usage. Try to increase this value if you are experiencing Quota usage matters.'),
        '#min' => 2,
        '#max' => 10,
        '#size' => 3,
      ];
      $form['geocoder']['delay'] = [
        '#type' => 'number',
        '#default_value' => !empty($config->get('geocoder.delay')) ? $config->get('geocoder.delay') : 800,
        '#title' => $this->t('The delay in milliseconds between when a keystroke occurs and when a Geocode search is started/performed'),
        '#description' => $this->t('Valid values ​​for the widget are multiples of 100, between 300 and 5000. A too low value (<= 300) will affect / increase the application Geocode Quota usage. Try to increase this value if you are experiencing Quota usage matters.'),
        '#min' => 300,
        '#max' => 5000,
        '#step' => 100,
        '#size' => 3,
      ];

      // @TODO Set the proper default and placeholder value
      $default_options = '{"googlemaps":{"apiKey":"AIzaSyCNet9OyalhelnshwPl1uasM-S2Agtc9d4","region":"it","useSsl":"true","locale":"it"},"geonames":{"username":"demo"}}';
      $form['geocoder']['options'] = [
        '#type' => 'textarea',
        '#rows' => 5,
        '#title' => $this->t('Geocoder Options'),
        '#description' => $this->t('An object literal of additional Geocoders options.<br>The syntax should respect the javascript object notation (json) format.<br>As suggested in the field placeholder, always use double quotes (") both for the indexes and the string values.'),
        '#default_value' => !empty($config->get('geocoder.options')) ? $config->get('geocoder.options') : $default_options,
        '#placeholder' => $default_options,
        '#element_validate' => [[get_class($this), 'jsonValidate']],
      ];

      $form['geocoder']['plugins_checkboxes'] = [
        '#type' => 'table',
        '#header' => [
          t('Geocoder plugins'),
          $this->t('Weight'),
          $this->t('Options'),
        ],
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'plugins-order-weight',
          ],
        ],
        '#caption' => $this->t('Select the Geocoder plugins to use, you can reorder them. The first one to return a valid value will be used.'),
      ];

      // Get the default/selected geocoder plugins.
      $default_plugins = !empty($config->get('geocoder.plugins')) ? $config->get('geocoder.plugins') : [];
      $plugins = array_combine($default_plugins, $default_plugins);
      foreach (\Drupal::service('plugin.manager.geocoder.provider')->getPluginsAsOptions() as $plugin_id => $plugin_name) {
        // Non-default values are appended at the end.
        $plugins[$plugin_id] = $plugin_name;
      }
      foreach ($plugins as $plugin_id => $plugin_name) {
        $form['geocoder']['plugins_checkboxes'][$plugin_id] = [
          'checked' => [
            '#type' => 'checkbox',
            '#title' => $plugin_name,
            '#default_value' => in_array($plugin_id, $default_plugins),
          ],
          'weight' => [
            '#type' => 'weight',
            '#title' => $this->t('Weight for @title', ['@title' => $plugin_name]),
            '#title_display' => 'invisible',
            '#attributes' => ['class' => ['plugins-order-weight']],
          ],
          '#attributes' => ['class' => ['draggable']],
        ];

        $form['geocoder']['plugins_checkboxes'][$plugin_id]['options'] = [
          '#type' => 'details',
          '#title' => $this->t('@title Options', ['@title' => $plugin_name]),
          'json_options' => [
            '#weight' => 0,
            '#type' => 'textarea',
            '#rows' => 3,
            '#description' => $this->t('An object literal of additional Geocoder options. The syntax should respect the javascript object notation (json) format. As suggested in the field placeholder, always use double quotes (") both for the indexes and the string values.'),
            '#default_value' => !empty($config->get('geocoder.plugins' . $plugin_id)) ? $config->get('geocoder.plugins' . $plugin_id) : '',
            '#placeholder' => $this->t('Json Default Options for @title', ['@title' => $plugin_name]),
            '#element_validate' => [[get_class($this), 'jsonValidate']],
          ],
        ];

        // If Geocoder is active, hide the 'gmap_api_key' form to 'value' type.
        $form['gmap_api_key'] = [
          '#type' => 'value',
          '#value' => $config->get('gmap_api_key'),
        ];

        if ('googlemaps' == $plugin_id) {
          $form['geocoder']['plugins_checkboxes'][$plugin_id]['checked']['#states'] = [
            'unchecked' => [
              ':input[name="gmap_api_key"]' => ['value' => ''],
              ':input[name="geocoder[plugins_checkboxes][googlemaps][options][gmap_api_key]"]' => ['value' => ''],
            ],
            'checked' => [
              [':input[name="gmap_api_key"]' => ['!value' => '']],
              [':input[name="geocoder[plugins_checkboxes][googlemaps][options][gmap_api_key]"]' => ['!value' => '']],
            ],
          ];
          //$form['geocoder']['plugins_checkboxes'][$plugin_id]['checked']['#description'] = $this->t('This is the description for this option');
          $form['geocoder']['plugins_checkboxes'][$plugin_id]['options']['#open'] = TRUE;
          $form['geocoder']['plugins_checkboxes'][$plugin_id]['options']['gmap_api_key'] = [
            '#weight' => -10,
            '#type' => 'textfield',
            '#default_value' => $config->get('gmap_api_key'),
            '#title' => $this->t('Gmap Api Key (@link)', [
              '@link' => $this->link->generate(t('Get a Key/Authentication for Google Maps Javascript Library'), Url::fromUri('https://developers.google.com/maps/documentation/javascript/get-api-key', [
                'absolute' => TRUE,
                'attributes' => ['target' => 'blank'],
              ])),
            ]),
            '#description' => $this->t('Geofield Map requires a valid Google API key for his main features based on Google & Google Maps APIs.'),
            '#placeholder' => $this->t('Input a valid Gmap API Key'),
          ];
        }

      }

      // Define the form element that will store the set plugins.
      $form['geocoder']['plugins'] = [
        '#type' => 'value',
        '#value' => $default_plugins,
      ];

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'geofield_map_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'geofield_map.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Geofield Map Settings/Configurations.
    $geofield_map_settings = $this->configFactory()->getEditable('geofield_map.settings');

    // Get all the form state values, in an array structure.
    $form_state_values = $form_state->getValues();

    // Update the geofield_map 'gmap_api_key' settings.
    $geofield_map_settings->set('gmap_api_key', $form_state_values['gmap_api_key']);

    // Add specific Geofield Map settings for the Geocoder module integration.
    if ($this->moduleHandler->moduleExists('geocoder')) {
      // Reset and refill the $form_state geocoder plugins value.
      $form_state_values['geocoder']['plugins'] = [];
      foreach ($form_state_values['geocoder']['plugins_checkboxes'] as $k => $option) {
        if ($option['checked']) {
          $form_state_values['geocoder']['plugins'][] = $k;
        }
      }

      // Check the googlemaps plugin option if no other is checked and the
      // gmap_api_key is set.
      if (!empty($form_state_values['gmap_api_key']) && empty($form_state_values['geocoder']['plugins'])) {
        $form_state_values['geocoder']['plugins'] = ['googlemaps'];
      }

      // If Geocoder is active, set the 'gmap_api_key' (hidden) form value from
      // the 'googlemaps plugin gmap_api_key' value.
      $geofield_map_settings->set('gmap_api_key', $form_state_values['geocoder']['plugins_checkboxes']['googlemaps']['options']['gmap_api_key']);

      // Update the geofield_map 'geocoder' configurations.
      $geofield_map_settings->set('geocoder', $form_state_values['geocoder']);

    }

    // Save the geofield_map settings updates.
    $geofield_map_settings->save();

    // Confirmation on form submission.
    drupal_set_message($this->t('The Geofield Map configurations have been saved.'));
  }

}
