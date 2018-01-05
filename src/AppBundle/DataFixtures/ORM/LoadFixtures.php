<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sample data to load in the database when running tests or for development
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class LoadFixtures implements FixtureInterface, ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadUsers($manager);
    }

    private function loadUsers(ObjectManager $manager)
    {
        $passwordEncoder = $this->container->get('security.password_encoder');

        $claraCustomer = new User();
        $claraCustomer->setAlias('Clara Haynes');
        $claraCustomer->setTitle('CFO');
        $claraCustomer->setUsername('clara_customer');
        $claraCustomer->setEmail('clara_customer@example.com');
        $claraCustomer->setRoles(['ROLE_CUSTOMER']);
        $encodedPassword = $passwordEncoder->encodePassword($claraCustomer, 'kitten');
        $claraCustomer->setPassword($encodedPassword);
        $manager->persist($claraCustomer);

        $johnUser = new User();
        $johnUser->setAlias('John Doe');
        $johnUser->setTitle('Lead Developer');
        $johnUser->setUsername('john_user');
        $johnUser->setEmail('john_user@example.com');
        $johnUser->setRoles(['ROLE_USER']);
        $encodedPassword = $passwordEncoder->encodePassword($johnUser, 'kitten');
        $johnUser->setPassword($encodedPassword);
        $manager->persist($johnUser);

        $tonyTeamlead = new User();
        $tonyTeamlead->setAlias('Tony Maier');
        $tonyTeamlead->setTitle('Head of Development');
        $tonyTeamlead->setUsername('tony_teamlead');
        $tonyTeamlead->setEmail('tony_teamlead@example.com');
        $tonyTeamlead->setRoles(['ROLE_TEAMLEAD']);
        $encodedPassword = $passwordEncoder->encodePassword($tonyTeamlead, 'kitten');
        $tonyTeamlead->setPassword($encodedPassword);
        $manager->persist($tonyTeamlead);

        $annaAdmin = new User();
        $annaAdmin->setAlias('Anna Smith');
        $annaAdmin->setTitle('Administrator');
        $annaAdmin->setUsername('anna_admin');
        $annaAdmin->setEmail('anna_admin@example.com');
        $annaAdmin->setRoles(['ROLE_ADMIN']);
        $encodedPassword = $passwordEncoder->encodePassword($annaAdmin, 'kitten');
        $annaAdmin->setPassword($encodedPassword);
        $manager->persist($annaAdmin);

        $susanSuper = new User();
        $susanSuper->setAlias('Susan Sanchez');
        $susanSuper->setTitle('Super Administrator');
        $susanSuper->setUsername('susan_super');
        $susanSuper->setEmail('susan_super@example.com');
        $susanSuper->setRoles(['ROLE_SUPER_ADMIN']);
        $encodedPassword = $passwordEncoder->encodePassword($susanSuper, 'kitten');
        $susanSuper->setPassword($encodedPassword);
        $manager->persist($susanSuper);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    protected function getPhrases()
    {
        return [
            'Lorem ipsum dolor sit amet consectetur adipiscing elit',
            'Pellentesque vitae velit ex',
            'Mauris dapibus risus quis suscipit vulputate',
            'Eros diam egestas libero eu vulputate risus',
            'In hac habitasse platea dictumst',
            'Morbi tempus commodo mattis',
            'Ut suscipit posuere justo at vulputate',
            'Ut eleifend mauris et risus ultrices egestas',
            'Aliquam sodales odio id eleifend tristique',
            'Urna nisl sollicitudin id varius orci quam id turpis',
            'Nulla porta lobortis ligula vel egestas',
            'Curabitur aliquam euismod dolor non ornare',
            'Sed varius a risus eget aliquam',
            'Nunc viverra elit ac laoreet suscipit',
            'Pellentesque et sapien pulvinar consectetur',
        ];
    }

    protected function getRandomPhrase()
    {
        return $this->getRandomPostTitle();
    }

    private function getRandomPostTitle()
    {
        $titles = $this->getPhrases();

        return $titles[array_rand($titles)];
    }
}
