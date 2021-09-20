<?php

namespace Drupal\content_filter\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Content Filter' block.
 *
 * @Block(
 *  id = "content_filter",
 *  admin_label = @Translation("Content Filter"),
 *  category = @Translation("Content Filter")
 * )
 */
class ContentFilterBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_user = \Drupal::currentUser();
    $user = $current_user->id();
    $form = \Drupal::formBuilder()->getForm('Drupal\content_filter\Form\ContentFilterForm', $user);
    return $form;
  }

}
