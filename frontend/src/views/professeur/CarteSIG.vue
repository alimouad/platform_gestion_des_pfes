<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import api from '@/services/api'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const projets     = ref([])
const postulations = ref([])
const sigData     = ref([])   // all donnees_spatiales for prof's projects
const loading     = ref(false)
const showSigLayers = ref(true)
const showZones   = ref(true)
const selected    = ref(null)   // selected project popup

let map = null
let markerLayer = L.layerGroup()
let sigLayer    = L.layerGroup()
let zoneLayer   = L.layerGroup()

const COLORS = ['#1e4a49','#4a7a30','#e07b39','#6b46c1','#0369a1','#be185d','#b45309']

onMounted(async () => {
  loading.value = true
  try {
    const me = await api.get('/me')
    user.value = me.data?.data || {}
    const profId = Number(user.value?.professeur?.id)
    if (!profId) { loading.value = false; return }

    const [pr, po] = await Promise.all([api.get('/projets'), api.get('/postulations')])
    projets.value     = (pr.data.data || []).filter(p => Number(p.professeur_id) === profId)
    const ids         = projets.value.map(p => Number(p.id))
    postulations.value = (po.data.data || []).filter(p => ids.includes(Number(p.projet_id)) && p.statut === 'accepte')

    // fetch SIG data for each project
    const sigResults = await Promise.all(projets.value.map(p =>
      api.get(`/sig/projet/${p.id}`).catch(() => null)
    ))
    sigData.value = sigResults.flatMap((r, i) =>
      (r?.data?.data || []).map(d => ({ ...d, _projetIdx: i }))
    )
  } catch (e) { console.error(e) }
  loading.value = false
  await nextTick()
  initMap()
})

onUnmounted(() => { if (map) { map.remove(); map = null } })

function initMap() {
  const el = document.getElementById('carte-sig-prof')
  if (!el || map) return
  map = L.map('carte-sig-prof').setView([29.5, -8], 5)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 18,
  }).addTo(map)
  markerLayer.addTo(map)
  sigLayer.addTo(map)
  zoneLayer.addTo(map)
  renderMarkers()
  renderZones()
  renderSigLayers()
}

function renderMarkers() {
  markerLayer.clearLayers()
  projets.value.forEach((p, i) => {
    if (!p.latitude || !p.longitude) return
    const color = COLORS[i % COLORS.length]
    const accepted = postulations.value.find(po => Number(po.projet_id) === Number(p.id))
    const marker = L.circleMarker([Number(p.latitude), Number(p.longitude)], {
      radius: 12,
      fillColor: color,
      color: '#fff',
      weight: 2,
      fillOpacity: 0.9,
    }).addTo(markerLayer)
    marker.bindTooltip(`<b>${p.titre}</b><br>${p.ville || ''}`, { direction: 'top' })
    marker.on('click', () => { selected.value = { ...p, accepted, color } })
  })
}

function renderZones() {
  zoneLayer.clearLayers()
  if (!showZones.value) return
  projets.value.forEach((p, i) => {
    if (!p.zone_etude) return
    const color = COLORS[i % COLORS.length]
    try {
      const z = typeof p.zone_etude === 'string' ? JSON.parse(p.zone_etude) : p.zone_etude
      if (z.type) {
        // GeoJSON polygon
        L.geoJSON(z, { style: { color, weight: 2, fillColor: color, fillOpacity: 0.15 } }).addTo(zoneLayer)
      } else if (z.south !== undefined) {
        // Rectangle bounds
        L.rectangle([[z.south, z.west],[z.north, z.east]], { color, weight: 2, fillColor: color, fillOpacity: 0.15 }).addTo(zoneLayer)
      }
    } catch {}
  })
}

function renderSigLayers() {
  sigLayer.clearLayers()
  if (!showSigLayers.value) return
  sigData.value.forEach((d, i) => {
    if (!d.geojson) return
    const color = COLORS[d._projetIdx % COLORS.length]
    try {
      const geo = typeof d.geojson === 'string' ? JSON.parse(d.geojson) : d.geojson
      L.geoJSON(geo, {
        style: { color, weight: 2, fillColor: color, fillOpacity: 0.25 },
        pointToLayer: (_f, ll) => L.circleMarker(ll, { radius: 5, fillColor: color, color: '#fff', weight: 1.5, fillOpacity: 0.9 }),
      }).addTo(sigLayer)
    } catch {}
  })
}

function toggleSig() { showSigLayers.value = !showSigLayers.value; renderSigLayers() }
function toggleZones() { showZones.value = !showZones.value; renderZones() }

function flyTo(p) {
  if (p.latitude && p.longitude && map)
    map.flyTo([Number(p.latitude), Number(p.longitude)], 9, { duration: 1 })
}

const acceptedStudent = (p) => postulations.value.find(po => Number(po.projet_id) === Number(p.id))

function projetColor(i) { return COLORS[i % COLORS.length] }
</script>

<template>
  <div class="space-y-5">

    <!-- Header -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-6 text-white relative overflow-hidden">
      <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/5"></div>
      <div class="absolute -bottom-6 right-24 h-24 w-24 rounded-full bg-[#d6e87a]/10"></div>
      <div class="relative">
        <p class="text-[11px] font-black uppercase tracking-widest text-[#d6e87a]">SIG · Cartographie</p>
        <h1 class="mt-1 text-2xl font-black">Carte des zones d'étude</h1>
        <p class="mt-1 text-sm text-white/60">{{ projets.length }} projet{{ projets.length !== 1 ? 's' : '' }} · {{ sigData.length }} couche{{ sigData.length !== 1 ? 's' : '' }} SIG</p>
      </div>
    </div>

    <div v-if="loading" class="rounded-3xl border border-white/70 bg-white/90 p-16 text-center text-sm text-slate-400">Chargement…</div>

    <div v-else class="grid gap-4 lg:grid-cols-[280px_1fr]" style="min-height:70vh">

      <!-- Sidebar -->
      <div class="space-y-4">

        <!-- Layer controls -->
        <div class="rounded-3xl border border-white/70 bg-white/90 p-5 shadow-sm">
          <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-3">Couches</p>
          <div class="space-y-2">
            <label class="flex items-center gap-3 cursor-pointer">
              <div class="relative w-9 h-5">
                <input type="checkbox" v-model="showZones" @change="toggleZones" class="peer sr-only" />
                <div class="w-9 h-5 rounded-full bg-slate-200 peer-checked:bg-[#1e4a49] transition"></div>
                <div class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></div>
              </div>
              <span class="text-sm font-bold text-slate-700">Zones d'étude</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
              <div class="relative w-9 h-5">
                <input type="checkbox" v-model="showSigLayers" @change="toggleSig" class="peer sr-only" />
                <div class="w-9 h-5 rounded-full bg-slate-200 peer-checked:bg-[#1e4a49] transition"></div>
                <div class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></div>
              </div>
              <span class="text-sm font-bold text-slate-700">Données SIG étudiants</span>
            </label>
          </div>
        </div>

        <!-- Project list -->
        <div class="rounded-3xl border border-white/70 bg-white/90 p-5 shadow-sm">
          <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-3">Projets</p>
          <div v-if="projets.length === 0" class="text-sm text-slate-400 italic">Aucun projet</div>
          <div class="space-y-2">
            <button v-for="(p, i) in projets" :key="p.id"
              @click="flyTo(p); selected = { ...p, accepted: acceptedStudent(p), color: projetColor(i) }"
              class="w-full flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-3 py-2.5 text-left hover:border-[#d6e87a] hover:bg-[#f8faef] transition">
              <span class="h-3 w-3 shrink-0 rounded-full" :style="`background:${projetColor(i)}`"></span>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-black text-slate-800 truncate">{{ p.titre }}</p>
                <p class="text-[10px] text-slate-400">{{ p.ville || 'Zone non définie' }}</p>
              </div>
              <i v-if="p.latitude" class="fa-solid fa-location-dot text-[#d6e87a] text-xs"></i>
            </button>
          </div>
        </div>

      </div>

      <!-- Map -->
      <div class="relative rounded-3xl border border-white/70 bg-white/90 shadow-sm overflow-hidden" style="min-height:500px">
        <div id="carte-sig-prof" class="absolute inset-0 z-0"></div>

        <!-- No coordinates warning -->
        <div v-if="projets.every(p => !p.latitude)" class="absolute inset-0 z-10 flex items-center justify-center bg-white/80 backdrop-blur-sm">
          <div class="text-center">
            <i class="fa-solid fa-map-location-dot text-5xl text-slate-300 mb-4"></i>
            <p class="font-black text-slate-700">Aucun projet avec coordonnées</p>
            <p class="text-sm text-slate-400 mt-1">Définissez les coordonnées de vos projets pour les voir ici.</p>
          </div>
        </div>

        <!-- Project popup overlay -->
        <div v-if="selected" class="absolute bottom-4 left-4 z-20 w-72 rounded-2xl bg-white shadow-xl border border-slate-100 overflow-hidden">
          <div class="h-1 w-full" :style="`background:${selected.color}`"></div>
          <div class="p-4">
            <div class="flex items-start justify-between gap-2 mb-3">
              <h3 class="text-sm font-black text-slate-900 leading-snug">{{ selected.titre }}</h3>
              <button @click="selected = null" class="text-slate-300 hover:text-slate-600 shrink-0">
                <i class="fa-solid fa-xmark text-sm"></i>
              </button>
            </div>
            <div class="space-y-1.5 text-xs text-slate-500">
              <p v-if="selected.ville" class="flex items-center gap-2">
                <i class="fa-solid fa-location-dot w-4 text-center text-slate-400"></i> {{ selected.ville }}
              </p>
              <p class="flex items-center gap-2">
                <i class="fa-solid fa-tags w-4 text-center text-slate-400"></i> {{ selected.domaine || '—' }}
              </p>
              <p v-if="selected.accepted" class="flex items-center gap-2">
                <i class="fa-solid fa-user-graduate w-4 text-center text-slate-400"></i>
                {{ selected.accepted.etudiant?.utilisateur?.prenom }} {{ selected.accepted.etudiant?.utilisateur?.nom }}
              </p>
              <p v-else class="flex items-center gap-2 italic text-slate-300">
                <i class="fa-solid fa-user w-4 text-center text-slate-300"></i> Aucun étudiant assigné
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
