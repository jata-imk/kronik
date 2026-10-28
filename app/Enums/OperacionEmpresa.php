<?php

namespace App\Enums;

enum OperacionEmpresa: string
{
    case Capturar = 'capturar';
    case Originar = 'originar';
    case Documentar = 'documentar';
    case ConsultarSic = 'consultar_sic';
    case Formalizar = 'formalizar';
    case Desembolsar = 'desembolsar';
    case AtenderCartera = 'atender_cartera';
    case ReportarCartera = 'reportar_cartera';
}
