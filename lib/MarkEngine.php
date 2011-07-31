<?php
/*
 * MarkEngine - a simple preprocessor for markdown-based websites
 *
 * (c) 2011 Dmitriy Kubyshkin <dmitriy@kubyshkin.ru>
 * http://markengine.kubyshkin.ru/
 */

/**
 * This class handles all request processing.
 * @package    MarkEngine
 * @author     Dmitriy Kubyshkin <dmitriy@kubyshkin.ru>
 */
class MarkEngine 
{
  /**
   * Holds path to page templates.
   * @var string
   */
  protected $templatesPath = '/templates';

  /**
   * Holds path to pages.
   * @var string
   */
  protected $pagesPath = '/pages';

  /**
   * Holds absolute path to MarkEngine files on server.
   * @var string
   */
  protected $rootPath;

  /**
   * Holds request uri without query string.
   * @var string
   */
  protected $requestUri;

  /**
   * Holds default page title.
   * @var string
   */
  protected $defaultTitle = '';

  /**
   * Holds current page title.
   * @var string
   */
  protected $currentTitle = '';

  /**
   * Holds meta keywords for current page.
   * @var string
   */
  protected $metaKeywords = '';

  /**
   * Holds meta keywords for current page.
   * @var string
   */
  protected $metaDescription = '';

  /**
   * Constructs MarkEngine object.
   */
  public function __construct()
  {
    // Preparing necessary paths
    $this->rootPath = dirname($_SERVER['SCRIPT_FILENAME']);
    $this->requestUri = str_replace(
      '?'.$_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']
    );
  }

  /**
   * Starts request processing.
   * @return void
   */
  public function start()
  {
    $requestUri = $this->requestUri;
    // If user requested directory index we show index.md file in that folder
    if($requestUri[strlen($requestUri) - 1] == '/')
    {
      $requestUri .= 'index.md';
    }
    else // Else replacing extension sent in request to .md
    {
      $requestUri = preg_replace(
        '/^(.+\.)(php|html?)$/', '$1md', $requestUri
      );
    }

    if(file_exists($page = $this->absolutePagesPath() . $requestUri))
    {
      $this->renderPage($page);
    }
    else
    {
      $this->error404();
    }
  }

  /**
   * Shows error 404 page. If 404.html is present either in templates path
   * or pages path it will be rendered. Otherwise a simple page will be shown.
   * @return void
   */
  protected function error404()
  {
    // If there was an error and headers were already sent we don't want
    // another php notice shown about already sent headers
    if(!headers_sent())
    {
      header("HTTP/1.0 404 Not Found");
    }

    // Searching for user-provided 404 page
    if(file_exists($page = $this->absoluteTemplatesPath() . '/404.html'))
    {
      include $page;
    }
    elseif(file_exists($page = $this->absolutePagesPath() . '/404.html'))
    {
      include $page;
    }
    else // If not found rendering simple one.
    {
      echo "<h1>Error 404</h1><p>Requested page not found.</p>";
    }
  }

  /**
   * Renders page. Accepts path to existing markdown file.
   * @param string $page
   * @return void
   */
  protected function renderPage($page)
  {
    // Transforming page from markdown to html
    require_once dirname(__FILE__) . '/markdown.php';
    $markdown = new Markdown_Parser();
    $content = file_get_contents($page);
    $content = $this->parseMeta($content);
    $content = $markdown->transform($content);

    // If there wasn't specified custom title inside document or otherwise
    // we and there was a header inside a document we use it
    if(empty($this->currentTitle) && !empty($markdown->document_title))
    {
      $this->currentTitle = $markdown->document_title;
    }

    // Outputting header template if present
    if(file_exists($header = $this->absoluteTemplatesPath() . '/header.php'))
    {
      include $header;
    }

    echo $content;

    // Outputting footer template if present
    if(file_exists($footer = $this->absoluteTemplatesPath() . '/footer.php'))
    {
      include $footer;
    }
  }

  /**
   * Parses meta data specified inside document.
   * @param string $content
   * @return string
   */
  protected function parseMeta($content)
  {
    $pattern = '/^\@(\w+) (.+)$/m';
    return preg_replace_callback($pattern, array($this, 'parseMetaCallback'), $content);
  }

  /**
   * Replaces meta data with empty string so the don't get parsed with markdown.
   * @param array $matches
   * @return string
   */
  protected function parseMetaCallback($matches)
  {
    switch($matches[1])
    {
      case 'title':
        $this->setCurrentTitle($matches[2]);
        break;
      case 'keywords':
        $this->setMetaKeywords($matches[2]);
      case 'description':
        $this->setMetaDescription($matches[2]);
    }
    return '';
  }

  /**
   * Sets meta description for current page.
   * @param string $metaDescription
   */
  public function setMetaDescription($metaDescription)
  {
    $this->metaDescription = $metaDescription;
  }

  /**
   * Returns meta description for current page.
   * @return string
   */
  public function metaDescription()
  {
    return $this->metaDescription;
  }

  /**
   * Sets meta description for current page.
   * @param string $metaKeywords
   */
  public function setMetaKeywords($metaKeywords)
  {
    $this->metaKeywords = $metaKeywords;
  }

  /**
   * Returns meta keywords for current page.
   * @return string
   */
  public function metaKeywords()
  {
    return $this->metaKeywords;
  }

  /**
   * Sets default page title.
   * @param string $metaTitle
   */
  public function setDefaultTitle($metaTitle)
  {
    $this->defaultTitle = $metaTitle;
  }

  /**
   * Returns default page title.
   * @return string
   */
  public function defaultTitle()
  {
    return $this->defaultTitle;
  }

  /**
   * Sets default page title.
   * @param string $metaTitle
   */
  public function setCurrentTitle($metaTitle)
  {
    $this->currentTitle = $metaTitle;
  }

  /**
   * Returns default page title.
   * @return string
   */
  public function currentTitle()
  {
    return $this->currentTitle;
  }

  /**
   * Returns full page title.
   * @return string
   */
  public function metaTitle()
  {
    if(!empty($this->defaultTitle))
    {
      return $this->currentTitle.' | '.$this->defaultTitle();
    }
    else
    {
      return $this->currentTitle();
    }
  }

  /**
   * Sets templates path relative to the engine root.
   * @param string $templatesPath
   */
  public function setTemplatesPath($templatesPath)
  {
    $this->templatesPath = $templatesPath;
  }

  /**
   * Returns templates path relative to the engine root.
   * @return string
   */
  public function templatesPath()
  {
    return $this->templatesPath;
  }

  /**
   * Returns absolute path to templates.
   * @return string
   */
  public function absoluteTemplatesPath()
  {
    return $this->rootPath . $this->templatesPath;
  }

  /**
   * Sets pages path relative to the engine root.
   * @param string $pagesPath
   */
  public function setPagesPath($pagesPath)
  {
    $this->pagesPath = $pagesPath;
  }

  /**
   * Returns pages path relative to the engine root.
   * @return string
   */
  public function pagesPath()
  {
    return $this->pagesPath;
  }

  /**
   * Returns absolute path to pages.
   * @return string
   */
  public function absolutePagesPath()
  {
    return $this->rootPath . $this->pagesPath;
  }

  /**
   * Returns request uri without query string.
   * @return string
   */
  public function requestUri()
  {
    return $this->requestUri;
  }

  /**
   * @return string
   */
  public function rootPath()
  {
    return $this->rootPath;
  }
}
