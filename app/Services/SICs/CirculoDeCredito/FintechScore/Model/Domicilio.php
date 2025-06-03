<?php

namespace App\Services\SICs\CirculoDeCredito\FintechScore\Model;

use ArrayAccess;
use App\Services\SICs\CirculoDeCredito\FintechScore\ObjectSerializer;

class Domicilio implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    protected static $RCCPMModelName = 'Domicilio';

    protected static $RCCPMTypes = [
        'direccion' => 'string',
        'colonia_poblacion' => 'string',
        'delegacion_municipio' => 'string',
        'ciudad' => 'string',
        'estado' => '\App\Services\SICs\CirculoDeCredito\FintechScore\Model\CatalogoEstados',
        'cp' => 'string',
        'pais' => '\App\Services\SICs\CirculoDeCredito\FintechScore\Model\CatalogoPais'
    ];

    protected static $RCCPMFormats = [
        'direccion' => null,
        'colonia_poblacion' => null,
        'delegacion_municipio' => null,
        'ciudad' => null,
        'estado' => null,
        'cp' => null,
        'pais' => null
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
        'direccion' => 'direccion',
        'colonia_poblacion' => 'coloniaPoblacion',
        'delegacion_municipio' => 'delegacionMunicipio',
        'ciudad' => 'ciudad',
        'estado' => 'estado',
        'cp' => 'CP',
        'pais' => 'pais'
    ];

    protected static $setters = [
        'direccion' => 'setDireccion',
        'colonia_poblacion' => 'setColoniaPoblacion',
        'delegacion_municipio' => 'setDelegacionMunicipio',
        'ciudad' => 'setCiudad',
        'estado' => 'setEstado',
        'cp' => 'setCp',
        'pais' => 'setPais'
    ];

    protected static $getters = [
        'direccion' => 'getDireccion',
        'colonia_poblacion' => 'getColoniaPoblacion',
        'delegacion_municipio' => 'getDelegacionMunicipio',
        'ciudad' => 'getCiudad',
        'estado' => 'getEstado',
        'cp' => 'getCp',
        'pais' => 'getPais'
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
        $this->container['direccion'] = isset($data['direccion']) ? $data['direccion'] : null;
        $this->container['colonia_poblacion'] = isset($data['colonia_poblacion']) ? $data['colonia_poblacion'] : null;
        $this->container['delegacion_municipio'] = isset($data['delegacion_municipio']) ? $data['delegacion_municipio'] : null;
        $this->container['ciudad'] = isset($data['ciudad']) ? $data['ciudad'] : null;
        $this->container['estado'] = isset($data['estado']) ? $data['estado'] : null;
        $this->container['cp'] = isset($data['cp']) ? $data['cp'] : null;
        $this->container['pais'] = isset($data['pais']) ? $data['pais'] : null;
    }

    public function listInvalidProperties()
    {
        $invalidProperties = [];
        if ($this->container['direccion'] === null) {
            $invalidProperties[] = "'direccion' can't be null";
        }
        if ((mb_strlen($this->container['direccion']) > 85)) {
            $invalidProperties[] = "invalid value for 'direccion', the character length must be smaller than or equal to 85.";
        }
        if (!is_null($this->container['colonia_poblacion']) && (mb_strlen($this->container['colonia_poblacion']) > 65)) {
            $invalidProperties[] = "invalid value for 'colonia_poblacion', the character length must be smaller than or equal to 65.";
        }
        if (!is_null($this->container['delegacion_municipio']) && (mb_strlen($this->container['delegacion_municipio']) > 65)) {
            $invalidProperties[] = "invalid value for 'delegacion_municipio', the character length must be smaller than or equal to 65.";
        }
        if (!is_null($this->container['ciudad']) && (mb_strlen($this->container['ciudad']) > 65)) {
            $invalidProperties[] = "invalid value for 'ciudad', the character length must be smaller than or equal to 65.";
        }
        if ($this->container['estado'] === null) {
            $invalidProperties[] = "'estado' can't be null";
        }
        if ($this->container['cp'] === null) {
            $invalidProperties[] = "'cp' can't be null";
        }
        if ((mb_strlen($this->container['cp']) > 5)) {
            $invalidProperties[] = "invalid value for 'cp', the character length must be smaller than or equal to 5.";
        }
        if ($this->container['pais'] === null) {
            $invalidProperties[] = "'pais' can't be null";
        }
        return $invalidProperties;
    }

    public function valid()
    {
        return count($this->listInvalidProperties()) === 0;
    }

    public function getDireccion()
    {
        return $this->container['direccion'];
    }

    public function setDireccion($direccion)
    {
        if ((mb_strlen($direccion) > 85)) {
            throw new \InvalidArgumentException('invalid length for $direccion when calling Domicilio., must be smaller than or equal to 85.');
        }
        $this->container['direccion'] = $direccion;
        return $this;
    }

    public function getColoniaPoblacion()
    {
        return $this->container['colonia_poblacion'];
    }

    public function setColoniaPoblacion($colonia_poblacion)
    {
        if (!is_null($colonia_poblacion) && (mb_strlen($colonia_poblacion) > 65)) {
            throw new \InvalidArgumentException('invalid length for $colonia_poblacion when calling Domicilio., must be smaller than or equal to 65.');
        }
        $this->container['colonia_poblacion'] = $colonia_poblacion;
        return $this;
    }

    public function getDelegacionMunicipio()
    {
        return $this->container['delegacion_municipio'];
    }

    public function setDelegacionMunicipio($delegacion_municipio)
    {
        if (!is_null($delegacion_municipio) && (mb_strlen($delegacion_municipio) > 65)) {
            throw new \InvalidArgumentException('invalid length for $delegacion_municipio when calling Domicilio., must be smaller than or equal to 65.');
        }
        $this->container['delegacion_municipio'] = $delegacion_municipio;
        return $this;
    }

    public function getCiudad()
    {
        return $this->container['ciudad'];
    }

    public function setCiudad($ciudad)
    {
        if (!is_null($ciudad) && (mb_strlen($ciudad) > 65)) {
            throw new \InvalidArgumentException('invalid length for $ciudad when calling Domicilio., must be smaller than or equal to 65.');
        }
        $this->container['ciudad'] = $ciudad;
        return $this;
    }

    public function getEstado()
    {
        return $this->container['estado'];
    }

    public function setEstado($estado)
    {
        $this->container['estado'] = $estado;
        return $this;
    }

    public function getCp()
    {
        return $this->container['cp'];
    }

    public function setCp($cp)
    {
        if ((mb_strlen($cp) > 5)) {
            throw new \InvalidArgumentException('invalid length for $cp when calling Domicilio., must be smaller than or equal to 5.');
        }
        $this->container['cp'] = $cp;
        return $this;
    }

    public function getPais()
    {
        return $this->container['pais'];
    }

    public function setPais($pais)
    {
        $this->container['pais'] = $pais;
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
