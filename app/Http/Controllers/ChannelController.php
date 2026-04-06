<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChannelController extends Controller
{
    private $apiToken;

    public function __construct()
    {
        $this->apiToken = env('CHANNEX_TOKEN');

    }
    public function index(){
        $token = $this->apiToken;
        return view('admin.channel.index', compact('token'));
    }
}
