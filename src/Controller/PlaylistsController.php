<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Playlists;

class PlaylistsController extends AbstractController
{
    /**
     * @Route("/playlist/create", name="playlists_create")
     */
    public function create()
    {
        $errors = [];
        $safe = [];

        if(!empty($_POST)){
            // Je nettoie les données
            // donc $_POST devient $safe
            $safe = array_map('trim', array_map('strip_tags', $_POST));

            if(strlen($safe['name']) < 2 || strlen($safe['name']) > 50){
                $errors[] = 'Votre nom de playlist doit comporter entre 2 et 50 caractères';
            }

            // Je compte mes erreurs
            if(count($errors) === 0){
                // connexion a la db
                $entityManager = $this->getDoctrine()->getManager();

                $playlistsInsert = new Playlists();
                $playlistsInsert->setName($safe['name']);

                $entityManager->persist($playlistsInsert);
                $entityManager->flush();

                $this->addFlash('success', 'Votre playlist a été ajoutée');
            }
            else {
                
                $this->addFlash('danger', implode('<br>', $errors));
            }

        }


        return $this->render('playlists/create.html.twig');
    }

    /**
     * @Route("/playlists/list", name="playlists_list")
     */
    public function list()
    {

        // Connexion bdd
        $em = $this->getDoctrine()->getManager();
        // SELECT * FROM playlists
        $playlists = $em->getRepository(Playlists::class)->findAll();



        return $this->render('playlists/list.html.twig', [
           'playlists' => $playlists,
        ]);
    }

    /**
     * @Route("/playlists/view/{id_de_ma_playlist}", name="playlists_view")
     */
    public function view(int $id_de_ma_playlist)
    {

        // Connexion bdd
        $em = $this->getDoctrine()->getManager();
        // SELECT * FROM playlists
        $playlist = $em->getRepository(Playlists::class)->find($id_de_ma_playlist);

         

        return $this->render('playlists/view.html.twig', [
           'playlist' => $playlist,
        ]);
    }
}