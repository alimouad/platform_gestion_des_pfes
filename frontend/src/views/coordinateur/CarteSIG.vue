<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import api from '@/services/api'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import 'leaflet-draw/dist/leaflet.draw.css'
import 'leaflet-draw'
import 'leaflet.markercluster/dist/MarkerCluster.css'
import 'leaflet.markercluster/dist/MarkerCluster.Default.css'
import 'leaflet.markercluster'

const projets    = ref([])
const loading    = ref(false)
const filterRect = ref(null)  // [[s,w],[n,e]] or null
const search     = ref('')

// Geocoding
const geocodeSearch  = ref('')
const geocodeResults = ref([])
const geocoding      = ref(false)

let map = null
let clusterGroup = null
let heatLayer    = null
let drawLayer    = L.layerGroup()
let drawControl  = null

const STATUT_COLOR = {
  soumis:   '#3b82f6',
  en_cours: '#f59e0b',
  valide:   '#22c55e',
  soutenu:  '#d6e87a',
  rejete:   '#ef4444',
}

const statuts = ['soumis','en_cours','valide','soutenu','rejete']
const statutLabel = { soumis:'Soumis', en_cours:'En cours', valide:'Validé', soutenu:'Soutenu', rejete:'Rejeté' }

const filterStatut = ref('all')
const selected = ref(null)

const villeCount = computed(() => {
  const counts = {}
  projets.value.forEach(p => {
    if (!p.ville) return
    counts[p.ville] = (counts[p.ville] || 0) + 1
  })
  return Object.entries(counts).sort((a,b) => b[1] - a[1])
})

const filteredProjets = computed(() => {
  let list = projets.value
  if (filterStatut.value !== 'all') list = list.filter(p => p.statut === filterStatut.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(p => p.titre?.toLowerCase().includes(q) || p.ville?.toLowerCase().includes(q))
  }
  if (filterRect.value) {
    const [[s,w],[n,e]] = filterRect.value
    list = list.filter(p => p.latitude && p.longitude &&
      p.latitude >= s && p.latitude <= n && p.longitude >= w && p.longitude <= e
    )
  }
  return list
})

onMounted(async () => {
  loading.value = true
  try {
    const res = await api.get('/projets')
    projets.value = res.data.data || []
  } catch {}
  loading.value = false
  await nextTick()
  initMap()
})

onUnmounted(() => { if (map) { map.remove(); map = null } })

function initMap() {
  const el = document.getElementById('carte-coord')
  if (!el || map) return
  map = L.map('carte-coord').setView([29.5, -8], 5)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap',
    maxZoom: 18,
  }).addTo(map)

  clusterGroup = L.markerClusterGroup({
    showCoverageOnHover: false,
    maxClusterRadius: 50,
    iconCreateFunction(cluster) {
      const n = cluster.getChildCount()
      return L.divIcon({
        html: `<div style="background:#1e4a49;color:#d6e87a;border:2.5px solid #d6e87a;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:13px;box-shadow:0 2px 8px rgba(0,0,0,0.25)">${n}</div>`,
        className: '',
        iconSize: [36, 36],
      })
    },
  })
  map.addLayer(clusterGroup)
  drawLayer.addTo(map)

  // Draw control (rectangle only)
  drawControl = new L.Control.Draw({
    draw: {
      rectangle: { shapeOptions: { color: '#1e4a49', fillColor: '#d6e87a', fillOpacity: 0.2, weight: 2 } },
      polygon: false, polyline: false, circle: false, circlemarker: false, marker: false,
    },
    edit: { featureGroup: drawLayer },
  })
  map.addControl(drawControl)

  map.on(L.Draw.Event.CREATED, e => {
    drawLayer.clearLayers()
    const layer = e.layer
    drawLayer.addLayer(layer)
    const b = layer.getBounds()
    filterRect.value = [[b.getSouth(), b.getWest()],[b.getNorth(), b.getEast()]]
    renderMarkers()
  })

  map.on(L.Draw.Event.DELETED, () => {
    filterRect.value = null
    renderMarkers()
  })

  renderMarkers()
  renderHeatmap()
}

function renderMarkers() {
  clusterGroup.clearLayers()
  filteredProjets.value.forEach(p => {
    if (!p.latitude || !p.longitude) return
    const color = STATUT_COLOR[p.statut] || '#94a3b8'
    const m = L.circleMarker([Number(p.latitude), Number(p.longitude)], {
      radius: 10,
      fillColor: color,
      color: '#fff',
      weight: 2,
      fillOpacity: 0.85,
    })
    m.bindTooltip(`<b>${p.titre}</b><br>${p.ville || ''} · ${statutLabel[p.statut] || p.statut}`, { direction: 'top' })
    m.on('click', () => { selected.value = p })
    clusterGroup.addLayer(m)
  })
}

function renderHeatmap() {
  // Simple heatmap using circle markers with opacity = count
  if (heatLayer) { map.removeLayer(heatLayer); heatLayer = null }
  const points = []
  const counts = {}
  projets.value.forEach(p => {
    if (!p.latitude || !p.longitude) return
    const k = `${Math.round(p.latitude*2)/2}_${Math.round(p.longitude*2)/2}`
    counts[k] = (counts[k] || { lat: Number(p.latitude), lng: Number(p.longitude), n: 0 })
    counts[k].n++
  })
  const max = Math.max(...Object.values(counts).map(c => c.n), 1)
  heatLayer = L.layerGroup()
  Object.values(counts).forEach(c => {
    L.circleMarker([c.lat, c.lng], {
      radius: 8 + (c.n / max) * 30,
      fillColor: '#d6e87a',
      color: '#1e4a49',
      weight: 1,
      fillOpacity: 0.15 + (c.n / max) * 0.5,
    }).addTo(heatLayer)
  })
  heatLayer.addTo(map)
}

function clearRect() {
  drawLayer.clearLayers()
  filterRect.value = null
  renderMarkers()
}

function onFilterChange() { renderMarkers() }

// ── Géocodage Nominatim ─────────────────────────────────────
let geocodeTimer = null
async function onGeocodeInput() {
  clearTimeout(geocodeTimer)
  geocodeResults.value = []
  if (geocodeSearch.value.length < 3) return
  geocodeTimer = setTimeout(async () => {
    geocoding.value = true
    try {
      const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(geocodeSearch.value)}&limit=5&accept-language=fr`)
      geocodeResults.value = await res.json()
    } catch {}
    geocoding.value = false
  }, 400)
}
function selectGeocode(r) {
  geocodeResults.value = []
  geocodeSearch.value = r.display_name.split(',')[0]
  if (map) map.flyTo([Number(r.lat), Number(r.lon)], 10, { duration: 1.2 })
}

// ── Export GeoJSON ─────────────────────────────────────────
function exportGeoJSON() {
  const features = filteredProjets.value.flatMap(p => {
    const feats = []
    if (p.latitude && p.longitude) {
      feats.push({
        type: 'Feature',
        geometry: { type: 'Point', coordinates: [Number(p.longitude), Number(p.latitude)] },
        properties: { id: p.id, titre: p.titre, statut: p.statut, ville: p.ville, domaine: p.domaine },
      })
    }
    if (p.zone_etude) {
      try {
        const z = typeof p.zone_etude === 'string' ? JSON.parse(p.zone_etude) : p.zone_etude
        const geo = z.type ? z : {
          type: 'Polygon',
          coordinates: [[[z.west,z.south],[z.east,z.south],[z.east,z.north],[z.west,z.north],[z.west,z.south]]],
        }
        feats.push({ type: 'Feature', geometry: geo, properties: { id: p.id, titre: p.titre, type: 'zone' } })
      } catch {}
    }
    return feats
  })
  const blob = new Blob([JSON.stringify({ type: 'FeatureCollection', features }, null, 2)], { type: 'application/json' })
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = 'projets-sig.geojson'
  a.click()
  URL.revokeObjectURL(a.href)
}
</script>

<template>
  <div class="space-y-5">

    <!-- Header -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-6 text-white relative overflow-hidden">
      <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/5"></div>
      <div class="absolute -bottom-6 right-24 h-24 w-24 rounded-full bg-[#d6e87a]/10"></div>
      <div class="relative flex items-center justify-between">
        <div>
          <p class="text-[11px] font-black uppercase tracking-widest text-[#d6e87a]">SIG · Cartographie</p>
          <h1 class="mt-1 text-2xl font-black">Carte des projets</h1>
          <p class="mt-1 text-sm text-white/60">{{ filteredProjets.length }} / {{ projets.length }} projets affichés</p>
        </div>
        <button @click="exportGeoJSON"
          class="flex items-center gap-2 rounded-2xl bg-[#d6e87a] px-4 py-2.5 text-sm font-black text-[#1e4a49] hover:bg-[#c8db60] transition shadow-lg">
          <i class="fa-solid fa-download text-sm"></i> Export GeoJSON
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-3">
      <div class="flex flex-1 min-w-48 items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-2.5 shadow-sm">
        <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
        <input v-model="search" @input="onFilterChange" placeholder="Rechercher…"
          class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
      </div>
      <select v-model="filterStatut" @change="onFilterChange"
        class="rounded-2xl border border-white/70 bg-white/90 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm outline-none">
        <option value="all">Tous les statuts</option>
        <option v-for="s in statuts" :key="s" :value="s">{{ statutLabel[s] }}</option>
      </select>
      <button v-if="filterRect" @click="clearRect"
        class="flex items-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-bold text-amber-700 shadow-sm hover:bg-amber-100 transition">
        <i class="fa-solid fa-draw-polygon text-xs"></i> Effacer la sélection
      </button>
    </div>

    <div v-if="loading" class="rounded-3xl border border-white/70 bg-white/90 p-16 text-center text-sm text-slate-400">Chargement…</div>

    <div v-else class="grid gap-4 lg:grid-cols-[260px_1fr]" style="min-height:70vh">

      <!-- Sidebar -->
      <div class="space-y-4">

        <!-- Geocode search -->
        <div class="rounded-3xl border border-white/70 bg-white/90 p-5 shadow-sm">
          <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-3">Recherche de lieu</p>
          <div class="relative">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input v-model="geocodeSearch" @input="onGeocodeInput" placeholder="Ville, région…"
              class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm outline-none focus:border-[#d6e87a] transition" />
            <i v-if="geocoding" class="fa-solid fa-circle-notch fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
          </div>
          <div v-if="geocodeResults.length" class="mt-2 rounded-xl border border-slate-100 bg-white shadow-lg overflow-hidden">
            <button v-for="r in geocodeResults" :key="r.place_id" @click="selectGeocode(r)"
              class="w-full px-3 py-2 text-left text-xs text-slate-700 hover:bg-[#f8faef] border-b border-slate-50 last:border-0 truncate">
              <i class="fa-solid fa-location-dot text-slate-400 mr-2"></i>{{ r.display_name }}
            </button>
          </div>
        </div>

        <!-- Legend -->
        <div class="rounded-3xl border border-white/70 bg-white/90 p-5 shadow-sm">
          <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-3">Légende</p>
          <div class="space-y-2">
            <div v-for="s in statuts" :key="s" class="flex items-center gap-2 text-xs font-semibold text-slate-600">
              <span class="h-3 w-3 rounded-full" :style="`background:${STATUT_COLOR[s]}`"></span>
              {{ statutLabel[s] }}
              <span class="ml-auto font-black text-slate-400">{{ projets.filter(p => p.statut === s).length }}</span>
            </div>
          </div>
        </div>

        <!-- Top villes -->
        <div class="rounded-3xl border border-white/70 bg-white/90 p-5 shadow-sm">
          <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-3">Top villes</p>
          <div v-if="villeCount.length === 0" class="text-sm italic text-slate-400">Aucune ville renseignée</div>
          <div class="space-y-2">
            <div v-for="[ville, count] in villeCount.slice(0, 8)" :key="ville" class="flex items-center gap-2">
              <span class="flex-1 truncate text-xs font-bold text-slate-700">{{ ville }}</span>
              <div class="h-1.5 w-16 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full bg-[#d6e87a] transition-all"
                  :style="`width:${(count / villeCount[0][1]) * 100}%`"></div>
              </div>
              <span class="w-4 text-right text-xs font-black text-slate-500">{{ count }}</span>
            </div>
          </div>
        </div>

        <!-- Tip -->
        <div class="rounded-2xl border border-[#d6e87a]/40 bg-[#f8faef] px-4 py-3 text-xs text-[#4a7a30]">
          <i class="fa-solid fa-draw-polygon mr-2"></i>
          Dessinez un rectangle sur la carte pour filtrer les projets par zone géographique.
        </div>
      </div>

      <!-- Map -->
      <div class="relative rounded-3xl border border-white/70 bg-white/90 shadow-sm overflow-hidden" style="min-height:500px">
        <div id="carte-coord" class="absolute inset-0 z-0"></div>

        <!-- Selected project popup -->
        <div v-if="selected" class="absolute bottom-4 left-4 z-20 w-72 rounded-2xl bg-white shadow-xl border border-slate-100 overflow-hidden">
          <div class="h-1 w-full" :style="`background:${STATUT_COLOR[selected.statut] || '#94a3b8'}`"></div>
          <div class="p-4">
            <div class="flex items-start justify-between gap-2 mb-3">
              <h3 class="text-sm font-black text-slate-900 leading-snug">{{ selected.titre }}</h3>
              <button @click="selected = null" class="text-slate-300 hover:text-slate-600 shrink-0">
                <i class="fa-solid fa-xmark text-sm"></i>
              </button>
            </div>
            <div class="space-y-1.5 text-xs text-slate-500">
              <p class="flex items-center gap-2">
                <i class="fa-solid fa-location-dot w-4 text-slate-400"></i> {{ selected.ville || '—' }}
              </p>
              <p class="flex items-center gap-2">
                <i class="fa-solid fa-tags w-4 text-slate-400"></i> {{ selected.domaine || '—' }}
              </p>
              <p class="flex items-center gap-2">
                <i class="fa-solid fa-circle w-2 text-xs" :style="`color:${STATUT_COLOR[selected.statut]}`"></i>
                {{ statutLabel[selected.statut] || selected.statut }}
              </p>
              <p v-if="selected.professeur" class="flex items-center gap-2">
                <i class="fa-solid fa-chalkboard-user w-4 text-slate-400"></i>
                {{ selected.professeur?.utilisateur?.prenom }} {{ selected.professeur?.utilisateur?.nom }}
              </p>
            </div>
          </div>
        </div>

        <!-- Rect filter badge -->
        <div v-if="filterRect" class="absolute top-4 left-1/2 -translate-x-1/2 z-20 rounded-full bg-amber-100 border border-amber-200 px-4 py-1.5 text-xs font-black text-amber-700 shadow">
          <i class="fa-solid fa-filter mr-1.5"></i> {{ filteredProjets.length }} projet{{ filteredProjets.length !== 1 ? 's' : '' }} dans la zone
        </div>
      </div>
    </div>

  </div>
</template>
