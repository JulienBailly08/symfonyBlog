<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comment;

class ArticleFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $faker = \Faker\Factory::create('fr_FR');

        // créer 3 cat

        for ($i=0;$i<=3;$i++):
            $categorie = new Category();
            $categorie ->setTitle($faker->sentence())
                        ->setDescription($faker->paragraph());
        $manager->persist($categorie);

        //créer entre 4 et 6 articles
            for ($j=1;$j<=mt_rand(4,6);$j++):
                $content ='<p>';
                for($m=0;$m<=mt_rand(3,6);$m++):
                $content = $content.$faker->paragraph().'</p><p>';
                endfor;
                $content = $content.'</p>';

                $article = new Article();
                $article->setTitle($faker->sentence())
                        ->setContent($content)
                        ->setImage($faker->imageUrl())
                        ->setCreatedAt($faker->dateTimeBetween('-6 months'))
                        ->setCategory($categorie);//categorie en cours de creation
                $manager->persist($article);
                
                for ($k=1;$k<=mt_rand(4,10);$k++):
                    
                    $content ='<p>';
                    for($m=0;$m<=mt_rand(1,3);$m++):
                        $content = $content.$faker->paragraph().'</p><p>';
                        endfor;
                    $content = $content.'</p>';

                    $now=new \DateTimeImmutable();
                    $interval= $now->diff($article->getCreatedAt());
                    $days = $interval->days;
                    $duree = '-'.$days.'days';

                    $comment= new Comment;
                    $comment->setAuthor($faker->name())
                            ->setContent($content)
                            ->setCreatedAt($faker->dateTimeBetween($duree))
                            ->setArticle($article);
                    $manager->persist($comment);
                    
                endfor;
            endfor;
        endfor;

        $manager->flush();
    }
}
