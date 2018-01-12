<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Sample data to load in the database when running tests or for development
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class AppFixtures extends Fixture
{

    const DEFAULT_PASSWORD = 'kitten';

    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadUsers($manager);
    }

    private function loadUsers(ObjectManager $manager)
    {
        $passwordEncoder = $this->encoder;

        $claraCustomer = new User();
        $claraCustomer
            ->setAlias('Clara Haynes')
            ->setTitle('CFO')
            ->setUsername('clara_customer')
            ->setEmail('clara_customer@example.com')
            ->setRoles(['ROLE_CUSTOMER'])
            ->setAvatar('https://www.gravatar.com/avatar/00000000000000000000000000000000?d=monsterid&f=y')
            ->setPassword($passwordEncoder->encodePassword($claraCustomer, self::DEFAULT_PASSWORD))
        ;
        $manager->persist($claraCustomer);

        $johnUser = new User();
        $johnUser
            ->setAlias('John Doe')
            ->setTitle('Developer')
            ->setUsername('john_user')
            ->setEmail('john_user@example.com')
            ->setRoles(['ROLE_USER'])
            ->setAvatar('https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y')
            ->setPassword($passwordEncoder->encodePassword($claraCustomer, self::DEFAULT_PASSWORD))
        ;
        $manager->persist($johnUser);

        $deactiveUser = new User();
        $deactiveUser
            ->setAlias('Chris Deactive')
            ->setTitle('Developer (left company)')
            ->setUsername('chris_user')
            ->setEmail('chris_user@example.com')
            ->setRoles(['ROLE_USER'])
            ->setAvatar('https://www.gravatar.com/avatar/00000000000000000000000000000000?d=retro&f=y')
            ->setPassword($passwordEncoder->encodePassword($claraCustomer, self::DEFAULT_PASSWORD))
            // inactive for testing user login and UI
            ->setActive(false)
        ;
        $manager->persist($deactiveUser);

        $tonyTeamlead = new User();
        $tonyTeamlead
            ->setAlias('Tony Maier')
            ->setTitle('Head of Development')
            ->setUsername('tony_teamlead')
            ->setEmail('tony_teamlead@example.com')
            ->setRoles(['ROLE_TEAMLEAD'])
            ->setAvatar('https://en.gravatar.com/userimage/3533186/bf2163b1dd23f3107a028af0195624e9.jpeg')
            ->setPassword($passwordEncoder->encodePassword($claraCustomer, self::DEFAULT_PASSWORD))
        ;
        $manager->persist($tonyTeamlead);

        $annaAdmin = new User();
        $annaAdmin
            ->setAlias('Anna Smith')
            ->setTitle('Administrator')
            ->setUsername('anna_admin')
            ->setEmail('anna_admin@example.com')
            ->setRoles(['ROLE_ADMIN'])
            // no avatar to test default image!
            ->setPassword($passwordEncoder->encodePassword($claraCustomer, self::DEFAULT_PASSWORD))
        ;
        $manager->persist($annaAdmin);

        $susanSuper = new User();
        $susanSuper
            // no alias to test the username macros
            ->setTitle('Super Administrator')
            ->setUsername('susan_super')
            ->setEmail('susan_super@example.com')
            ->setRoles(['ROLE_SUPER_ADMIN'])
            ->setAvatar('/bundles/avanzuadmintheme/img/avatar.png')
            ->setPassword($passwordEncoder->encodePassword($claraCustomer, self::DEFAULT_PASSWORD))
        ;
        $manager->persist($susanSuper);

        $manager->flush();
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
