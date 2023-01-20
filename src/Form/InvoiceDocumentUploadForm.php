<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Configuration\SystemConfiguration;
use App\Repository\InvoiceDocumentRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class InvoiceDocumentUploadForm extends AbstractType
{
    public const EXTENSIONS = ['.html.twig', '.pdf.twig', '.docx', '.xlsx', '.ods'];
    public const EXTENSIONS_NO_TWIG = ['.docx', '.xlsx', '.ods'];
    public const FILENAME_RULE = 'Any-Latin; Latin-ASCII; [^A-Za-z0-9_\-] remove; Lower()';

    /** @var array<string> */
    private array $extensions = [];

    public function __construct(private InvoiceDocumentRepository $repository, private SystemConfiguration $systemConfiguration)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->extensions = self::EXTENSIONS_NO_TWIG;
        $extensions = 'DOCX, ODS, XLSX';
        $mimetypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.oasis.opendocument.spreadsheet',
        ];

        if ((bool) $this->systemConfiguration->find('invoice.upload_twig') === true) {
            $this->extensions = self::EXTENSIONS;
            $extensions = 'DOCX, ODS, XLSX, TWIG (PDF & HTML)';
            $mimetypes = array_merge($mimetypes, [
                'application/octet-stream',  // needed for twig templates
                'text/html', // needed for twig templates
                'text/plain', // needed for twig templates
            ]);
        }

        $builder
            ->add('document', FileType::class, [
                'label' => 'invoice_renderer',
                'translation_domain' => 'invoice-renderer',
                'help' => 'help.upload',
                'help_translation_parameters' => ['%extensions%' => $extensions],
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'mimeTypes' => $mimetypes,
                        'mimeTypesMessage' => 'This file type is not allowed',
                    ]),
                    new Callback([$this, 'validateDocument'])
                ],
            ])
        ;
    }

    public function validateDocument($value, ExecutionContextInterface $context): void
    {
        if (!($value instanceof UploadedFile)) {
            return;
        }

        $name = $value->getClientOriginalName();

        foreach ($this->repository->findBuiltIn() as $document) {
            if ($document->getName() !== $name) {
                continue;
            }

            $context->buildViolation('This invoice document cannot be used, please rename the file and upload it again.')
                ->setTranslationDomain('validators')
                ->setCode('kimai-invoice-document-upload-01')
                ->addViolation();

            return;
        }

        $extension = null;
        $nameWithoutExtension = null;

        foreach ($this->extensions as $ext) {
            $len = \strlen($ext);
            if (substr_compare($name, $ext, -$len) === 0) {
                $extension = $ext;
                $nameWithoutExtension = str_replace($ext, '', $name);
                break;
            }
        }

        if ($extension === null || $nameWithoutExtension === null) {
            $context->buildViolation('This invoice document cannot be used, allowed file extensions are: %extensions%')
                ->setParameters(['%extensions%' => implode(', ', $this->extensions)])
                ->setTranslationDomain('validators')
                ->setCode('kimai-invoice-document-upload-02')
                ->addViolation();

            return;
        }

        $safeFilename = transliterator_transliterate(self::FILENAME_RULE, $nameWithoutExtension);

        if ($safeFilename !== $nameWithoutExtension) {
            $context->buildViolation('This invoice document cannot be used, filename may only contain the following ascii character: %character%')
                ->setParameters(['%character%' => 'A-Z a-z 0-9 _ -'])
                ->setTranslationDomain('validators')
                ->setCode('kimai-invoice-document-upload-03')
                ->addViolation();
        }

        if (mb_strlen($nameWithoutExtension) > 20) {
            $context->buildViolation('This invoice document cannot be used, allowed filename length without extension is %character% character.')
                ->setParameters(['%character%' => 20])
                ->setTranslationDomain('validators')
                ->setCode('kimai-invoice-document-upload-04')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'admin_invoice_document_upload',
            'attr' => [
                'data-form-event' => 'kimai.invoiceTemplateUpdate',
                'data-msg-success' => 'action.update.success',
                'data-msg-error' => 'action.update.error',
            ],
        ]);
    }
}
