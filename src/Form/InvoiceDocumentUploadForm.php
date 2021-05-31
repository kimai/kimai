<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Repository\InvoiceDocumentRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class InvoiceDocumentUploadForm extends AbstractType
{
    /**
     * @var InvoiceDocumentRepository
     */
    private $repository;

    public function __construct(InvoiceDocumentRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('document', FileType::class, [
                'label' => 'label.invoice_renderer',
                'translation_domain' => 'invoice-renderer',
                'help' => 'help.upload',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.oasis.opendocument.spreadsheet',
                        ],
                        'mimeTypesMessage' => 'This file type is not allowed',
                    ]),
                    new Callback([$this, 'validateDocument'])
                ],
            ])
        ;
    }

    public function validateDocument($value, ExecutionContextInterface $context)
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
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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
