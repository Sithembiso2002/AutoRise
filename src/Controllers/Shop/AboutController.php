<?php
declare(strict_types=1);

namespace App\Controllers\Shop;

use App\Core\Request;
use App\Core\Response;

final class AboutController
{
    public function index(Request $request): Response
    {
        return Response::html(view('shop.about', [
            'title' => 'About Us | BuyCar',
        ]));
    }

    public function contact(Request $request): Response
    {
        return Response::html(view('shop.contact', [
            'title' => 'Contact Us | BuyCar',
        ]));
    }
}