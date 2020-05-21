<?php

namespace Drupal\lexicon\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Configure lexicon admin configuration settings.
 */
class lexiconAdminConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lexicon_configuration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['lexicon.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('lexicon.settings');
    $form = [];
    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => t('General settings'),
    ];

    $options = [];
    $options = taxonomy_vocabulary_get_names();
    // dump($result);
    // exit;
    // $result = db_query('SELECT vid FROM {taxonomy_term_field_data} ORDER BY name');.
    // Foreach ($result as $record) {
    //   dump($record);
    //     $options[$record->vid] = Xss::filterAdmin($record->vid);
    // }.
    if (!$options) {
      drupal_set_message(t('No vocabularies were found. Until you set up, and select, at least one vocabulary to use as a Lexicon, no lexicon is displayed and no marking of terms in the content of the website can be done.'));
    }

    $form['general']['lexicon_vids'] = [
      '#type' => 'checkboxes',
      '#title' => t('Which vocabularies act as lexicons.'),
      '#default_value' => $config->get('lexicon_vids') ?? [],
      '#options' => $options,
      '#description' => t('Select one or more vocabularies which hold terms for your lexicons.'),
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];

    $form['general']['lexicon_allow_no_description'] = [
      '#type' => 'checkbox',
      '#title' => t('Show/mark lexicon terms even if there is no description.'),
      '#default_value' => $config->get('lexicon_allow_no_description'),
      '#description' => t('By default, Lexicon does not show/mark terms if there is no term description. This setting overrides that. This is useful on free-tagging vocabularies that rarely get descriptions.'),
    ];

    $form['lexicon_page'] = [
      '#type' => 'fieldset',
      '#title' => t('Lexicon Page'),
    ];

    $form['lexicon_page']['lexicon_page_per_letter'] = [
      '#type' => 'checkbox',
      '#title' => t('Show lexicon across many smaller pages.'),
      '#default_value' => $config->get('lexicon_page_per_letter'),
      '#description' => t('Do you want to show all terms on one lexicon page or break up the lexicon into a page for each first letter (i.e. many pages).'),
    ];

    $form['lexicon_page']['lexicon_separate_letters'] = [
      '#type' => 'checkbox',
      '#title' => t('Separate letters.'),
      '#default_value' => $config->get('lexicon_separate_letters'),
      '#description' => t('Separate the terms by the first letters. This will create heading with the letter at the beginning of each section. If the terms are not separated by the first letters, no alphabar will be shown.'),
      '#states' => [
        'visible' => [
          ':input[name="lexicon_page_per_letter"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    $form['lexicon_page']['lexicon_show_description'] = [
      '#type' => 'checkbox',
      '#title' => t('Show lexicon term descriptions.'),
      '#default_value' => $config->get('lexicon_show_description'),
      '#description' => t('Lexicon term descriptions may be large and/or include pictures, therefore the Lexicon can take a long time to load if you include the full descriptions.'),
    ];

    $form['lexicon_page']['lexicon_link_to_term_page'] = [
      '#type' => 'checkbox',
      '#title' => t('Link terms to the taxonomy term page.'),
      '#default_value' => $config->get('lexicon_link_to_term_page'),
      '#description' => t('Do you want the term names to link to their taxonomy term page?'),
    ];

    $form['lexicon_page']['lexicon_link_related'] = [
      '#type' => 'checkbox',
      '#title' => t('Link related terms on the Lexicon page.'),
      '#default_value' => $config->get('lexicon_link_related'),
      '#description' => t('Do you want terms that are related to link to each other?'),
    ];

    $click_options = [
      t('Show only the single term.'),
      t('Advance the whole lexicon to the term.'),
    ];
    $form['lexicon_page']['lexicon_click_option'] = [
      '#type' => 'select',
      '#title' => t('Clicking on a term link will:'),
      '#options' => $click_options,
      '#default_value' => $config->get('lexicon_click_option'),
      '#description' => t('Set the link to show only the single term by using the default taxonomy single term view or go to the lexicon overview.'),
    ];

    $form['lexicon_page']['lexicon_show_edit'] = [
      '#type' => 'checkbox',
      '#title' => t('Show "edit" link.'),
      '#default_value' => $config->get('lexicon_show_edit'),
      '#description' => t('Determines whether or not to show an "edit term" link for each entry.'),
    ];

    $form['lexicon_page']['lexicon_show_search'] = [
      '#type' => 'checkbox',
      '#title' => t('Show "search" link.'),
      '#default_value' => $config->get('lexicon_show_search'),
      '#description' => t('Determines whether or not to show an "search for term" link for each entry.'),
    ];

    $form['lexicon_page']['lexicon_go_to_top_link'] = [
      '#type' => 'checkbox',
      '#title' => t('Show "go to top" link.'),
      '#default_value' => $config->get('lexicon_go_to_top_link'),
      '#description' => t('Determines whether or not to show a "go to top" link after each group of lexicon items per letter.'),
    ];

    $form['lexicon_page']['lexicon_go_to_top_link_fragment'] = [
      '#type' => 'textfield',
      '#title' => t('"Go to top" link anchor name.'),
      '#default_value' => $config->get('lexicon_go_to_top_link_fragment'),
      '#description' => t('Sets the "go to top" link anchor name to link to.'),
      '#states' => [
        'invisible' => [
          ':input[name="lexicon_go_to_top_link"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    $form['lexicon_page']['lexicon_local_links_scroll'] = [
      '#type' => 'checkbox',
      '#title' => t('Use scroll animation for local links.'),
      '#default_value' => $config->get('lexicon_local_links_scroll'),
      '#description' => t('Determines whether or not to animate scrolling to sections when following local links on the page.'),
    ];

    $text_format_settings = Link::fromTextAndUrl(
      $this->t('Text format settings'), Url::fromRoute('filter.admin_overview')
    );
    $form['mark_terms'] = [
      '#type' => 'fieldset',
      '#title' => t('Term marking settings'),
      '#description' => $this->t('You have to enable the <em>Lexicon Filter</em> in one or more text formats in the @text_format_settings to enable term marking in content.', [
        '@text_format_settings' => $text_format_settings->toString(),
      ]),
    ];

    $form['mark_terms']['lexicon_mark_terms'] = [
      '#type' => 'checkbox',
      '#title' => t('Mark terms in content.'),
      '#default_value' => $config->get('lexicon_mark_terms'),
      '#description' => t('Determines whether or not to mark terms in the content.'),
    ];

    $form['mark_terms']['match'] = [
      '#type' => 'fieldset',
      '#title' => t('Term matching'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#states' => [
        'invisible' => [
          ':input[name="lexicon_mark_terms"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    $form['mark_terms']['match']['lexicon_match'] = [
      '#type' => 'select',
      '#title' => t('Match type'),
      '#default_value' => $config->get('lexicon_match'),
      '#options' => [
        'b' => t('Word'),
        'lr' => t('Right or left substring'),
        'l' => t('Left substring'),
        'r' => t('Right substring'),
        's' => t('Any substring'),
      ],
      '#description' => t('Choose the match type of lexicon links. "Word" means a word break must occur on both sides of the term. "Right or left" requires a word break on either side. "Left" requires a word break on the left side of the term. "Right" requires a word break on the right. "Any" means any substring will match.'),
    ];

    $form['mark_terms']['match']['lexicon_case'] = [
      '#type' => 'select',
      '#title' => t('Case sensitivity'),
      '#default_value' => $config->get('lexicon_case', '1'),
      '#options' => [
        t('Case insensitive'),
        t('Case sensitive'),
      ],
      '#description' => t('Match either case sensitive or not. Case sensitive matches are not very resource intensive.'),
    ];

    $form['mark_terms']['match']['lexicon_replace_all'] = [
      '#type' => 'select',
      '#title' => t('Replace matches'),
      '#default_value' => $config->get('lexicon_replace_all', 1),
      '#options' => [
        t('Only the first match'),
        t('All matches'),
      ],
      '#description' => t('Whether only the first match should be replaced or all matches.'),
    ];

    $form['mark_terms']['match']['lexicon_blocking_tags'] = [
      '#type' => 'textarea',
      '#title' => t('Blocked elements'),
      '#default_value' => $config->get('lexicon_blocking_tags', 'abbr acronym'),
      '#cols' => 60,
      '#rows' => 1,
      '#maxlength' => 512,
      '#description' => t('Which HTML elements (tags) should not include Lexicon links;
        that is, text within these elements will not be scanned for lexicon terms.
        Enter the list separated by a space and do not include < and > characters (e.g. h1 h2).
        To use a %span element to skip text, prefix the class name with a dot (e.g. ".skipping-this").
        All "a," "code," and "pre" elements will be skipped by default.
        ', ['%span' => 'span']),
    ];

    $form['mark_terms']['indicator'] = [
      '#type' => 'fieldset',
      '#title' => t('Link style'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#states' => [
        'invisible' => [
          ':input[name="lexicon_mark_terms"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    $indicator_options = [
      'template' => t('Use the template file: @template_file.', [
        '@template_file' => '<em>lexicon-mark-term.tpl.php</em>',
      ]),
      'superscript' => t('Superscript'),
      'icon' => t('Icon'),
      'iconterm' => t('Icon + Term in span'),
      'term' => t('Term in span'),
      'abbr' => t('Use @type element', [
        '@type' => \Drupal::l('abbr', Url::fromUri('http://www.w3.org/TR/html401/struct/text.html#edef-ABBR')),
      ]),
      'acronym' => t('Use @type element', [
        '@type' => \Drupal::l(t('acronym'), Url::fromUri('http://www.w3.org/TR/html401/struct/text.html#edef-ACRONYM')),
        ]),
      'cite' => t('Use @type element', [
        '@type' => \Drupal::l(t('cite'), Url::fromUri('http://www.w3.org/TR/html401/struct/text.html#edef-CITE')),
        ]),
      'dfn' => t('Use @type element', [
        '@type' => \Drupal::l(t('dfn'), Url::fromUri('http://www.w3.org/TR/html401/struct/text.html#edef-DFN')),
        ]),
    ];

    $form['mark_terms']['indicator']['lexicon_replace'] = [
      '#type' => 'radios',
      '#title' => t('Term Indicator'),
      '#default_value' => $config->get('lexicon_replace', 'template'),
      '#options' => $indicator_options,
      '#description' => t('This determines how the the lexicon term will be marked in the content. The default template renderes a simple hyperlink and gives site developers more control over how terms are marked than the "preset options". The "phrase" items are linked to the standards in case you want to study them.'),
    ];

    $form['mark_terms']['indicator']['lexicon_superscript'] = [
      '#type' => 'textfield',
      '#title' => t('Superscript'),
      '#default_value' => $config->get('lexicon_superscript', 'i'),
      '#size' => 15,
      '#maxlength' => 255,
      '#description' => t('Enter the superscript text.'),
      '#states' => [
        'visible' => [
          ':input[name="lexicon_replace"]' => [
            'value' => 'superscript',
          ],
        ],
      ],
    ];

    $form['mark_terms']['indicator']['lexicon_icon'] = [
      '#type' => 'textfield',
      '#title' => t('Lexicon Icon URL'),
      '#default_value' => $config->get('lexicon_icon', drupal_get_path('module', 'lexicon') . '/imgs/lexicon.gif'),
      '#size' => 50,
      '#maxlength' => 255,
      '#description' => t('Enter the URL of the lexicon icon relative to the root ("%root") of your Drupal site.', ['%root' => base_path()]),
    ];
    // Since #states does not support "OR" a workaround is used:
    // http://api.drupal.org/api/drupal/includes!common.inc/function/
    // drupal_process_states/7#comment-24708.
    $all_options = array_keys($indicator_options);
    $visible = ['icon', 'iconterm'];
    foreach (array_diff($all_options, $visible) as $i => $option) {
      $form['mark_terms']['indicator']['lexicon_icon']['#states']['visible'][':input[name="lexicon_replace"], dummy-element' . $i] = ['!value' => $option];
    }
    $form['mark_terms']['indicator']['lexicon_link'] = [
      '#type' => 'select',
      '#options' => [
        'none' => t('none'),
        'normal' => t('normal'),
        'absolute' => t('absolute'),
      ],
      '#title' => t('Link type'),
      '#default_value' => $config->get('lexicon_link', 'normal'),
      '#description' => t('You may choose no linking of terms ("none"), standard site linking ("normal"), or "absolute" links. RSS feeds need absolute links to ensure they point back to this site. If you are not providing RSS feeds, it is better to choose one of the other types.'),
    ];

    $form['mark_terms']['indicator']['lexicon_term_class'] = [
      '#type' => 'textfield',
      '#title' => t('Term link class'),
      '#default_value' => $config->get('lexicon_term_class', 'lexicon-term'),
      '#description' => t('This is the style class that will be used for "acronym," "abbr," and "hovertip" links. It should only be used by those with specific site standards.'),
    ];

    $form['mark_terms']['indicator']['lexicon_disable_indicator'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow the user to disable lexicon links.'),
      '#default_value' => $config->get('lexicon_disable_indicator', FALSE),
      '#description' => t('Determines whether or not the individual user may disable the Lexicon indicators.'),
    ];

    $form['lexicon_clear_filter_cache_on_submit'] = [
      '#type' => 'checkbox',
      '#title' => t('Clear the cache when settings are submitted.'),
      '#default_value' => $config->get('lexicon_clear_filter_cache_on_submit', TRUE),
      '#description' => t('Changes in the filter behaviour are only visible when the filter cache is flushed. This setting ensures that the filter cache is flushed when the settings are submitted.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable('lexicon.settings')
    // Set the submitted configuration setting.
      ->set('lexicon_vids', array_filter($form_state->getValue('lexicon_vids')))
      ->set('lexicon_allow_no_description', $form_state->getValue('lexicon_allow_no_description'))
      ->set('lexicon_page_per_letter', $form_state->getValue('lexicon_page_per_letter'))
      ->set('lexicon_separate_letters', $form_state->getValue('lexicon_separate_letters'))
      ->set('lexicon_show_description', $form_state->getValue('lexicon_show_description'))
      ->set('lexicon_link_to_term_page', $form_state->getValue('lexicon_link_to_term_page'))
      ->set('lexicon_link_related', $form_state->getValue('lexicon_link_related'))
      ->set('lexicon_click_option', $form_state->getValue('lexicon_click_option'))
      ->set('lexicon_show_edit', $form_state->getValue('lexicon_show_edit'))
      ->set('lexicon_show_search', $form_state->getValue('lexicon_show_search'))
      ->set('lexicon_go_to_top_link', $form_state->getValue('lexicon_go_to_top_link'))
      ->set('lexicon_go_to_top_link_fragment', $form_state->getValue('lexicon_go_to_top_link_fragment'))
      ->set('lexicon_local_links_scroll', $form_state->getValue('lexicon_local_links_scroll'))
      ->set('lexicon_mark_terms', $form_state->getValue('lexicon_mark_terms'))
      ->set('lexicon_match', $form_state->getValue('lexicon_match'))
      ->set('lexicon_case', $form_state->getValue('lexicon_case'))
      ->set('lexicon_replace_all', $form_state->getValue('lexicon_replace_all'))
      ->set('lexicon_blocking_tags', $form_state->getValue('lexicon_blocking_tags'))
      ->set('lexicon_replace', $form_state->getValue('lexicon_replace'))
      ->set('lexicon_superscript', $form_state->getValue('lexicon_superscript'))
      ->set('lexicon_icon', $form_state->getValue('lexicon_icon'))
      ->set('lexicon_link', $form_state->getValue('lexicon_link'))
      ->set('lexicon_term_class', $form_state->getValue('lexicon_term_class'))
      ->set('lexicon_disable_indicator', $form_state->getValue('lexicon_disable_indicator'))
      ->set('lexicon_clear_filter_cache_on_submit', $form_state->getValue('lexicon_clear_filter_cache_on_submit'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
