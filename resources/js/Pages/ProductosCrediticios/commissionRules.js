const normalCourseMoments = [
    "inicio",
    "firma",
    "desembolso_descuento",
    "cada_pago",
];

export const commissionIncludesCat = (commission) =>
    commission.obligatoria === true &&
    normalCourseMoments.includes(commission.momento_cobro);

export const commissionCatReason = (commission) => {
    if (["evento", "liquidacion"].includes(commission.momento_cobro))
        return "Se excluye porque depende de un evento; el CAT supone cumplimiento puntual y sin prepago.";
    if (!commission.obligatoria)
        return "Se excluye del CAT base porque el cliente puede elegir no contratarla.";

    return "Se incluye porque es un cargo obligatorio del curso normal del crédito.";
};

export const isSelectableOptionalCommission = (commission) =>
    commission.obligatoria === false &&
    normalCourseMoments.includes(commission.momento_cobro);

export const isConditionalCommission = (commission) =>
    ["evento", "liquidacion"].includes(commission.momento_cobro);
