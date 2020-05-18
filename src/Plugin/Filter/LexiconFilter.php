<?php

namespace Drupal\lexicon\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Link;
use Drupal\Core\Url;

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
          return $text;
        }
      }
    }

    $current_term = 0;
    // If the current page is a taxonomy term page then set $current_term.
    // if (strcmp(arg(0), 'taxonomy') == 0 && strcmp(arg(1), 'term') == 0 && arg(2) > 0) {
    //   $current_term = arg(2);
    // }

    // If marking of terms is enabled then mark terms and synonyms.
    if ($config->get('lexicon_mark_terms', 0) == 1) {
      $text = ' ' . $text . ' ';
      $replace_mode = $config->get('lexicon_replace', 'superscript');
      $link_style = $config->get('lexicon_link', 'normal');
      $absolute_link = ($link_style == 'absolute');
      $vids = $config->get('lexicon_vids', array());
      $terms = _lexicon_get_terms($vids);
      $terms_replace = array();
      $tip_list = array();



      if (is_array($terms)) {


        foreach ($terms as $term) {
          // If the term is equal to $current_term than skip marking that term.
          if ($current_term == $term->id()) {
            continue;
          }
          $term_title = $term->getName();
          $fragment = NULL;
          if (!empty($vids) && ($config->get('lexicon_click_option', 0) == 1)) {
            // If terms should not be marked if a term has no description continue with the next term.
            if (!$config->get('lexicon_allow_no_description', FALSE) && empty($term_title)) {
              continue;
            }
            if ($config->get('lexicon_page_per_letter', FALSE)) {
              $linkto = $config->get('lexicon_path_' . $term->id(), '/lexicon/' . $term->id()) . '/letter_' . drupal_strtolower(drupal_substr($term->name, 0, 1));
            }
            else {
              $linkto = $config->get('lexicon_path_' . $term->id(), '/lexicon/' . $term->id());
            }

            // Create a valid anchor id.
            $fragment = _lexicon_create_valid_id($term->getName());
          }
          else {
            $linkto = '/taxonomy/term/' . $term->tid;
          }

          $term_class = $config->get('lexicon_term_class', 'lexicon-term');

          if ($replace_mode == 'template') {
            // Set the information needed by the template.
            $terms_replace[] = array(
              'term' => $term,
              'absolute_link' => $absolute_link,
              'linkto' => $linkto,
              'fragment' => $fragment,
              'term_class' => $term_class,
            );
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
                  $link = Link::fromTextAndUrl($config->get('lexicon_superscript', 'i'), Url::fromUserInput('/' .$linkto, [
                    'fragment' => $fragment,
                    'absolute' => $absolute_link,
                  ]))->toRenderable();
                  $link['#attributes'] = array(
                    'title' => $term_title,
                    'class' => array(
                      $term_class,
                    ),
                  );
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
                  $ins_before .= '<' . $replace_mode . ' title="' . SafeMarkup::checkPlain($term_title) . '"><a class="' . $term_class . '" href="' . $linkto . '#' .$fragment . '" title="' . SafeMarkup::checkPlain($term_title) . '">';
                  $ins_after .= '</a></' . $replace_mode . '>';
                }
                // var_dump("<pre>".$ins_before);die("DDDSSSS");
                break;

              case 'acronym':
              case 'cite':
              case 'dfn':
                if ($link_style == 'none') {
                  $ins_after .= '</' . $replace_mode . '>';
                }
                else {
                  $ins_before .= '<a class="' . $term_class . '" href="' . $linkto . '#' .$fragment . '">';
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
                  $ins_before .= '<a class="' . $term_class . '" href="' . $linkto . '#' .$fragment . '" title="' . SafeMarkup::checkPlain($term_title) . '">';
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
                  $ins_after = l($img, $linkto, array(
                    'attributes' => array(
                      'title' => $term_title,
                      'class' => 'lexicon-icon',
                    ),
                    'fragment' => $fragment,
                    'absolute' => $absolute_link,
                    'html' => TRUE,
                  ));
                }
                break;

              case 'term':
                // Term format.
                if ($link_style == 'none') {
                  $ins_before = '<span class="' . $term_class . '">';
                  $ins_after = '</span>';
                }
                else {
                  $ins_before = '<a class="' . $term_class . '" href="' . url($linkto, array('fragment' => $fragment, 'absolute' => $absolute_link)) . '" title="' . SafeMarkup::checkPlain($term_title) . '">';
                  $ins_after = '</a>';
                }
                break;

              default:
                break;
            }

            $terms_replace[] = array(
              'term' => $term,
              'ins_before' => $ins_before,
              'ins_after' => $ins_after,
            );


          }
        }
      }
      ;
      $text = _lexicon_insertlink($text, $terms_replace);
      // var_dump($text); die("DDDDSS");
      return new FilterProcessResult($text);
    }
    return $text;
  }

  /**
   *
   */
  public function lexicon_get_terms($vids) {
    static $got = [];
    $terms = [];
    // If the terms have not been loaded get the tree for each lexicon vocabulary.
    if (!$got) {
      foreach ($vids as $vid) {
        // Load the entire vocabulary with all entities.
        $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, 0, NULL, TRUE);
        // Add extra information to each term in the tree.
        foreach ($tree as $term) {
          $this->lexicon_term_add_info($term, TRUE);
          $terms[] = $term;
        }
      }
      $got = $terms;
    }
    else {
      // Use the already loaded terms.
      $terms = $got;
    }
    return $terms;
  }

  /**
   *
   */
  public function lexicon_term_add_info(&$term, $filter = FALSE) {

    if (!isset($term->info_added)) {
      $destination = \Drupal::service('redirect.destination');
      static $click_option, $link_related, $image_field, $synonyms_field, $page_per_letter, $show_edit, $show_desc, $show_search, $edit_voc, $access_search;
      if (!isset($click_option)) {
        // $config->get('lexicon_click_option', 0);.
        $click_option = 0;
        // $config->get('lexicon_link_related', TRUE);.
        $link_related = TRUE;
        // $config->get('lexicon_image_field_' . $term->id(), '');.
        $image_field = '';
        // $config->get("lexicon_synonyms_field_" . $term->id(), '');.
        $synonyms_field = '';
        // $config->get('lexicon_page_per_letter', FALSE);.
        $page_per_letter = FALSE;
        // $config->get('lexicon_show_edit', TRUE);.
        $show_edit = TRUE;
        // $config->get('lexicon_show_search', TRUE);.
        $show_search = TRUE;
        // user_access('edit terms in ' . $term->id());.
        $edit_voc = TRUE;
        // user_access('search content');.
        $access_search = TRUE;
      }
      $term->id = $this->lexicon_create_valid_id($term->getName());

      $term->safe_name = SafeMarkup::checkPlain($term->getName());
      if ($filter) {
        $term->safe_description = strip_tags($term->getDescription());
      }
      else {

        $term->safe_description = check_markup($term->getDescription(), $term->getFormat());
      }

      // If there is an image for the term add the image information to the $term
      // object.
      if ($image_field != '') {
        // $image_field_items = field_get_items('taxonomy_term', $term, $image_field);
        //                if (!empty($image_field_items)) {
        //                    $term->image['uri'] = $image_field_items[0]['uri'];
        //                    $term->image['alt'] = SafeMarkup::checkPlain($image_field_items[0]['alt']);
        //                    $term->image['title'] = SafeMarkup::checkPlain($image_field_items[0]['title']);
        //                }
      }

      // If there are synonyms add them to the $term object.
      if ($synonyms_field != '') {
        // $synonyms_field_items = field_get_items('taxonomy_term', $term, $synonyms_field);
        //                if (!empty($synonyms_field_items)) {
        //                    foreach ($synonyms_field_items as $item) {
        //                        $term->synonyms[] = $item['safe_value'];
        //                    }
        //                }
      }
      // $config->get('lexicon_path_' . $term->id(), 'lexicon/' . $term->id());.
      $path = 'lexicon/test1';
      if ($page_per_letter) {
        // $term->link['path'] = $path . '/letter_' . drupal_strtolower(drupal_substr($term->name, 0, 1));
      }
      // If the Lexicon overview shows all letters on one page the link must lead
      // to the appropriate anchor.
      else {
        $term->link['path'] = $path;
      }
      $term->link['fragment'] = $this->lexicon_create_valid_id($term->getName());
      // If there are related terms add the information of each related term.
      //            if ($relations = _lexicon_get_related_terms($term)) {
      //                foreach ($relations as $related) {
      //                    $term->related[$related->tid]['name'] = SafeMarkup::checkPlain($related->name);
      //                    $related_path = $config->get('lexicon_path_' . $related->vid, 'lexicon/' . $related->vid);
      //                    // If the related terms have to be linked add the link information.
      //                    if ($link_related) {
      //                        if ($click_option == 1) {
      //                            // The link has to point to the term on the Lexicon page.
      //                            if ($page_per_letter) {
      //                                $term->related[$related->tid]['link']['path'] = $related_path . '/letter_' . drupal_strtolower(drupal_substr($related->name, 0, 1));
      //                            }
      //                            // If the Lexicon overview shows all letters on one page the link
      //                            // must lead to the appropriate anchor.
      //                            else {
      //                                $term->related[$related->tid]['link']['path'] = $related_path;
      //                            }
      //                            $term->related[$related->tid]['link']['fragment'] = _lexicon_create_valid_id($related->name);
      //                        }
      //                        else {
      //                            // The link has to point to the page of the term itself.
      //                            $term->related[$related->tid]['link']['path'] = 'taxonomy/term/' . $related->tid;
      //                            $term->related[$related->tid]['link']['fragment'] = '';
      //                        }
      //                    }
      //                }
      //            }.
      if ($show_edit && $edit_voc) {
        $term->extralinks['edit term']['name'] = t('edit term');
        $term->extralinks['edit term']['path'] = 'taxonomy/term/' . $term->id() . '/edit';
        $term->extralinks['edit term']['attributes'] = [
          'class' => 'lexicon-edit-term',
          'title' => t('edit this term and definition'),
          'query' => $destination,
        ];
      }
      if ($show_search && $access_search) {
        $term->extralinks['search for term']['name'] = t('search for term');
        $term->extralinks['search for term']['path'] = 'search/node/' . $term->getName();
        $term->extralinks['search for term']['attributes'] = [
          'class' => 'lexicon-search-term',
          'title' => t('search for content using this term'),
          'query' => $destination,
        ];
      }

      $term->info_added = TRUE;
    }
    return $term;
  }

  /**
   *
   */
  public function lexicon_create_valid_id($name) {
    $allowed_chars = '-A-Za-z0-9._:';

    $id = preg_replace([
      '/&nbsp;|\s/',
      '/\'/',
      '/&mdash;/',
      '/&amp;/',
      '/&[a-z]+;/',
      '/[^' . $allowed_chars . ']/',
      '/^[-0-9._:]+/',
      '/__+/',
    ], [
          // &nbsp; and spaces.
      '_',
          // apostrophe, so it makes things slightly more readable.
      '-',
          // &mdash;.
      '--',
          // &amp;.
      'and',
          // Any other entity.
      '',
          // Any character that is invalid as an ID name.
      '',
          // Any digits at the start of the name.
      '',
          // Reduce multiple underscores to just one.
      '_',
    ], strip_tags($name));
    return $id;
  }

}
