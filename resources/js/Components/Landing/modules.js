// Public copy and captures belong together. Images contain synthetic or hidden data.
export const featuredModules = [
    {
        id: "productos",
        stage: "01 / Preparar",
        name: "Productos crediticios",
        icon: "pi pi-sliders-h",
        title: "Define una base clara.",
        description:
            "Organiza montos, tasas, periodicidades, comisiones e impuestos. Cada versión conserva las condiciones con las que se originó el crédito.",
        steps: [
            "Configura una versión con sus condiciones y reglas de pago.",
            "Simula la tabla y revisa capital, intereses y cargos.",
            "Activa la versión que podrá elegir tu equipo al originar.",
        ],
        shot: {
            src: "/images/landing/productos.webp",
            title: "Productos y versiones",
            alt: "Catálogo de productos crediticios y sus versiones",
            caption: "Una base versionada para cada nuevo crédito.",
            width: 1061,
            height: 620,
        },
    },
    {
        id: "documentos",
        stage: "01 / Preparar",
        name: "Documentos y plantillas",
        icon: "pi pi-file-edit",
        title: "Dale forma a cada documento.",
        description:
            "Prepara plantillas con variables, encabezados y pies. Revisa el PDF y conserva versiones para que cada documento generado mantenga su historia.",
        steps: [
            "Elige el tipo de documento y prepara su contenido.",
            "Previsualiza el PDF antes de activar la plantilla.",
            "Genera desde el expediente o la solicitud y consulta su versión.",
        ],
        shot: {
            src: "/images/landing/documentos.webp",
            title: "Documentos y plantillas",
            alt: "Centro documental con plantillas y versiones",
            caption: "Del borrador al PDF, con contexto y versiones.",
            width: 1061,
            height: 620,
        },
    },
    {
        id: "solicitudes",
        stage: "02–03 / Originar y resolver",
        name: "Solicitudes",
        icon: "pi pi-folder-open",
        title: "Un siguiente paso visible.",
        description:
            "Conecta la solicitud con el cliente, el expediente y el producto. Captura, revisión, aprobación y formalización se reúnen en un mismo recorrido.",
        steps: [
            "Selecciona cliente y producto; completa las condiciones.",
            "Envía a revisión y atiende los requisitos de la etapa.",
            "Resuelve con el rol autorizado y continúa a formalización.",
        ],
        shot: {
            src: "/images/landing/solicitudes.webp",
            title: "Solicitudes y próximos pasos",
            alt: "Solicitudes con filtros, estados y próximos pasos",
            caption: "Responsables, estados y acciones en el mismo lugar.",
            width: 1076,
            height: 620,
        },
    },
    {
        id: "creditos",
        stage: "04 / Acompañar",
        name: "Créditos y pagos",
        icon: "pi pi-wallet",
        title: "Acompaña hasta el último pago.",
        description:
            "Consulta cartera, saldos y movimientos. En el flujo QA, registra pagos, anticipos y liquidación con una previa, recibos y calendarios que conservan sus versiones.",
        steps: [
            "Abre el crédito y revisa el saldo y los cargos a la fecha.",
            "Cotiza el pago, anticipo o liquidación y revisa su distribución.",
            "Confirma la operación QA y consulta el recibo y su historial.",
        ],
        shot: {
            src: "/images/landing/creditos.webp",
            title: "Créditos y pagos",
            alt: "Listado de créditos y pagos con resumen y filtros",
            caption: "La cartera y los movimientos continúan el recorrido.",
            width: 1076,
            height: 620,
        },
    },
];

export const sicShots = [
    {
        src: "/images/landing/sic-reporte.png",
        title: "Del score al contexto",
        alt: "Reporte SIC sintético con score, razones y saldos reportados en MXN",
        caption: "Modelo, razones y saldos, con su fuente y fecha.",
        width: 1440,
        height: 1000,
    },
    {
        src: "/images/landing/sic-cuentas.png",
        title: "Lo que cuentan las cuentas",
        alt: "Gráficas sintéticas de composición de cuentas y saldos vencidos",
        caption: "Composición y vencidos, con sus datos disponibles.",
        width: 983,
        height: 426,
    },
    {
        src: "/images/landing/sic-historial.png",
        title: "Cada consulta tiene historia",
        alt: "Historial de consultas SIC con servicios y fechas de ejemplo",
        caption: "Vuelve a los resultados y sigue el estado de cada intento.",
        width: 998,
        height: 371,
    },
];

export const accessModule = {
    id: "accesos",
    stage: "El equipo / Roles y permisos",
    name: "Roles y permisos",
    icon: "pi pi-shield",
    title: "Cada persona, con su alcance.",
    description:
        "Define quién consulta, quién captura y quién autoriza. Los roles reúnen permisos por módulo y el contexto de equipo y sucursal acompaña la operación.",
    steps: [
        "Crea un rol para una responsabilidad: asesor, analista o caja.",
        "Selecciona los permisos por módulo y revisa sus integrantes.",
        "Asigna el rol y comprueba el acceso con esa cuenta.",
    ],
    shot: {
        src: "/images/landing/roles-permisos.png",
        title: "Roles y permisos",
        alt: "Administración de un rol de ejemplo con permisos agrupados por módulo",
        caption: "Permisos visibles y responsabilidades explícitas.",
        width: 1440,
        height: 1000,
    },
};

export const supportingModules = [
    {
        id: "clientes",
        icon: "pi pi-users",
        name: "Clientes y expediente",
        description:
            "Identidad, perfil económico, referencias, garantías, documentos y consentimientos conectados con cada solicitud.",
        steps: [
            "Registra al cliente en su sucursal responsable.",
            "Completa los datos y adjunta las evidencias requeridas.",
            "Valida el expediente y abre la solicitud desde su ficha.",
        ],
    },
    {
        id: "evaluacion",
        icon: "pi pi-chart-line",
        name: "Evaluación y capacidad",
        description:
            "Un espacio para documentar el análisis humano, sus fuentes, metodología y capacidad de pago.",
        steps: [
            "Abre la revisión vigente de la solicitud.",
            "Contrasta ingresos, deuda y condiciones solicitadas.",
            "Registra el dictamen y el fundamento de tu evaluación.",
        ],
    },
    {
        id: "cumplimiento",
        icon: "pi pi-verified",
        name: "Cumplimiento y PLD",
        description:
            "Documenta la revisión manual, el nivel de riesgo y el seguimiento con acceso reservado a los responsables.",
        steps: [
            "Revisa expediente y fuentes disponibles para el caso.",
            "Documenta hallazgos, nivel de riesgo y fundamento.",
            "Registra el resultado y el seguimiento que corresponda.",
        ],
    },
    {
        id: "firma",
        icon: "pi pi-pencil",
        name: "Contrato y firma",
        description:
            "En QA, genera el paquete con sus condiciones congeladas, recibe la copia digitalizada y conserva la revisión manual.",
        steps: [
            "Genera el contrato desde una solicitud aprobada.",
            "Recibe una copia firmada de prueba y revisa sus páginas.",
            "Acepta o devuelve con motivo, conservando la evidencia.",
        ],
    },
    {
        id: "desembolso",
        icon: "pi pi-arrow-up-right",
        name: "Desembolso QA",
        description:
            "Conecta la formalización con la apertura del crédito mediante un registro interno de prueba, con fecha y referencia.",
        steps: [
            "Revisa la formalización y la fecha del paquete.",
            "Captura la referencia sintética y verifica el importe.",
            "Confirma el registro QA y abre el crédito creado.",
        ],
    },
    {
        id: "anticipos",
        icon: "pi pi-calculator",
        name: "Anticipos y liquidación",
        description:
            "En QA, compara el efecto sobre el capital y las cuotas antes de confirmar. El contrato determina si se reduce plazo o pago.",
        steps: [
            "Elige anticipo parcial o liquidación total.",
            "Revisa devengos, cargos, distribución y nuevo calendario.",
            "Confirma y conserva recibo, movimientos y versiones.",
        ],
    },
    {
        id: "usuarios",
        icon: "pi pi-user-plus",
        name: "Usuarios y equipos",
        description:
            "Organiza integrantes, estados de acceso y responsabilidades. Cada persona puede reforzar su cuenta con verificación en dos pasos.",
        steps: [
            "Invita o registra al integrante autorizado.",
            "Configura su equipo, sucursales y roles.",
            "Cada integrante puede activar la verificación en dos pasos desde su perfil.",
        ],
    },
    {
        id: "sucursales",
        icon: "pi pi-map-marker",
        name: "Empresa y sucursales",
        description:
            "Centraliza la configuración de la institución y organiza los puntos de operación con sus propios datos y folios.",
        steps: [
            "Completa la información de la empresa.",
            "Configura las sucursales y sus identificadores.",
            "Asigna al equipo y selecciona el contexto para operar.",
        ],
    },
    {
        id: "tablero",
        icon: "pi pi-home",
        name: "Tablero y Mi trabajo",
        description:
            "Encuentra un resumen de la operación y las solicitudes que requieren tu atención, según tus permisos y contexto.",
        steps: [
            "Entra al tablero con tu cuenta.",
            "Abre Mi trabajo y filtra los casos pendientes.",
            "Continúa desde la acción disponible para cada etapa.",
        ],
    },
    {
        id: "actividad",
        icon: "pi pi-history",
        name: "Actividad e historial",
        description:
            "Consulta los eventos registrados y las evidencias que cada flujo conserva para entender qué ocurrió y cuándo.",
        steps: [
            "Abre el historial del caso o la actividad autorizada.",
            "Revisa evento, fecha y responsable registrados.",
            "Contrasta con el documento, recibo o versión relacionados.",
        ],
    },
];
