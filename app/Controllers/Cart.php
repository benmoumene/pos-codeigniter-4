<?php

/**
 *
 * Admin is main dashboard
 */
namespace App\Controllers;

use App\Libraries\Candle\CandleAuth as Auth;
use App\Libraries\Candle\CandleModel as Model;

use  \Config\Services;

class Cart extends CandleController
{
    private $session = null;
    
    // models
    private $products = null;
    private $orders  = null;
    private $users = null;
    public function __construct()
    {
        parent::__construct();
        $this->session = Services::session();

        //assign model instance
        $this->products = Model::name("products");
        $this->users = Model::name("user");
        $this->orders = Model::name("orders");
    }
///////////////////////////////////////////////////////////////////////////////////////////
   
    public function get_cart()
    {
        $arr = $this->session->get("products");
        return $this->response->setJSON($arr);
    }
///////////////////////////////////////////////////////////////////////////////////////////
    public function delete() {
       //get shopping cart from sesssion
        $shopping_cart = $this->session->get("products");

        if ($this->request->getMethod() == "post") {
            foreach ($shopping_cart as $keys=>$values) 
            {
                if ($values["product_id"] == $this->request->getVar("item_id") ) 
                {
                    unset($shopping_cart[$keys]);
                }
            }
            $this->session->set(["products" => $shopping_cart]);    
           // var_dump($shopping_cart);
            return redirect()->back();
        }

      
    }
////////////////////////////////////////////////////////////////////////////////////////////////
    public function addtocart()
    {
        $data = [];

        if ($this->request->getMethod() == "post") {
            $price = (double) $this->request->getPost("price");
            $qty = (double) $this->request->getPost("qty");

            $data[] = [
                "product_id"    => $this->request->getPost("product_id"),
                "product_name"  => $this->request->getPost("product_name"),
                "quantity"      => $qty,
                "price"         => $price,
                "subtotal"      => $qty * $price,
            ];

            // put products under products object (json)
            $arr = [];
            $arr["products"] = $data;
            
            $shopping_cart= $this->session->get("products"); 

            if ($shopping_cart == null) {
                $shopping_cart = [];
            }

            // delete if product already exist
            foreach ($shopping_cart as $keys=>$values) 
            {
                if ($values["product_id"] == $data[0]["product_id"] ) 
                {
                    unset($shopping_cart[$keys]);
                }
            }

            if ( $shopping_cart == null  ) {
                $this->session->set($arr);
            } else {
                $this->session->push("products",$data);
            }
            
            // put above array into session
            //$this->session->set($arr);
            $this->response->setJSON($arr);
        }
        return redirect()->back();
    }
//////////////////////////////////////////////////////////////////////////////////////////////
    // public function delete_cart($sl)
    // {
    //     //get products from session
    //     $products = $this->session->get("products");
        
    //     array_splice($products, $sl, 1);
        
    //     $data["products"] = $products;
        
    //     // put above array into session
    //     $this->session->set($data);
    //     return $this->response->setJSON($products);
    // }
   
////////////////////////////////////////////////////////////////////////////////////////
    public function clear()
    {
        // temp delete
        $this->session->remove("products");
    }
////////////////////////////////////////////////////////////////////////////////////////
    public function cart()
    {
        $view = $this->getTwigViewName(__FUNCTION__);
        return $this->twig->render($view, compact('products'));
    }

////////////////////////////////////////////////////////////////////////////////////////
    public function place_order()
    {
        $user = $this->users->find(Auth::auth()->id);

        // get products from session
        $products = $this->session->get("products");

        $total = 0;
        foreach ($products as $product) {
            $total += $product["subtotal"];
        }

        if ($this->request->getMethod() == "post") {
            
            // get order details from post
            // and put into {order} entity
            $order_details = $this->request->getPost();
            $order = new \App\Entities\Order($order_details);
            //-------------------------------------------------
            
            // assign order->user_id from $user details
            $order->user_id = $user->id;
           
            $order->status = 'Pending';
    
            $this->orders->save($order);
    
            $order->id = $this->orders->insertID();
    
            $product = new \App\Entities\OrderProduct();
            
           
            for ($i = 0; $i < count($products); $i++) {

                $product->order_id = $order->id;
                $product->product_name = $products[$i]["product_name"];
                $product->price = $products[$i]["price"];
                $product->quantity = $products[$i]["quantity"];
                $product->subtotal = $products[$i]["subtotal"];
    
                Model::name("orderproduct")->save($product);
            }
            // empty the shopping cart
            $this->session->set("products",[]);
        } // if method is post



        $view = $this->getTwigViewName(__FUNCTION__);
        return $this->twig->render($view, compact('user', 'total'));
    }
/////////////////////////////////////////////////////////////////////////////////////

}