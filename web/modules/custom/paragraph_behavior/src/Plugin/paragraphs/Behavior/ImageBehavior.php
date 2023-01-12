<?php

namespace Drupal\paragraph_behavior\Plugin\paragraphs\Behavior;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Class provides image behavior for "Photo with text" paragraph type.
 *
 * Setting can be enabled on paragraph type configuration page.
 *
 * @ParagraphsBehavior(
 *  id = "paragraphs_behavior",
 *  label = @Translation("Image settings"),
 *  description = @Translation("Settings for photo with text paragraph type"),
 *  weight = 0,
 * )
 */
class ImageBehavior extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return $paragraphs_type->id() == "photo_with_text";
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraphs_entity, EntityViewDisplayInterface $display, $view_mode): void {
    $image_position = $paragraphs_entity->getBehaviorSetting($this->getPluginId(), 'image_position', 'left');
    $class = 'paragraph-' . $paragraphs_entity->bundle() . ($view_mode == 'default' ? '' : '-$view_mode') . '--image-position-' . $image_position;
    $build['#attributes']['class'][] = Html::getClass($class);
    $build['#attributes']['class'][] = Html::getClass('clearfix');
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form['image_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Image position relative to text'),
      '#options' => [
        'left' => $this->t('left'),
        'right' => $this->t('right'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'image_position', 'left'),
    ];
  }

}
