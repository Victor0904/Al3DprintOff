<template>
  <div class="homepage">
    <!-- Hero Section -->
    <section class="hero">
      <div class="hero-background">
        <div class="hero-particles">
          <div class="particle" v-for="i in 50" :key="i" :style="getParticleStyle(i)"></div>
        </div>
      </div>

      <div class="container">
        <div class="hero-content">
          <div class="hero-text">
            <h1 class="hero-title">
              Bienvenue dans l'<span class="highlight">Avenir</span><br>
              de l'Impression 3D
            </h1>
            <p class="hero-description">
              Découvrez notre collection exclusive de produits imprimés en 3D,
              personnalisables selon vos envies. Qualité premium, design futuriste.
            </p>
            <div class="hero-actions">
              <router-link to="/products" class="btn btn-primary hero-btn">
                Découvrir les produits
              </router-link>
              <button class="btn btn-secondary hero-btn" @click="scrollToFeatures">
                En savoir plus
              </button>
            </div>
          </div>

          <!-- 3D Print Animation -->
          <div class="hero-visual">
            <div class="printer-wrapper">
              <canvas ref="canvas3d" class="printer-canvas"></canvas>
              <div class="printer-status">
                <span class="status-dot" :class="{ building: isBuilding }"></span>
                <span class="status-text">
                  {{ isBuilding
                    ? `Impression · Couche ${currentLayer} / ${TOTAL_LAYERS}`
                    : '✓ Pièce terminée' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="features" ref="featuresSection">
      <div class="container">
        <h2 class="section-title">Pourquoi choisir Al3DPrint ?</h2>
        <div class="features-grid">
          <div class="feature-card" v-for="feature in features" :key="feature.id">
            <div class="feature-icon"><Icon :name="feature.icon" :size="28" /></div>
            <h3 class="feature-title">{{ feature.title }}</h3>
            <p class="feature-description">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Products Section -->
    <section class="products-section">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Produits Populaires</h2>
          <router-link to="/products" class="view-all-link">
            Voir tous les produits →
          </router-link>
        </div>

        <div v-if="loading" class="loading-container">
          <div class="loading-spinner"></div>
          <p>Chargement des produits...</p>
        </div>

        <div v-else-if="products.length === 0" class="no-products">
          <div class="no-products-icon">🔮</div>
          <h3>Aucun produit disponible</h3>
          <p>Revenez bientôt pour découvrir nos créations !</p>
        </div>

        <div v-else class="products-grid">
          <ProductCard
            v-for="product in products.slice(0, 6)"
            :key="product.id"
            :product="product"
            @add-to-cart="addToCart"
          />
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
      <div class="container">
        <div class="cta-content">
          <h2 class="cta-title">Prêt à créer l'avenir ?</h2>
          <p class="cta-description">
            Rejoignez notre communauté et découvrez des produits uniques
            imprimés selon vos spécifications.
          </p>
          <div class="cta-actions">
            <router-link to="/products" class="btn btn-primary cta-btn">
              Commencer maintenant
            </router-link>
            <router-link to="/about" class="btn btn-secondary cta-btn">
              En savoir plus
            </router-link>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useCartStore } from '../stores/cart'
import ProductCard from '../components/ProductCard.vue'
import Icon from '../components/Icon.vue'

const cartStore = useCartStore()

// ─── état produits ───────────────────────────────────────────────
const loading = ref(true)
const products = ref([])
const featuresSection = ref(null)

const features = ref([
  { id: 1, icon: 'palette', title: 'Personnalisation', description: 'Chaque produit peut être personnalisé selon vos envies et besoins spécifiques.' },
  { id: 2, icon: 'bolt', title: 'Impression Rapide', description: 'Technologie d\'impression 3D de pointe pour des délais de livraison optimisés.' },
  { id: 3, icon: 'award', title: 'Qualité Premium', description: 'Matériaux de haute qualité et finitions impeccables pour tous nos produits.' },
  { id: 4, icon: 'leaf', title: 'Écologique', description: 'Matériaux recyclables et processus de production respectueux de l\'environnement.' }
])

const scrollToFeatures = () => featuresSection.value?.scrollIntoView({ behavior: 'smooth' })
const addToCart = (product) => cartStore.addToCart(product)

const getParticleStyle = (i) => {
  const delay    = Math.random() * 5
  const duration = 3 + Math.random() * 4
  const size     = 1 + Math.random() * 3
  const left     = Math.random() * 100
  return { left: `${left}%`, animationDelay: `${delay}s`, animationDuration: `${duration}s`, width: `${size}px`, height: `${size}px` }
}

const loadProducts = async () => {
  try {
    const r = await fetch('/api/produits?actif=true')
    if (r.ok) products.value = await r.json()
  } catch {}
  finally { loading.value = false }
}

// ─── Canvas 3D print animation ───────────────────────────────────
const canvas3d   = ref(null)
const isBuilding = ref(true)
const currentLayer = ref(0)
const TOTAL_LAYERS = 22

const NUM_RINGS = 22
const NUM_PTS   = 20

// Génère une vase en mode spiral (comme en impression 3D réelle)
function buildVase () {
  const rings = []
  for (let r = 0; r < NUM_RINGS; r++) {
    const t      = r / (NUM_RINGS - 1)                         // 0 = bas, 1 = haut
    const radius = 38 + 22 * Math.sin(t * Math.PI * 0.98)      // profil vase
    const y      = (t - 0.5) * 185
    const twist  = r * 0.20                                     // décalage spirale
    const ring   = []
    for (let p = 0; p < NUM_PTS; p++) {
      const a = twist + (p / NUM_PTS) * Math.PI * 2
      ring.push({ x: Math.cos(a) * radius, y, z: Math.sin(a) * radius })
    }
    rings.push(ring)
  }
  return rings
}
const VASE = buildVase()

// Projection 3-D → 2-D (perspective simple, rotation Y)
function proj (pt, rotY, cx, cy, sc) {
  const cY = Math.cos(rotY), sY = Math.sin(rotY)
  const rx =  pt.x * cY + pt.z * sY
  const rz = -pt.x * sY + pt.z * cY
  const fov = 400
  const d   = fov / (fov + rz + 200)
  return { px: cx + rx * d * sc, py: cy + pt.y * d * sc, d }
}

function mkState (prevRotY = 0) {
  return {
    printedRings : 0,
    ringAlpha    : new Float32Array(NUM_RINGS),
    rotY         : prevRotY,
    nozzleAngle  : 0,
    phase        : 'building',   // 'building' | 'show' | 'fade'
    elapsed      : 0,
    lastTs       : null,
    fadeOut      : 1
  }
}

let animFrame = null
let S = null

function startAnim () {
  S = mkState()

  function tick (ts) {
    const dt = S.lastTs === null ? 16 : Math.min(ts - S.lastTs, 80)
    S.lastTs  = ts
    S.elapsed += dt

    const canvas = canvas3d.value
    if (!canvas) { animFrame = requestAnimationFrame(tick); return }

    // Resize buffer si nécessaire (responsive)
    const dpr = window.devicePixelRatio || 1
    const W   = canvas.clientWidth
    const H   = canvas.clientHeight
    if (canvas.width !== Math.round(W * dpr) || canvas.height !== Math.round(H * dpr)) {
      canvas.width  = Math.round(W * dpr)
      canvas.height = Math.round(H * dpr)
    }

    const ctx = canvas.getContext('2d')
    ctx.save()
    ctx.scale(dpr, dpr)
    ctx.clearRect(0, 0, W, H)

    const sc = Math.min(W, H) / 295
    const cx = W / 2
    const cy = H / 2

    // ── machine à états ──────────────────────────────────────────
    if (S.phase === 'building') {
      const target = Math.min(NUM_RINGS, Math.floor(S.elapsed / 130))
      if (target > S.printedRings) S.printedRings = target
      currentLayer.value = S.printedRings
      isBuilding.value   = true
      S.nozzleAngle += dt * 0.0075
      S.rotY        += dt * 0.00030
      for (let r = 0; r < S.printedRings; r++)
        S.ringAlpha[r] = Math.min(1, S.ringAlpha[r] + dt / 220)
      if (S.printedRings >= NUM_RINGS) {
        S.phase = 'show'; S.elapsed = 0; isBuilding.value = false
      }
    } else if (S.phase === 'show') {
      S.rotY += dt * 0.0013
      currentLayer.value = NUM_RINGS
      if (S.elapsed > 3600) { S.phase = 'fade'; S.elapsed = 0 }
    } else if (S.phase === 'fade') {
      S.rotY   += dt * 0.0013
      S.fadeOut = Math.max(0, 1 - S.elapsed / 750)
      if (S.elapsed > 750) {
        S = mkState(S.rotY)
        S.lastTs = ts
      }
    }

    ctx.globalAlpha = S.fadeOut

    // ── Grille de fond ───────────────────────────────────────────
    ctx.strokeStyle = 'rgba(208,255,89,0.04)'
    ctx.lineWidth   = 0.5
    const gs = 25 * sc
    for (let x = cx % gs; x < W; x += gs) {
      ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, H); ctx.stroke()
    }
    for (let y = cy % gs; y < H; y += gs) {
      ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(W, y); ctx.stroke()
    }

    // ── Ligne de scan (tête d'impression) ────────────────────────
    if (S.phase === 'building' && S.printedRings < NUM_RINGS) {
      const sp  = proj(VASE[S.printedRings][0], S.rotY, cx, cy, sc)
      const gr  = ctx.createLinearGradient(0, sp.py, W, sp.py)
      gr.addColorStop(0,    'rgba(208,255,89,0)')
      gr.addColorStop(0.2,  'rgba(208,255,89,0.28)')
      gr.addColorStop(0.8,  'rgba(208,255,89,0.28)')
      gr.addColorStop(1,    'rgba(208,255,89,0)')
      ctx.save()
      ctx.strokeStyle = gr
      ctx.lineWidth   = 1
      ctx.setLineDash([5, 9])
      ctx.beginPath(); ctx.moveTo(0, sp.py); ctx.lineTo(W, sp.py); ctx.stroke()
      ctx.setLineDash([])
      ctx.restore()
    }

    // ── Rings imprimés ───────────────────────────────────────────
    for (let r = 0; r < S.printedRings; r++) {
      const ring  = VASE[r]
      const alpha = S.ringAlpha[r]
      if (alpha <= 0) continue
      const isNew = r === S.printedRings - 1
      const pts   = ring.map(p => proj(p, S.rotY, cx, cy, sc))

      // contour du ring
      ctx.save()
      ctx.strokeStyle = isNew
        ? `rgba(255,255,255,${alpha})`
        : `rgba(208,255,89,${alpha * 0.82})`
      ctx.lineWidth  = (isNew ? 1.8 : 1.0) * sc
      ctx.shadowBlur = (isNew ? 18 : 7) * sc
      ctx.shadowColor = isNew ? '#ffffff' : '#d0ff59'
      ctx.beginPath()
      ctx.moveTo(pts[0].px, pts[0].py)
      for (let p = 1; p <= pts.length; p++)
        ctx.lineTo(pts[p % pts.length].px, pts[p % pts.length].py)
      ctx.stroke()
      ctx.restore()

      // liaisons verticales (tous les 3 points pour pas surcharger)
      if (r < S.printedRings - 1) {
        const npts = VASE[r + 1].map(p => proj(p, S.rotY, cx, cy, sc))
        ctx.save()
        ctx.strokeStyle = `rgba(208,255,89,${alpha * 0.18})`
        ctx.lineWidth   = 0.5 * sc
        for (let p = 0; p < pts.length; p += 3) {
          ctx.beginPath()
          ctx.moveTo(pts[p].px, pts[p].py)
          ctx.lineTo(npts[p].px, npts[p].py)
          ctx.stroke()
        }
        ctx.restore()
      }

      // points de vertex
      for (const pt of pts) {
        ctx.save()
        ctx.fillStyle   = isNew ? `rgba(255,255,255,${alpha})` : `rgba(208,255,89,${alpha * 0.65})`
        ctx.shadowBlur  = isNew ? 8 * sc : 0
        ctx.shadowColor = '#d0ff59'
        ctx.beginPath()
        ctx.arc(pt.px, pt.py, (isNew ? 2.2 : 1.4) * sc * pt.d, 0, Math.PI * 2)
        ctx.fill()
        ctx.restore()
      }
    }

    // ── Nozzle (buse mobile) ─────────────────────────────────────
    if (S.phase === 'building' && S.printedRings < NUM_RINGS) {
      const ring = VASE[S.printedRings]
      const ni   = ((Math.round((S.nozzleAngle / (Math.PI * 2)) * NUM_PTS)) % NUM_PTS + NUM_PTS) % NUM_PTS
      const nPt  = proj(ring[ni], S.rotY, cx, cy, sc)

      // halo de chaleur
      const gr = ctx.createRadialGradient(nPt.px, nPt.py, 0, nPt.px, nPt.py, 22 * sc)
      gr.addColorStop(0,   'rgba(255,255,255,0.90)')
      gr.addColorStop(0.35,'rgba(208,255,89,0.55)')
      gr.addColorStop(1,   'rgba(208,255,89,0)')
      ctx.save()
      ctx.fillStyle = gr
      ctx.beginPath(); ctx.arc(nPt.px, nPt.py, 22 * sc, 0, Math.PI * 2); ctx.fill()
      ctx.restore()

      // point central
      ctx.save()
      ctx.fillStyle   = '#fff'
      ctx.shadowBlur  = 14 * sc
      ctx.shadowColor = '#d0ff59'
      ctx.beginPath(); ctx.arc(nPt.px, nPt.py, 3.8 * sc, 0, Math.PI * 2); ctx.fill()
      ctx.restore()

      // traînée derrière la buse
      const pi  = ((ni - 1) % NUM_PTS + NUM_PTS) % NUM_PTS
      const pPt = proj(ring[pi], S.rotY, cx, cy, sc)
      ctx.save()
      ctx.strokeStyle = 'rgba(208,255,89,0.45)'
      ctx.lineWidth   = 2.5 * sc
      ctx.lineCap     = 'round'
      ctx.beginPath(); ctx.moveTo(pPt.px, pPt.py); ctx.lineTo(nPt.px, nPt.py); ctx.stroke()
      ctx.restore()
    }

    ctx.restore()
    animFrame = requestAnimationFrame(tick)
  }

  animFrame = requestAnimationFrame(tick)
}

// ─── lifecycle ───────────────────────────────────────────────────
onMounted(() => {
  loadProducts()
  startAnim()
})
onUnmounted(() => {
  if (animFrame) cancelAnimationFrame(animFrame)
})
</script>

<style scoped>
.homepage {
  min-height: 100vh;
}

/* ── Hero ───────────────────────────────────────────────────────── */
.hero {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  overflow: hidden;
}

.hero-background {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at center, #111 0%, #050505 70%);
}

.hero-particles { position: absolute; inset: 0; pointer-events: none; }
.particle {
  position: absolute;
  background: var(--primary-color);
  border-radius: 50%;
  opacity: 0.3;
  animation: float-particles linear infinite;
}
@keyframes float-particles {
  0%   { transform: translateY(100vh) rotate(0deg);    opacity: 0; }
  10%  { opacity: 0.3; }
  90%  { opacity: 0.3; }
  100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
}

.hero-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  align-items: center;
  position: relative;
  z-index: 2;
}

.hero-text { max-width: 600px; }

.hero-title {
  font-size: 48px;
  font-weight: bold;
  line-height: 1.2;
  margin-bottom: 20px;
  color: var(--text-primary);
}

.highlight {
  background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-description {
  font-size: 18px;
  color: var(--text-secondary);
  line-height: 1.6;
  margin-bottom: 30px;
}

.hero-actions { display: flex; gap: 20px; }
.hero-btn { padding: 15px 30px; font-size: 16px; }

/* ── Printer visual ─────────────────────────────────────────────── */
.hero-visual {
  display: flex;
  justify-content: center;
  align-items: center;
}

.printer-wrapper {
  position: relative;
  width: 100%;
  max-width: 460px;
  aspect-ratio: 1 / 1;
}

.printer-canvas {
  width: 100%;
  height: 100%;
  display: block;
  border-radius: 22px;
  border: 1px solid rgba(208, 255, 89, 0.14);
  background:
    radial-gradient(ellipse at 50% 55%, rgba(208,255,89,0.045) 0%, transparent 65%),
    #050505;
  box-shadow:
    0 0 0 1px rgba(208,255,89,0.06),
    0 0 60px rgba(208,255,89,0.07),
    0 40px 80px rgba(0,0,0,0.9);
}

.printer-status {
  position: absolute;
  bottom: 18px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(5, 5, 5, 0.80);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(208, 255, 89, 0.22);
  border-radius: 999px;
  padding: 6px 16px;
  white-space: nowrap;
  pointer-events: none;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: rgba(208,255,89,0.35);
  flex-shrink: 0;
  transition: background 0.4s;
}
.status-dot.building {
  background: #d0ff59;
  animation: blink-dot 1s ease-in-out infinite;
}
@keyframes blink-dot {
  0%, 100% { box-shadow: 0 0 0 0   rgba(208,255,89,0.5); }
  50%       { box-shadow: 0 0 0 5px rgba(208,255,89,0);   }
}

.status-text {
  font-size: 11px;
  color: rgba(208,255,89,0.80);
  font-family: 'Courier New', monospace;
  letter-spacing: 0.6px;
}

/* ── Sections communes ──────────────────────────────────────────── */
section { padding: 80px 0; }

.section-title {
  font-size: 36px;
  font-weight: bold;
  text-align: center;
  margin-bottom: 50px;
  color: var(--text-primary);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 40px;
}

.section-header .section-title { margin: 0; }

.view-all-link { color: var(--primary-color); text-decoration: none; font-weight: 600; transition: color 0.3s; }
.view-all-link:hover { color: var(--accent-color); }

/* ── Features ───────────────────────────────────────────────────── */
.features { background: rgba(24,24,24,0.9); }
.features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; }

.feature-card {
  text-align: center;
  padding: 30px 20px;
  background: rgba(20, 20, 20,0.8);
  border: 1px solid var(--border-color);
  border-radius: 15px;
  transition: all 0.3s ease;
}
.feature-card:hover { transform: translateY(-10px); border-color: var(--primary-color); box-shadow: 0 15px 40px var(--shadow-color); }
.feature-icon {
  width: 56px;
  height: 56px;
  margin: 0 auto 20px;
  display: grid;
  place-items: center;
  border-radius: 14px;
  color: var(--primary-color);
  background: rgba(208, 255, 89, 0.08);
  border: 1px solid rgba(208, 255, 89, 0.2);
}
.feature-title { font-size: 20px; font-weight: 600; margin-bottom: 15px; color: var(--text-primary); }
.feature-description { color: var(--text-secondary); line-height: 1.6; }

/* ── Products ───────────────────────────────────────────────────── */
.products-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }

/* ── CTA ────────────────────────────────────────────────────────── */
.cta-section { background: linear-gradient(135deg, var(--dark-secondary) 0%, var(--dark-tertiary) 100%); text-align: center; }
.cta-content { max-width: 600px; margin: 0 auto; }
.cta-title { font-size: 36px; font-weight: bold; margin-bottom: 20px; color: var(--text-primary); }
.cta-description { font-size: 18px; color: var(--text-secondary); line-height: 1.6; margin-bottom: 30px; }
.cta-actions { display: flex; gap: 20px; justify-content: center; }
.cta-btn { padding: 15px 30px; font-size: 16px; }

/* ── Loading ────────────────────────────────────────────────────── */
.loading-container, .no-products { text-align: center; padding: 60px 20px; }
.loading-spinner {
  width: 50px; height: 50px;
  border: 3px solid var(--border-color);
  border-top-color: var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}
@keyframes spin { to { transform: rotate(360deg); } }
.no-products-icon { font-size: 64px; margin-bottom: 20px; opacity: 0.5; }

/* ── Responsive ─────────────────────────────────────────────────── */
@media (max-width: 1024px) {
  .hero-content {
    grid-template-columns: 1fr;
    gap: 40px;
    text-align: center;
  }
  .hero-title { font-size: 36px; }
  .printer-wrapper { max-width: 340px; }
  .hero-actions { justify-content: center; }
}

@media (max-width: 768px) {
  .hero-title { font-size: 28px; }
  .hero-description { font-size: 16px; }
  .hero-actions { flex-direction: column; align-items: center; }
  .section-title { font-size: 28px; }
  .cta-title { font-size: 28px; }
  .cta-actions { flex-direction: column; align-items: center; }
  section { padding: 60px 0; }
  .printer-wrapper { max-width: 280px; }
}

@media (max-width: 480px) {
  .products-grid {
    grid-template-columns: 1fr;
  }
  .printer-wrapper { max-width: 240px; }
  .status-text { font-size: 10px; }
  .hero-title { font-size: 24px; }
}
</style>
