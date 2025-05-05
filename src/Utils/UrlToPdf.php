<?php


namespace App\Utils;


use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class UrlToPdf
{
    /** @var BrowserFactory */
    private $factory;
    /** @var \HeadlessChromium\Browser\ProcessAwareBrowser */
    private $browser;


    public function __construct(private ParameterBagInterface $bag, private LoggerInterface $logger)
    {
    }

    public function init()
    {
        $binary = $this->bag->get('CHROME_BINARY');
        if (!$this->browser) {
            $this->factory = new BrowserFactory($binary);
            $this->browser = $this->factory->createBrowser(['headless' => true, 'disableGpu' => true, 'keepAlive' => false, 'windowSize' => [1920, 1080]]);
        }
    }

    public function generate($url, $filename = null)
    {
        $this->init();
        try {
            $browser = $this->browser;
            $page = $browser->createPage();

            $navigation = $page->navigate($url);

            $navigation->waitForNavigation(Page::DOM_CONTENT_LOADED);


            if (!$filename) {
                $pageTitle = $page->evaluate('document.title')->getReturnValue();
                $slugger = new AsciiSlugger($pageTitle);
                $filename = $slugger->slug($pageTitle . ' ' . sha1($url)) . '.pdf';
            }
            if (!str_ends_with($filename, '.pdf')) {
                $filename .= '.pdf';
            }
//            $this->logger->critical('Generating File => ' . __DIR__ . '/../../public/pdf/' . $filename);
            $page->pdf(['printBackground' => true, 'preferCSSPageSize' => true])->saveToFile(__DIR__ . '/../../public/pdf/' . $filename);
            $browser->close();
            return $filename;
        } catch (\Throwable $e) {
            //            dd($e);
            $this->logger->critical('File ' . $e->getFile() . ' Line ' . $e->getLine() . ' : ' . $e->getMessage());
        }
    }

    public function generateHtml($html, $path)
    {
        $this->init();
        $browser = $this->browser;
        $page = $browser->createPage();
        $page->setHtml($html);
        $page->pdf(['printBackground' => true, 'preferCSSPageSize' => true])->saveToFile($path);
        $browser->close();
        return $path;
    }

    public function generatePath($url, $path)
    {
        $this->init();
        try {
            $browser = $this->browser;
            $page = $browser->createPage();

            $navigation = $page->navigate($url);

            $navigation->waitForNavigation(Page::DOM_CONTENT_LOADED);

//            $this->logger->critical('Generating File => ' . $path);
            $page->pdf(['printBackground' => true, 'preferCSSPageSize' => true])->saveToFile($path);
            $browser->close();
            return $path;
        } catch (\Throwable $e) {
            //            dd($e);
            $this->logger->critical('File ' . $e->getFile() . ' Line ' . $e->getLine() . ' : ' . $e->getMessage());
        }
    }

    public function generateAttestation($url, $filename = null)
    {
        //        dd('o');
        $this->init();

        try {
            $browser = $this->browser;
            $page = $browser->createPage();

            $navigation = $page->navigate($url);

            $navigation->waitForNavigation(Page::DOM_CONTENT_LOADED);

            $pageTitle = $page->evaluate('document.title')->getReturnValue();

            $evaluation = $page->evaluate('document.documentElement.innerHTML');

            // wait for the value to return and get it
            $value = $evaluation->getReturnValue();
            echo $value;
            dd('h');
            exit;
            dd($value);
            if (!$filename) {
                $slugger = new AsciiSlugger($pageTitle);
                $filename = $slugger->slug($pageTitle . ' ' . sha1($url)) . '.pdf';
            }
            if (!str_ends_with($filename, '.pdf')) {
                $filename .= '.pdf';
            }
            dd($filename);
            $this->logger->critical('Generating File => ' . __DIR__ . '/../../public/pdf/' . $filename);
            $page->pdf(['printBackground' => true, 'preferCSSPageSize' => true])->saveToFile(__DIR__ . '/../../public/pdf/' . $filename);
            $browser->close();
            return $filename;
        } catch (\Throwable $e) {
            $this->logger->critical('File ' . $e->getFile() . ' Line ' . $e->getLine() . ' : ' . $e->getMessage());
        }
    }
}
