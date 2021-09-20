<?php
namespace Drupal\content_filter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Builds the Content Filter form.
 */
class ContentFilterForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_filter_form';
  }
  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['content_filter.settings'];
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = 0) {
    $this->uid = $user;
    $vocabularies = Vocabulary::loadMultiple();
    $config = \Drupal::config('content_filter.settings');
    $vids[] = $config->get('content_filter_categories');
    $display = $config->get('content_filter_display');
    if (count($vids)) {
      $form['content_filter']['content_filter_scheme_1'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Content Filter'),
        '#description' => $this->t('Filter content by preferences.'),
        '#open' => TRUE,
        '#tree' => TRUE,
      ];
      // Create a form element for each vocabulary.
      foreach ($vids as $vid) {
        if (!empty($vocabularies[$vid])) {
          $v = $vocabularies[$vid];
          $default_values = [];
          $data = \Drupal::service('user.data')->get('content_filter', $user, 'content_filter_scheme_1') ?: [];
          if (!empty($data[$vid])) {
            $default_values = $data[$vid];
          }
          if ($display == 'checkboxes') {
            $form['content_filter']['content_filter_scheme_1'][$vid] = $this->termDisplayCheckboxes($v, $default_values);
          }
          else {
            $form['content_filter']['content_filter_scheme_1'][$vid] = $this->termDisplaySelect($v, $default_values);
          }
        }

      }
    }
    return parent::buildForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $this->uid;
    \Drupal::service('user.data')->set('content_filter', $uid, 'content_filter_scheme_1', $form_state->getValue('content_filter_scheme_1'));
  }

  /**
   * Helper function to build a taxonomy term select element for a form.
   *
   * @param object $v
   *   A vocabulary object containing a vid and name.
   * @param array $default_values
   *   An array of values to use for the default_value argument for this
   *   form element.
   */
  public static function termDisplaySelect($v, $default_values = []) {
    $tree = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadTree($v->get('vid'));
    $options = [0 => '<none>'];
    if ($tree) {
      foreach ($tree as $term) {
        $choice = new \stdClass();
        $choice->option = [$term->tid => str_repeat('-', $term->depth) . $term->name];
        $options[] = $choice;
      }
    }
    $field_array = [
      '#type' => 'select',
      '#title' => $v->get('name'),
      '#default_value' => $default_values,
      '#options' => $options,
      '#multiple' => TRUE,
      '#description' => $v->get('description'),
    ];
    return $field_array;
  }

  /**
   * Helper function to build a taxonomy term checkbox element for a form.
   *
   * @param object $v
   *   A vocabulary object containing a vid and name.
   * @param array $default_values
   *   An array of values to use for the default_value argument for this
   *   form element.
   */
  public static function termDisplayCheckboxes($v, $default_values = []) {
    $tree = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadTree($v->get('vid'));
    $options = [];
    if ($tree) {
      foreach ($tree as $term) {
        $options[$term->tid] = str_repeat('-', $term->depth) . $term->name;
      }
    }
    $field_array = [
      '#type' => 'checkboxes',
      '#title' => $v->get('name'),
      '#default_value' => $default_values,
      '#options' => $options,
      '#description' => $v->get('description'),
    ];
    return $field_array;
  }

}
