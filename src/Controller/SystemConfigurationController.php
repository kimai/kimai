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
use App\Form\Type\DateTimeTextType;
use App\Form\Type\LanguageType;
use App\Form\Type\RoundingModeType;
use App\Form\Type\SkinType;
use App\Form\Type\TrackingModeType;
use App\Form\Type\WeekDaysType;
use App\Form\Type\YesNoType;
use App\Repository\ConfigurationRepository;
use App\Validator\Constraints\DateTimeFormat;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Controller used for executing system relevant tasks.
 *
 * @Route(path="/admin/system-config")
 * @Security("is_granted('system_configuration')")
 */
final class SystemConfigurationController extends AbstractController
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var SystemConfiguration
     */
    private $configurations;
    /**
     * @var ConfigurationRepository
     */
    private $repository;

    public function __construct(EventDispatcherInterface $dispatcher, ConfigurationRepository $repository, SystemConfiguration $config)
    {
        $this->eventDispatcher = $dispatcher;
        $this->repository = $repository;
        $this->configurations = $config;
    }

    /**
     * @Route(path="/", name="system_configuration", methods={"GET"})
     */
    public function indexAction(): Response
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
     * @Route(path="/edit/{section}", name="system_configuration_section", methods={"GET"})
     */
    public function sectionAction(string $section): Response
    {
        $configSettings = $this->getInitializedConfigurations();

        $configurations = [];
        foreach ($configSettings as $configModel) {
            if ($configModel->getSection() !== $section) {
                continue;
            }

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
     * @Route(path="/update/{section}", name="system_configuration_update", methods={"POST"})
     *
     * @param Request $request
     * @param string $section
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function configUpdate(Request $request, string $section)
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
        $options = [
            'action' => $this->generateUrl('system_configuration_update', ['section' => $configuration->getSection()]),
            'method' => 'POST',
        ];

        return $this->container
            ->get('form.factory')
            ->createNamedBuilder('system_configuration_form_' . $configuration->getSection(), SystemConfigurationForm::class, $configuration, $options)
            ->getForm();
    }

    /**
     * @return SystemConfigurationModel[]
     */
    protected function getInitializedConfigurations()
    {
        $types = $this->getConfigurationTypes();

        $event = new SystemConfigurationEvent($types);
        $this->eventDispatcher->dispatch($event);

        foreach ($event->getConfigurations() as $configs) {
            foreach ($configs->getConfiguration() as $config) {
                $configValue = $this->configurations->find($config->getName());
                if (null !== $configValue) {
                    $config->setValue($configValue);
                }
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
                        ->setType(TrackingModeType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration())
                        ->setName('timesheet.default_begin')
                        ->setType(DateTimeTextType::class)
                        ->setConstraints([new DateTimeFormat(), new NotNull()])
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
                ->setSection(SystemConfigurationModel::SECTION_ROUNDING)
                ->setConfiguration([
                    (new Configuration())
                        ->setName('timesheet.rounding.default.mode')
                        ->setType(RoundingModeType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration())
                        ->setName('timesheet.rounding.default.begin')
                        ->setType(IntegerType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setConstraints([
                            new GreaterThanOrEqual(['value' => 0])
                        ]),
                    (new Configuration())
                        ->setName('timesheet.rounding.default.end')
                        ->setType(IntegerType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setConstraints([
                            new GreaterThanOrEqual(['value' => 0])
                        ]),
                    (new Configuration())
                        ->setName('timesheet.rounding.default.duration')
                        ->setType(IntegerType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setConstraints([
                            new GreaterThanOrEqual(['value' => 0])
                        ]),
                    (new Configuration())
                        ->setName('timesheet.rounding.default.days')
                        ->setType(WeekDaysType::class)
                        ->setTranslationDomain('system-configuration'),
                ]),
            (new SystemConfigurationModel())
                ->setSection(SystemConfigurationModel::SECTION_FORM_INVOICE)
                ->setConfiguration([
                    (new Configuration())
                        ->setName('invoice.number_format')
                        ->setLabel('invoice.number_format')
                        ->setRequired(true)
                        ->setType(TextType::class) // TODO that should be a custom type with validation
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration())
                        ->setName('invoice.simple_form')
                        ->setLabel('simple_form')
                        ->setRequired(false)
                        ->setType(YesNoType::class)
                        ->setTranslationDomain('system-configuration'),
                ]),
            (new SystemConfigurationModel())
                ->setSection(SystemConfigurationModel::SECTION_FORM_CUSTOMER)
                ->setConfiguration([
                    (new Configuration())
                        ->setName('defaults.customer.timezone')
                        ->setLabel('timezone')
                        ->setType(TimezoneType::class)
                        ->setValue(date_default_timezone_get()),
                    (new Configuration())
                        ->setName('defaults.customer.country')
                        ->setLabel('country')
                        ->setType(CountryType::class),
                    (new Configuration())
                        ->setName('defaults.customer.currency')
                        ->setLabel('currency')
                        ->setType(CurrencyType::class),
                ]),
            (new SystemConfigurationModel())
                ->setSection(SystemConfigurationModel::SECTION_FORM_USER)
                ->setConfiguration([
                    (new Configuration())
                        ->setName('defaults.user.timezone')
                        ->setLabel('timezone')
                        ->setType(TimezoneType::class)
                        ->setValue(date_default_timezone_get()),
                    (new Configuration())
                        ->setName('defaults.user.language')
                        ->setLabel('language')
                        ->setType(LanguageType::class),
                    (new Configuration())
                        ->setName('defaults.user.theme')
                        ->setLabel('skin')
                        ->setType(SkinType::class),
                    (new Configuration())
                        ->setName('defaults.user.currency')
                        ->setLabel('currency')
                        ->setType(CurrencyType::class),
                ]),
            (new SystemConfigurationModel())
                ->setSection(SystemConfigurationModel::SECTION_THEME)
                ->setConfiguration([
                    (new Configuration())
                        ->setName('theme.autocomplete_chars')
                        ->setLabel('theme.autocomplete_chars')
                        ->setType(IntegerType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration())
                        ->setName('timesheet.markdown_content')
                        ->setLabel('theme.markdown_content')
                        ->setType(CheckboxType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration())
                        ->setName('theme.tags_create')
                        ->setLabel('theme.tags_create')
                        ->setType(CheckboxType::class)
                        ->setTranslationDomain('system-configuration'),
                    // TODO should that be configurable per user?
                    /*
                    (new Configuration())
                        ->setName('theme.auto_reload_datatable')
                        ->setLabel('theme.auto_reload_datatable') // TODO translation
                        ->setType(CheckboxType::class)
                        ->setTranslationDomain('system-configuration'),
                    */
                ]),
            (new SystemConfigurationModel())
                ->setSection(SystemConfigurationModel::SECTION_CALENDAR)
                ->setConfiguration([
                    (new Configuration())
                        ->setName('calendar.week_numbers')
                        ->setTranslationDomain('system-configuration')
                        ->setType(CheckboxType::class),
                    (new Configuration())
                        ->setName('calendar.weekends')
                        ->setTranslationDomain('system-configuration')
                        ->setType(CheckboxType::class),
                    (new Configuration())
                        ->setName('calendar.businessHours.begin')
                        ->setTranslationDomain('system-configuration')
                        ->setType(TextType::class)
                        ->setConstraints([new DateTime(['format' => 'H:i']), new NotNull()]),
                    (new Configuration())
                        ->setName('calendar.businessHours.end')
                        ->setTranslationDomain('system-configuration')
                        ->setType(TextType::class)
                        ->setConstraints([new DateTime(['format' => 'H:i']), new NotNull()]),
                    (new Configuration())
                        ->setName('calendar.visibleHours.begin')
                        ->setTranslationDomain('system-configuration')
                        ->setType(TextType::class)
                        ->setConstraints([new DateTime(['format' => 'H:i']), new NotNull()]),
                    (new Configuration())
                        ->setName('calendar.visibleHours.end')
                        ->setTranslationDomain('system-configuration')
                        ->setType(TextType::class)
                        ->setConstraints([new DateTime(['format' => 'H:i']), new NotNull()]),
                    (new Configuration())
                        ->setName('calendar.slot_duration')
                        ->setTranslationDomain('system-configuration')
                        ->setType(TextType::class)
                        ->setConstraints([new Regex(['pattern' => '/[0-2]{1}[0-9]{1}:[0-9]{2}:[0-9]{2}/']), new NotNull()]),
                ]),
            (new SystemConfigurationModel())
                ->setSection(SystemConfigurationModel::SECTION_BRANDING)
                ->setConfiguration([
                    (new Configuration())
                        ->setName('theme.branding.logo')
                        ->setTranslationDomain('system-configuration')
                        ->setRequired(false)
                        ->setType(TextType::class),
                    (new Configuration())
                        ->setName('theme.branding.company')
                        ->setTranslationDomain('system-configuration')
                        ->setRequired(false)
                        ->setType(TextType::class),
                    (new Configuration())
                        ->setName('theme.branding.mini')
                        ->setTranslationDomain('system-configuration')
                        ->setRequired(false)
                        ->setType(TextType::class),
                    (new Configuration())
                        ->setName('theme.branding.title')
                        ->setTranslationDomain('system-configuration')
                        ->setRequired(false)
                        ->setType(TextType::class),
                ]),
        ];
    }
}
