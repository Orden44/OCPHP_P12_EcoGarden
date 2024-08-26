<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Conseil;
use \DateTime;
use \DateInterval;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ConseilFixtures extends Fixture implements DependentFixtureInterface
{
    public const CONSEIL_1 = 'conseil 1';
    public const CONSEIL_2 = 'conseil 2';
    public const CONSEIL_3 = 'conseil 3';
    public const CONSEIL_4 = 'conseil 4';
    public const CONSEIL_5 = 'conseil 5';

    public function load(ObjectManager $manager): void
    {        
        $faker = (new \Faker\Factory())::create('fr_FR');

        // Création des conseils
        $conseil1 = new Conseil();
        $conseil1->setTitle($faker->sentence())
            ->setDescription($faker->paragraph(2))
            ->setUser($this->getReference(UserFixtures::USER_1))
            ->setCreatedate(new DateTime('now -' . mt_rand(1,6). ' month'));
        $manager->persist($conseil1);
        $this->addReference(self::CONSEIL_1, $conseil1);

        $conseil2 = new Conseil();
        $conseil2->setTitle($faker->sentence())
            ->setDescription($faker->paragraph(2))
            ->setUser($this->getReference(UserFixtures::USER_2))
            ->setCreatedate(new DateTime('now -' . mt_rand(1,10). ' day'))
            ->setUpdatedate((new DateTime())->add(new DateInterval('P4D')));

        $manager->persist($conseil2);
        $this->addReference(self::CONSEIL_2, $conseil2);

        $conseil3 = new Conseil();
        $conseil3->setTitle($faker->sentence())
            ->setDescription($faker->paragraph(2))
            ->setUser($this->getReference(UserFixtures::USER_2))
            ->setCreatedate(new DateTime('now -' . mt_rand(1,6). ' month'));
        $manager->persist($conseil3);
        $this->addReference(self::CONSEIL_3, $conseil3);

        $conseil4 = new Conseil();
        $conseil4->setTitle($faker->sentence())
            ->setDescription($faker->paragraph(2))
            ->setUser($this->getReference(UserFixtures::USER_1))
            ->setCreatedate(new DateTime('now -' . mt_rand(1,10). ' day'))
            ->setUpdatedate((new DateTime())->add(new DateInterval('P7D')));
        $manager->persist($conseil4);
        $this->addReference(self::CONSEIL_4, $conseil4);

        $conseil5 = new Conseil();
        $conseil5->setTitle($faker->sentence())
            ->setDescription($faker->paragraph(2))
            ->setUser($this->getReference(UserFixtures::USER_2))
            ->setCreatedate(new DateTime());
        $manager->persist($conseil5);
        $this->addReference(self::CONSEIL_5, $conseil5);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class
        ];
    }
}
