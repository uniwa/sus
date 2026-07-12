<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Replacement for the abandoned `oh_google_maps` form type (Oh\GoogleMapFormTypeBundle,
 * Symfony 2 only) used for `Unit.latlng` — the entity's virtual property mapped onto the
 * `latitude`/`longitude` columns via getLatLng()/setLatLng(array{lat,lng}).
 *
 * Renders two plain text inputs (Latitude / Longitude). The old Google-Maps picker widget
 * (jquery.ohgooglemaps.js) is NOT reimplemented; the `default_lat`/`default_lng`/
 * `include_jquery` options are accepted for signature parity but ignored.
 */
final class LatLngType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lat', TextType::class, $options['lat_options'])
            ->add('lng', TextType::class, $options['lng_options']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'lat_options' => ['label' => 'Latitude', 'required' => false, 'empty_data' => '0'],
            'lng_options' => ['label' => 'Longitude', 'required' => false, 'empty_data' => '0'],
            // accepted-but-ignored legacy oh_google_maps options (map picker not ported)
            'default_lat' => 37.984042,
            'default_lng' => 23.728179,
            'include_jquery' => false,
        ]);

        $resolver->setAllowedTypes('lat_options', 'array');
        $resolver->setAllowedTypes('lng_options', 'array');
    }

    public function getBlockPrefix(): string
    {
        return 'sus_latlng';
    }
}
