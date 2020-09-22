<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;


class SmsController extends AbstractController
{



    function httpPost($url, $data)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    /**
     * @Route("/sms", name="sms")
     */
    public function send()
    {
        $errors = [];
        $safe = [];

        // Je m'assure que le formulaire ai été envoyé
        if (!empty($_POST)) {

            // Permet de retirer les tags HTML et les espaces en début et fin de chaine sur l'ensemble des données du post
            // C'est du nettoyage de données
            // ma variable $safe contiendra exactemment les mêmes données que $_POST (mais clean)
            $safe = array_map('trim', array_map('strip_tags', $_POST));
            

            // On valide les champs
            if (!preg_match("#[0][- \.?]?[6-7][- \.?]?([0-9][- \.?]?){8}$#", $safe['phone'])) {
                $errors[] = 'le numéro doit comporter 10 chiffres et commencer par un 06 ou 07';
                
            }
            if (strlen($safe['content']) > 120) {
                $errors[] = 'Votre contenu ne peut dépasser les 120 caractères';
                
            }
            if (count($errors) === 0) {
                
                $token = md5(date('dmY') . 'AxWeb6731@');
                $phone = str_replace(['\'', '-', '.'], '', $safe['phone']);
                $this->httpPost('https://axessweb.io/api/sendSMS', [
                    'receiver'     => $phone,
                    'message'      => $safe['content'],
                    'passphrase'   => $token
                ]);
                $this->addFlash('success', 'Votre sms bien été envoyé');

            }
        
        }

        return $this->render('sms/sms.html.twig');
    }
}
