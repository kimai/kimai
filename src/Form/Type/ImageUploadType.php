<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Utils\FileHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * Reusable form type for image uploads.
 * Stores the uploaded file in the Kimai data directory and keeps the filename as value.
 */
final class ImageUploadType extends AbstractType
{
    public function __construct(
        private readonly FileHelper $fileHelper,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $originalData = null;

        // Store the original value so we can restore it when no file is uploaded
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use (&$originalData): void {
            $originalData = $event->getData();
            $event->setData(null);
        });

        // After submit: if a file was uploaded, save it and store the filename;
        // if no file was uploaded, restore the original value
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use (&$originalData): void {
            $normData = $event->getForm()->getNormData();
            if ($normData instanceof UploadedFile) {
                $filename = FileHelper::convertToAsciiFilename($normData->getClientOriginalName());
                $normData->move($this->fileHelper->getDataDirectory('images'), $filename);
                $event->setData($filename);
            } elseif ($normData === null && $originalData !== null) {
                $event->setData($originalData);
            }
        });
    }

    public function getParent(): string
    {
        return FileType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'mapped' => true,
            'data_class' => null,
            'constraints' => [
                new File([
                    'mimeTypes' => ['image/png', 'image/jpeg', 'image/gif', 'image/webp'],
                    'mimeTypesMessage' => 'Please upload a valid image file (PNG, JPEG, GIF, WebP).',
                    'maxSize' => '2048k',
                ]),
            ],
        ]);
    }
}
