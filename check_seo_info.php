<?php
/**
 * Created by PhpStorm.
 * User: Vadim
 * Date: 16.02.2018
 */

class CSVFileNotFoundException extends \RuntimeException
{
    public function __construct(string $message, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class DomParserException extends \RuntimeException
{
    public function __construct(string $message, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}

abstract class AbstractCSVParser
{

    private $csv_file_path;

    /**
     * AbstractCSVParser constructor
     * @param $csv_file_path
     * @throws CSVFileNotFoundException
     */
    public function __construct($csv_file_path)
    {
        if ($csv_file_path != null && file_exists(realpath($csv_file_path))) {
            $this->csv_file_path = $csv_file_path;
        } else {
            throw new CSVFileNotFoundException("CSV file not found by specified file path: " . $csv_file_path);
        }
    }

    /**
     * Get parsed CVS Array
     *
     * @return array parsed data array
     */
    public function getParsedArray()
    {
        $handle = fopen($this->csv_file_path, "r");
        $csv_data_array = array();

        fgetcsv($handle, 0, ";"); # пропустить headers
        while (($line = fgetcsv($handle, 0, ";")) !== FALSE) {
            $csv_data_array[] = $line;
        }
        fclose($handle);
        return $csv_data_array;
    }
}

class SEOInfoCSVParser extends AbstractCSVParser
{
    /**
     * Get parsed CSV array with parsed fields
     *
     * @return array
     */
    public function getParsedSEOInfoArray()
    {
        $seo_info_array = array();
        $csv_parsed_array = self::getParsedArray();
        foreach ($csv_parsed_array as $parsed_row) {
            array_push($seo_info_array, array($parsed_row["0"], $parsed_row["1"], $parsed_row["2"]));
        }

        return $seo_info_array;
    }
}

class DOMElementFinder
{
    /**
     * Get dom-element from dom-document by xpath expression
     *
     * @param $domDocument
     * @param $xpath
     * @return \DOMNodeList
     */
    public static function getElementByXpath($domDocument, $xpath)
    {
        $dom_xpath_finder = new \DOMXPath($domDocument);
        $found_dom_element = $dom_xpath_finder->query($xpath);

        if ($found_dom_element->length == 0) {
            throw new RuntimeException("Element not found by xpath: " . $xpath);
        }
        return $found_dom_element;
    }
}

class DOMParser
{

    private const TIMEOUT = 30;

    private $options = array(
        'http' => array(
            'method' => "GET",
            'timeout' => self::TIMEOUT
        ));

    /**
     * Add cookies to request options
     *
     * @param $cookies_values_string , example: "user=user123; pass=pass123"
     * @throws DomParserException
     */
    public function addCookies($cookies_values_string)
    {
        if ($cookies_values_string === null) {
            throw new DomParserException("Empty cookies string was pass");
        }

        $this->addHttpOption(array('header' => "Accept-language: en\r\n" .
            "Cookie: " . $cookies_values_string . "\r\n"));
    }

    /**
     * @param $option array
     */
    public function addHttpOption($option)
    {
        array_push($this->options['http'], $option);
    }

    /**
     * Return DOM-document from specified URL
     *
     * @param $url
     * @return bool|DOMDocument
     * @throws DomParserException
     */
    public function getDomFromUrl($url)
    {
        $url = $this->normalizeUrl($url);
        $this->validateUrl($url);
        $htmlString = $this->getHTMLString($url, $this->options);
        $pageDOM = $this->getDOMFromHTMLString($htmlString);

        return $pageDOM;
    }


    /**
     * Return HTML-document string of specified URL
     *
     * @param $url
     * @param $options
     * @return string
     * @throws DomParserException
     */
    private function getHTMLString($url, $options)
    {
        $context = stream_context_create($options);
        $htmlString = file_get_contents($url, false, $context);
        if ($htmlString === false) {
            throw new DomParserException("Error while getting content from url: " . $url);
        }
        return $htmlString;
    }

    /**
     * Add protocol to url, if it`s not indicated
     *
     * @param $url
     *
     * @return string
     */
    private function normalizeUrl($url)
    {
        $urlScheme = parse_url($url, PHP_URL_SCHEME);
        if (($urlScheme === false) || is_null($urlScheme)) {
            $url = 'http://' . $url;
        }
        return $url;
    }

    /**
     * Validate specific url
     *
     * @param $url
     *
     * @throws DomParserException
     */
    private function validateUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new DomParserException("Given url is not valid: " . $url);
        }
    }


    /**
     * Get DOM Document from HTML string.
     *
     * @param $string
     *
     * @return bool|DOMDocument
     */
    private function getDOMFromHTMLString($string)
    {
        if (empty($string)) {
            return false;
        }
        $pageDOM = new DOMDocument(null, 'UTF-8');
        libxml_use_internal_errors(true); # disable err_reporting
        $pageDOM->loadHTML(mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8'));

        return $pageDOM;
    }
}


class Logger
{
    public static function info($message)
    {
        echo($message);
        echo("\n");
    }

    public static function print_errors($errors)
    {
        foreach ($errors as $error) {
            self::info($error);
        }
    }
}

class Assertions
{
    public static $title_checks_errors = array();
    public static $description_checks_errors = array();

    public static $title_assertions_count = 0;
    public static $description_assertions_count = 0;

    /**
     * Assert function
     *
     * @param $expected
     * @param $actual
     * @param $check_type
     * @param $check_url
     */
    public static function assert($expected, $actual, $check_type, $check_url)
    {
        $error_message = null;
        $check_type_prefix_log = "Check " . $check_type;

        if ($expected === $actual) {
            $isCheckPassed = true;

            Logger::info($check_type_prefix_log . "........................PASSED");
        } else {
            $isCheckPassed = false;

            Logger::info($check_type_prefix_log . "........................FAILED");
            $error_message = "\n\nFailed check: " . $check_type . "\nURL: " . $check_url . "\nExpected: " . $expected . "\nActual: " . $actual;
        }

        switch ($check_type) {
            case "title":
                self::$title_assertions_count++;
                if (!$isCheckPassed) array_push(self::$title_checks_errors, $error_message);
                break;
            case "description":
                self::$description_assertions_count++;
                if (!$isCheckPassed) array_push(self::$description_checks_errors, $error_message);
                break;
            default:
                throw new RuntimeException("Unknown check type: " . $check_type);
        }
    }

    /**
     * @param $check_type
     * @param $assertions_count
     * @param $errors_array
     */
    public static function getResults($check_type, $assertions_count, $errors_array)
    {
        Logger::info("\n\n############################### " . (strtoupper($check_type)) . " CHECKS REPORT ###############################");
        Logger::info((strtoupper($check_type)) . " checks count: " . self::$title_assertions_count);
        $passed_checks = $assertions_count - count($errors_array);
        Logger::info("Passed checks: " . $passed_checks . " (" . (($passed_checks / $assertions_count) * 100) . "%)");
        Logger::info("Failed checks: " . count($errors_array));
        Logger::print_errors($errors_array);
        Logger::info("################################################################");
    }
}

/**
 * Class Main
 */
class Main
{
    /**
     * check seo data
     */
    public static function checkAll()
    {
        $options = getopt("f:c:");

        $seo_info_parser_instance = new SEOInfoCSVParser($options["f"]);
        $seo_info_parsed_data = $seo_info_parser_instance->getParsedSEOInfoArray();

        $domParser = new DOMParser();
        $domParser->addCookies($options["c"]);

        for ($i = 0; $i < count($seo_info_parsed_data); $i++) {
            $currentUrl = $seo_info_parsed_data[$i][0];
            $seoTitle = $seo_info_parsed_data[$i][1];
            $seoDescription = $seo_info_parsed_data[$i][2];

            $currentDomDocument = $domParser->getDomFromUrl($currentUrl);

            Logger::info("\nCheck: " . ($i + 1) . "/" . count($seo_info_parsed_data));
            Logger::info("URL: " . $currentUrl);

            $titleDomElement = DOMElementFinder::getElementByXpath($currentDomDocument, "//title");
            $titleDomElementText = $titleDomElement->item(0)->textContent;
            Assertions::assert($seoTitle, $titleDomElementText, "title", $currentUrl);

            $descriptionElement = DOMElementFinder::getElementByXpath($currentDomDocument, "//meta[@name='description']");
            $descriptionElementText = $descriptionElement->item(0)->getAttribute("content");
            Assertions::assert($seoDescription, $descriptionElementText, "description", $currentUrl);
        }

        Logger::info("\n================================ CHECKS COMPLETE ! ================================");
        Assertions::getResults("title", Assertions::$title_assertions_count, Assertions::$title_checks_errors);
        Assertions::getResults("description", Assertions::$description_assertions_count, Assertions::$description_checks_errors);
    }
}

Main::checkAll();