<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Songs;
use App\Entity\Playlists;

class SongsController extends AbstractController
{

    public $allowLinks = ['www.youtube.com', 'youtube.com', 'm.youtube.com', 'youtu.be'];


    /**
     * @Route("/songs/add", name="songs_add")
     */
    public function add()
    {
        $errors = [];
        $safe = [];
        
        // Connexion bdd
        $em = $this->getDoctrine()->getManager();
        // SELECT * FROM playlists
        $playlists = $em->getRepository(Playlists::class)->findAll();
        
        if(!empty($_POST)){

            // Je nettoie les données
            $safe = array_map('trim', array_map('strip_tags', $_POST));

            if(strlen($safe['title']) < 2 || strlen($safe['title']) > 50){
                $errors[] = 'Votre titre de chanson doit comporter entre 2 et 50 caractères';
            }

            // Cette erreur, dans une utilisation normale elle ne s'affichera pas
            // Je sélectionne la playlist dans la base de données.. si elle n'existe pas $selected_playlist sera égal à "null"
            // SELECT * FROM playlists WHERE id = :id_posté
            if(isset($safe['playlist']) && !empty($safe['playlist'])){
                $selected_playlist = $em->getRepository(Playlists::class)->find($safe['playlist']);
                if(empty($selected_playlist)){
                    $errors[] = 'La playlist sélectionnée n\'existe pas';
                }
            }
            else {
                $errors[] = 'Vous devez sélectionner une playlist';
            }


            if(empty($safe['video'])){
                $errors[] = 'Vous devez saisir un lien Youtube';
            }
            else if(!filter_var($safe['video'], FILTER_VALIDATE_URL) 
                    || !in_array(parse_url($safe['video'], PHP_URL_HOST), $this->allowLinks)){
                $errors[] = 'Votre lien est invalide, seuls les liens vers Youtube sont autorisés';
            }


            if(isset($_FILES['audio']) && !empty($_FILES['audio']) && $_FILES['audio']['error'] != UPLOAD_ERR_NO_FILE){

                if($_FILES['audio']['error'] != UPLOAD_ERR_OK){
                    $errors[] = 'Une erreur est survenue lors du transfert de fichier';
                }
                else {
                     // On génère un nouveau nom de fichier
                    $extension = pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION);
                    $filename = md5(uniqid()).'.'.$extension;

                    $allowExtensions = ['mp3', 'mpeg'];
                    $allowMimeTypes = ['audio/mp3', 'audio/mpeg'];


                    // On vérifie si l'extension et le type sont absents des tableaux
                    if(!in_array(strtolower($extension), $allowExtensions) 
                        || !in_array($_FILES['audio']['type'], $allowMimeTypes)){
                        $errors[] = 'Le type du fichier est incorrect (MP3 ou MPEG uniquement)';
                    }
                    else {
                        $sizeMax = 10 * 1024 * 1024; // 10 Mo en octet
                         // On vérifie si la taille dépasse le maximum
                        if($_FILES['audio']['size'] > $sizeMax){
                            $errors[] = 'Le fichier audio est trop volumineux (10Mo maximum)';
                        }
                    }
                }
            }


            // Je compte mes erreurs
            if(count($errors) === 0){

                if(isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK && isset($filename)){
                    $folderDestination = $_SERVER['DOCUMENT_ROOT'].'assets/uploads/'.$filename;
    
                    // $folderDestination = C:/htdocs/symfony-blog/public/assets/uploads/audio.mp3
    
                    if(move_uploaded_file($_FILES['audio']['tmp_name'], $folderDestination)){
                        // Le fichier audio est bien sauvegardé
                        // Je mets le chemin à partir de public/
                        $filenameInDb = 'assets/uploads/'.$filename;
                    }

                    
                }

                $parse_url = (parse_url($safe['video']));

                //Je traite les liens //www.youtube.com et m.youtube.com
                if(strpos($parse_url['host'], 'youtube.com') !== false){
                    
                    //La fonction parse_str() me permet de parset une chaîne de caractère en get.
                    
                    parse_str($parse_url['query'], $result);

                    $identCodeYoutube = $result['v'];
                }
                // Les liens "partager"
                elseif($parse_url['host'] === 'youtu.be'){
                    $identCodeYoutube = substr($parse_url['path'], 1); 
                }


                //www.youtube.com
                //m.youtube.com
            

                
                $songsInsert = new Songs();
                $songsInsert->setTitle($safe['title']);
                $songsInsert->setPlaylist($selected_playlist); // Je passe la playlist sélectionnée à mon insertion
                $songsInsert->setVideo($identCodeYoutube);
                $songsInsert->setAudio($filenameInDb ?? null);

                

                $em->persist($songsInsert);
                $em->flush();

                $this->addFlash('success', 'Votre chanson a été ajoutée');
            }
            else {
                $this->addFlash('danger', implode('<br>', $errors));
            }

        }



        return $this->render('songs/add.html.twig', [
            'playlists' => $playlists,

        ]);
    }


    /**
     * @Route("/songs/view/{id_song}", name="songs_view")
     */
    public function view(int $id_song)
    {

        $em = $this->getDoctrine()->getManager();
        $song = $em->getRepository(Songs::class)->find($id_song);

        dd($song->getPlaylist()->getName());

        return $this->render('songs/view.html.twig', [
            'song' => $song,

        ]);
    }

    /**
     * @Route("/songs/update/{$id_song}", name="songs_update")
     */

    function updateAction(int$id_song)
    {
        $id = ['id'];
        $em = $this->getDoctrine()->getManager();
        $song = $em->getRepository(Songs::class)->find($id_song);
 
        if (!$song) {
            throw $this->createNotFoundException(
                'Aucun son trouvé pour ce titre : '.$id_song
            );
        }
 
        
        //$songsInsert->setTitle($song->getTitle);
        //$songsInsert->setPlaylist($song->getPlaylist); // Je passe la playlist sélectionnée à mon insertion
        //$songsInsert->setVideo($song->getVideo);
        //$songsInsert->setAudio($song->getAudio);
 
        $form = $this->createFormBuilder($song)
            ->add('Titre', 'title')
            ->add('Playlist', 'playlist')
            ->add('audio', 'audio')
            ->add('video', 'video')
            ->getForm();
 
    
            return $this->render('songs/update.html.twig', [
                'song' => $song,
            ]);

        }
    }



