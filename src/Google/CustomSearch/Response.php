<?php

require_once(dirname(__FILE__).'/Response/Context.php');
require_once(dirname(__FILE__).'/Response/Item.php');
require_once(dirname(__FILE__).'/Response/Promotion.php');
require_once(dirname(__FILE__).'/Response/Query.php');

/**
 * Google_CustomSearch_Response parses and formats the raw API response
 *
 * @author Stephen Melrose <me@stephenmelrose.co.uk>
 */
class Google_CustomSearch_Response
{
    // ------------------------------------------------------
    // Constants
    // ------------------------------------------------------
    
    const KIND = 'customsearch#search';

    // ------------------------------------------------------
    // Properties
    // ------------------------------------------------------

    /**
     * @var Google_CustomSearch_Response_Context
     */
    protected $context;

    /**
     * @var array
     */
    protected $items = array();

    /**
     * @var array
     */
    protected $promotions = array();

    /**
     * @var array
     */
    protected $queries = array();

    // ------------------------------------------------------
    // Constructor
    // ------------------------------------------------------

    /**
     * Creates a new Google_CustomSearch_Response
     *
     * @param string $apiResponse
     */
    public function __construct($apiResponse)
    {
        $this->parse($apiResponse);
    }

    // ------------------------------------------------------
    // Methods
    // ------------------------------------------------------

    /**
     * Parses the raw API response for validity and formats it
     *
     * @param string $apiResponse
     */
    protected function parse($apiResponse)
    {
        if (!is_string($apiResponse) || strlen(trim($apiResponse)) < 1)
        {
            throw new InvalidArgumentException('Invalid response format. Expected non-empty string.');
        }

        $response = @json_decode($apiResponse);
        if (!($response instanceof stdClass))
        {
            throw new RuntimeException('The response data could not be JSON decoded, invalid format.');
        }

        if (!isset($response->kind) || $response->kind != self::KIND)
        {
            throw new RuntimeException(sprintf('Invalid or missing response kind, expected "%s".', self::KIND));
        }

        if (isset($response->queries) && $response->queries instanceof stdClass)
        {
            $this->parseQueries($response->queries);
        }

        if (isset($response->context) && $response->context instanceof stdClass)
        {
            $this->context = new Google_CustomSearch_Response_Context($response->context);
        }

        if (isset($response->promotions) && is_array($response->promotions))
        {
            $this->parsePromotions($response->promotions);
        }

        if (isset($response->items) && is_array($response->items))
        {
            $this->parseItems($response->items);
        }
    }

    /**
     * Parses the "queries" data from the response
     *
     * @param stdClass $queries
     */
    protected function parseQueries(stdClass $queries)
    {
        if (isset($queries->request) && is_array($queries->request) && isset($queries->request[0]) && $queries->request[0] instanceof stdClass)
        {
            $this->queries['request'] = new Google_CustomSearch_Response_Query($queries->request[0]);
        }

        if (isset($queries->nextPage) && is_array($queries->nextPage) && isset($queries->nextPage[0]) && $queries->nextPage[0] instanceof stdClass)
        {
            $this->queries['nextPage'] = new Google_CustomSearch_Response_Query($queries->nextPage[0]);
        }

        if (isset($queries->previousPage) && is_array($queries->previousPage) && isset($queries->previousPage[0]) && $queries->previousPage[0] instanceof stdClass)
        {
            $this->queries['previousPage'] = new Google_CustomSearch_Response_Query($queries->previousPage[0]);
        }
    }

    /**
     * Parses the "promotions" data from the response
     *
     * @param stdClass $promotions
     */
    protected function parsePromotions(array $promotions)
    {
        foreach($promotions as $promotion)
        {
            if (!($promotion instanceof stdClass))
            {
                throw new RuntimeException('Invalid promotion format.');
            }

            $promotionObject = new Google_CustomSearch_Response_Promotion($promotion);

            array_push($this->items, $promotionObject);
        }
    }

    /**
     * Parses the "items" data from the response
     *
     * @param stdClass $items
     */
    protected function parseItems(array $items)
    {
        foreach($items as $item)
        {
            if (!($item instanceof stdClass))
            {
                throw new RuntimeException('Invalid item format.');
            }

            $itemObject = new Google_CustomSearch_Response_Item($item);

            array_push($this->items, $itemObject);
        }
    }

    // ------------------------------------------------------
    // Getters
    // ------------------------------------------------------

    /**
     * Get the response context.
     * 
     * @return Google_CustomSearch_Response_Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Get the response items.
     *
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Get the response promotions.
     *
     * @return array
     */
    public function getPromotions()
    {
        return $this->promotions;
    }

    /**
     * Determines if there is a response context.
     *
     * @return boolean
     */
    public function hasContext()
    {
        return !is_null($this->getContext());
    }

    /**
     * Determines if there are response items.
     *
     * @return boolean
     */
    public function hasItems()
    {
        return count($this->getItems()) > 0;
    }

    /**
     * Determines if there are response promotions.
     *
     * @return boolean
     */
    public function hasPromotions()
    {
        return count($this->getPromotions()) > 0;
    }
}