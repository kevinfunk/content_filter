<?php

namespace Drupal\content_filter\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Builds the configuration form.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['content_filter.settings'];
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_filter_admin_settings';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $vocabularies = Vocabulary::loadMultiple();
    if (!count($vocabularies)) {
      $form['body'] = [
        '#markup' => $this->t('You must <a href=":url">create a vocabulary</a> before you can use
          content_filter.', [':url' => Url::fromRoute('entity.taxonomy_vocabulary.add_form')->toString()]),
      ];
      return $form;
    }
    else {
      $settings = $this->configFactory->get('content_filter.settings');
      $options = [];
      foreach ($vocabularies as $vocab) {
        $options[$vocab->get('vid')] = $vocab->get('name');
      }
      $form['content_filter_categories'] = [
        '#type' => 'select',
        '#title' => $this->t('Vocabulary'),
        '#default_value' => $settings->get('content_filter_categories'),
        '#options' => $options,
        '#description' => $this->t('Select a vocabulary to filter content.<br/>Use caution with hierarchical (nested) taxonomies as <em>visibility</em> settings may cause problems on node edit forms.<br/>Do not select free tagging vocabularies, they are not supported.'),
        '#required' => TRUE,
      ];
      $form['content_filter_display'] = [
        '#type' => 'select',
        '#title' => $this->t('Display settings'),
        '#options' => [
          'checkboxes' => $this->t('Checkboxes'),
          'select' => $this->t('Select'),
        ],
        '#default_value' => $settings->get('content_filter_display'),
        '#description' => $this->t('Display settings for selecting taxonomies.'),
      ];
      $form['content_filter_rebuild'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Rebuild content permissions now'),
        // Default false because usually only needed after scheme
        // has been changed.
        '#default_value' => FALSE,
        '#description' => $this->t('Do this once, after you have fully configured access by taxonomy.'),
      ];
    }
    return parent::buildForm($form, $form_state);
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Change configuration.
    $this->config('content_filter.settings')
      ->set('content_filter_categories', $form_state->getValue('content_filter_categories'))
      ->set('content_filter_display', $form_state->getValue('content_filter_display'))
      ->save();

    // Rebuild the node_access table.
    $rebuild = $form_state->getValue('content_filter_rebuild');
    if ($rebuild) {
      node_access_rebuild(TRUE);
    }
    else {
      $this->messenger()->addWarning($this->t('Do not forget to <a href=:url>rebuild node access permissions </a> after you have configured taxonomy-based access.', [
        ':url' => Url::fromRoute('node.configure_rebuild_confirm')->toString(),
      ]));
    }
    // And rebuild menus, in case the number of schemes has changed.
    \Drupal::service('router.builder')->rebuild();
    parent::submitForm($form, $form_state);
  }

}
