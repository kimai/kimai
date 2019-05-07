<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\SystemConfiguration;
use App\Event\SystemConfigurationEvent;
use App\Form\Model\Configuration;
use App\Form\Model\SystemConfiguration as SystemConfigurationModel;
use App\Form\SystemConfigurationForm;
use App\Form\Type\TimesheetModeType;
use App\Repository\ConfigurationRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

/**
 * Controller used for executing system relevant tasks.
 *
 * @Route(path="/admin/system-config")
 * @Security("is_granted('system_configuration')")
 */
class SystemConfigurationController extends AbstractController
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;
    /**
     * @var SystemConfiguration
     */
    protected $configurations;
    /**
     * @var ConfigurationRepository
     */
    protected $repository;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param ConfigurationRepository $repository
     * @param SystemConfiguration $config
     */
    public function __construct(EventDispatcherInterface $dispatcher, ConfigurationRepository $repository, SystemConfiguration $config)
    {
        $this->eventDispatcher = $dispatcher;
        $this->repository = $repository;
        $this->configurations = $config;
    }

    /**
     * @Route(path="/", name="system_configuration", methods={"GET"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $configSettings = $this->getInitializedConfigurations();

        $configurations = [];
        foreach ($configSettings as $configModel) {
            $configurations[] = [
                'model' => $configModel,
                'form' => $this->createConfigurationsForm($configModel)->createView(),
            ];
        }

        return $this->render('system-configuration/index.html.twig', [
            'sections' => $configurations,
        ]);
    }

    /**
     * @Route(path="/timesheet", name="system_configuration_timesheet", methods={"POST"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function timesheet(Request $request)
    {
        return $this->handleConfigUpdate($request, SystemConfigurationModel::SECTION_TIMESHEET);
    }

    /**
     * @Route(path="/customer", name="system_configuration_form_customer", methods={"POST"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function formDefaults(Request $request)
    {
        return $this->handleConfigUpdate($request, SystemConfigurationModel::SECTION_FORM_CUSTOMER);
    }

    /**
     * @param Request $request
     * @param string $section
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function handleConfigUpdate(Request $request, string $section)
    {
        $configModel = null;
        $configSettings = $this->getInitializedConfigurations();

        foreach ($configSettings as $configModel) {
            if ($configModel->getSection() === $section) {
                break;
            }
        }

        if (null === $configModel) {
            throw $this->createNotFoundException('Could not find config model: ' . $section);
        }

        $form = $this->createConfigurationsForm($configModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->repository->saveSystemConfiguration($form->getData());
                $this->flashSuccess('action.update.success');
            } catch (\Exception $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }

            return $this->redirectToRoute('system_configuration');
        }

        $configSettings = $this->getInitializedConfigurations();

        $configurations = [];
        foreach ($configSettings as $configModel) {
            if ($section !== $configModel->getSection()) {
                $form2 = $this->createConfigurationsForm($configModel);
            } else {
                $form2 = $form;
            }
            $configurations[] = [
                'model' => $configModel,
                'form' => $form2->createView(),
            ];
        }

        return $this->render('system-configuration/index.html.twig', [
            'sections' => $configurations,
        ]);
    }

    /**
     * @param SystemConfigurationModel $configuration
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createConfigurationsForm(SystemConfigurationModel $configuration)
    {
        return $this->createForm(
            SystemConfigurationForm::class,
            $configuration,
            [
                'attr' => ['id' => 'system_configuration_form_' . $configuration->getSection()],
                'action' => $this->generateUrl('system_configuration_' . $configuration->getSection()),
                'method' => 'POST'
            ]
        );
    }

    /**
     * @return SystemConfigurationModel[]
     */
    protected function getInitializedConfigurations()
    {
        $types = $this->getConfigurationTypes();

        $event = new SystemConfigurationEvent($types);
        $this->eventDispatcher->dispatch(SystemConfigurationEvent::CONFIGURE, $event);

        foreach ($event->getConfigurations() as $configs) {
            foreach ($configs->getConfiguration() as $config) {
                $config->setValue($this->configurations->find($config->getName()));
            }
        }

        return $event->getConfigurations();
    }

    /**
     * @return SystemConfigurationModel[]
     */
    protected function getConfigurationTypes()
    {
        return [
            (new SystemConfigurationModel())
                ->setSection(SystemConfigurationModel::SECTION_TIMESHEET)
                ->setConfiguration([
                    (new Configuration())
                        ->setName('timesheet.mode')
                        ->setType(TimesheetModeType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration())
                        ->setName('timesheet.markdown_content')
                        ->setType(CheckboxType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration())
                        ->setName('timesheet.rules.allow_future_times')
                        ->setType(CheckboxType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration())
                        ->setName('timesheet.active_entries.hard_limit')
                        ->setType(IntegerType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setConstraints([
                            new GreaterThanOrEqual(['value' => 1])
                        ]),
                    (new Configuration())
                        ->setName('timesheet.active_entries.soft_limit')
                        ->setType(IntegerType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setConstraints([
                            new GreaterThanOrEqual(['value' => 1])
                        ]),
                ]),
            (new SystemConfigurationModel())
                ->setSection(SystemConfigurationModel::SECTION_FORM_CUSTOMER)
                ->setConfiguration([
                    (new Configuration())
                        ->setName('defaults.customer.timezone')
                        ->setLabel('timezone')
                        ->setType(TimezoneType::class),
                    (new Configuration())
                        ->setName('defaults.customer.country')
                        ->setLabel('country')
                        ->setType(CountryType::class),
                    (new Configuration())
                        ->setName('defaults.customer.currency')
                        ->setLabel('currency')
                        ->setType(CurrencyType::class),
                ]),
        ];
    }
}
