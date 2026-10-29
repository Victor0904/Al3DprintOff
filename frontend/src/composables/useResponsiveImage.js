// Les images du catalogue existent en 400, 800 et 1600 px : « …/01-800.webp ».
const SIZED = /-(400|800|1600)\.webp$/

// URL de la même image dans une autre taille (inchangée si l'image n'est pas déclinée)
export const sizedUrl = (url, size) =>
  url && SIZED.test(url) ? url.replace(SIZED, `-${size}.webp`) : url

// srcset pour laisser le navigateur choisir selon l'écran (mobile / PC / Retina)
export const srcsetFor = (url) =>
  url && SIZED.test(url)
    ? [400, 800, 1600].map((s) => `${sizedUrl(url, s)} ${s}w`).join(', ')
    : undefined
