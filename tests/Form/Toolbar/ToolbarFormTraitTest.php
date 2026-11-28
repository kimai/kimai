<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Toolbar;

use App\Entity\Customer;
use App\Entity\Team;
use App\Entity\User;
use App\Form\Toolbar\ActivityToolbarForm;
use App\Form\Toolbar\ExportToolbarForm;
use App\Form\Toolbar\InvoiceArchiveToolbarForm;
use App\Form\Toolbar\InvoiceToolbarForm;
use App\Form\Toolbar\ProjectToolbarForm;
use App\Form\Toolbar\TimesheetToolbarForm;
use App\Repository\Query\ActivityQuery;
use App\Repository\Query\ExportQuery;
use App\Repository\Query\InvoiceArchiveQuery;
use App\Repository\Query\InvoiceQuery;
use App\Repository\Query\ProjectQuery;
use App\Repository\Query\TimesheetQuery;
use App\Tests\KernelTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

#[CoversClass(ActivityToolbarForm::class)]
#[CoversClass(ExportToolbarForm::class)]
#[CoversClass(InvoiceArchiveToolbarForm::class)]
#[CoversClass(InvoiceToolbarForm::class)]
#[CoversClass(ProjectToolbarForm::class)]
#[CoversClass(TimesheetToolbarForm::class)]
#[Group('integration')]
/**
 * Tests the shared ToolbarFormTrait through the toolbar forms using it.
 */
class ToolbarFormTraitTest extends KernelTestCase
{
    use KernelTestTrait;

    /**
     * Toolbar forms using the customer select, with the options they need for this test.
     *
     * @return \Generator<string, array{0: class-string, 1: object, 2: array<string, mixed>}>
     */
    public static function getToolbarFormsWithCustomerSelect(): \Generator
    {
        yield 'activity' => [ActivityToolbarForm::class, new ActivityQuery(), []];
        yield 'export' => [ExportToolbarForm::class, new ExportQuery(), ['include_user' => false]];
        yield 'invoice' => [InvoiceToolbarForm::class, new InvoiceQuery(), ['include_user' => false]];
        yield 'invoice archive' => [InvoiceArchiveToolbarForm::class, new InvoiceArchiveQuery(), []];
        yield 'project' => [ProjectToolbarForm::class, new ProjectQuery(), []];
        yield 'timesheet' => [TimesheetToolbarForm::class, new TimesheetQuery(), []];
    }

    /**
     * PHP warnings (like an undefined array key in the customer query builder) are converted to exceptions,
     * as the test environment runs with APP_DEBUG=0 and PHPUnit only reports them without failing the test.
     *
     * @param class-string $formType
     * @param array<string, mixed> $options
     */
    #[DataProvider('getToolbarFormsWithCustomerSelect')]
    public function testCustomerSelectCanBeRendered(string $formType, object $query, array $options): void
    {
        $customer = $this->createCustomer('toolbar trait customer', true);

        $view = $this->submitAndRender($this->getUserByRole(User::ROLE_USER), $formType, $query, $options, ['customers' => [(string) $customer->getId()]]);

        self::assertArrayHasKey('customers', $view->children);
        self::assertContains((string) $customer->getId(), $this->getChoiceValues($view->children['customers']));
    }

    /**
     * Hidden customers are not a security barrier: the timesheet toolbar allows to pre-select them via URL.
     * Customers of a foreign team must never be pre-selectable.
     */
    public function testTimesheetToolbarAllowsHiddenCustomerButNoForeignTeamCustomer(): void
    {
        $em = $this->getEntityManager();
        $user = $this->getUserByRole(User::ROLE_USER);

        $ownTeam = new Team('toolbar trait own team');
        $ownTeam->addUser($user);
        $em->persist($ownTeam);

        $foreignTeam = new Team('toolbar trait foreign team');
        $em->persist($foreignTeam);

        $hidden = $this->createCustomer('toolbar trait hidden customer', false);
        $foreign = $this->createCustomer('toolbar trait hidden foreign customer', false);
        $foreignTeam->addCustomer($foreign);
        // explicit persist is required, as customers use the DEFERRED_EXPLICIT change tracking policy
        $em->persist($foreign);
        $em->flush();

        $submitted = ['customers' => [(string) $hidden->getId(), (string) $foreign->getId()]];
        $view = $this->submitAndRender($user, TimesheetToolbarForm::class, new TimesheetQuery(), [], $submitted);

        $values = $this->getChoiceValues($view->children['customers']);
        self::assertContains((string) $hidden->getId(), $values, 'Hidden customer cannot be pre-selected');
        self::assertNotContains((string) $foreign->getId(), $values, 'Customer of a foreign team can be pre-selected');
    }

    private function createCustomer(string $name, bool $visible): Customer
    {
        $customer = new Customer($name);
        $customer->setCountry('DE');
        $customer->setCurrency('EUR');
        $customer->setTimezone('Europe/Berlin');
        $customer->setVisible($visible);

        $em = $this->getEntityManager();
        $em->persist($customer);
        $em->flush();

        return $customer;
    }

    /**
     * @param class-string $formType
     * @param array<string, mixed> $options
     * @param array<string, mixed> $submitted
     */
    private function submitAndRender(User $user, string $formType, object $query, array $options, array $submitted): FormView
    {
        // the "user" option of every form field is the user of the security token
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = self::getContainer()->get('security.token_storage');
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'secured_area', $user->getRoles()));

        /** @var FormFactoryInterface $factory */
        $factory = self::getContainer()->get('form.factory');

        /** @var FormInterface<object> $form */
        $form = $factory->createNamed('', $formType, $query, array_merge(['method' => 'GET', 'csrf_protection' => false], $options)); // @phpstan-ignore argument.type

        set_error_handler(static function (int $type, string $message, string $file, int $line): bool {
            if (($type & (E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE)) === 0) {
                return false;
            }
            throw new \ErrorException($message, 0, $type, $file, $line);
        });

        try {
            $form->submit($submitted, false);

            return $form->createView();
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @return array<string>
     */
    private function getChoiceValues(FormView $view): array
    {
        $values = [];
        foreach ($view->vars['choices'] as $choice) {
            if ($choice instanceof ChoiceGroupView) {
                foreach ($choice->choices as $child) {
                    $values[] = $child->value;
                }
            } else {
                $values[] = $choice->value;
            }
        }

        return $values;
    }
}
