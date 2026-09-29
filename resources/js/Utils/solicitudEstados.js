export const solicitudEstados = {
    borrador: { label: "Borrador", siguiente: "Completar los datos y enviar a revisión." },
    en_revision: { label: "En revisión", siguiente: "Evaluación y cumplimiento pendientes; no hay aprobación ni autorización de desembolso." },
    devuelta: { label: "Devuelta para corrección", siguiente: "Consultar el motivo, corregir los datos y reenviar una nueva revisión." },
    rechazada: { label: "Rechazada", siguiente: "Solicitud cerrada. Consulta el motivo y el historial." },
    cancelada: { label: "Cancelada", siguiente: "Solicitud cerrada. Se conserva la evidencia registrada." },
    aprobada: { label: "Aprobada", siguiente: "Abre Contrato y tabla de pagos: prepara el original, recibe la copia firmada y solicita su revisión. Aprobar no equivale a firmar ni desembolsar." },
    formalizada: { label: "Formalizada · QA", siguiente: "La copia firmada fue aceptada en QA. Contrato, tabla y evidencia están conservados. El desembolso se implementará en la siguiente entrega; no hay transferencia registrada." },
};

export function solicitudEditable(estado) {
    return ["borrador", "devuelta"].includes(estado);
}
