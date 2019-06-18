<?php

namespace Drupal\webtools_paragraphs\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class RenderWebtools.
 */
class RenderWebtools implements CommandInterface {

  /**
   * A CSS selector string.
   *
   * Selector to where we will render the widget.
   *
   * @var string
   */
  protected $selector;

  /**
   * The json object to be render by EC smart loader.
   *
   * @var string|array
   */
  protected $content;

  /**
   * Constructs an RenderWebtools (command) object.
   *
   * @param string $selector
   *   A CSS selector (ID).
   * @param string $content
   *   The content (json object) that will be rendered by webtools smart loader.
   */
  public function __construct($selector, $content) {
    $this->setSelector($selector);
    $this->content = $content;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'render',
      'selector' => $this->selector,
      'content' => $this->content,
    ];
  }

  /**
   * Sets a css selector (ID) where widget will be render.
   *
   * We remove the # from selector since webtools is expecting a id without #.
   *
   * @param string $selector
   *   ID of dom element.
   */
  private function setSelector(string $selector) {
    if ($selector[0] === '#') {
      $selector = ltrim($selector, $selector[0]);
      $this->selector = $selector;
    }
    $this->selector = $selector;
  }

}
