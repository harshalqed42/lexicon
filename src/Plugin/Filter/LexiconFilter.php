<?php

namespace Drupal\lexicon\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\taxonomy\TermStorage;
use Drupal\Component\Utility\SafeMarkup;

/**
 * @Filter(
 *   id = "filter_lexicon",
 *   title = @Translation("Lexicon Filter"),
 *   description = @Translation("Mark Lexicon terms"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class LexiconFilter extends FilterBase {

    public function process($text, $langcode) {
//        $replace = '<span class="celebrate-filter">' . $this->t('Good Times!') . '</span>';
//        $new_text = str_replace('[celebrate]', $replace, $text);
//        return new FilterProcessResult($new_text);
        $current_term = 0;
        $term = \Drupal::routeMatch()->getParameter('taxonomy_term');
        if (!empty($route_match)) {
            $current_term = $term->id();
        }
        $text = ' ' . $text . ' ';
        $replace_mode = 'superscript';
        $link_style = 'normal';
        $vids = ['test1'];
        $terms = $this->lexicon_get_terms($vids);
        $terms_replace = array();





        $replace = '<span class="celebrate-filter">' . $this->t('Good Times!') . '</span>';
        $new_text = str_replace('[celebrate]', $replace, $text);
        return new FilterProcessResult($new_text);
    }

    protected function lexicon_get_terms($vids) {
        static $got = array();
        $terms = array();

        // If the terms have not been loaded get the tree for each lexicon vocabulary.
        if (!$got) {
            foreach ($vids as $vid) {
                // Load the entire vocabulary with all entities.
                $tree = TermStorage::loadTree($vid, 0, NULL, TRUE);
                // Add extra information to each term in the tree.
                foreach ($tree as $term) {
                    lexicon_term_add_info($term, TRUE);
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

    public function lexicon_term_add_info(&$term, $filter = FALSE) {
        if (!isset($term->info_added)) {
            $destination = \Drupal::service('redirect.destination');
            static $click_option, $link_related, $image_field, $synonyms_field, $page_per_letter, $show_edit, $show_desc, $show_search, $edit_voc, $access_search;
            if (!isset($click_option)) {
                $click_option = 0;//variable_get('lexicon_click_option', 0);
                $link_related = TRUE;//variable_get('lexicon_link_related', TRUE);
                $image_field = '';//variable_get('lexicon_image_field_' . $term->vid, '');
                $synonyms_field = '';//variable_get("lexicon_synonyms_field_" . $term->vid, '');
                $page_per_letter = FALSE;//variable_get('lexicon_page_per_letter', FALSE);
                $show_edit = TRUE;//variable_get('lexicon_show_edit', TRUE);
                $show_search = TRUE;//variable_get('lexicon_show_search', TRUE);
                $edit_voc = TRUE;//user_access('edit terms in ' . $term->vid);
                $access_search = TRUE;//user_access('search content');
            }
            $term->id = lexicon_create_valid_id($term->name);

            $term->safe_name = SafeMarkup::checkPlain($term->name);
            if ($filter) {
                $term->safe_description = strip_tags($term->description);
            }
            else {
                $term->safe_description = check_markup($term->description, $term->format);
            }

        }
    }

    protected function lexicon_create_valid_id($name){
        $allowed_chars = '-A-Za-z0-9._:';

        $id = preg_replace(array(
            '/&nbsp;|\s/',
            '/\'/',
            '/&mdash;/',
            '/&amp;/',
            '/&[a-z]+;/',
            '/[^' . $allowed_chars . ']/',
            '/^[-0-9._:]+/',
            '/__+/',
        ), array(
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
        ), strip_tags($name));

        return $id;
    }
}
