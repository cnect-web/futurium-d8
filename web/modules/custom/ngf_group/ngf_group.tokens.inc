<?php

/**
 * @file
 * Builds placeholder replacement tokens for group-related data.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\group\Entity\GroupContent;

/**
 * Implements hook_token_info().
 */
function ngf_group_token_info() {
  $types['group'] = [
    'name' => t('Group'),
    'description' => t('Tokens related to individual groups.'),
    'needs-data' => 'group',
  ];

  $tokens['group']['alias'] = [
    'name' => t('Alias'),
    'description' => t('The internal alias of the group.'),
  ];

  $tokens['group']['ngf_path'] = [
    'name' => t('Custom path'),
    'description' => t('Custom path builder.'),
  ];

  return ['types' => $types, 'tokens' => $tokens];
}

/**
 * Implements hook_tokens().
 */
function ngf_group_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  $url_options = ['absolute' => FALSE];
  $token_service = \Drupal::token();

  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }

  if ($type == 'group' && !empty($data[$type])) {
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $data['group'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'alias':
          $url = $group->toUrl('canonical', $url_options);
          $replacements[$original] = $url->toString();
          break;

        case 'ngf_path':
          $groups = GroupContent::loadByEntity($group);
          if (empty($groups)) {
            $replacements[$original] = 'group';
          }
          else {
            $parent_group = array_pop($groups)->getGroup();
            $url = $parent_group->toUrl('canonical', $url_options);
            $replacements[$original] = $url->toString();
            // If the group is a discussion group, then this is a subgroup.
            if ($group->getGroupType()->id() == 'ngf_discussion_group') {
              $replacements[$original] .= '/subgroups';
            }
          }
          break;
      }
    }
  }

  return $replacements;
}
