let csrfLoaded = false
let lastLoadedAt = null
const EXPIRATION_TIME_MS = 30 * 60 * 1000 // 30 minutos

export async function ensureCsrfCookie(force = false) {
  const now = Date.now()

  // Si ya se cargó y no ha expirado, no hacemos nada
  if (!force && csrfLoaded && (now - lastLoadedAt < EXPIRATION_TIME_MS)) {
    return
  }

  try {
    const response = await fetch('/sanctum/csrf-cookie', {
      credentials: 'include',
    })

    if (!response.ok) {
      throw new Error(`CSRF cookie fetch failed with status ${response.status}`)
    }

    csrfLoaded = true
    lastLoadedAt = now
  } catch (error) {
    console.error('[CSRF] Error al obtener cookie CSRF:', error)
    throw error
  }
}
