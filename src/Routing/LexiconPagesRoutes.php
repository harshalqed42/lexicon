<?php

namespace Drupal\lexicon\Routing;

use Symfony\Component\Routing\Route;

/**
 * Lexicon dynamic routes.
 */
class LexiconPagesRoutes {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $config = \Drupal::config('lexicon.settings');
    $vids = $config->get('lexicon_vids');
    $routes = [];
    foreach ($vids as $vid) {
      $routes['lexicon.page.' . $vid] = new Route(
        $config->get('lexicon_path_' . $vid),
        [
          '_controller' => '\Drupal\lexicon\Controller\LexiconPageController::page',
          '_title' => $config->get('lexicon_title_' . $vid),
        ],
        [
          '_permission' => 'access content',
        ]
      );
        $routes['lexicon.letter_per_page.' . $vid] = new Route(
            $config->get('lexicon_path_' . $vid) .'/{letter}',
            [
                '_controller' => '\Drupal\lexicon\Controller\LexiconPageController::letterPage',
                '_title_callback' => '\Drupal\lexicon\Controller\LexiconPageController::letterPageTitle',
                'title_default' => $config->get('lexicon_title_' . $vid),
            ],
            [
                '_permission' => 'access content',
            ],
            [
                'letter' => '\w+',
            ]
        );
    }
    return $routes;
  }

}
