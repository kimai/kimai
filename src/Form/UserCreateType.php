<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Form\Type\TeamType;
use App\Form\Type\UserRoleType;
use App\Form\Type\YesNoType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used to create Users.
 */
class UserCreateType extends UserEditType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('username', null, [
            'label' => 'username',
            'required' => true,
            'attr' => [
                'autofocus' => 'autofocus'
            ],
        ]);

        $builder->add('plainPassword', RepeatedType::class, [
            'required' => true,
            'type' => PasswordType::class,
            'first_options' => [
                'label' => 'password',
                'attr' => ['autocomplete' => 'new-password'],
                'block_prefix' => 'secret'
            ],
            'second_options' => [
                'label' => 'password_repeat',
                'attr' => ['autocomplete' => 'new-password'],
                'block_prefix' => 'secret'
            ],
        ]);

        parent::buildForm($builder, $options);

        if ($options['include_teams'] === true) {
            $builder->add('teams', TeamType::class, [
                'multiple' => true,
                'expanded' => false,
                'required' => false,
            ]);
        }

        if ($options['include_roles'] === true) {
            $builder->add('roles', UserRoleType::class, [
                'multiple' => true,
                'expanded' => false,
                'required' => false,
            ]);
        }

        $builder->add('requiresPasswordReset', YesNoType::class, [
            'label' => 'force_password_change',
            'help' => 'force_password_change_help',
            'required' => false,
        ]);

        $builder->add('billable', ChoiceType::class, [
            'label' => 'Billable Status',
            'choices' => [
                'Yes' => true,
                'No' => false,
            ],
            'data' => false,
            'required' => true,
            'mapped' => true,
        ]);

        $builder->add('position', ChoiceType::class, [
            'label' => 'Position',
            'choices' => [
                'Senior Data Science Analyst - Annotation' => 'Senior Data Science Analyst - Annotation',
                'QA Lead' => 'QA Lead',
                'Lead Data Science Analyst - Annotation' => 'Lead Data Science Analyst - Annotation',
                'Product Manager' => 'Product Manager',
                'Lead Engineer' => 'Lead Engineer',
                'Data Science Analyst - Annotation' => 'Data Science Analyst - Annotation',
                'Assistant Manager - Data Science Analyst - Annotation' => 'Assistant Manager - Data Science Analyst - Annotation',
                'Senior Manager - Annotation' => 'Senior Manager - Annotation',
                'QA Engineer' => 'QA Engineer',
                'Senior Manager - HR' => 'Senior Manager - HR',
                'Senior Software Engineer' => 'Senior Software Engineer',
                'Data Science Architect' => 'Data Science Architect',
                'Manager - Annotation' => 'Manager - Annotation',
                'Senior Analyst' => 'Senior Analyst',
                'Manager - UI/UX Design' => 'Manager - UI/UX Design',
                'Senior QA Engineer' => 'Senior QA Engineer',
                'QA Engineer II' => 'QA Engineer II',
                'Proposal Manager' => 'Proposal Manager',
                'Architect' => 'Architect',
                'Technical Lead' => 'Technical Lead',
                'Senior IT Administrator' => 'Senior IT Administrator',
                'Assistant Manager - HR' => 'Assistant Manager - HR',
                'Lead analyst' => 'Lead analyst',
                'Software Engineer II' => 'Software Engineer II',
                'UI/UX Designer' => 'UI/UX Designer',
                'QA Analyst' => 'QA Analyst',
                'Junior Data Science Analyst - Annotation' => 'Junior Data Science Analyst - Annotation',
                'Solution Architect' => 'Solution Architect',
                'QA Manager' => 'QA Manager',
                'Senior Accountant' => 'Senior Accountant',
                'ML Ops Senior Engineer' => 'ML Ops Senior Engineer',
                'QA Engineer I' => 'QA Engineer I',
                'Senior Solution Architect' => 'Senior Solution Architect',
                'Director - Product Design' => 'Director - Product Design',
                'Software Engineer I' => 'Software Engineer I',
                'Content Writer' => 'Content Writer',
                'Proposal Writer' => 'Proposal Writer',
                'Assistant Manager' => 'Assistant Manager',
                'Admin Incharge' => 'Admin Incharge',
                'Principal Software Engineer' => 'Principal Software Engineer',
                'Lead Engineer - IOS' => 'Lead Engineer - IOS',
                'Senior Manager - Talent Acquisition' => 'Senior Manager - Talent Acquisition',
                'Lead Android Developer' => 'Lead Android Developer',
                'Manager - Talent Acquisition' => 'Manager - Talent Acquisition',
                'Manager - Business Development' => 'Manager - Business Development',
                'Senior Finance Executive' => 'Senior Finance Executive',
                'Manager - Accounts' => 'Manager - Accounts',
                'Data Engineer' => 'Data Engineer',
                'Project Manager' => 'Project Manager',
                'Senior Executive - Accounts' => 'Senior Executive - Accounts',
                'Senior Executive - HR' => 'Senior Executive - HR',
                'SDE 1' => 'SDE 1',
                'Assistant Manager - Digital Marketing' => 'Assistant Manager - Digital Marketing',
                'Principal Engineer' => 'Principal Engineer',
                'Quality Assurance Manager' => 'Quality Assurance Manager',
                'Senior DevOps Engineer' => 'Senior DevOps Engineer',
                'DevOps Engineer' => 'DevOps Engineer',
                'Senior Cloud Engineer' => 'Senior Cloud Engineer',
                'Lead QA Engineer' => 'Lead QA Engineer',
                'Engineering Manager' => 'Engineering Manager',
                'Cloud Engineer' => 'Cloud Engineer',
                'Lead DevOps Engineer' => 'Lead DevOps Engineer',
                'Manager - Demand Generation' => 'Manager - Demand Generation',
                'Lead - Demand Generation' => 'Lead - Demand Generation',
                'Manager Demand Generation' => 'Manager Demand Generation',
                'Senior Demand Generation' => 'Senior Demand Generation',
                'Senior Manager Inside Sales' => 'Senior Manager Inside Sales',
                'Manager - HR' => 'Manager - HR',
                'Senior Inside Sales Executive' => 'Senior Inside Sales Executive',
                'Team Lead Post Closer' => 'Team Lead Post Closer',
                'Senior Executive - Talent Acquisition' => 'Senior Executive - Talent Acquisition',
                'Assistant Manager - Presales' => 'Assistant Manager - Presales',
                'Intern' => 'Intern',
            ],
            'placeholder' => 'Select Position',
            'required' => false,
            'mapped' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'validation_groups' => ['UserCreate', 'Registration'],
            'include_roles' => false,
            'include_teams' => false,
        ]);
    }
}
