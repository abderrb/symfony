<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{

    /**
     * @Route("/user_inscription", name="user_inscription")
     */
    public function inscription()
    {
        return $this->render('user/inscription.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }
}



