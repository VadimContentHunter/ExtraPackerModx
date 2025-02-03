<?php

namespace Packer\Services\PackageBuilder;

use Error;
use xPDO\Om\xPDOObject;
use MODX\Revolution\modX;
use xPDO\Transport\xPDOTransport;
use MODX\Revolution\Transport\modPackageBuilder;
use MODX\Revolution\Transport\modTransportVehicle;

class SettingVehicle
{
    private ?modTransportVehicle $vehicle = null;

    private ?xPDOObject $obj = null;

    private array $attributes = [];

    public function __construct(
        private modX $modx,
        private modPackageBuilder $builder
    ) {}

    public function checkVehicle()
    {
        if ($this->vehicle === null) {
            throw new Error("Не создан Vehicle");
        }
    }

    public function addAttribute(string $key, mixed $value)
    {
        $this->attributes = array_merge($this->attributes, [
            $key => $value
        ]);
    }

    public function setObject(xPDOObject $obj, string $uniqueKey, bool $update = true, bool $setOldPK = false)
    {
        $this->obj = $obj;
        $this->attributes = array_merge($this->attributes, [
            xPDOTransport::UNIQUE_KEY => $uniqueKey,
            xPDOTransport::UPDATE_OBJECT => $update,
            xPDOTransport::PRESERVE_KEYS => $setOldPK,
        ]);
    }

    public function addRelatedObjAttribute(string $attributeName, string $uniqueKey, bool $update = true, bool $setOldPK = false)
    {
        if (!array_key_exists(xPDOTransport::RELATED_OBJECTS, $this->attributes)) {
            $this->attributes[xPDOTransport::RELATED_OBJECTS] = true;
        }

        if (!array_key_exists(xPDOTransport::RELATED_OBJECT_ATTRIBUTES, $this->attributes)) {
            $this->attributes[xPDOTransport::RELATED_OBJECT_ATTRIBUTES] = [];
        }

        $this->attributes[xPDOTransport::RELATED_OBJECT_ATTRIBUTES][$attributeName] = [
            xPDOTransport::UNIQUE_KEY => $uniqueKey,
            xPDOTransport::UPDATE_OBJECT => $update,
            xPDOTransport::PRESERVE_KEYS => $setOldPK,
        ];
    }

    private function createVehicle()
    {
        if ($this->obj === null) {
            throw new Error("Не корректный объект для создании Vehicle");
        }
        if ($this->vehicle === null) {
            $this->vehicle = $this->builder->createVehicle($this->obj, $this->attributes);
        }
    }

    public function copyFile(string $sourcePath, string $targetPath)
    {
        $this->createVehicle();
        $this->checkVehicle();
        $this->vehicle->resolve('file', array(
            'source' => $sourcePath,
            'target' => "return '$targetPath';",
        ));
    }

    public function addResolver(string $sourcePath)
    {
        $this->createVehicle();
        $this->checkVehicle();
        $this->vehicle->resolve('php', array(
            'source' => $sourcePath,
        ));
    }

    public function putVehicle()
    {
        $this->createVehicle();
        $this->checkVehicle();
        $this->builder->putVehicle($this->vehicle);
    }
}