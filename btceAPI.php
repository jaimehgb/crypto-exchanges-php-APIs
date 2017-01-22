<?php

include 'exchange.php';

class Btc_e extends Exchange
{
    private $p_endpoint = ''; # public endpoint
    private $t_endpoint = 'https://btc-e.com/tapi'; # trade endpoint
    private $apiKey = '';
    private $secretKey = '';

    
    public function __construct($key, $secret)
    {
        $this->apiKey = $key;
        $this->secretKey = $secret;
        
        //test request to see if keys are valid
        $rights = $this->permissions;
        if(!$rights) return false;
        if(isset($rights->error)) return false;
        if($rights->info != 1 || $rights->trade != 1) return false;
    }
    
    public function setSymbols()
    {
        $url = 'https://btc-e.com/api/3/info';
        $json = json_decode(file_get_contents($url));
        
        foreach($json->pairs as $pair=>$info)
        {
            $this->symbols[$pair] = $info;
        }
        return;
    }
    
    public function updatePrices()
    {
        $url = 'https://btc-e.com/api/3/ticker/';
        foreach($this->symbols as $pair=>$info)
        {
            $ticker = file_get_contents($url . $pair);
            $obj = json_decode($ticker);
            $this->prices[$pair]['high'] = $obj->$pair->high;
            $this->prices[$pair]['low'] = $obj->$pair->low;
            $this->prices[$pair]['bid'] = $obj->$pair->buy;
            $this->prices[$pair]['ask'] = $obj->$pair->sell;
            $this->prices[$pair]['last'] = $obj->$pair->last;
            $this->prices[$pair]['timestamp'] = $obj->$pair->updated;
        }
        print_r($this->prices);
    }
    
    public function apiCall($payload, $url)
    {
        $payload['nonce'] = time();
        $request = http_build_query($payload, '', '&');
        $signature = hash_hmac("sha512", $request, $this->secretKey);
        
        $curl = curl_init($url);
    	curl_setopt($curl, CURLOPT_POST, true);
    	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Key: " . $this->apiKey,
            'Sign: ' . $signature
        )); 
    	curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    	$response = curl_exec($curl);
    	echo curl_error($curl);
    	curl_close($curl);
        return $response;
    }
    
    public function placeOrder($symbol, $amount, $price, $side)
    {
        $request = array(
            'pair'=>$symbol,
            'amount'=>$amount,
            'rate'=>$price,
            'type'=>$side,
            'method'=>'Trade'
        );
        $url = $this->t_endpoint . '/trade';
        return $this->apiCall($request, $url);
    }
    
    public function cancelOrder($id)
    {
        $request = array(
            'method'=>'CancelOrder',
            'order_id'=>$id
        );
        $url = $this->t_endpoint . '/CancelOrder';
        return $this->apiCall($request, $url);
    }
    
    public function cancelAll()
    {
        $json = json_decode($this->activeOrders());
        if($json->success == 0 || !empty($json->error)) return false;
        $orders = $json->return;
        
        foreach($orders as $id=>$info)
        {
            $res = json_decode($this->cancelOrder($id));
            if($res->error) $errors[] = $res->error;
        }
        if(!empty($errors)) return $errors;
        return true;
    }
    
    public function orderStatus($id)
    {
        $request = array(
            'method'=>'OrderInfo',
            'order_id'=>$id
        );
        $url = $this->t_endpoint . '/OrderInfo';
        return $this->apiCall($request, $url);
    }
    
    public function activeOrders()
    {
        //get active orders first
        $request = array(
            'method'=>'ActiveOrders'
        );
        $url = $this->t_endpoint . '/ActiveOrders';
        return $this->apiCall($request, $url);
    }
    
    public function activePositions()
    {
        
    }
    
    public function permissions()
    {
        $request = array(
            'method'=>'getInfo'    
        );
        $url = $this->t_endpoint . '/getInfo';
        $res = $this->apiCall($request, $url);
        $json = json_decode($res);
        if(!$json) return false;
        if($json->success != 1) return $json;
        return $json->return->rights;
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
        $request = array(
            'method'=>'getInfo'    
        );
        $url = $this->t_endpoint . '/getInfo';
        $res = $this->apiCall($request, $url);
        $json = json_decode($res);
        if(!$json) return false;
        if($json->success != 1) return $json;
        return $json->return->funds;
    }
}
