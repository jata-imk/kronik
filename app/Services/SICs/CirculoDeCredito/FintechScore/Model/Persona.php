<?php

namespace App\Services\SICs\CirculoDeCredito\FintechScore\Model;

use ArrayAccess;
use App\Services\SICs\CirculoDeCredito\FintechScore\ObjectSerializer;

class Persona implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    protected static $RCCPMModelName = 'Persona';

    protected static $RCCPMTypes = [
        'apellido_paterno' => 'string',
        'apellido_materno' => 'string',
        'primer_nombre' => 'string',
        'segundo_nombre' => 'string',
        'fecha_nacimiento' => 'string',
        'rfc' => 'string',
        'domicilio' => '\App\Services\SICs\CirculoDeCredito\FintechScore\Model\Domicilio'
    ];

    protected static $RCCPMFormats = [
        'apellido_paterno' => null,
        'apellido_materno' => null,
        'primer_nombre' => null,
        'segundo_nombre' => null,
        'fecha_nacimiento' => 'yyyy-MM-dd',
        'rfc' => null,
        'domicilio' => null
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
        'apellido_paterno' => 'apellidoPaterno',
        'apellido_materno' => 'apellidoMaterno',
        'primer_nombre' => 'primerNombre',
        'segundo_nombre' => 'segundoNombre',
        'fecha_nacimiento' => 'fechaNacimiento',
        'rfc' => 'RFC',
        'domicilio' => 'domicilio'
    ];

    protected static $setters = [
        'apellido_paterno' => 'setApellidoPaterno',
        'apellido_materno' => 'setApellidoMaterno',
        'primer_nombre' => 'setPrimerNombre',
        'segundo_nombre' => 'setSegundoNombre',
        'fecha_nacimiento' => 'setFechaNacimiento',
        'rfc' => 'setRfc',
        'domicilio' => 'setDomicilio'
    ];

    protected static $getters = [
        'apellido_paterno' => 'getApellidoPaterno',
        'apellido_materno' => 'getApellidoMaterno',
        'primer_nombre' => 'getPrimerNombre',
        'segundo_nombre' => 'getSegundoNombre',
        'fecha_nacimiento' => 'getFechaNacimiento',
        'rfc' => 'getRfc',
        'domicilio' => 'getDomicilio'
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
        $this->container['apellido_paterno'] = isset($data['apellido_paterno']) ? $data['apellido_paterno'] : null;
        $this->container['apellido_materno'] = isset($data['apellido_materno']) ? $data['apellido_materno'] : null;
        $this->container['primer_nombre'] = isset($data['primer_nombre']) ? $data['primer_nombre'] : null;
        $this->container['segundo_nombre'] = isset($data['segundo_nombre']) ? $data['segundo_nombre'] : null;
        $this->container['fecha_nacimiento'] = isset($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null;
        $this->container['rfc'] = isset($data['rfc']) ? $data['rfc'] : null;
        $this->container['domicilio'] = isset($data['domicilio']) ? $data['domicilio'] : null;
    }

    public function listInvalidProperties()
    {
        $invalidProperties = [];
        if ($this->container['apellido_paterno'] === null) {
            $invalidProperties[] = "'apellido_paterno' can't be null";
        }
        if ((mb_strlen($this->container['apellido_paterno']) > 30)) {
            $invalidProperties[] = "invalid value for 'apellido_paterno', the character length must be smaller than or equal to 30.";
        }
        if (!is_null($this->container['apellido_materno']) && (mb_strlen($this->container['apellido_materno']) > 30)) {
            $invalidProperties[] = "invalid value for 'apellido_materno', the character length must be smaller than or equal to 30.";
        }
        if ($this->container['primer_nombre'] === null) {
            $invalidProperties[] = "'primer_nombre' can't be null";
        }
        if ((mb_strlen($this->container['primer_nombre']) > 50)) {
            $invalidProperties[] = "invalid value for 'primer_nombre', the character length must be smaller than or equal to 50.";
        }
        if (!is_null($this->container['segundo_nombre']) && (mb_strlen($this->container['segundo_nombre']) > 50)) {
            $invalidProperties[] = "invalid value for 'segundo_nombre', the character length must be smaller than or equal to 50.";
        }
        if ($this->container['fecha_nacimiento'] === null) {
            $invalidProperties[] = "'fecha_nacimiento' can't be null";
        }
        if ($this->container['domicilio'] === null) {
            $invalidProperties[] = "'domicilio' can't be null";
        }
        return $invalidProperties;
    }

    public function valid()
    {
        return count($this->listInvalidProperties()) === 0;
    }

    public function getApellidoPaterno()
    {
        return $this->container['apellido_paterno'];
    }

    public function setApellidoPaterno($apellido_paterno)
    {
        if ((mb_strlen($apellido_paterno) > 30)) {
            throw new \InvalidArgumentException('invalid length for $apellido_paterno when calling Persona., must be smaller than or equal to 30.');
        }
        $this->container['apellido_paterno'] = $apellido_paterno;
        return $this;
    }

    public function getApellidoMaterno()
    {
        return $this->container['apellido_materno'];
    }

    public function setApellidoMaterno($apellido_materno)
    {
        if (!is_null($apellido_materno) && (mb_strlen($apellido_materno) > 30)) {
            throw new \InvalidArgumentException('invalid length for $apellido_materno when calling Persona., must be smaller than or equal to 30.');
        }
        $this->container['apellido_materno'] = $apellido_materno;
        return $this;
    }

    public function getPrimerNombre()
    {
        return $this->container['primer_nombre'];
    }

    public function setPrimerNombre($primer_nombre)
    {
        if ((mb_strlen($primer_nombre) > 50)) {
            throw new \InvalidArgumentException('invalid length for $primer_nombre when calling Persona., must be smaller than or equal to 50.');
        }
        $this->container['primer_nombre'] = $primer_nombre;
        return $this;
    }

    public function getSegundoNombre()
    {
        return $this->container['segundo_nombre'];
    }

    public function setSegundoNombre($segundo_nombre)
    {
        if (!is_null($segundo_nombre) && (mb_strlen($segundo_nombre) > 50)) {
            throw new \InvalidArgumentException('invalid length for $segundo_nombre when calling Persona., must be smaller than or equal to 50.');
        }
        $this->container['segundo_nombre'] = $segundo_nombre;
        return $this;
    }

    public function getFechaNacimiento()
    {
        return $this->container['fecha_nacimiento'];
    }

    public function setFechaNacimiento($fecha_nacimiento)
    {
        $this->container['fecha_nacimiento'] = $fecha_nacimiento;
        return $this;
    }

    public function getRfc()
    {
        return $this->container['rfc'];
    }

    public function setRfc($rfc)
    {
        $this->container['rfc'] = $rfc;
        return $this;
    }

    public function getDomicilio()
    {
        return $this->container['domicilio'];
    }

    public function setDomicilio($domicilio)
    {
        $this->container['domicilio'] = $domicilio;
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
