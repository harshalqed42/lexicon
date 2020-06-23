<?php

namespace Drupal\lexicon\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\Unicode;

/**
 * @Filter(
 *   id = "filter_lexicon",
 *   title = @Translation("Lexicon Filter"),
 *   description = @Translation("Mark Lexicon terms"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class LexiconFilter extends FilterBase {

  /**
   *
   */
  public function process($text, $langcode) {

    $user = \Drupal::currentUser();
    $config = \Drupal::config('lexicon.settings');
    // If the Lexicon is setup so that users can indicate if they want terms to be
    // marked and the current user has indicated that they do not want terms to be
    // marked then return the text as it is.
    if ($config->get('lexicon_disable_indicator', FALSE)) {
      if (isset($user->data['lexicon_disable_indicator'])) {
        if ($user->data['lexicon_disable_indicator'] == 1) {
            return new FilterProcessResult($text);
        }
      }
    }
    if (!empty($config->get('lexicon_allowed_content_types'))) {
      $parameters = \Drupal::request()->attributes->all();
      if (isset($parameters['node'])) {
        $node_type = $parameters['node']->getType();
        if (!in_array($node_type, $config->get('lexicon_allowed_content_types'))) {
          return new FilterProcessResult($text);
        }
      }
    }


    $current_term = 0;
    // If the current page is a taxonomy term page then set $current_term.
    // if (strcmp(arg(0), 'taxonomy') == 0 && strcmp(arg(1), 'term') == 0 && arg(2) > 0) {
    //   $current_term = arg(2);
    // }.
    // If marking of terms is enabled then mark terms and synonyms.
    if ($config->get('lexicon_mark_terms', 0) == 1) {
      $text = ' ' . $text . ' ';
      $replace_mode = $config->get('lexicon_replace', 'superscript');
      $link_style = $config->get('lexicon_link', 'normal');
      $absolute_link = ($link_style == 'absolute');
      $vids = $config->get('lexicon_vids', []);
      $terms = _lexicon_get_terms($vids);
      $terms_replace = [];
      $tip_list = [];

      if (is_array($terms)) {
        foreach ($terms as $term) {
          // If the term is equal to $current_term than skip marking that term.
          if ($current_term == $term->id()) {
              continue;
          }
          $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
          $current_language_term = $term->getTranslation($current_language);
          // add terms for replacement only if they are of current language.otherwise skip it.
          if ($current_language_term->getName() != $term->getName()) {
              continue;
          }
          $term_title = $term->getName();
          $fragment = NULL;
          if (!empty($vids) && ($config->get('lexicon_click_option', 0) == 1)) {
            // If terms should not be marked if a term has no description continue with the next term.
            if (!$config->get('lexicon_allow_no_description', FALSE) && empty($term_title)) {
              continue;
            }

            $langprefix = '';
            $defaultLangcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
            if ($term->get('langcode')->getString() != $defaultLangcode) {
              $langprefix = '/' . $term->get('langcode')->getString();
            }
            $lang = ($term->get('langcode')->getString() != 'und' && $langprefix != '') ?  $langprefix : '';

            if ($config->get('lexicon_page_per_letter', FALSE)) {
              $linkto = $lang . $config->get('lexicon_path_' . $term->getVocabularyId(), '/lexicon/' . $term->getVocabularyId()) . '/letter_' . Unicode::strtolower(Unicode::substr($term->getName(), 0, 1));
            }
            else {
              $linkto = $lang . $config->get('lexicon_path_' . $term->getVocabularyId(), '/lexicon/' . $term->getVocabularyId());
            }


            // Create a valid anchor id.
            $fragment = _lexicon_create_valid_id($term->getName());
          }
          else {
            $linkto = '/taxonomy/term/' . $term->id();
          }
          $term_class = $config->get('lexicon_term_class', 'lexicon-term');

          if ($replace_mode == 'template') {
            // Set the information needed by the template.
            $terms_replace[] = [
              'term' => $term,
              'absolute_link' => $absolute_link,
              'linkto' => $linkto,
              'fragment' => $fragment,
              'term_class' => $term_class,
            ];
            // var_dump(count($terms));die("DDD");
          }
          else {

            $ins_before = $ins_after = NULL;

            // Set the correct $ins_before and $ins_after to be used for marking.
            switch ($replace_mode) {
              case 'superscript':
                $ins_after = '<sup class="lexicon-indicator" title="' . $term_title . '">';
                if ($link_style == 'none') {
                  $ins_after .= $config->get('lexicon_superscript', 'i');
                }
                else {
                  $link = Link::fromTextAndUrl($config->get('lexicon_superscript', 'i'), Url::fromUserInput('/' . $linkto, [
                    'fragment' => $fragment,
                    'absolute' => $absolute_link,
                  ]))->toRenderable();
                  $link['#attributes'] = [
                    'title' => $term_title,
                    'class' => [
                      $term_class,
                    ],
                  ];
                  $ins_after .= render($link);
                }
                $ins_after .= '</sup>';
                break;

              case 'abbr':
                if ($link_style == 'none') {
                  $ins_before .= '<span class="' . $term_class . '" title="' . SafeMarkup::checkPlain($term_title) . '"><' . $replace_mode . ' title="' . SafeMarkup::checkPlain($term_title) . '">';
                  $ins_after .= '</' . $replace_mode . '></span>';
                }
                else {
                  $ins_before .= '<' . $replace_mode . ' title="' . SafeMarkup::checkPlain($term_title) . '"><a class="' . $term_class . '" href="' . $linkto . '#' . $fragment . '" title="' . SafeMarkup::checkPlain($term_title) . '">';
                  $ins_after .= '</a></' . $replace_mode . '>';
                }
                // var_dump([$linkto, $ins_before]);die("DDDSSSS");.
                break;

              case 'acronym':
              case 'cite':
              case 'dfn':
                if ($link_style == 'none') {
                  $ins_after .= '</' . $replace_mode . '>';
                }
                else {
                  $ins_before .= '<a class="' . $term_class . '" href="' . $linkto . '#' . $fragment . '">';
                  $ins_after .= '</' . $replace_mode . '></a>';
                }
                $ins_before .= '<' . $replace_mode . ' title="' . SafeMarkup::checkPlain($term_title) . '">';
                break;

              case 'iconterm':
                // Icon format, plus term link.
                $img = '<img src="' . base_path() . $config->get('lexicon_icon', '/imgs/lexicon.gif') . "\" />";
                if ($link_style == 'none') {
                  $ins_after .= $img;
                }
                else {
                  $ins_before .= '<a class="' . $term_class . '" href="' . $linkto . '#' . $fragment . '" title="' . SafeMarkup::checkPlain($term_title) . '">';
                  $ins_after = $img . '</a>';
                }
                break;

              case 'icon':
                // Icon format.
                $img = '<img src="' . base_path() . $config->get('lexicon_icon', '/imgs/lexicon.gif') . "\" />";
                if ($link_style == 'none') {
                  $ins_after .= $img;
                }
                else {
                  $ins_after = l($img, $linkto, [
                    'attributes' => [
                      'title' => $term_title,
                      'class' => 'lexicon-icon',
                    ],
                    'fragment' => $fragment,
                    'absolute' => $absolute_link,
                    'html' => TRUE,
                  ]);
                }
                break;

              case 'term':
                // Term format.
                if ($link_style == 'none') {
                  $ins_before = '<span class="' . $term_class . '">';
                  $ins_after = '</span>';
                }
                else {
                  $ins_before = '<a class="' . $term_class . '" href="' . url($linkto, ['fragment' => $fragment, 'absolute' => $absolute_link]) . '" title="' . SafeMarkup::checkPlain($term_title) . '">';
                  $ins_after = '</a>';
                }
                break;

              default:
                break;
            }

            $terms_replace[] = [
              'term' => $term,
              'ins_before' => $ins_before,
              'ins_after' => $ins_after,
            ];

          }
        }
      };
      $text = _lexicon_insertlink($text, $terms_replace);
    }
    return new FilterProcessResult($text);
  }

}
