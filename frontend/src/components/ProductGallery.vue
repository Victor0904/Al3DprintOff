<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import { srcsetFor, sizedUrl } from '../composables/useResponsiveImage'

const props = defineProps({
  images: { type: Array, default: () => [] }, // URLs (…-800.webp pour le catalogue)
  video: { type: String, default: null },     // Vidéo 360° (MP4)
  poster: { type: String, default: null },
  alt: { type: String, default: '' },
})

const index = ref(0)          // image affichée
const showVideo = ref(false)  // vue 360° active
const lightbox = ref(false)
const zoomed = ref(false)
const origin = ref('50% 50%')

const current = computed(() => props.images[index.value] || null)
const posterUrl = computed(() => props.poster || sizedUrl(props.images[0], 800))

// Nouveau coloris : on revient à la première photo
watch(() => [props.images, props.video], () => {
  index.value = 0
  showVideo.value = !props.images.length && !!props.video
})

const show = (i) => { index.value = i; showVideo.value = false }
const step = (d) => {
  const n = props.images.length
  if (n) show((index.value + d + n) % n)
}

// Balayage horizontal sur mobile
let touchX = null
const onTouchStart = (e) => { touchX = e.touches[0].clientX }
const onTouchEnd = (e) => {
  if (touchX === null || showVideo.value) return
  const dx = e.changedTouches[0].clientX - touchX
  if (Math.abs(dx) > 40) step(dx < 0 ? 1 : -1)
  touchX = null
}

// Plein écran avec zoom suivant le pointeur
const openLightbox = () => { if (current.value) { lightbox.value = true; zoomed.value = false } }
const closeLightbox = () => { lightbox.value = false }
const moveOrigin = (e) => {
  const r = e.currentTarget.getBoundingClientRect()
  origin.value = `${((e.clientX - r.left) / r.width) * 100}% ${((e.clientY - r.top) / r.height) * 100}%`
}
const toggleZoom = (e) => { moveOrigin(e); zoomed.value = !zoomed.value }

const onKey = (e) => {
  if (!lightbox.value) return
  if (e.key === 'Escape') closeLightbox()
  if (e.key === 'ArrowRight') step(1)
  if (e.key === 'ArrowLeft') step(-1)
}
watch(lightbox, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
  open ? window.addEventListener('keydown', onKey) : window.removeEventListener('keydown', onKey)
})
onUnmounted(() => {
  document.body.style.overflow = ''
  window.removeEventListener('keydown', onKey)
})
</script>

<template>
  <div class="gallery">
    <div class="stage" @touchstart.passive="onTouchStart" @touchend="onTouchEnd">
      <video
        v-if="showVideo && video"
        :key="video"
        class="stage-media"
        :src="video"
        :poster="posterUrl"
        autoplay
        muted
        loop
        playsinline
        preload="metadata"
        :aria-label="`${alt} — vue à 360°`"
      ></video>
      <button v-else-if="current" type="button" class="stage-btn" @click="openLightbox" aria-label="Agrandir l'image">
        <img
          class="stage-media"
          :src="current"
          :srcset="srcsetFor(current)"
          sizes="(max-width: 900px) 100vw, 560px"
          :alt="alt"
          width="800"
          height="800"
          @error="e => { e.target.onerror = null; e.target.removeAttribute('srcset'); e.target.src = '/placeholder.svg' }"
        >
        <span class="zoom-hint">Cliquer pour zoomer</span>
      </button>
      <img v-else class="stage-media" src="/placeholder.svg" alt="">

      <template v-if="images.length > 1 && !showVideo">
        <button type="button" class="nav prev" @click="step(-1)" aria-label="Image précédente">‹</button>
        <button type="button" class="nav next" @click="step(1)" aria-label="Image suivante">›</button>
      </template>
    </div>

    <div v-if="images.length > 1 || video" class="thumbs">
      <button
        v-if="video"
        type="button"
        :class="['thumb', 'thumb-360', { active: showVideo }]"
        @click="showVideo = true"
        aria-label="Voir à 360°"
      >
        <img v-if="posterUrl" :src="sizedUrl(posterUrl, 400)" alt="" loading="lazy">
        <span class="badge-360">360°</span>
      </button>
      <button
        v-for="(img, i) in images"
        :key="img"
        type="button"
        :class="['thumb', { active: !showVideo && i === index }]"
        @click="show(i)"
        :aria-label="`Image ${i + 1}`"
      >
        <img :src="sizedUrl(img, 400)" alt="" loading="lazy" @error="e => { e.target.onerror = null; e.target.src = '/placeholder.svg' }">
      </button>
    </div>

    <Teleport to="body">
      <div v-if="lightbox" class="lightbox" role="dialog" aria-modal="true" @click.self="closeLightbox">
        <button type="button" class="lb-close" @click="closeLightbox" aria-label="Fermer">×</button>
        <div
          :class="['lb-frame', { zoomed }]"
          @click="toggleZoom"
          @pointermove="zoomed && moveOrigin($event)"
        >
          <img
            :src="sizedUrl(current, 1600)"
            :alt="alt"
            :style="{ transformOrigin: origin }"
            @error="e => { e.target.onerror = null; e.target.src = current }"
          >
        </div>
        <template v-if="images.length > 1">
          <button type="button" class="nav prev lb-nav" @click="step(-1)" aria-label="Image précédente">‹</button>
          <button type="button" class="nav next lb-nav" @click="step(1)" aria-label="Image suivante">›</button>
        </template>
        <p class="lb-hint">{{ zoomed ? 'Déplacez pour explorer · cliquez pour dézoomer' : 'Cliquez sur l\'image pour zoomer' }}</p>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.gallery { display: flex; flex-direction: column; gap: 12px; }

.stage {
  position: relative;
  aspect-ratio: 1 / 1;
  border-radius: var(--radius);
  overflow: hidden;
  border: 1px solid var(--border-color);
  background: radial-gradient(circle at 50% 40%, #232323 0%, var(--surface) 70%);
}
.stage-btn { all: unset; display: block; width: 100%; height: 100%; cursor: zoom-in; }
.stage-media { width: 100%; height: 100%; object-fit: contain; display: block; }
.zoom-hint {
  position: absolute; right: 12px; bottom: 12px;
  font-size: 12px; padding: 4px 10px; border-radius: 999px;
  background: rgba(0, 0, 0, 0.55); color: var(--text-secondary);
  opacity: 0; transition: opacity 0.2s;
}
.stage-btn:hover .zoom-hint, .stage-btn:focus-visible .zoom-hint { opacity: 1; }

.nav {
  position: absolute; top: 50%; transform: translateY(-50%);
  width: 40px; height: 40px; border-radius: 50%;
  border: 1px solid var(--border-color);
  background: rgba(0, 0, 0, 0.5); color: var(--text-primary);
  font-size: 24px; line-height: 1; cursor: pointer;
}
.nav:hover { border-color: var(--primary-color); color: var(--primary-color); }
.prev { left: 10px; }
.next { right: 10px; }

.thumbs { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 4px; scrollbar-width: thin; }
.thumb {
  position: relative; flex: 0 0 72px; height: 72px; padding: 0;
  border-radius: 10px; overflow: hidden; cursor: pointer;
  border: 2px solid transparent; background: var(--surface);
}
.thumb img { width: 100%; height: 100%; object-fit: contain; }
.thumb.active { border-color: var(--primary-color); }
.thumb-360 img { opacity: 0.55; }
.badge-360 {
  position: absolute; inset: 0; display: grid; place-items: center;
  font-weight: 800; font-size: 15px; color: var(--primary-color);
}

.lightbox {
  position: fixed; inset: 0; z-index: 2000;
  background: rgba(5, 5, 5, 0.96);
  display: flex; align-items: center; justify-content: center;
}
.lb-frame {
  width: min(92vw, 92vh); height: min(92vw, 92vh);
  overflow: hidden; cursor: zoom-in; touch-action: none;
}
.lb-frame img { width: 100%; height: 100%; object-fit: contain; transition: transform 0.25s ease; }
.lb-frame.zoomed { cursor: zoom-out; }
.lb-frame.zoomed img { transform: scale(2.5); }
.lb-close {
  position: absolute; top: 14px; right: 18px; z-index: 1;
  background: none; border: none; color: var(--text-primary);
  font-size: 36px; cursor: pointer;
}
.lb-nav { position: fixed; }
.lb-hint {
  position: absolute; bottom: 14px; left: 0; right: 0;
  text-align: center; font-size: 13px; color: var(--text-secondary);
}

@media (max-width: 600px) {
  .thumb { flex-basis: 60px; height: 60px; }
  .nav { width: 34px; height: 34px; font-size: 20px; }
}
</style>
