<?php
 /**
  * Giphy API Library
  *
  * Functions related to GIPHTY
  * 
  * Some functions recreated from http://github.com/rfreebern/giphy-php
  *  
  * @copyright 2016 GLADE
  * @author Probably Lewis
  *
  */

/**
 *
 * Returns the gif in a html image tag
 *
 *
 * @param    string  $giphyID the giphyID
 * @return   string  html image tag
 *
 */
function CreateImage($giphyID)
{
	$giphyArray = getByID($giphyID);
	$gifURL = $giphyArray["data"]["images"]["original"]["url"];

	$htmlReturn = "<img src=\"$gifURL\" alt=\"\">";

	return $htmlReturn;
}

/**
 *
 * Returns the HTML giphy image if any otherwise will return the string given
 *
 *
 * @param    string  $content
 * @return   string  html image or orginal text
 *
 */
function CheckForGiphy($content)
{
	$contentArray = json_decode($content, true);
	if($contentArray)
	{
		if(array_key_exists("giphy", $contentArray))
		{
			$content = CreateImage($contentArray["giphy"]);
		}

	}

	return $content;
}


/**
 *
 * Returns an array of gifs matching the $query
 *
 *
 * @param    string $query search query
 * @param    int  	$limit number to load
 * @param    int  	$offset page number
 * @return   array 	of results matching the $query
 *
 */
function search ($query, $limit = 25, $offset = 0)
{
	$endpoint = '/v1/gifs/search';
    $params = array(
        'q' => urlencode($query),
        'limit' => (int) $limit,
        'offset' => (int) $offset
    );
	return request($endpoint, $params);
}

/**
 *
 * Returns an array of the request gif
 *
 *
 * @param    string $id of the gif to load
 * @return   array 	of gif information
 *
 */
function getByID($id)
{
	$endpoint = "/v1/gifs/$id";
	return request($endpoint);
}

/**
 *
 * Returns an array of the request gif
 *
 *
 * @param    array $ids of the gifs to load (comma seperated)
 * @return   array 	of each gif's information
 *
 */
function getByIDs($ids)
{
	$endpoint = '/v1/gifs';
    $params = array(
        'ids' => implode(',', $ids)
    );
	return request($endpoint, $params);
}

/**
 *
 * Returns an array of gif matching the $query
 *
 *
 * @param    string $query search query 
 * @return   array 	of results matching the $query
 *
 */
function translate ($query) 
{
	$endpoint = '/v1/gifs/translate';
	$params = array(
	    's' => urlencode($query)
	);
	return request($endpoint, $params);
}

/**
 *
 * Returns a random gif matching the $tag given
 * if null will pick a totally random gif
 *
 *
 * @param    string $tag serch query
 * @return   array 	of results matching the $query
 *
 */
function random ($tag = null) 
{
    $endpoint = '/v1/gifs/random';
    $params = array(
        'tag' => urlencode($tag)
    );
	return request($endpoint, $params);
}

/**
 *
 * Returns an array of the trending gifs
 *
 *
 * @param    int $limit limit to display
 * @return   array 	of results
 *
 */
function trending ($limit = 25) 
{
	$endpoint = '/v1/gifs/trending';
	$params = array(
	    'limit' => (int) $limit
	);
	return request($endpoint, $params);
}

/**
 *
 * Processes the request to the giphy api and
 * returns the result as an array
 *
 *
 * @param    string $endpoint the url of the api without parameters
 * @param    array $params parameters to pass to the api
 * @return   array 	of results
 *
 */
function request($endpoint, $params = array())
{
	$params["api_key"] = "dc6zaTOxFJmzC";
	$query = http_build_query($params);
	$url = "http://api.giphy.com" . $endpoint . ($query ? "?$query": "");
	$result = file_get_contents($url);
	return $result ? json_decode($result, true) : false;
}
?>