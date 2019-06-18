<?php

namespace Drupal\webtools_paragraphs;

trait WebtoolsCodeHelperTrait  {

  public function isValidCode($value) {


    $json = self::extractJson($value);

    if (!empty($json)) {
      try {
        $json_decode = json_decode($json);
        if (!isset($json_decode->service)) {
          return FALSE;
        }
        return TRUE;
      } catch (\Throwable $th) {
        return FALSE;
      }
    }
    return FALSE;

  }

  public function extractJson($value = null) {
    $re = '/<script type="application\/json\b[^>]*>([\s\S]*?)<\/script>/m';
    preg_match($re,$value, $matches);

    if (isset($matches[1])) {
      return $matches[1];
    }

    return FALSE;
  }

}