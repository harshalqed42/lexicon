<?php

namespace Drupal\lexicon\Routing;

use Symfony\Component\Routing\Route;


/**
 * Defines dynamic routes.
 */
class LexiconRoutes {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = [];
    // Declares a single route under the name 'example.content'.
    // Returns an array of Route objects.
    $routes['example.content'] = new Route(
      // Path to attach this route to:
      '/example',
      // Route defaults:
      [
        '_controller' => '\Drupal\example\Controller\ExampleController::content',
        '_title' => 'Hello',
      ],
      // Route requirements:
      [
        '_permission'  => 'access content',
      ]
    );
    return $routes;
  }

}
