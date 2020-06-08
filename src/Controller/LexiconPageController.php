<?php

namespace Drupal\lexicon\Controller;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Lexicon pages.
 */
class LexiconPageController extends ControllerBase {
  use StringTranslationTrait;

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $configFactory;

  /**
   * Returns current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The redirect destination helper.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDest;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a CronController object.
   */
  public function __construct($configFactory, $currentPath, $redirectDest, $languageManager, $entityTypeManager, $currentUser) {
    $this->config = $configFactory->get('lexicon.settings');
    $this->currentPath = $currentPath->getPath();
    $this->redirectDest = $redirectDest->getAsArray();
    $this->languageManager = $languageManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.current'),
      $container->get('redirect.destination'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Callback for lexicon page Title.
   */
   public function letterPageTitle(Request $request,$letter) {
       $title = '' ;
       if (isset($request->attributes->all()['title_default'])) {
         $title = $request->attributes->all()['title_default'];
       }
       $config = $this->config;
       $page_per_letter = $config->get('lexicon_page_per_letter', FALSE);

       if ($alphabet = $this->_fetch_letter_from_text($letter) && $page_per_letter) {
           $title = $this->t('@title beginning with @let', [
               '@title' => $title,
               '@let' => strtoupper($letter),
           ]);
       }


     return ['#markup' => $title, '#allowed_tags' => Xss::getHtmlTagList()];
   }

   public function _fetch_letter_from_text($letter) {
       $letter_arr = explode('letter_', $letter);
       if (isset($letter_arr) && !$letter_arr[0]) {
           return $letter_arr[1];
       }
       else {
           return false;
       }
   }

  /**
   * Callback for lexicon page.
   */
   public function letterPage($letter) {
      if ( $alphabet = $this->_fetch_letter_from_text($letter)) {
         return $this->page($alphabet);
      }
      throw new NotFoundHttpException();
   }
  /**
   * Callback for lexicon page.
   */
  public function page($alphabet = NULL) {
    $config = $this->config;
    $found_vid = NULL;
    // @codingStandardsIgnoreLine
    // $scroll_enabled = $config->get('lexicon_local_links_scroll', 0);
    // $path = drupal_get_path('module', 'lexicon');
    $vids = $config->get('lexicon_vids', []);

    // Get the vocabulary-id for the vocabulary which the page callback is
    // called for by comparing the current path to the path that is configured
    // for each Lexicon.
    foreach ($vids as $vid) {
      $tmp_path = $config->get('lexicon_path_' . $vid);
      if (strpos($this->currentPath, $tmp_path) !== FALSE) {
        $found_vid = $vid;
        break;
      }
    }
    $voc = Vocabulary::load($found_vid);
    if (!$alphabet) {
        $letter = NULL;
        // Check the argument and derive the letter from it if it is correct.
        if ($letter != NULL) {
            if (drupal_strlen($letter) != 8 || Unicode::substr($letter, 0, 7) != 'letter_') {
                return MENU_NOT_FOUND;
            } else {
                $letter = Unicode::substr($letter, 7, 1);
            }
        }

        // Set the active menu to be "primary-links" to make the breadcrumb work.
        // By default the active menu would be "navigation", causing only
        // "Home" > $node->title to be shown.
        // menu_set_active_menu_names('primary-links');.
        return $this->lexiconOverview($voc, $letter);
    }
    else {
        return $this->lexiconOverview($voc, $alphabet);

    }
  }

  /**
   * Returns overview.
   *
   * Lexicon overview function that creates all the data end renders the output
   * through the various theme templates.
   */
  protected function lexiconOverview($vocab, $letter = NULL) {
    $config = $this->config;
    $vid = $vocab->id();
    // @codingStandardsIgnoreLine
    // $path = $config->get('lexicon_path_' . $vid, '/lexicon/' . $vid);

    $current_let = '';

    $separate = $config->get('lexicon_separate_letters', FALSE);
    $page_per_letter = $config->get('lexicon_page_per_letter', FALSE);
    $show_description = $config->get('lexicon_show_description', FALSE);
    $link_to_term_page = $config->get('lexicon_link_to_term_page', FALSE);

    // @todo: If the Lexicon is configured to show one big list of terms, but there is a
    // letter in the argument, return 404.
    if (!$page_per_letter && $letter) {
      return MENU_NOT_FOUND;
    }

    // @TODO: Set the title if the terms are displayed per letter instead of
    // in one big list if the Lexicon is configured to be split into multiple
    // pages and there is a letter argument.
    if ($page_per_letter && $letter) {
   /**   drupal_set_title($this->t('@title beginning with @let', [
        '@title' => $config->get('lexicon_title_' . $vid, $vocab->name),
        '@let' => drupal_strtoupper($letter),
      ]));**/
    }

    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $tree = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vid, 0, NULL, TRUE);
    // Switch to translated term switched to different language.
    if ($langcode != $this->languageManager->getDefaultLanguage()->getId()) {
      foreach ($tree as $k => $term) {
        if (!$term->hasTranslation($langcode)){
          unset($tree[$k]);
        }
        else{
          $tree[$k] = $term->getTranslation($langcode);
        }
      }
    }

    // Since the tree might not be sorted alphabetically sort it.
    uasort($tree, function ($a, $b) {
      // Sort callback function to sort vocabulary trees alphabetically
      // on term name.
      if (Unicode::strtolower($a->getName()) == Unicode::strtolower($b->getName())) {
        return 0;
      }
      else {
        if (Unicode::strtolower($a->getName()) < Unicode::strtolower($b->getName())) {
          return -1;
        }
        else {
          return 1;
        }
      }
    });

    $lexicon_alphabar = NULL;
    // If the overview is separated in sections per letter or the Lexicon is
    // displayed spread over multiple pages per letter create the alphabar.
    if ($separate || $page_per_letter) {
      $lexicon_alphabar = $this->lexiconAlphabar($vid, $tree);
    }
    $lexicon_overview_sections = [];
    $lexicon_introduction = NULL;

    // Check if the Lexicon is spread over multiple pages per letter and if a
    // letter argument is present.
    if (!$letter) {
      $introduction_text = $config->get('lexicon_introduction_' . $vid, NULL);
      // Display the introduction text if it is set in the configuration.
      if ($introduction_text['value'] != '') {
        $lexicon_introduction = check_markup($introduction_text['value'], $introduction_text['format'])->__toString();
      }
    }
    if (!($page_per_letter && !$letter)) {
      // var_dump($page_per_letter); var_dump($letter); die('ddd');.
      $lexicon_overview_items = [];
      $lexicon_section = new \stdClass();
      if ($tree) {
        $not_first = FALSE;
        // Build up the list by iterating through all terms within
        // the vocabulary.
        foreach ($tree as $term) {
          // If terms should not be marked if a term has no description
          // continue with the next term.
          if (!$config->get('lexicon_allow_no_description', FALSE) && empty($term->getDescription())) {
            continue;
          }
          // If we're looking for a single letter, see if this is it.
          $term->let = Unicode::strtolower(Unicode::substr($term->getName(), 0, 1));

          // If there is no letter argument or the first letter of the term
          // equals the letter argument process the term.
          if ((!$letter) || $term->let == $letter) {
            // See if it's a new section.
            if ($term->let != $current_let) {
              if ($not_first) {
                if ($separate) {
                  // Create the section output for the previous section.
                  $lexicon_overview_sections[] = [
                    '#theme' => 'lexicon_overview_section',
                    '#lexicon_section' => $lexicon_section,
                    '#lexicon_overview_items' => $lexicon_overview_items,
                  ];
                  // Clear the items to fill with the items of the new section.
                  $lexicon_overview_items = [];
                  $lexicon_section = new \stdClass();
                }
              }
              if ($separate) {
                $lexicon_section->letter = $term->let;
                // Set the anchor id of the section used by the alphabar and
                // linked terms. The anchor is as meaningful as possible
                // ("letter"_$letter) for accessibility purposes.
                $lexicon_section->id = 'letter_' . $lexicon_section->letter;
              }
            }
            // Create the term output.
            // This needs to be unset because it doesn't work with translations.
            if (isset($term->info_added)) {
              unset($term->info_added);
            }
            $term_output = _lexicon_term_add_info($term);
            // Unset the description if it should not be shown.
            if (!$show_description) {
              unset($term_output->description);
              unset($term_output->safe_description);
            }
            if ($link_to_term_page) {
              $term_output->name = Link::fromTextAndUrl($term_output->getName(), Url::fromUserInput('/taxonomy/term/' . $term_output->id()));
              $term_output->safe_name = Link::fromTextAndUrl($term_output->getName(), Url::fromUserInput('/taxonomy/term/' . $term_output->id()));
            }
            // var_dump($term_output); die("DDDD$term_output");.
            $lexicon_overview_items[] = [
              '#theme' => 'lexicon_overview_item',
              '#term' => $term_output,
            ];
            // For future use:
            // @codingStandardsIgnoreLine
            // $lexicon_overview_items = \Drupal::service('renderer')->render(taxonomy_term_view($term_output, 'lexicon'));.
            $current_let = $term->let;
            $not_first = TRUE;
          }
        }
        // Create a section without anchor and heading if the Lexicon is not
        // seperated into sections per letter or if there are no items
        // to display.
        if (!$separate || $lexicon_overview_items == '') {
          $lexicon_section = NULL;
        }
        // var_dump((array)($lexicon_overview_items)); die("DDD");
        // var_dump($lexicon_section); die("DDD");
        // Create the last section output.
        $lexicon_overview_sections[] = [
          '#theme' => 'lexicon_overview_section',
          '#lexicon_section' => $lexicon_section,
          '#lexicon_overview_items' => $lexicon_overview_items,
        ];
      }
    }

    $lexicon_overview = new \stdClass();
    // var_dump(get_class_methods($vocab)); die($vocab->id());
    $lexicon_overview->voc_name = Unicode::strtolower(_lexicon_create_valid_id($vocab->id()));

    $lexicon_overview->description = Xss::filter($vocab->getDescription());
    $lexicon_overview->introduction = $lexicon_introduction;
    if ($separate && $config->get('lexicon_go_to_top_link', FALSE) == TRUE) {
      $lexicon_overview->go_to_top_link['name'] = $this->t('Go to top');
      $lexicon_overview->go_to_top_link['fragment'] = $config->get('lexicon_go_to_top_link_fragment', 'top');
      $lexicon_overview->go_to_top_link['attributes'] = [
        'class' => ['lexicon_go_to_top_link'],
      ];
    }

    $output = [
      'admin_links' => [
        '#theme' => 'links',
        '#prefix' => '<div class="lexicon-admin-links">',
        '#links' => $this->lexiconAdminLinks($vocab, $this->redirectDest),
        '#suffix' => '</div>',
      ],
      'overview' => [
        '#theme' => 'lexicon_overview',
        '#lexicon_overview' => $lexicon_overview,
        '#lexicon_alphabar' => $lexicon_alphabar,
        '#lexicon_overview_sections' => $lexicon_overview_sections,
      ],
      '#cache' => [
        'tags' => [
          'config:lexicon.settings',
        ],
        'contexts' => [
          'languages',
        ],
      ],
      '#attached' => [
        'library' => ['lexicon/lexicon'],
      ],
    ];
    return $output;
  }

  /**
   * Returns Admin links.
   *
   * Lexicon admin links function. Returns an array of admin links if the user
   * has the appropriate permissions.
   */
  protected function lexiconAdminLinks($vocabulary, $destination) {
    $links = [];
    if ($this->currentUser->hasPermission('administer taxonomy')) {
      $links['lexicon_add_term'] = [
        'title' => $this->t('Add term'),
        'url' => Url::fromUserInput('/admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add'),
      ];
      $links['lexicon_edit'] = [
        'title' => $this->t('Edit @name', ['@name' => Unicode::strtolower(Html::escape($vocabulary->id()))]),
        'url' => Url::fromUserInput('/admin/structure/taxonomy/manage/' . $vocabulary->id()),
        'query' => $destination,
      ];
    }

    if ($this->currentUser->hasPermission('administer filters')) {
      $links['lexicon_admin'] = [
        'title' => $this->t('Lexicon settings'),
        'url' => Url::fromUserInput('/admin/config/system/lexicon'),
        'query' => $destination,
      ];
    }

    return $links;
  }

  /**
   * Return Alphabar.
   *
   * Function that builds up the alphabar that is displayed at the top of the
   * Lexicon overview page.
   */
  protected function lexiconAlphabar($vid, &$tree) {
    $config = $this->config;
    $path = $config->get('lexicon_path_' . $vid, 'lexicon/' . $vid);
    $page_per_letter = $config->get('lexicon_page_per_letter', FALSE);

    if ($config->get('lexicon_suppress_unused', FALSE)) {
      // Just make it empty; it will be filled in below.
      $letters = [];
    }
    else {
      // Create the array of characters to use for the alphabar.
      $lets = array_merge($config->get('lexicon_alphabet', range('a', 'z')), $config->get('lexicon_digits', range('0', '9')));
      // var_dump($lets); var_dump($letters); die("DD");
      // $letters = drupal_map_assoc($lets);
      $letters = array_combine($lets, $lets);
    }
    // For each term in the vocabulary get the first letter and put it in the
    // array with the correct link.
    foreach ($tree as $term) {
      // If terms should not be marked if a term has no description continue
      // with the next term.
      if (!$config->get('lexicon_allow_no_description', FALSE) && empty($term->description)) {
        continue;
      }
      $term->let = Unicode::strtolower(Unicode::substr($term->getName(), 0, 1));
      // If the Lexicon is split up in seperate pages per letter the link must
      // refer to the appropriate page.
      if ($page_per_letter) {
        $letters[$term->let] = Link::fromTextAndUrl($term->let, Url::fromUserInput($path . '/letter_' . $term->let))->toRenderable();
        $letters[$term->let]['#attributes'] = [
          'class' => ['lexicon-item'],
        ];
      }

        // If the Lexicon is displayed with all letters on one overview then the
      // link must refer to an anchor.
      else {
        $letters[$term->let] = Link::fromTextAndUrl($term->let, Url::fromUserInput($path, ['fragment' => 'letter_' . $term->let]))->toRenderable();;
        $letters[$term->let]['#attributes'] = [
          'class' => ['lexicon-item'],
        ];
        $letters[$term->let] = render($letters[$term->let]);
      }



    }


    $lexicon_alphabar = new \stdClass();
    $lexicon_alphabar->separator = ' ' . $config->get('lexicon_alphabar_separator', '|') . ' ';
    $lexicon_alphabar->instructions = Html::escape($config->get('lexicon_alphabar_instruction', _lexicon_alphabar_instruction_default()));
    $lexicon_alphabar->letters = $letters;
    return [
      '#theme' => 'lexicon_alphabar',
      '#lexicon_alphabar' => $lexicon_alphabar,
    ];
  }

}
