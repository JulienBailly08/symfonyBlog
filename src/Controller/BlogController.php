<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comment;
use App\Form\CommentType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class BlogController extends AbstractController
{
    public function getCategories(){
        $repo=$this->getDoctrine()->getRepository(Category::class);
        return $repo->findAll();
    }

    #[Route('/blog', name: 'blog')]
    public function index()
    {
        $categories=$this->getCategories();
        $repo=$this->getDoctrine()->getRepository(Article::class);
        $articles = $repo->findAll();

        return $this->render('blog/index.html.twig', [
            'articles'=>$articles,
            'categories'=>$categories
        ]);
    }

    #[Route('/blog/cat/{id}', name: 'blogCat')]
    public function indexBlogByCat($id)
    {   
        $categories=$this->getCategories();
        $repo=$this->getDoctrine()->getRepository(Article::class);
        $articles = $repo->findBy(['category'=>$id]);
  
        if(empty($articles)):
           return $this->redirectToRoute('blog'); // gère le fait qu'une id n'existe pas => redirige sur l'affichage de l'ensemble des articles
        else:
        return $this->render('blog/index.html.twig', [
            'articles'=>$articles,
            'categories'=>$categories
        ]);
        endif;
    }

    #[Route('/', name: 'home')]
    public function home()
    {
        $categories=$this->getCategories();

        return $this->render('blog/home.html.twig',[
            'title'=>'Bienvenue !',
            'categories'=>$categories         
        ]);
    }

    #[Route('blog/{id}/com', name: 'addCom')]
    public function addCom(Article $article, Request $request, EntityManagerInterface $manager)
    {
        $categories=$this->getCategories();
        $form=$this->createForm(CommentType::class);
        $comment=new Comment;     
          
        $form->handleRequest($request);
     

        if($form->isSubmitted() && $form->isValid()):

            $comment=$form->getData();
            $comment->setCreatedAt(new \DateTimeImmutable())
                    ->setArticle($article);
                                              
            $manager->persist($comment);
            $manager->flush();
            return $this->redirectToRoute('blog_show',[
                'id'=> $article->getId()
            ]); 
        endif;

        return $this->render('blog/addComment.html.twig', [
            'formComment' => $form->createView(),
            'categories'=>$categories        
        ]);
    }

    #[Route('/blog/admin/new', name: 'blog_create')]
    #[Route('/blog/admin/{id}/edit', name: 'blog_edit')]
    public function form(Article $article=null, Request $request, EntityManagerInterface $manager) : Response
    {
        $categories=$this->getCategories();
        //Article $article dans la fonction form permet de recuper l'objet Article dont l'id est present dans l'url, si pas d'id=> alors creation simple variable $article=null
        if(!$article): // si $article est null alors creation de l'objet Article avec les propriétés ==null
        $article = new Article;
        endif;
        $form = $this->createFormBuilder($article)
                        ->add('title')
                        ->add('category',EntityType::class,[//ajout categories depuis bd !!
                            'class'=>Category::class,
                            'choice_label'=>'title'
                        ])
                        ->add('content')
                        ->add('image')
                        ->getForm();
        
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()):
            if(!$article->getId()): // si $article ne possède pas d'id
                $article->setCreatedAt(new \DateTimeImmutable());// peuplement du champs de la date
            endif;      
            $manager->persist($article);
            $manager->flush();
            return $this->redirectToRoute('blog_show',['id'=> $article->getId()]);
        endif;

        return $this->render('blog/create.html.twig',[
            'formArticle'=> $form->createView(),
            'editMode'=>$article->getId()!==null, // si l'id de l'article est differrent de null(resultat : true) alors on edit un article existant donc editMode=true :D
            'categories'=>$categories
        ]);
    }

    #[Route('/blog/admin/{id}/erase', name: 'blog_erase')]
    public function erase($id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $article = $this->getDoctrine()->getRepository(Article::class)->find($id);
        if(!$article):
            throw $this->createNotFoundException('L\'article demandé n\'existe pas');
        endif;
        $entityManager->remove($article);
        $entityManager->flush();

        return $this->redirectToRoute('blog'); 
    }

    
    #[Route('/blog/admin/{id}/comerase', name: 'comment_erase')]
    public function eraseCom($id, Request $request)
    {
     
        $entityManager = $this->getDoctrine()->getManager();
        $comment = $this->getDoctrine()->getRepository(Comment::class)->find($id);
 
        $redirect=$comment->getArticle()->getId();
       
        $entityManager->remove($comment);
        $entityManager->flush();

        return $this->redirectToRoute('blog_show',['id'=> $redirect]);
    }

    //#[IsGranted("ROLE_ADMIN")] 
    #[Route('/blog/{id}', name: 'blog_show')]
    public function show($id)
    {
        //$this->denyAccessUnlessGranted('ROLE_ADMIN');
        $categories=$this->getCategories();
        $repo = $this->getDoctrine()->getRepository(Article::class);
        $article = $repo->find($id);
        return $this->render('blog/show.html.twig',[
            'article'=>$article,
            'categories'=>$categories          
        ]);
    }
    
}
