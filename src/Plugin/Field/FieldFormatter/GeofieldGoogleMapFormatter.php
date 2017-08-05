<?php

namespace Drupal\geofield_map\Plugin\Field\FieldFormatter;

use Drupal\geofield_map\GeofieldMapFieldTrait;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\geofield\GeoPHP\GeoPHPInterface;

/**
 * Plugin implementation of the 'geofield_google_map' formatter.
 *
 * @FieldFormatter(
 *   id = "geofield_google_map",
 *   label = @Translation("Geofield Google Map"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class GeofieldGoogleMapFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use GeofieldMapFieldTrait;

  /**
   * Empty Map Options.
   *
   * @var array
   */
  protected $emptyMapOptions = [
    '0' => 'Empty field',
    '1' => 'Custom Message',
    '2' => 'Empty Map Centered at the Default Center',
  ];

  /**
   * Google Map Types Options.
   *
   * @var array
   */
  protected $gMapTypesOptions = [
    'roadmap' => 'Roadmap',
    'satellite' => 'Satellite',
    'hybrid' => 'Hybrid',
    'terrain' => 'Terrain',
  ];

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The EntityField Manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The GeoPHPWrapper service.
   *
   * @var \Drupal\geofield\GeoPHP\GeoPHPInterface
   */
  protected $GeoPHPWrapper;

  /**
   * GeofieldGoogleMapFormatter constructor.
   *
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The Translation service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The Entity Field Manager.
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface $geophp_wrapper
   *   The The GeoPHPWrapper.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    ConfigFactoryInterface $config_factory,
    TranslationInterface $string_translation,
    LinkGeneratorInterface $link_generator,
    EntityFieldManagerInterface $entity_field_manager,
    GeoPHPInterface $geophp_wrapper
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->config = $config_factory;
    $this->link = $link_generator;
    $this->entityFieldManager = $entity_field_manager;
    $this->GeoPHPWrapper = $geophp_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('string_translation'),
      $container->get('link_generator'),
      $container->get('entity_field.manager'),
      $container->get('geofield.geophp')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'gmap_api_key' => '',
      'map_dimensions' => [
        'width' => '100%',
        'height' => '450px',
      ],
      'map_empty' => [
        'empty_behaviour' => '0',
        'empty_message' => t('No Geofield Value entered for this field'),
      ],
      'map_center' => [
        'lat' => '42',
        'lon' => '12.5',
        'center_force' => 0,
      ],
      'map_zoom_and_pan' => [
        'zoom' => [
          'initial' => 6,
          'force' => 0,
          'min' => 1,
          'max' => 22,
        ],
        'scrollwheel' => 1,
        'draggable' => 1,
      ],
      'map_controls' => [
        'disable_default_ui' => 0,
        'zoom_control' => 1,
        'map_type_id' => 'roadmap',
        'map_type_control' => 1,
        'map_type_control_options_type_ids' => [
          'roadmap' => 'roadmap',
          'satellite' => 'satellite',
          'hybrid' => 'hybrid',
          'terrain' => 'terrain',
        ],
        'scale_control' => 1,
        'street_view_control' => 1,
        'fullscreen_control' => 1,
      ],
      'map_marker_and_infowindow' => [
        'icon_image_path' => '',
        'infowindow_field' => 'title',
      ],
      'map_markercluster' => [
        'markercluster_control' => 0,
        'markercluster_additional_options' => '',
      ],
      'map_additional_options' => '',

      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $default_settings = self::defaultSettings();
    $settings = $this->getSettings();

    $elements = [];

    // Attach Geofield Map Library.
    $elements['#attached']['library'] = [
      'geofield_map/geofield_map_general',
    ];

    $gmap_api_key = $this->getGmapApiKey();

    // If it is defined GMap API Key in the general configuration,
    // force to use it, instead.
    if (!empty($gmap_api_key)) {
      $elements['map_google_api_key'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('<strong>Gmap Api Key:</strong> @gmaps_api_key_link', [
          '@gmaps_api_key_link' => $this->link->generate($gmap_api_key, Url::fromRoute('geofield_map.settings', [], [
            'query' => [
              'destination' => Url::fromRoute('<current>')
                ->toString(),
            ],
          ])),
        ]),
      ];
    }
    else {
      $elements['map_google_api_key_missing'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => t("Gmap Api Key missing | The Geocode Address and ReverseGeocode functionalities won't be available.<br>@settings_page_link", [
          '@settings_page_link' => $this->link->generate(t('Set it in the Geofield Map Configuration Page'), Url::fromRoute('geofield_map.settings', [], [
            'query' => [
              'destination' => Url::fromRoute('<current>')
                ->toString(),
            ],
          ])),
        ]),
        '#attributes' => [
          'class' => ['geofield-map-apikey-missing'],
        ],
      ];
    }

    $elements['map_dimensions'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Map Dimensions'),
    );
    $elements['map_dimensions']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map width'),
      '#default_value' => $settings['map_dimensions']['width'],
      '#size' => 25,
      '#maxlength' => 25,
      '#description' => $this->t('The default width of a Google map, as a CSS length or percentage. Examples: <em>50px</em>, <em>5em</em>, <em>2.5in</em>, <em>95%</em>'),
      '#required' => TRUE,
    ];
    $elements['map_dimensions']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map height'),
      '#default_value' => $settings['map_dimensions']['height'],
      '#size' => 25,
      '#maxlength' => 25,
      '#description' => $this->t('The default height of a Google map, as a CSS length or percentage. Examples: <em>50px</em>, <em>5em</em>, <em>2.5in</em>, <em>95%</em>'),
      '#required' => TRUE,
    ];

    $elements['gmaps_api_link_markup'] = [
      '#markup' => $this->t('The following settings comply with the @gmaps_api_link.', [
        '@gmaps_api_link' => $this->link->generate(t('Google Maps JavaScript API Library'), Url::fromUri('https://developers.google.com/maps/documentation/javascript', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
    ];

    $elements['map_empty'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Which behaviour for the empty map?'),
      '#description' => $this->t('If there are no entries on the map, what should be the output of field?'),
    );
    $elements['map_empty']['empty_behaviour'] = [
      '#type' => 'select',
      '#title' => $this->t('Behaviour'),
      '#default_value' => $settings['map_empty']['empty_behaviour'],
      '#options' => $this->emptyMapOptions,
    ];
    $elements['map_empty']['empty_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Empty Map Message'),
      '#description' => $this->t('The message that should be rendered instead on an empty map.'),
      '#default_value' => $settings['map_empty']['empty_message'],
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_empty][empty_behaviour]"]' => ['value' => '1'],
        ],
      ],
    ];

    $elements['map_center'] = [
      '#type' => 'geofield_latlon',
      '#title' => $this->t('Default Center'),
      '#default_value' => $settings['map_center'],
      '#size' => 25,
      '#description' => $this->t('If there are no entries on the map, where should the map be centered?'),
      '#geolocation' => TRUE,
      'center_force' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Force the Map Center'),
        '#description' => $this->t('The Map will generally focus center on the input Geofields.<br>This option will instead force the Map Center notwithstanding the Geofield Values'),
        '#default_value' => $settings['map_center']['center_force'],
        '#return_value' => 1,
      ],
    ];

    $elements['map_zoom_and_pan'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Map Zoom and Pan'),
    );
    $elements['map_zoom_and_pan']['zoom'] = [
      'initial' => [
        '#type' => 'number',
        '#min' => $settings['map_zoom_and_pan']['zoom']['min'],
        '#max' => $settings['map_zoom_and_pan']['zoom']['max'],
        '#title' => $this->t('Start Zoom'),
        '#default_value' => $settings['map_zoom_and_pan']['zoom']['initial'],
        '#description' => $this->t('The Initial Zoom level of the Google Map.'),
        '#element_validate' => [[get_class($this), 'zoomLevelValidate']],
      ],
      'force' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Force the Start Zoom'),
        '#description' => $this->t('The Map will generally focus zoom on the input Geofields bounds.<br>This option will instead force the Map Zoom notwithstanding the Geofield Values'),
        '#default_value' => $settings['map_zoom_and_pan']['zoom']['force'],
        '#return_value' => 1,
      ],
      'min' => [
        '#type' => 'number',
        '#min' => $default_settings['map_zoom_and_pan']['zoom']['min'],
        '#max' => $settings['map_zoom_and_pan']['zoom']['max'],
        '#title' => $this->t('Min Zoom Level'),
        '#default_value' => $settings['map_zoom_and_pan']['zoom']['min'],
        '#description' => $this->t('The Minimum Zoom level for the Map.'),
      ],
      'max' => [
        '#type' => 'number',
        '#min' => $settings['map_zoom_and_pan']['zoom']['min'],
        '#max' => $default_settings['map_zoom_and_pan']['zoom']['max'],
        '#title' => $this->t('Max Zoom Level'),
        '#default_value' => $settings['map_zoom_and_pan']['zoom']['max'],
        '#description' => $this->t('The Maximum Zoom level for the Map.'),
        '#element_validate' => [[get_class($this), 'maxZoomLevelValidate']],
      ],
    ];
    $elements['map_zoom_and_pan']['scrollwheel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Scrollwheel'),
      '#description' => $this->t('Enable scrollwheel zooming'),
      '#default_value' => $settings['map_zoom_and_pan']['scrollwheel'],
      '#return_value' => 1,
    ];
    $elements['map_zoom_and_pan']['draggable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Draggable'),
      '#description' => $this->t('Enable dragging/panning on the map'),
      '#default_value' => $settings['map_zoom_and_pan']['draggable'],
      '#return_value' => 1,
    ];

    $elements['map_controls'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Map Controls'),
    );
    $elements['map_controls']['disable_default_ui'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Default UI'),
      '#description' => $this->t('This property disables any automatic UI behavior from the Google Maps JavaScript API'),
      '#default_value' => $settings['map_controls']['disable_default_ui'],
      '#return_value' => 1,
    ];
    $elements['map_controls']['zoom_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Zoom Control'),
      '#description' => $this->t('The enabled/disabled state of the Zoom control.'),
      '#default_value' => $settings['map_controls']['zoom_control'],
      '#return_value' => 1,
    ];
    $elements['map_controls']['map_type_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Map Type'),
      '#default_value' => $settings['map_controls']['map_type_id'],
      '#options' => $this->gMapTypesOptions,
    ];
    $elements['map_controls']['map_type_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled Map Type Control'),
      '#description' => $this->t('The initial enabled/disabled state of the Map type control.'),
      '#default_value' => $settings['map_controls']['map_type_control'],
      '#return_value' => 1,
    ];
    $elements['map_controls']['map_type_control_options_type_ids'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('The enabled Map Types'),
      '#description' => $this->t('The Map Types that will be available in the Map Type Control.'),
      '#default_value' => $settings['map_controls']['map_type_control_options_type_ids'],
      '#options' => $this->gMapTypesOptions,
      '#return_value' => 1,
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_controls][map_type_control]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $elements['map_controls']['scale_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Scale Control'),
      '#description' => $this->t('Show map scale'),
      '#default_value' => $settings['map_controls']['scale_control'],
      '#return_value' => 1,
    ];
    $elements['map_controls']['street_view_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Streetview Control'),
      '#description' => $this->t('Enable the Street View functionality on the Map.'),
      '#default_value' => $settings['map_controls']['street_view_control'],
      '#return_value' => 1,
    ];
    $elements['map_controls']['fullscreen_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fullscreen Control'),
      '#description' => $this->t('Enable the Fullscreen View of the Map.'),
      '#default_value' => $settings['map_controls']['fullscreen_control'],
      '#return_value' => 1,
    ];

    $fields_list = array_merge_recursive(
      $this->entityFieldManager->getFieldMapByFieldType('string_long'),
      $this->entityFieldManager->getFieldMapByFieldType('string')
    );

    $string_fields_options = [
      '0' => $this->t('- Any - No Infowindow'),
      'title' => $this->t('- Title -'),
    ];

    foreach ($fields_list[$form['#entity_type']] as $k => $field) {
      if (in_array(
          $form['#bundle'], $field['bundles']) &&
        !in_array($k, ['title', 'revision_log'])) {
        $string_fields_options[$k] = $k;
      }
    }

    $elements['map_marker_and_infowindow'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Map Marker and Infowindow'),
    );
    $elements['map_marker_and_infowindow']['icon_image_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Icon Image'),
      '#size' => '120',
      '#description' => $this->t('Input the Specific Icon Image path (absolute or relative to the Drupal site root). If not set, the Default Google Marker will be used.'),
      '#default_value' => $settings['map_marker_and_infowindow']['icon_image_path'],
      '#placeholder' => 'https://developers.google.com/maps/documentation/javascript/examples/full/images/beachflag.png',
      '#element_validate' => [[get_class($this), 'urlValidate']],
    );
    $elements['map_marker_and_infowindow']['infowindow_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Marker Infowindow Content from'),
      '#description' => $this->t('Choose an existing string type field from which populate the Marker Infowindow'),
      '#options' => $string_fields_options,
      '#default_value' => $settings['map_marker_and_infowindow']['infowindow_field'],
    );

    $elements['map_additional_options'] = [
      '#type' => 'textarea',
      '#rows' => 5,
      '#title' => $this->t('Map Additional Options'),
      '#description' => $this->t('<strong>These will override the above settings</strong><br>An object literal of additional map options, that comply with the Google Maps JavaScript API. The syntax should respect the javascript object notation (json) format.<br>As suggested in the field placeholder, always use double quotes (") both for the indexes and the string values.<br>It is even possible to input Map Control Positions. For this use the numeric values of the google.maps.ControlPosition, otherwise the option will be passed as incomprehensible string to Google Maps API.'),
      '#default_value' => $settings['map_additional_options'],
      '#placeholder' => $this->t('{"disableDoubleClickZoom": "cooperative",
"gestureHandling": "none",
"streetViewControlOptions": {"position": 5}
}'),
      '#element_validate' => [[get_class($this), 'jsonValidate']],
    ];

    $elements['map_markercluster'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Marker Clustering'),
    );
    $elements['map_markercluster']['markup'] = [
      '#markup' => $this->t('Enable the functionality of the @markeclusterer_api_link.', [
        '@markeclusterer_api_link' => $this->link->generate(t('Marker Clusterer Google Maps JavaScript Library'), Url::fromUri('https://github.com/googlemaps/js-marker-clusterer', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
    ];
    $elements['map_markercluster']['markercluster_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Marker Clustering'),
      '#default_value' => $settings['map_markercluster']['markercluster_control'],
      '#return_value' => 1,
    ];
    $elements['map_markercluster']['markercluster_additional_options'] = [
      '#type' => 'textarea',
      '#rows' => 5,
      '#title' => $this->t('Marker Cluster Additional Options'),
      '#description' => $this->t('An object literal of additional marker cluster options, that comply with the Marker Clusterer Google Maps JavaScript Library. The syntax should respect the javascript object notation (json) format.<br>As suggested in the field placeholder, always use double quotes (") both for the indexes and the string values.'),
      '#default_value' => $settings['map_markercluster']['markercluster_additional_options'],
      '#placeholder' => $this->t('{"maxZoom": 12, "gridSize": 25, "imagePath": "modules/custom/geofield_map/images/m"}'),
      '#element_validate' => [[get_class($this), 'jsonValidate']],
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_markercluster][markercluster_control]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $elements + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $settings = $this->getSettings();
    $gmap_api_key = $this->getGmapApiKey();

    $map_gmap_api_key = [
      '#markup' => $this->t('Google Maps API Key: @state', [
        '@state' => !empty($gmap_api_key) ? $this->link->generate($gmap_api_key, Url::fromRoute('geofield_map.settings', [], [
          'query' => [
            'destination' => Url::fromRoute('<current>')
              ->toString(),
          ],
        ])) : t("<span class='geofield-map-apikey-missing'>Gmap Api Key missing (Geocode functionalities not available).</span> @settings_page_link", [
          '@settings_page_link' => $this->link->generate(t('Set it in the Geofield Map Configuration Page'), Url::fromRoute('geofield_map.settings', [], [
            'query' => [
              'destination' => Url::fromRoute('<current>')
                ->toString(),
            ],
          ])),
        ]),
      ]),
    ];
    $map_dimensions = [
      '#markup' => $this->t('Map Dimensions: Width: @width - Height: @height', ['@width' => $settings['map_dimensions']['width'], '@height' => $settings['map_dimensions']['height']]),
    ];
    $map_empty = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('Behaviour for the Empty Map: @state', ['@state' => $this->emptyMapOptions[$settings['map_empty']['empty_behaviour']]]),
    ];

    if ($settings['map_empty']['empty_behaviour'] === '1') {
      $map_empty['message'] = [
        '#markup' => $this->t('Empty Field Message: Width: @state', ['@state' => $settings['map_empty']['empty_message']]),
      ];
    }

    $map_center = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('Map Default Center: @state_lat, @state_lon', [
        '@state_lat' => $settings['map_center']['lat'],
        '@state_lon' => $settings['map_center']['lon'],
      ]),
      'center_force' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Force Map Center: @state', ['@state' => $settings['map_center']['center_force'] ? $this->t('Yes') : $this->t('No')]),
      ],
    ];
    $map_zoom_and_pan = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '<u>' . $this->t('Map Zoom and Pan:') . '</u>',
      'zoom' => [
        'initial' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t('Start Zoom: @state', ['@state' => $settings['map_zoom_and_pan']['zoom']['initial']]),
        ],
        'force' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t('Force Start Zoom: @state', ['@state' => $settings['map_zoom_and_pan']['zoom']['force'] ? $this->t('Yes') : $this->t('No')]),
        ],
        'min' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t('Min Zoom: @state', ['@state' => $settings['map_zoom_and_pan']['zoom']['min']]),
        ],
        'max' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t('Max Zoom: @state', ['@state' => $settings['map_zoom_and_pan']['zoom']['max']]),
        ],
      ],
      'scrollwheel' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Scrollwheel: @state', ['@state' => $settings['map_zoom_and_pan']['scrollwheel'] ? $this->t('Yes') : $this->t('No')]),
      ],
      'draggable' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Draggable: @state', ['@state' => $settings['map_zoom_and_pan']['draggable'] ? $this->t('Yes') : $this->t('No')]),
      ],
    ];

    // Remove the unselected array keys
    // from the map_type_control_options_type_ids.
    $map_type_control_options_type_ids = array_filter($settings['map_controls']['map_type_control_options_type_ids'], function ($value) {
      return $value !== 0;
    });

    $map_controls = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '<u>' . $this->t('Map Controls:') . '</u>',
      'disable_default_ui' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Disable Default UI: @state', ['@state' => $settings['map_controls']['disable_default_ui'] ? $this->t('Yes') : $this->t('No')]),
      ],
      'zoom_control' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Zoom Control: @state', ['@state' => $settings['map_controls']['zoom_control'] ? $this->t('Yes') : $this->t('No')]),
      ],
      'map_type_id' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Default Map Type: @state', ['@state' => $settings['map_controls']['map_type_id']]),
      ],
      'map_type_control' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Map Type Control: @state', ['@state' => $settings['map_controls']['map_type_control'] ? $this->t('Yes') : $this->t('No')]),
      ],
      'map_type_control_options_type_ids' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $settings['map_controls']['map_type_control'] ? $this->t('Enabled Map Types: @state', ['@state' => implode(', ', array_keys($map_type_control_options_type_ids))]) : '',
      ],
      'scale_control' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Scale Control: @state', ['@state' => $settings['map_controls']['scale_control'] ? $this->t('Yes') : $this->t('No')]),
      ],
      'street_view_control' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Streetview Control: @state', ['@state' => $settings['map_controls']['street_view_control'] ? $this->t('Yes') : $this->t('No')]),
      ],
      'fullscreen_control' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Fullscreen Control: @state', ['@state' => $settings['map_controls']['fullscreen_control'] ? $this->t('Yes') : $this->t('No')]),
      ],
    ];

    $map_marker_and_infowindow = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '<u>' . $this->t('Map Marker and Infowindow:') . '</u>',
      'icon_image_path' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Marker Icon: @state', ['@state' => !empty($settings['map_marker_and_infowindow']['icon_image_path']) ? $settings['map_marker_and_infowindow']['icon_image_path'] : $this->t('Default Google Marker')]),
      ],
      'infowindow_field' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Marker Infowindow @state', ['@state' => !empty($settings['map_marker_and_infowindow']['infowindow_field']) ? 'from: ' . $settings['map_marker_and_infowindow']['infowindow_field'] : $this->t('disabled')]),
      ],
    ];

    if (!empty($settings['map_additional_options'])) {
      $map_additional_options = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => '<u>' . $this->t('Map Additional Options:') . '</u>',
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $settings['map_additional_options'],
        ],
      ];
    }

    $map_markercluster = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '<u>' . $this->t('Marker Clustering:') . '</u>',
      'markercluster_control' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Marker Cluster Enabled: @state', ['@state' => $settings['map_markercluster']['markercluster_control'] ? $this->t('Yes') : $this->t('No')]),
      ],
    ];

    if (!empty($settings['map_markercluster']['markercluster_additional_options'])) {
      $map_markercluster['markercluster_additional_options'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Marker Cluster Additional Options:'),
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $settings['map_markercluster']['markercluster_additional_options'],
        ],
      ];
    }

    $summary = [
      'map_gmap_api_key' => $map_gmap_api_key,
      'map_dimensions' => $map_dimensions,
      'map_empty' => $map_empty,
      'map_center' => $map_center,
      'map_zoom_and_pan' => $map_zoom_and_pan,
      'map_controls' => $map_controls,
      'map_marker_and_infowindow' => $map_marker_and_infowindow,
      'map_additional_options' => isset($map_additional_options) ? $map_additional_options : NULL,
      'map_markercluster' => $map_markercluster,
    ];

    // Attach Geofield Map Library.
    $summary['library'] = [
      '#attached' => [
        'library' => [
          'geofield_map/geofield_map_general',
        ],
      ],
    ];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $items->getEntity();
    $entity_type = $entity->bundle();
    $entity_id = $entity->id();
    /* @var \Drupal\Core\Field\FieldDefinitionInterface $field */
    $field = $items->getFieldDefinition();

    $map_settings = $this->getSettings();

    // Set the gmap_api_key as map settings.
    $map_settings['gmap_api_key'] = $this->getGmapApiKey();

    // Transform into simple array values the map_type_control_options_type_ids.
    $map_settings['map_controls']['map_type_control_options_type_ids'] = array_keys(array_filter($map_settings['map_controls']['map_type_control_options_type_ids'], function ($value) {
      return $value !== 0;
    }));

    // Generate Absolute icon_image_path, if it is not.
    $icon_image_path = $map_settings['map_marker_and_infowindow']['icon_image_path'];
    if (!empty($icon_image_path) && !UrlHelper::isExternal($map_settings['map_marker_and_infowindow']['icon_image_path'])) {
      $map_settings['map_marker_and_infowindow']['icon_image_path'] = Url::fromUri('base:' . $icon_image_path, ['absolute' => TRUE])
        ->toString();
    }

    $js_settings = [
      'mapid' => Html::getUniqueId("geofield_map_entity_{$entity_type}_{$entity_id}_{$field->getName()}"),
      'map_settings' => $map_settings,
      'data' => [],
    ];

    $data = [];

    if (!empty($map_settings['map_marker_and_infowindow']['infowindow_field'])) {
      $description = $map_settings['map_marker_and_infowindow']['infowindow_field'] != 'title' ? $entity->$map_settings['map_marker_and_infowindow']['infowindow_field']->value : $entity->label();
    }
    foreach ($items as $delta => $item) {

      /* @var \Point $geometry */
      $geometry = $this->GeoPHPWrapper->load($item->value);
      if (!empty($geometry)) {
        $datum = [
          "type" => "Feature",
          "geometry" => json_decode($geometry->out('json')),
        ];
        $datum['properties'] = [
          'description' => isset($description) ? $description : NULL,
        ];
        $data[] = $datum;
      }
    }

    if (empty($data) && $map_settings['map_empty']['empty_behaviour'] !== '2') {
      return [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $map_settings['map_empty']['empty_behaviour'] === '1' ? $map_settings['map_empty']['empty_message'] : '',
        '#attributes' => [
          'class' => ['empty-geofield'],
        ],
      ];
    }
    else {
      $js_settings['data'] = [
        'type' => 'FeatureCollection',
        'features' => $data,
      ];
    }
    $element = geofield_map_googlemap_render($js_settings);
    return $element;
  }

}
