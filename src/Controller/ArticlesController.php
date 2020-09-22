<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Entity\Articles; // Appelle le modèle pour insérer dans la table articles

class ArticlesController extends AbstractController
{
    /**
     * Page pour ajouter un article
     * @Route("/articles/add", name="articles_add")
     * @IsGranted("ROLE_ADMIN")
     */
    public function add()
    {
        $errors = [];
        $safe = [];
        // Je m'assure que le formulaire ai été envoyé
        if(!empty($_POST)){

            // Permet de retirer les tags HTML et les espaces en début et fin de chaine sur l'ensemble des données du post
            // C'est du nettoyage de données
            // ma variable $safe contiendra exactemment les mêmes données que $_POST (mais clean)
            $safe = array_map('trim', array_map('strip_tags', $_POST));
            
            // On valide les champs
            if(strlen($safe['title']) < 3 || strlen($safe['title']) > 100){
                $errors[] = 'Votre titre doit comporter entre 3 et 100 caractères';
            }
            if(strlen($safe['content']) < 20 || strlen($safe['content']) > 1500){
                $errors[] = 'Votre contenu doit comporter entre 20 et 1500 caractères';
            }
           
            if(isset($_FILES['image']) && !empty($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE){

                if($_FILES['image']['error'] != UPLOAD_ERR_OK){
                    $errors[] = 'Une erreur est survenue lors du transfert de fichier';
                }
                else {
                     // On génère un nouveau nom de fichier
                    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = md5(uniqid()).'.'.$extension;

                    $allowExtensions = ['png', 'jpg', 'jpeg', 'jfif', 'pjpeg', 'pjp'];
                    $allowMimeTypes = ['image/png', 'image/jpeg', 'image/pjpeg'];


                    // On vérifie si l'extension et le type sont absents des tableaux
                    if(!in_array(strtolower($extension), $allowExtensions) 
                        || !in_array($_FILES['image']['type'], $allowMimeTypes)){
                        $errors[] = 'Le type de l\'image est incorrect (PNG ou JPG uniquement)';
                    }
                    else {
                        $sizeMax = 3 * 1024 * 1024; //3 Mo en octet
                         // On vérifie si la taille dépasse le maximum
                        if($_FILES['image']['size'] > $sizeMax){
                            $errors[] = 'L\'image est trop volumineuse (3Mo maximum)';
                        }
                    }

                }
            } // fin isset($_FILES['image']) && !empty($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE
            else {
                //$errors[] = 'Vous devez sélectionner une image';
            }

            
            // Compte le nombre d'éléments dans le tableau $errors
            // Je suis sur que l'utilisateur n'a pas fait d'erreur
            if(count($errors) === 0){
                // Je peux donc sauvegarder mon image
                // Mais également faire mon insertion en base de données
                // Pour l'image, en bdd je ne sauvegarde que le nom ($filename)
                // Et le fichier je l'enregistre dans un répertoire /uploads/

                // $_SERVER['DOCUMENT_ROOT'] : donne le chemin d'accès jusqu'au répertoire /public/
                // C'est le répertoire de mon site web pour les internautes

                if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && isset($filename)){
                    $folderDestination = $_SERVER['DOCUMENT_ROOT'].'assets/uploads/'.$filename;
    
                    // $folderDestination = C:/htdocs/symfony-blog/public/assets/uploads/nom_image.jpg
    
                    if(move_uploaded_file($_FILES['image']['tmp_name'], $folderDestination)){
                        // L'image est bien sauvegardée
                        // Je mets le chemin à partir de public/
                        $filenameInDb = 'assets/uploads/'.$filename;
                    }

                }

                // On appelle le modèle (donc la base de données) et on sauvegarde

                // La variable $em (EntityManager) permet de se connecter à la base de données
                // c'est un peu l'équivalent du "new PDO()"
                $em = $this->getDoctrine()->getManager();

                // Je sélectionne la table dans la quelle je travaille
                $article = new Articles();
                $article->setTitle($safe['title']); // Je défini le titre
                $article->setContent($safe['content']); // Le contenu
                $article->setPicture($filenameInDb ?? null); // L'image
                $article->setCreatedAt(new \DateTime('now'));

                // Equivalent du "prepare()" que vous faisiez en PHP classique
                $em->persist($article);
                $em->flush(); // Equivalent de notre "execute()"

                $this->addFlash('success', 'Bravo, votre article a bien été enregistré');

            }
            else {
                //$this->addFlash('niveau d\'alerte', 'message qu\'on veut afficher');
                /**
                 * La méthode addFlash est l'équivalent de $_SESSION['message]
                 * @see https://symfony.com/doc/current/controller.html#flash-messages
                 * On peut utiliser les niveaux d'alertes suivants. Ces niveaux d'alerte me permette de faire facilement du css :
                 * danger
                 * warning
                 * info
                 * success
                 */
                // La fonction implode() permet de réunir les élements d'un tableau en une chaine (string) séparée par un <br>
                
                $this->addFlash('danger', implode('<br>', $errors));
            }
        }
        

        return $this->render('articles/add.html.twig', [
            'controller_name' => 'ArticlesController',
        ]);
    }

    /**
     * Page listant tous les articles
     * @Route("/articles/list", name="articles_list")
     */
    public function list()
    {
        // Je me connecte ma base de données
        // $em = EntityManager
        $em = $this->getDoctrine()->getManager();

        // J'accède à la table "articles" 
        // La variable $articles, contient tout mes articles

        // Rappelle de la fonction findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
        $articles = $em->getRepository(Articles::class)
                        ->findBy([], ['created_at' => 'DESC']);

        


        return $this->render('articles/list.html.twig', [
            'articles'  => $articles
        ]);
    }

    /**
     * Page de détail d'un article
     * @Route("/article/view/{id_article}", name="article_view")
     */
    public function view(int $id_article)
    {

        $em = $this->getDoctrine()->getManager();

        $article = $em->getRepository(Articles::class)->find($id_article);

        return $this->render('articles/view.html.twig', [
            'article' => $article,
        ]);
    }


    /**
     * Page de mise à jour d'un article
     * @Route("/article/update/{id_article}", name="article_update")
     * @IsGranted("ROLE_ADMIN")
     */
    public function update(int $id_article)
    {
        // Connexion BDD
        $em = $this->getDoctrine()->getManager();
        // Sélection de l'article pour remplir mes champs
        $article = $em->getRepository(Articles::class)->find($id_article);

        $errors = [];
        $safe = [];

        if(!empty($_POST)){

            $safe = array_map('trim', array_map('strip_tags', $_POST));
            
            // On valide les champs
            if(strlen($safe['title']) < 3 || strlen($safe['title']) > 100){
                $errors[] = 'Votre titre doit comporter entre 3 et 100 caractères';
            }
            if(strlen($safe['content']) < 20 || strlen($safe['content']) > 1500){
                $errors[] = 'Votre contenu doit comporter entre 20 et 1500 caractères';
            }
           
            if(isset($_FILES['image']) && !empty($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE){

                if($_FILES['image']['error'] != UPLOAD_ERR_OK){
                    $errors[] = 'Une erreur est survenue lors du transfert de fichier';
                }
                else {
                    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = md5(uniqid()).'.'.$extension;

                    $allowExtensions = ['png', 'jpg', 'jpeg', 'jfif', 'pjpeg', 'pjp'];
                    $allowMimeTypes = ['image/png', 'image/jpeg', 'image/pjpeg'];

                    if(!in_array(strtolower($extension), $allowExtensions) 
                        || !in_array($_FILES['image']['type'], $allowMimeTypes)){
                        $errors[] = 'Le type de l\'image est incorrect (PNG ou JPG uniquement)';
                    }
                    else {
                        $sizeMax = 3 * 1024 * 1024; 
                        if($_FILES['image']['size'] > $sizeMax){
                            $errors[] = 'L\'image est trop volumineuse (3Mo maximum)';
                        }
                    }

                }
            }
            else {
                //$errors[] = 'Vous devez sélectionner une image';
            }


            if(count($errors) === 0){

                // Ok j'ai bien une image au format souhaité (jpg, png) et elle s'est bien téléchargé
                if($_FILES['image']['error'] === UPLOAD_ERR_OK && isset($filename)){
                    $folderDestination = $_SERVER['DOCUMENT_ROOT'].'assets/uploads/'.$filename;
   
                    // Je l'enregistre
                    if(move_uploaded_file($_FILES['image']['tmp_name'], $folderDestination)){
                        $filenameInDb = 'assets/uploads/'.$filename;

                        // Je supprime l'ancien fichier qui ne sera plus utilisé :(
                        // $_SERVER['DOCUMENT_ROOT'] = C:/xampp/htdocs/symfony-blog/public
                        $oldPicture = $_SERVER['DOCUMENT_ROOT'].$article->getPicture();
                        if(file_exists($oldPicture) && !is_dir($oldPicture)){
                            unlink($oldPicture);
                        }
                    }
               }

                //$article = new Article(); // Cette ligne n'est pas nécessaire puisque j'ai déjà sélectionné l'article à mettre à jour

                $article->setTitle($safe['title']); // Je défini le titre
                $article->setContent($safe['content']); // Le contenu
                $article->setPicture($filenameInDb ?? $article->getPicture() ?? null); // L'image
                // Ou une seconde méthode
                /* if(isset($filenameInDb)){
                    $article->setPicture($filenameInDb);
                } */
                $em->persist($article); // ligne optionnelle dans le cas d'un update
                $em->flush();

                
                $this->addFlash('success', 'Bravo, votre article a bien été mis à jour');
                // Redirige vers la page de l'article 
                return $this->redirectToRoute('article_view', ['id_article' => $article->getId()]);
            }
            else {
                $this->addFlash('danger', implode('<br>', $errors));
            }
        }

        return $this->render('articles/update.html.twig', [
            'article' => $article,
        ]);
    }

    /**
     * Page de suppression d'un article
     * @Route("/article/delete/{id_article}", name="article_delete")
     */
    public function delete(int $id_article)
    {
        $em = $this->getDoctrine()->getManager();

        $article = $em->getRepository(Articles::class)->find($id_article);
  
        
        if(!empty($_POST)){

            if(isset($_POST['delete']) && $_POST['delete'] == 'yes'){
                // Je supprime
                $em->remove($article); // on supprime l'article
                $em->flush();


                $this->addFlash('success', 'Votre article a bien été supprimé');
                return $this->redirectToRoute('articles_list');
            }

         }


    
        return $this->render('articles/delete.html.twig', [
            'article' => $article,
        ]);
    }

}