export const sugerencias = async (cp) => {
    try {
        const response = await fetch(`/codigos-postales/sugerencias?codigo=${cp}`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Error ${response.status}: ${response.statusText}`)
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error al obtener sugerencias de C.P:', error);
        throw error;
    }
};

export const buscar = async (cp) => {
    try {
        const response = await fetch(`/codigos-postales/buscar?codigo=${cp}`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Error ${response.status}: ${response.statusText}`)
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error al buscar C.P:', error);
        throw error;
    }
};