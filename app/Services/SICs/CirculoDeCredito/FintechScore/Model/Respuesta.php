<?php

namespace App\Services\SICs\CirculoDeCredito\FintechScore\Model;

use ArrayAccess;
use App\Services\SICs\CirculoDeCredito\FintechScore\ObjectSerializer;

class Respuesta implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    protected static $RCCPMModelName = 'Respuesta';

    protected static $RCCPMTypes = [
        'folio_consulta' => 'string',
        'folio_otorgante' => 'string',
        'score' => '\App\Services\SICs\CirculoDeCredito\FintechScore\Model\Score'
    ];

    protected static $RCCPMFormats = [
        'folio_consulta' => null,
        'folio_otorgante' => null,
        'score' => null
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
        'folio_consulta' => 'folioConsulta',
        'folio_otorgante' => 'folioOtorgante',
        'score' => 'score'
    ];

    protected static $setters = [
        'folio_consulta' => 'setFolioConsulta',
        'folio_otorgante' => 'setFolioOtorgante',
        'score' => 'setScore'
    ];

    protected static $getters = [
        'folio_consulta' => 'getFolioConsulta',
        'folio_otorgante' => 'getFolioOtorgante',
        'score' => 'getScore'
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
        $this->container['folio_consulta'] = isset($data['folio_consulta']) ? $data['folio_consulta'] : null;
        $this->container['folio_otorgante'] = isset($data['folio_otorgante']) ? $data['folio_otorgante'] : null;
        $this->container['score'] = isset($data['score']) ? $data['score'] : null;
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

    public function getFolioConsulta()
    {
        return $this->container['folio_consulta'];
    }

    public function setFolioConsulta($folio_consulta)
    {
        $this->container['folio_consulta'] = $folio_consulta;
        return $this;
    }

    public function getFolioOtorgante()
    {
        return $this->container['folio_otorgante'];
    }

    public function setFolioOtorgante($folio_otorgante)
    {
        $this->container['folio_otorgante'] = $folio_otorgante;
        return $this;
    }

    public function getScore()
    {
        return $this->container['score'];
    }

    public function setScore($score)
    {
        $this->container['score'] = $score;
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
