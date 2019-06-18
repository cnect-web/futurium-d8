<?php

namespace Drupal\webtools_paragraphs;

/**
 * Provides a trait to help extract / validate Webtools code.
 */
trait WebtoolsCodeHelperTrait {

  /**
   * Check if value is valid code and if there is a service property.
   *
   * @param string $value
   *   Field Value.
   *
   * @return bool
   *   TRUE if valid code.
   */
  public function isValidCode($value) {

    $json = self::extractJson($value);

    if (!empty($json)) {
      try {
        $json_decode = json_decode($json);
        if (!isset($json_decode->service)) {
          return FALSE;
        }
        return TRUE;
      }
      catch (\Throwable $th) {
        return FALSE;
      }
    }
    return FALSE;

  }

  /**
   * Extract json from value.
   *
   * @param string $value
   *   Field Value.
   *
   * @return mixed
   *   Json object or false.
   */
  public function extractJson($value = NULL) {
    $re = '/<script type="application\/json\b[^>]*>([\s\S]*?)<\/script>/m';
    preg_match($re, $value, $matches);

    if (isset($matches[1])) {
      return $matches[1];
    }

    return FALSE;
  }

}
