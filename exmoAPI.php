<?php

class Exmo extends Exchange
{
    
    private $privateKey = '';
    private $publicKey = '';
    
    
    public function __construct($priv, $pub)
    {
        $this->privateKey = $priv;
        $this->publicKey = $pub;
        
        // check keys permissions
        // ....
        if(!$this->getBalance()) return false;
    }
    public function setSymbols()
    {
        $url = 'https://api.exmo.com/v1/ticker/';
        $json = json_decode(file_get_contents($url));
        
        foreach($json as $key=>$pair)
        {
            $this->symbols[] = $key;
            $this->prices[$key]['high'] = $pair->high;
            $this->prices[$key]['low'] = $pair->low;
            $this->prices[$key]['last'] = $pair->last_trade;
            $this->prices[$key]['bid'] = $pair->buy_price;
            $this->prices[$key]['ask'] = $pair->sell_price;
            $this->prices[$key]['time'] = $pair->timestamp;
        }
    }
    
    public function apiCall($req, $url)
    {
        
        $mt = explode(' ', microtime());
        $req['nonce'] = $mt[1] . substr($mt[0], 2, 6);
        $post_data = http_build_query($req, '', '&');
        $signature = hash_hmac('sha512', $post_data, $this->privateKey);
        $headers = array(
            'Key: ' .$this->publicKey,
            'Sign: ' . $signature
        );
        
        $curl = curl_init($url);
    	curl_setopt($curl, CURLOPT_POST, true);
    	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    	curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    	$response = curl_exec($curl);
    	curl_close($curl);
    	return $response;
    }
    
    public function placeOrder($symbol, $amount, $price, $side)
    {
        $req = array(
            'pair'=>$symbol,
            'quantity'=>$amount,
            'price'=>$price,
            'type'=>$side
        );
        $url = 'https://api.exmo.com/v1/order_create';
        
        return $this->apiCall($req, $url);
    }
    
    public function cancelOrder($id)
    {
        $url = 'https://api.exmo.com/v1/order_cancel';
        $req['order_id'] = $id;
        return $this->apiCall($req, $url);
    }
    
    public function activeOrders()
    {
        $url = 'https://api.exmo.com/v1/user_open_orders';
        return $this->apiCall('', $url);
    }
    
    public function orderStatus($id)
    {
        $url = 'https://api.exmo.com/v1/order_trades';
        $req['order_id'] = $id;
        return $this->apiCall($req, $url);
    }
    
    public function buy($symbol, $amount, $price)
    {
        return $this->placeOrder($symbol, $amount, $price, 'buy');
    }
    
    public function sell($symbol, $amount, $price)
    {
        return $this->placeOrder($symbol, $amount, $price, 'sell');
    }
    
    public function instant($symbol, $amount, $price, $side)
    {
        if($side == 'sell')
        {
            return $this->placeOrder($symbol, $amount, $price, 'market_sell');
        }
        if($side == 'buy')
        {
            return $this->placeOrder($symbol, $amount, $price, 'market_buy');
        }
        return false;
    }
    
    public function getBalance()
    {
        $url = 'https://api.exmo.com/v1/user_info';
        $json = json_decode($res = $this->apiCall('', $url));
        if($json->uid)
        {
            return $json->balances;
        }
        else
        {
            return false;
        }
    }
}