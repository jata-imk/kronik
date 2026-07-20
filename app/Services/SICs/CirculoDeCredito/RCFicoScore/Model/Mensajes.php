<?php

namespace App\Services\SICs\CirculoDeCredito\RCFicoScore\Model;

use \ArrayAccess;
use App\Services\SICs\CirculoDeCredito\RCFicoScore\ObjectSerializer;

class Mensajes implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    protected static $RCCPMModelName = 'Mensajes';

    protected static $RCCPMTypes = [
        'mensajes' => 'App\Services\SICs\CirculoDeCredito\RCFicoScore\Model\Mensaje[]'
    ];

    protected static $RCCPMFormats = [
        'mensajes' => null
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
        'mensajes' => 'mensajes'
    ];

    protected static $setters = [
        'mensajes' => 'setMensajes'
    ];

    protected static $getters = [
        'mensajes' => 'getMensajes'
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
        $this->container['mensajes'] = isset($data['mensajes']) ? $data['mensajes'] : null;
    }

    public function listInvalidProperties()
    {
        $invalidProperties = [];
        return $invalidProperties;
    }

    public function valid()
    {
        return count($this->listInvalidProperties()) === 0;
    }

    public function getMensajes()
    {
        return $this->container['mensajes'];
    }

    public function setMensajes($mensajes)
    {
        $this->container['mensajes'] = $mensajes;
        return $this;
    }

    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
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
