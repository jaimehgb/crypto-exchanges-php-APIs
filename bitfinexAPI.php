<?php

class Exchange
{
    protected $symbols = array();
    protected $prices = array();
    
    public function getSymbols()
    {
        return $this->symbols;
    }
    
    public function getPrices()
    {
        return $this->prices;
    }
}


class Bitfinex extends Exchange
{
    private $endpoint = 'https://api.bitfinex.com/v1/';
    private $privateKey = '';
    private $publicKey = '';
    
    public function __construct($priv, $pub)
    {
        $this->privateKey = $priv;
        $this->publicKey = $pub;
        
        $json = json_decode($this->activeOrders());
        if($json->message == 'Invalid X-BFX-SIGNATURE.' 
        || $json->message == 'Could not find a key matching the given X-BFX-APIKEY.')
        {
            return false;
        }
        return true;
    }
    
    public function setSymbols()
    {
        $url = $this->endpoint . '/symbols';
        $json = json_decode(file_get_contents($url));
        foreach($json as $pair)
        {
            $this->symbols[] = $pair;
        }
    }
    
    public function updatePrices()
    {
        $url = $this->endpoint . '/pubticker/';
        foreach($this->symbols as $pair)
        {
            $url = $this->endpoint . '/pubticker/' . $pair;
            $json = file_get_contents($url);
            $obj = json_decode($json);
            $this->prices[$pair]['high'] = $obj->high;
            $this->prices[$pair]['low']  = $obj->low;
            $this->prices[$pair]['bid']  = $obj->bid;
            $this->prices[$pair]['ask']  = $obj->ask;
            $this->prices[$pair]['last'] = $obj->last_price;
            $this->prices[$pair]['time'] = $obj->timestamp;
        }
    }
    
    public function apiCall($payload, $url)
    {
        $payload = base64_encode(json_encode($payload));
        $signature = hash_hmac("sha384", $payload, $this->privateKey);
        
        $request = array($this->publicKey, $payload, $signature);

        
        $curl = curl_init($url);
    	curl_setopt($curl, CURLOPT_POST, true);
    	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "X-BFX-APIKEY: ".$this->publicKey,
            'X-BFX-PAYLOAD: '.$payload,
            'X-BFX-SIGNATURE: '.$signature
        ));
    	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($request));
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    	$response = curl_exec($curl);
    	curl_close($curl);
    	return $response;
    	
    }
    
    public function placeOrder($symbol, $amount, $price, $side, $type, $ocoorder = false, $buy_price_oco = '')
    {
        $time = time() * 100000;
        $request = array(
            'nonce'=>"$time",
            'request'=>'/v1/order/new',
            'symbol'=>$symbol,
            'amount'=>"$amount",
            'price'=>"$price",
            'exchange'=>'bitfinex',
            'side'=>$side,
            'type'=>"$type",
            'ocoorder'=>$ocoorder,
            'buy_price_oco'=>"$buy_price_oco"
        );
        $url = $this->endpoint . '/order/new';
        return $this->apiCall($request, $url);
    }
    
    public function cancelOrder($id)
    {
        $time = time() * 100000;
        $url = $this->endpoint . "/order/cancel";
        $request = array(
            'request'=>'/v1/order/cancel',
            'order_id'=>$id,
            'nonce'=>"$time"
        );
        return $this->apiCall($request, $url);
    }
    
    public function cancelAll()
    {
        $time = time() * 100000;
        $url = $this->endpoint . '/order/cancel/all';
        $request = array(
            'request'=>'/v1/order/cancel/all',
            'nonce'=>"$time"
        );
        return $this->apiCall($request, $url);
    }
    
    public function orderStatus($id)
    {
        $time = time() * 100000;
        $url = $this->endpoint . "/order/status";
        $request = array(
            'request'=>'/v1/order/status',
            'nonce'=>"$time",
            'order_id'=>$id
        );
        return $this->apiCall($request, $url);
    }
    
    public function activeOrders()
    {
        $time = time() * 100000;
        $url = $this->endpoint . "/orders";
        $request = array(
            'request'=>'/v1/orders',
            'nonce'=>"$time"
        );
        return $this->apiCall($request, $url);
    }
    
    public function activePositions()
    {
        $time = time() * 100000;
        $url = $this->endpoint . "/positions";
        $request = array(
            'request'=>'/v1/positions',
            'nonce'=>"$time"
        );
        return $this->apiCall($request, $url);
    }
    
    public function permissions()
    {
        $time = time() * 100000;
        $url = $this->endpoint . '/key_info';
        $request = array(
            'request'=>'/v1/key_info',
            'nonce'=>"$time"
        );
        return $this->apiCall($request, $url);
    }
    
    public function buy($symbol, $amount, $price, $ocoorder = false, $buy_price_oco = '')
    {
        return $this->placeOrder($symbol, $amount, $price, 'buy', 'exchange limit', $ocoorder, $buy_price_oco);
    }
    
    public function sell($symbol, $amount, $price, $ocoorder = false, $buy_price_oco = '')
    {
        return $this->placeOrder($symbol, $amount, $price, 'sell', 'exchange limit', $ocoorder, $buy_price_oco);
    }
    
    public function getBalance()
    {
        $time = time() * 100000;
        $url = $this->endpoint . '/balances';
        $request = array(
            'request'=>'/v1/balances',
            'nonce'=>"$time"
        );
        return $this->apiCall($request, $url);
    }
}



?>