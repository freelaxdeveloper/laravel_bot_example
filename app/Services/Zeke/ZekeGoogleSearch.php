<?php namespace App\Services\Zeke;

require_once app_path('CustomClasses/simple_html_dom.php');

/**
 * Class ZekeGoogleSearch
 * @package App\Services\Zeke
 */
class ZekeGoogleSearch
{
  public $search;
  public $links = [];
  public $limit = 3;

  public function __construct($search)
  {
    $this->search = $search;
  }

    /**
     * @return string
     */
  public function html()
  {
    $html = 'Here are what I found in Google on your question:<br/>';
    foreach ($this->links as $link) {
      $html .= "<a target='_blank' href='{$link['href']}'>{$link['title']}</a><br/>";
    }
    return $html;
  }

    /**
     * @return $this
     */
  public function search()
  {
    $links = [];
    $html = file_get_html('https://www.google.com/search?q=' . urlencode($this->search));
    foreach($html->find('.r > a') as $element) {
      if (!strrpos($element->href, 'url?')) {
        continue;
      }
      $href = explode('://', $element->href);
      $href = preg_replace('/^(.+)\&.*$/iU', '$1', $href[1]);

      $this->links[] = [
        'title' =>  mb_convert_encoding($element->plaintext, "utf-8", "auto"),
        'href' => "https://{$href}",
      ];
      if (count($this->links) >= $this->limit) {
        break;
      }
    }
    return $this;
  }

}