<?php

namespace App\Services\SICs\CirculoDeCredito\FintechScore\Model;

use ArrayAccess;
use App\Services\SICs\CirculoDeCredito\FintechScore\ObjectSerializer;

class Score implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    protected static $RCCPMModelName = 'Score';

    protected static $RCCPMTypes = [
        'valor' => 'int',
        'razon' => '\App\Services\SICs\CirculoDeCredito\FintechScore\Model\CatalogoRazones'
    ];

    protected static $RCCPMFormats = [
        'valor' => 'int32',
        'razon' => null
    ];

    public static function RCCPMTypes()
    {
        return self::$RCCPMTypes;
    }

    public static function RCCPMFormats()
    {
        return self::$RCCPMFormats;
    }

    protected static $attributeMap = [
        'valor' => 'valor',
        'razon' => 'razon'
    ];

    protected static $setters = [
        'valor' => 'setValor',
        'razon' => 'setRazon'
    ];

    protected static $getters = [
        'valor' => 'getValor',
        'razon' => 'getRazon'
    ];

    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    public static function setters()
    {
        return self::$setters;
    }

    public static function getters()
    {
        return self::$getters;
    }

    public function getModelName()
    {
        return self::$RCCPMModelName;
    }



    protected $container = [];

    public function __construct(array $data = null)
    {
        $this->container['valor'] = isset($data['valor']) ? $data['valor'] : null;
        $this->container['razon'] = isset($data['razon']) ? $data['razon'] : null;
    }

    public function listInvalidProperties()
    {
        $invalidProperties = [];
        if (!is_null($this->container['valor']) && ($this->container['valor'] > 999)) {
            $invalidProperties[] = "invalid value for 'valor', must be smaller than or equal to 999.";
        }
        if (!is_null($this->container['valor']) && ($this->container['valor'] < 0)) {
            $invalidProperties[] = "invalid value for 'valor', must be bigger than or equal to 0.";
        }
        return $invalidProperties;
    }

    public function valid()
    {
        return count($this->listInvalidProperties()) === 0;
    }

    public function getValor()
    {
        return $this->container['valor'];
    }

    public function setValor($valor)
    {
        if (!is_null($valor) && ($valor > 999)) {
            throw new \InvalidArgumentException('invalid value for $valor when calling Score., must be smaller than or equal to 999.');
        }
        if (!is_null($valor) && ($valor < 0)) {
            throw new \InvalidArgumentException('invalid value for $valor when calling Score., must be bigger than or equal to 0.');
        }
        $this->container['valor'] = $valor;
        return $this;
    }

    public function getRazon()
    {
        return $this->container['razon'];
    }

    public function setRazon($razon)
    {
        $this->container['razon'] = $razon;
        return $this;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->container[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->container[$offset]);
    }

    public function __toString()
    {
        if (defined('JSON_PRETTY_PRINT')) {
            return json_encode(
                ObjectSerializer::sanitizeForSerialization($this),
                JSON_PRETTY_PRINT
            );
        }
        return json_encode(ObjectSerializer::sanitizeForSerialization($this));
    }
}
