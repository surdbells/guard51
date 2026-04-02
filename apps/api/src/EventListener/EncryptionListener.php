<?php
declare(strict_types=1);
namespace Guard51\EventListener;

use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Guard51\Attribute\Encrypted;
use Guard51\Service\EncryptionService;

final class EncryptionListener
{
    public function __construct(private readonly EncryptionService $encryption) {}

    public function prePersist(PrePersistEventArgs $args): void { $this->encryptFields($args->getObject()); }
    public function preUpdate(PreUpdateEventArgs $args): void { $this->encryptFields($args->getObject()); }
    public function postLoad(PostLoadEventArgs $args): void { $this->decryptFields($args->getObject()); }

    private function encryptFields(object $entity): void
    {
        foreach ((new \ReflectionClass($entity))->getProperties() as $prop) {
            if (!empty($prop->getAttributes(Encrypted::class))) {
                $prop->setAccessible(true);
                $val = $prop->getValue($entity);
                if ($val !== null && is_string($val)) $prop->setValue($entity, $this->encryption->encrypt($val));
            }
        }
    }

    private function decryptFields(object $entity): void
    {
        foreach ((new \ReflectionClass($entity))->getProperties() as $prop) {
            if (!empty($prop->getAttributes(Encrypted::class))) {
                $prop->setAccessible(true);
                $val = $prop->getValue($entity);
                if ($val !== null && is_string($val)) $prop->setValue($entity, $this->encryption->decrypt($val));
            }
        }
    }
}
