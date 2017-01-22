<?php

include 'exchange.php';

class Bter extends Exchange
{
    private $endpoint = 'http://data.bter.com/api/1';
    private $privateKey = '';
    private $publicKey = '';
    
    public function __construct($priv, $pub)
    {
        $this->privateKey = $priv;
        $this->publicKey = $pub;
        
        // check if keys are valid
        // ....
    }
    
    public function setSymbols()
    {
        $json = file_get_contents($url . '/pairs');
        $obj = json_decode($json);
        
        foreach($obj as $pair)
        {
            $this->symbols[] = $pair;
        }
    }
    
    public function updatePrices()
    {
        $json = file_get_contents($url . '/tickers');
        $obj = json_decode($json);
        
        foreach($obj as $pair=>$info)
        {
            $this->prices[$pair]['high'] = $info->high;
            $this->prices[$pair]['low'] = $info->low;
            $this->prices[$pair]['buy'] = $info->bid;
            $this->prices[$pair]['sell'] = $info->ask;
            $this->prices[$pair]['last'] = $info->last;
        }
    }
    
    public function singleTicker($pair)
    {
        $json = file_get_contents($url . '/ticker/' . $pair);
        return $json;
    }
    
    public function apiCall($request, $url)
    {
        
        $request['nonce'] = time();
        // generate the POST data string
		$post_data = http_build_query($request, '', '&');
		$signature = hash_hmac('sha512', $post_data, $this->privateKey);
	 
		// generate the extra headers
		$headers = array(
			'KEY: '.$this->publicKey,
			'SIGN: '.$signature
		);

        $curl = curl_init($url);
    	curl_setopt($curl, CURLOPT_POST, true);
    	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($request));
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    	$response = curl_exec($curl);
    	curl_close($curl);
    	return $response;		
		
    }
    
    public function placeOrder($symbol, $amount, $price, $side)
    {
        $url = $this->endpoint . '/private/placeOrder';
        $request = array(
            'pair'=> $symbol,
            'type'=>$side,
            'rate'=>$price,
            'amount'=>$amount
        );
        return $this->apiCall($request, $url);
    }
    
    public function cancelOrder($id)
    {
        $url = $this->endpoint . '/private/cancelorder';
        $request = array(
            'order_id'=>$id    
        );
        return $this->apiCall($request, $url);
    }
    
    public function orderStatus($id)
    {
        $url = $this->endpoint . '/private/getorder';
        $request = array(
            'order_id'=>$id    
        );
        return $this->apiCall($request, $url);
    }
    
    public function activeOrders()
    {
        $url = $this->endpoint . '/private/orderlist';
        return $this->apiCall('', $url);
    }
    
    public function buy($symbol, $amount, $price)
    {
        return $this->placeOrder($symbol, $amount, $price, 'buy');
    }
    
    public function sell($symbol, $amount, $price)
    {
        return $this->placeOrder($symbol, $amount, $price, 'sell');
    }
    
    public function getBalance()
    {
        $url = $this->endpoint . '/private/getfunds';
        $res = $this->apiCall('', $url);
        if(json_decode($res)->result == true)
        {
            return $res;
        }
        return false;
    }
    
}
