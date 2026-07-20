<?php

namespace App\Services\SICs\CirculoDeCredito\RCFicoScore\Model;

use \ArrayAccess;
use App\Services\SICs\CirculoDeCredito\RCFicoScore\ObjectSerializer;

class PersonaRespuesta implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    protected static $RCCPMModelName = 'PersonaRespuesta';

    protected static $RCCPMTypes = [
        'apellido_paterno' => 'string',
        'apellido_materno' => 'string',
        'apellido_adicional' => 'string',
        'nombres' => 'string',
        'fecha_nacimiento' => 'string',
        'rfc' => 'string',
        'curp' => 'string',
        'numero_seguridad_social' => 'string',
        'nacionalidad' => 'App\Services\SICs\CirculoDeCredito\RCFicoScore\Model\CatalogoNacionalidad',
        'residencia' => 'App\Services\SICs\CirculoDeCredito\RCFicoScore\Model\CatalogoResidencia',
        'estado_civil' => 'App\Services\SICs\CirculoDeCredito\RCFicoScore\Model\CatalogoEstadoCivil',
        'sexo' => 'App\Services\SICs\CirculoDeCredito\RCFicoScore\Model\CatalogoSexo',
        'clave_elector_ife' => 'string',
        'numero_dependientes' => 'int',
        'fecha_defuncion' => 'string'
    ];

    protected static $RCCPMFormats = [
        'apellido_paterno' => null,
        'apellido_materno' => null,
        'apellido_adicional' => null,
        'nombres' => null,
        'fecha_nacimiento' => 'yyyy-MM-dd',
        'rfc' => null,
        'curp' => null,
        'numero_seguridad_social' => null,
        'nacionalidad' => null,
        'residencia' => null,
        'estado_civil' => null,
        'sexo' => null,
        'clave_elector_ife' => null,
        'numero_dependientes' => 'int32',
        'fecha_defuncion' => 'yyyy-MM-dd'
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
        'apellido_adicional' => 'apellidoAdicional',
        'nombres' => 'nombres',
        'fecha_nacimiento' => 'fechaNacimiento',
        'rfc' => 'RFC',
        'curp' => 'CURP',
        'numero_seguridad_social' => 'numeroSeguridadSocial',
        'nacionalidad' => 'nacionalidad',
        'residencia' => 'residencia',
        'estado_civil' => 'estadoCivil',
        'sexo' => 'sexo',
        'clave_elector_ife' => 'claveElectorIFE',
        'numero_dependientes' => 'numeroDependientes',
        'fecha_defuncion' => 'fechaDefuncion'
    ];

    protected static $setters = [
        'apellido_paterno' => 'setApellidoPaterno',
        'apellido_materno' => 'setApellidoMaterno',
        'apellido_adicional' => 'setApellidoAdicional',
        'nombres' => 'setNombres',
        'fecha_nacimiento' => 'setFechaNacimiento',
        'rfc' => 'setRfc',
        'curp' => 'setCurp',
        'numero_seguridad_social' => 'setNumeroSeguridadSocial',
        'nacionalidad' => 'setNacionalidad',
        'residencia' => 'setResidencia',
        'estado_civil' => 'setEstadoCivil',
        'sexo' => 'setSexo',
        'clave_elector_ife' => 'setClaveElectorIfe',
        'numero_dependientes' => 'setNumeroDependientes',
        'fecha_defuncion' => 'setFechaDefuncion'
    ];

    protected static $getters = [
        'apellido_paterno' => 'getApellidoPaterno',
        'apellido_materno' => 'getApellidoMaterno',
        'apellido_adicional' => 'getApellidoAdicional',
        'nombres' => 'getNombres',
        'fecha_nacimiento' => 'getFechaNacimiento',
        'rfc' => 'getRfc',
        'curp' => 'getCurp',
        'numero_seguridad_social' => 'getNumeroSeguridadSocial',
        'nacionalidad' => 'getNacionalidad',
        'residencia' => 'getResidencia',
        'estado_civil' => 'getEstadoCivil',
        'sexo' => 'getSexo',
        'clave_elector_ife' => 'getClaveElectorIfe',
        'numero_dependientes' => 'getNumeroDependientes',
        'fecha_defuncion' => 'getFechaDefuncion'
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
        $this->container['apellido_adicional'] = isset($data['apellido_adicional']) ? $data['apellido_adicional'] : null;
        $this->container['nombres'] = isset($data['nombres']) ? $data['nombres'] : null;
        $this->container['fecha_nacimiento'] = isset($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null;
        $this->container['rfc'] = isset($data['rfc']) ? $data['rfc'] : null;
        $this->container['curp'] = isset($data['curp']) ? $data['curp'] : null;
        $this->container['numero_seguridad_social'] = isset($data['numero_seguridad_social']) ? $data['numero_seguridad_social'] : null;
        $this->container['nacionalidad'] = isset($data['nacionalidad']) ? $data['nacionalidad'] : null;
        $this->container['residencia'] = isset($data['residencia']) ? $data['residencia'] : null;
        $this->container['estado_civil'] = isset($data['estado_civil']) ? $data['estado_civil'] : null;
        $this->container['sexo'] = isset($data['sexo']) ? $data['sexo'] : null;
        $this->container['clave_elector_ife'] = isset($data['clave_elector_ife']) ? $data['clave_elector_ife'] : null;
        $this->container['numero_dependientes'] = isset($data['numero_dependientes']) ? $data['numero_dependientes'] : null;
        $this->container['fecha_defuncion'] = isset($data['fecha_defuncion']) ? $data['fecha_defuncion'] : null;
    }

    public function listInvalidProperties()
    {
        $invalidProperties = [];
        if (!is_null($this->container['apellido_paterno']) && (mb_strlen($this->container['apellido_paterno']) > 30)) {
            $invalidProperties[] = "invalid value for 'apellido_paterno', the character length must be smaller than or equal to 30.";
        }
        if (!is_null($this->container['apellido_materno']) && (mb_strlen($this->container['apellido_materno']) > 30)) {
            $invalidProperties[] = "invalid value for 'apellido_materno', the character length must be smaller than or equal to 30.";
        }
        if (!is_null($this->container['apellido_adicional']) && (mb_strlen($this->container['apellido_adicional']) > 30)) {
            $invalidProperties[] = "invalid value for 'apellido_adicional', the character length must be smaller than or equal to 30.";
        }
        if (!is_null($this->container['nombres']) && (mb_strlen($this->container['nombres']) > 50)) {
            $invalidProperties[] = "invalid value for 'nombres', the character length must be smaller than or equal to 50.";
        }
        if (!is_null($this->container['numero_seguridad_social']) && (mb_strlen($this->container['numero_seguridad_social']) > 11)) {
            $invalidProperties[] = "invalid value for 'numero_seguridad_social', the character length must be smaller than or equal to 11.";
        }
        if (!is_null($this->container['clave_elector_ife']) && (mb_strlen($this->container['clave_elector_ife']) > 20)) {
            $invalidProperties[] = "invalid value for 'clave_elector_ife', the character length must be smaller than or equal to 20.";
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
        if (!is_null($apellido_paterno) && (mb_strlen($apellido_paterno) > 30)) {
            throw new \InvalidArgumentException('invalid length for $apellido_paterno when calling PersonaRespuesta., must be smaller than or equal to 30.');
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
            throw new \InvalidArgumentException('invalid length for $apellido_materno when calling PersonaRespuesta., must be smaller than or equal to 30.');
        }
        $this->container['apellido_materno'] = $apellido_materno;
        return $this;
    }

    public function getApellidoAdicional()
    {
        return $this->container['apellido_adicional'];
    }

    public function setApellidoAdicional($apellido_adicional)
    {
        if (!is_null($apellido_adicional) && (mb_strlen($apellido_adicional) > 30)) {
            throw new \InvalidArgumentException('invalid length for $apellido_adicional when calling PersonaRespuesta., must be smaller than or equal to 30.');
        }
        $this->container['apellido_adicional'] = $apellido_adicional;
        return $this;
    }

    public function getNombres()
    {
        return $this->container['nombres'];
    }

    public function setNombres($nombres)
    {
        if (!is_null($nombres) && (mb_strlen($nombres) > 50)) {
            throw new \InvalidArgumentException('invalid length for $nombres when calling PersonaRespuesta., must be smaller than or equal to 50.');
        }
        $this->container['nombres'] = $nombres;
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

    public function getCurp()
    {
        return $this->container['curp'];
    }

    public function setCurp($curp)
    {
        $this->container['curp'] = $curp;
        return $this;
    }

    public function getNumeroSeguridadSocial()
    {
        return $this->container['numero_seguridad_social'];
    }

    public function setNumeroSeguridadSocial($numero_seguridad_social)
    {
        if (!is_null($numero_seguridad_social) && (mb_strlen($numero_seguridad_social) > 11)) {
            throw new \InvalidArgumentException('invalid length for $numero_seguridad_social when calling PersonaRespuesta., must be smaller than or equal to 11.');
        }
        $this->container['numero_seguridad_social'] = $numero_seguridad_social;
        return $this;
    }

    public function getNacionalidad()
    {
        return $this->container['nacionalidad'];
    }

    public function setNacionalidad($nacionalidad)
    {
        $this->container['nacionalidad'] = $nacionalidad;
        return $this;
    }

    public function getResidencia()
    {
        return $this->container['residencia'];
    }

    public function setResidencia($residencia)
    {
        $this->container['residencia'] = $residencia;
        return $this;
    }

    public function getEstadoCivil()
    {
        return $this->container['estado_civil'];
    }

    public function setEstadoCivil($estado_civil)
    {
        $this->container['estado_civil'] = $estado_civil;
        return $this;
    }

    public function getSexo()
    {
        return $this->container['sexo'];
    }

    public function setSexo($sexo)
    {
        $this->container['sexo'] = $sexo;
        return $this;
    }

    public function getClaveElectorIfe()
    {
        return $this->container['clave_elector_ife'];
    }

    public function setClaveElectorIfe($clave_elector_ife)
    {
        if (!is_null($clave_elector_ife) && (mb_strlen($clave_elector_ife) > 20)) {
            throw new \InvalidArgumentException('invalid length for $clave_elector_ife when calling PersonaRespuesta., must be smaller than or equal to 20.');
        }
        $this->container['clave_elector_ife'] = $clave_elector_ife;
        return $this;
    }

    public function getNumeroDependientes()
    {
        return $this->container['numero_dependientes'];
    }

    public function setNumeroDependientes($numero_dependientes)
    {
        $this->container['numero_dependientes'] = $numero_dependientes;
        return $this;
    }

    public function getFechaDefuncion()
    {
        return $this->container['fecha_defuncion'];
    }

    public function setFechaDefuncion($fecha_defuncion)
    {
        $this->container['fecha_defuncion'] = $fecha_defuncion;
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
