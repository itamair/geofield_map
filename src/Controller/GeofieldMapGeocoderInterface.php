<?php

namespace Drupal\geofield_map\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface GeofieldMapGeocoderInterface.
 */
interface GeofieldMapGeocoderInterface {

  /**
   * Geocode.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return Json Response.
   */
  public function geocode(Request $request);

  /**
   * Reverse Geocode.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return Json Response.
   */
  public function reverseGeocode(Request $request);

}
