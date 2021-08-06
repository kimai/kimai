<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Team;
use App\Form\Type\TeamMemberType;
use App\Form\Type\UserType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeamEditForm extends AbstractType
{
    use ColorTrait;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Team|null $team */
        $team = $options['data'] ?? null;

        $builder
            ->add('name', TextType::class, [
                'label' => 'label.name',
                'attr' => [
                    'autofocus' => 'autofocus'
                ],
                // documentation is for NelmioApiDocBundle
                'documentation' => [
                    'type' => 'string',
                    'description' => 'Name of the team',
                ],
        ]);
        $this->addColor($builder);

        $builder->add('members', CollectionType::class, [
            'entry_type' => TeamMemberType::class,
            'entry_options' => [
                'label' => false,
            ],
            'allow_add' => true,
            'by_reference' => false,
            'allow_delete' => true,
            'label' => 'label.user',
            // FIXME documentation
        ]);

        $builder->add('users', UserType::class, [
            'label' => 'add_user.label',
            'help' => 'team.add_user.help',
            'mapped' => false,
            'multiple' => false,
            'expanded' => false,
            'required' => false,
            'ignore_users' => $team !== null ? $team->getUsers() : []
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Team::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'admin_team_edit',
            'attr' => [
                'data-form-event' => 'kimai.teamUpdate'
            ],
        ]);
    }
}
