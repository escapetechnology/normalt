<?php

namespace Normalt\Normalizer;

use ArrayObject;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use UnexpectedValueException;

/**
 * Functionality extracted from Symfony\Component\Serializer\Serializer in order to
 * limit it to denormalization and normalization while still giving the user lot of
 * flexibility when needed.
 *
 * @package Normalt
 */
class AggregateNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    protected $normalizers = array();
    protected $denormalizers = array();

    public function __construct($normalizers = array())
    {
        array_map(array($this, 'add'), $normalizers);
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): ArrayObject|array|string|int|float|bool|null
    {
        if ($normalizer = $this->getNormalizer($data, $format)) {
            return $normalizer->normalize($data, $format, $context);
        }

        throw new UnexpectedValueException('No supported normalizer found for "' . get_class($data) . '".');
    }

    public function denormalize($data, $class, $format = null, array $context = array()): mixed
    {
        if ($denormalizer = $this->getDenormalizer($data, $class, $format)) {
            return $denormalizer->denormalize($data, $class, $format, $context);
        }

        throw new UnexpectedValueException('No supported normalizer found for "' . $class . '".');
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return (boolean) $this->getNormalizer($data, $format);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            '*' => false,
        ];
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return (boolean) $this->getDenormalizer($data, $type, $format);
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        foreach ($this->normalizers as $normalizer) {
            if ($normalizer instanceof SerializerAwareInterface) {
                $normalizer->setSerializer($serializer);
            }
        }

        foreach ($this->denormalizers as $normalizer) {
            if ($normalizer instanceof SerializerAwareInterface) {
                $normalizer->setSerializer($serializer);
            }
        }
    }

    protected function getNormalizer($data, $format = null)
    {
        foreach ($this->normalizers as $normalizer) {
            if (false == $normalizer->supportsNormalization($data, $format)) {
                continue;
            }

            if ($normalizer instanceof AggregateNormalizerAware) {
                $normalizer->setAggregateNormalizer($this);
            }

            return $normalizer;
        }
    }

    protected function getDenormalizer($data, $type, $format = null)
    {
        foreach ($this->denormalizers as $denormalizer) {
            if (false == $denormalizer->supportsDenormalization($data, $type, $format)) {
                continue;
            }

            if ($denormalizer instanceof AggregateNormalizerAware) {
                $denormalizer->setAggregateNormalizer($this);
            }

            return $denormalizer;
        }
    }

    protected function add($normalizer)
    {
        if ($normalizer instanceof NormalizerInterface) {
            $this->normalizers[] = $normalizer;
        }

        if ($normalizer instanceof DenormalizerInterface) {
            $this->denormalizers[] = $normalizer;
        }
    }

}
