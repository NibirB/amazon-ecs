<?php

namespace Dawson\AmazonECS;

use InvalidArgumentException;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class AmazonECS
{
	use Helpers;

	/**
	 * Base API URL
	 * 
	 * @var string
	 */
	protected $baseUrl = 'webservices.amazon';

	/**
	 * Available Locales
	 * 
	 * @var array
	 */
	protected $locales = [
		'co.uk', 'com', 'ca', 'com.br', 'de', 'es', 'fr', 'in', 'it', 'co.jp', 'com.mx'
	];

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->validConfig();

		$this->response 		= null;

		$this->access_key 		= config('amazon.access_key');
		$this->secret_key 		= config('amazon.secret_key');
		$this->associate_tag	= config('amazon.associate_tag');
		$this->locale			= config('amazon.locale');
		$this->response_group	= str_replace(' ', '', config('amazon.response_group'));
		$this->client 			= new Client;
	}

	/**
	 * Amazon Product Advertisting API - ItemSearch
	 * 
	 * @param  string $query
	 * @return response
	 */
	public function search($query)
	{
		$query		= rawurlencode($query);
		$params 	= $this->params(['Keywords' => $query, 'SearchIndex' => config('amazon.search_index'), 'ResponseGroup' => $this->response_group]);
		$string 	= $this->buildString($params);
		$signature 	= $this->signString($string);
		$url 		= $this->url($params, $signature);

		try {
			$this->response = $this->client->get($url)->getBody();
			return $this;
		} catch(ClientErrorResponseException $e) {
			return $e->getResponse();
		}
	}
	
	/**
	 * Amazon Product Advertisting API - Search by ISBN
	 *
	 * @param  string $query
	 * @return response
	 */

	public function isbn($query)
	{
		$query		= rawurlencode($query);
		$params 	= $this->params(['Keywords' => $query, 'IdType' => 'ISBN', 'ItemId' => $query, 'SearchIndex' => config('amazon.search_index'), 'ResponseGroup' => 'Large']);
		$string 	= $this->buildString($params);
		$signature 	= $this->signString($string);
		$url 		= $this->url($params, $signature);

		try {
			$this->response = $this->client->get($url)->getBody();
			return $this;
		} catch(ClientErrorResponseException $e) {
			return $e->getResponse();
		}
	}


	/**
	 * Amazon Product Advertisting API - ItemLookup
	 * 
	 * @param  string $id
	 * @return response
	 */
	public function lookup($id)
	{
		$params 	= $this->params(['ItemId' => $id, 'ResponseGroup' =>  $this->response_group], 'ItemLookup');
		$string 	= $this->buildString($params);
		$signature 	= $this->signString($string);
		$url 		= $this->url($params, $signature);

		try {
			$this->response = $this->client->get($url)->getBody();
			return $this;
		} catch(ClientErrorResponseException $e) {
			return $e->getResponse();
		}
	}

	/**
	 * Returns the response as XML
	 * 
	 * @return Response
	 */
	public function xml()
	{
		return simplexml_load_string($this->response);
	}

	/**
	 * Returns the response as JSON
	 * 
	 * @return Response
	 */
	public function json()
	{
		$xml  = simplexml_load_string($this->response);
		$json = json_encode($xml);
		$json = json_decode($json, true);

		return $json;
	}

	/**
	 * Determines if the configuration was valid.
	 * 
	 * @return InvalidException
	 */
	private function validConfig()
	{
		if(empty(config('amazon.access_key')) || empty(config('amazon.secret_key')))
		{
			throw new InvalidArgumentException('No Access Key or Secret Key has been set.');
		}

		if(!in_array(config('amazon.locale'), $this->locales))
		{
			throw new InvalidArgumentException(
				sprintf(
					'You have configured an invalid locale "%s". Possible locales are: %s',
					config('amazon.locale'),
					implode(', ', $this->locales)
				)
			);
		}
	}
}
