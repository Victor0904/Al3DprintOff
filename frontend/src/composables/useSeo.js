/**
 * Composable SEO — gère dynamiquement les balises <head>
 * sans dépendance externe (manipulation DOM directe).
 */

const SITE_NAME = 'Al3DPrint'
const BASE_URL  = typeof window !== 'undefined' ? window.location.origin : import.meta.env.VITE_SITE_URL

/**
 * Retourne ou crée une balise <meta> unique identifiée par `attr=value`.
 */
function getOrCreateMeta(attr, value) {
  let el = document.querySelector(`meta[${attr}="${value}"]`)
  if (!el) {
    el = document.createElement('meta')
    el.setAttribute(attr, value)
    document.head.appendChild(el)
  }
  return el
}

/**
 * Retourne ou crée une balise <link> unique identifiée par `rel`.
 */
function getOrCreateLink(rel) {
  let el = document.querySelector(`link[rel="${rel}"]`)
  if (!el) {
    el = document.createElement('link')
    el.setAttribute('rel', rel)
    document.head.appendChild(el)
  }
  return el
}

/**
 * Injecte ou met à jour le script JSON-LD.
 * Un seul script JSON-LD "application/ld+json" de type seoMain par page.
 */
function setJsonLd(data) {
  let el = document.querySelector('script[data-seo="main"]')
  if (!el) {
    el = document.createElement('script')
    el.setAttribute('type', 'application/ld+json')
    el.setAttribute('data-seo', 'main')
    document.head.appendChild(el)
  }
  el.textContent = JSON.stringify(data)
}

function removeJsonLd() {
  const el = document.querySelector('script[data-seo="main"]')
  if (el) el.remove()
}

/**
 * API principale.
 *
 * @param {Object} options
 * @param {string}  options.title       — titre de l'onglet (sans suffixe site)
 * @param {string}  options.description — meta description (~155 chars)
 * @param {string}  [options.keywords]  — mots-clés (optionnel)
 * @param {string}  [options.image]     — URL absolue de l'image OG
 * @param {string}  [options.url]       — URL canonique absolue
 * @param {boolean} [options.noindex]   — true pour noindex,nofollow
 * @param {Object}  [options.jsonLd]    — objet JSON-LD (Schema.org)
 */
export function useSeo({
  title = SITE_NAME,
  description = "Al3DPrint — Impression 3D personnalisée de qualité. Découvrez nos produits innovants et créez vos objets sur mesure.",
  keywords = null,
  image = `${BASE_URL}/logo.png`,
  url = BASE_URL,
  noindex = false,
  jsonLd = null
} = {}) {

  const fullTitle = title === SITE_NAME
    ? `${SITE_NAME} — Impression 3D Personnalisée`
    : `${title} | ${SITE_NAME}`

  /* ── Title ─────────────────────────────────────────────────── */
  document.title = fullTitle

  /* ── Meta description ──────────────────────────────────────── */
  getOrCreateMeta('name', 'description').setAttribute('content', description)

  /* ── Meta keywords (optionnel) ─────────────────────────────── */
  if (keywords) {
    getOrCreateMeta('name', 'keywords').setAttribute('content', keywords)
  }

  /* ── Robots ────────────────────────────────────────────────── */
  getOrCreateMeta('name', 'robots').setAttribute(
    'content',
    noindex ? 'noindex, nofollow' : 'index, follow'
  )

  /* ── Canonical ─────────────────────────────────────────────── */
  getOrCreateLink('canonical').setAttribute('href', url)

  /* ── Open Graph ────────────────────────────────────────────── */
  getOrCreateMeta('property', 'og:title').setAttribute('content', fullTitle)
  getOrCreateMeta('property', 'og:description').setAttribute('content', description)
  getOrCreateMeta('property', 'og:image').setAttribute('content', image)
  getOrCreateMeta('property', 'og:url').setAttribute('content', url)
  getOrCreateMeta('property', 'og:type').setAttribute('content', 'website')
  getOrCreateMeta('property', 'og:site_name').setAttribute('content', SITE_NAME)
  getOrCreateMeta('property', 'og:locale').setAttribute('content', 'fr_FR')

  /* ── Twitter Card ──────────────────────────────────────────── */
  getOrCreateMeta('name', 'twitter:card').setAttribute('content', 'summary_large_image')
  getOrCreateMeta('name', 'twitter:title').setAttribute('content', fullTitle)
  getOrCreateMeta('name', 'twitter:description').setAttribute('content', description)
  getOrCreateMeta('name', 'twitter:image').setAttribute('content', image)

  /* ── JSON-LD ───────────────────────────────────────────────── */
  if (jsonLd) {
    setJsonLd(jsonLd)
  } else {
    removeJsonLd()
  }
}

export { BASE_URL, SITE_NAME }
