export function documentVersionChain(documents, current) {
    const byId = new Map(documents.map((document) => [document.id, document]));
    const history = [];
    const seen = new Set();
    let id = current?.reemplaza_documento_id;
    while (id && !seen.has(id)) {
        seen.add(id);
        const document = byId.get(id);
        if (!document) break;
        history.push(document);
        id = document.reemplaza_documento_id;
    }
    return history;
}
