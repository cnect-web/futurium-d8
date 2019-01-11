<?php

namespace Drupal\ngf_user_registration\Controller;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class UserRegistrationController.
 */
class UserRegistrationController extends ControllerBase {

  /**
   * Profile.
   *
   * @return string
   *   Return Hello string.
   */
  public function citiesAutocomplete(Request $request, $country_id = null) {
    $results = [];
    if ($input = $request->query->get('q')) {
      if (strlen($input) > 1) {

        $query = \Drupal::entityQuery('taxonomy_term');
        $query->condition('vid', 'ngf_cities');
        $query->condition('name', "%$input%", 'LIKE');

        if ($country_id) {
          $query->condition('field_ngf_country', $country_id);
        }

        $terms = Term::LoadMultiple($query->execute());

        foreach ($terms as $term) {
          $name = Tags::encode($term->getName());
          $results[] = [
            'value' => "$name ({$term->id()})",
            'label' => $term->getName(),
          ];
        }
      }
    }
    return new JsonResponse($results);
  }

  /**
   * User register redirect.
   *
   * @return string
   *   Return Another route
   */
  public function redirectJoin() {
    return $this->redirect('ngf_user_registration');
  }

}
