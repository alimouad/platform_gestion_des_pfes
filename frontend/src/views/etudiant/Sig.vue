<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import api from '@/services/api'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const etudiantId = computed(() => user.value?.etudiant?.id)

const postulations = ref([])
const existingData = ref(null)
const loading = ref(true)
const uploading = ref(false)
const error = ref('')
const success = ref('')

const selectedFile = ref(null)
const fileInput = ref(null)

let map = null
let geoLayer = null

const monProjet = computed(() => {
  const accepted = postulations.value.find(p => p.statut === 'accepte')
  return accepted?.projet || null
})

async function fetchAll() {
  loading.value = true
  try {
    const eid = Number(etudiantId.value)
    const [po, si] = await Promise.all([
      api.get('/postulations'),
      monProjet.value ? api.get(`/sig/projet/${monProjet.value.id}`) : Promise.resolve({ data: { data: null } }),
    ])
    postulations.value = eid ? po.data.data.filter(p => Number(p.etudiant_id) === eid) : []

    if (monProjet.value) {
      const sigRes = await api.get(`/sig/projet/${monProjet.value.id}`).catch(() => null)
      existingData.value = sigRes?.data?.data || null
    }
  } catch {}
  loading.value = false
  await nextTick()
  initMap()
}

onMounted(async () => {
  loading.value = true
  const eid = Number(etudiantId.value)
  try {
    const po = await api.get('/postulations')
    postulations.value = eid ? po.data.data.filter(p => Number(p.etudiant_id) === eid) : []

    if (monProjet.value) {
      const sigRes = await api.get(`/sig/projet/${monProjet.value.id}`).catch(() => null)
      existingData.value = sigRes?.data?.data || null
    }
  } catch {}
  loading.value = false
  await nextTick()
  initMap()
})

onUnmounted(() => {
  if (map) { map.remove(); map = null }
})

function initMap() {
  const el = document.getElementById('sig-map')
  if (!el || map) return
  map = L.map('sig-map').setView([31.5, -7.5], 5)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
  }).addTo(map)

  if (existingData.value?.geojson) {
    loadGeoJsonOnMap(existingData.value.geojson)
  } else if (monProjet.value?.zone_etude) {
    const z = monProjet.value.zone_etude
    const bounds = [[z.south, z.west], [z.north, z.east]]
    L.rectangle(bounds, { color: '#1e4a49', weight: 2, fillColor: '#d6e87a', fillOpacity: 0.2 }).addTo(map)
    map.fitBounds(bounds)
  }
}

function loadGeoJsonOnMap(geojson) {
  if (!map) return
  if (geoLayer) { map.removeLayer(geoLayer); geoLayer = null }
  try {
    const data = typeof geojson === 'string' ? JSON.parse(geojson) : geojson
    geoLayer = L.geoJSON(data, {
      style: { color: '#1e4a49', weight: 2, fillColor: '#d6e87a', fillOpacity: 0.35 },
      pointToLayer: (f, latlng) => L.circleMarker(latlng, { radius: 6, fillColor: '#d6e87a', color: '#1e4a49', weight: 2, fillOpacity: 0.9 }),
    }).addTo(map)
    if (geoLayer.getBounds().isValid()) map.fitBounds(geoLayer.getBounds(), { padding: [30, 30] })
  } catch (e) {
    error.value = 'Impossible d\'afficher les données sur la carte.'
  }
}

function onFileChange(e) {
  const f = e.target.files[0]
  if (!f) return
  selectedFile.value = f
  error.value = ''
  success.value = ''
}

function onDrop(e) {
  const f = e.dataTransfer.files[0]
  if (!f) return
  selectedFile.value = f
  error.value = ''
  success.value = ''
}

const fileIcon = computed(() => {
  if (!selectedFile.value) return 'fa-file'
  const name = selectedFile.value.name.toLowerCase()
  if (name.endsWith('.zip')) return 'fa-file-zipper'
  if (name.endsWith('.geojson') || name.endsWith('.json')) return 'fa-map'
  return 'fa-file'
})

const fileSizeLabel = computed(() => {
  if (!selectedFile.value) return ''
  const mb = selectedFile.value.size / 1024 / 1024
  return mb < 1 ? `${(mb * 1024).toFixed(0)} KB` : `${mb.toFixed(1)} MB`
})

async function upload() {
  if (!selectedFile.value || !monProjet.value) return
  error.value = ''
  success.value = ''
  uploading.value = true
  try {
    const fd = new FormData()
    fd.append('fichier', selectedFile.value)
    fd.append('projet_id', monProjet.value.id)
    const res = await api.post('/sig/upload', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    existingData.value = res.data.data
    success.value = 'Données importées avec succès !'
    selectedFile.value = null
    if (fileInput.value) fileInput.value.value = ''
    if (existingData.value?.geojson) loadGeoJsonOnMap(existingData.value.geojson)
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors de l\'import.'
  }
  uploading.value = false
}
</script>

<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-6 text-white relative overflow-hidden">
      <div class="absolute -right-8 -top-8 h-36 w-36 rounded-full bg-white/5"></div>
      <div class="absolute -bottom-6 right-24 h-20 w-20 rounded-full bg-[#d6e87a]/15"></div>
      <div class="relative">
        <p class="text-[11px] font-bold uppercase tracking-widest text-[#d6e87a]">Données géospatiales</p>
        <h1 class="mt-1 text-2xl font-black">Mes données SIG</h1>
        <p class="mt-1 text-sm text-white/60">Importez vos fichiers GeoJSON ou Shapefile pour votre projet</p>
      </div>
    </div>

    <!-- No project warning -->
    <div v-if="!loading && !monProjet" class="rounded-2xl border border-amber-200 bg-amber-50 px-6 py-5 flex items-center gap-4">
      <i class="fa-solid fa-triangle-exclamation text-amber-500 text-xl"></i>
      <div>
        <p class="font-bold text-slate-800">Aucun projet assigné</p>
        <p class="text-sm text-slate-500">Vous devez avoir une postulation acceptée pour importer des données SIG.</p>
      </div>
    </div>

    <template v-else-if="!loading">
      <!-- Project info -->
      <div class="rounded-2xl border border-slate-200 bg-white px-6 py-4 flex items-center gap-4">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#d6e87a]">
          <i class="fa-solid fa-folder-open text-[#4a5e20] text-base"></i>
        </div>
        <div>
          <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Projet actif</p>
          <p class="font-bold text-slate-800">{{ monProjet.titre }}</p>
        </div>
        <div v-if="existingData" class="ml-auto flex items-center gap-2 rounded-xl bg-emerald-50 border border-emerald-200 px-3 py-1.5">
          <i class="fa-solid fa-circle-check text-emerald-500 text-xs"></i>
          <span class="text-xs font-semibold text-emerald-700">Données importées</span>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        <!-- Upload card -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 class="mb-4 text-sm font-extrabold uppercase tracking-widest text-slate-500">Importer un fichier</h2>

          <!-- Drop zone -->
          <div
            @dragover.prevent
            @drop.prevent="onDrop"
            @click="fileInput?.click()"
            class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 px-6 py-10 transition hover:border-[#d6e87a] hover:bg-[#f8faef]"
          >
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#d6e87a]/40">
              <i class="fa-solid fa-cloud-arrow-up text-2xl text-[#4a5e20]"></i>
            </div>
            <div class="text-center">
              <p class="font-bold text-slate-700">Glissez-déposez ou cliquez pour choisir</p>
              <p class="mt-1 text-xs text-slate-400">GeoJSON (.geojson, .json) ou Shapefile ZIP (.zip) — max 20 MB</p>
            </div>
            <input ref="fileInput" type="file" accept=".geojson,.json,.zip" class="hidden" @change="onFileChange" />
          </div>

          <!-- Selected file preview -->
          <div v-if="selectedFile" class="mt-4 flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#d6e87a]">
              <i :class="`fa-solid ${fileIcon} text-[#4a5e20]`"></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="truncate text-sm font-semibold text-slate-800">{{ selectedFile.name }}</p>
              <p class="text-xs text-slate-400">{{ fileSizeLabel }}</p>
            </div>
            <button @click.stop="selectedFile = null; fileInput && (fileInput.value = '')" class="text-slate-400 hover:text-red-400">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- Alerts -->
          <div v-if="error" class="mt-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-600 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i> {{ error }}
          </div>
          <div v-if="success" class="mt-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i> {{ success }}
          </div>

          <button
            @click="upload"
            :disabled="!selectedFile || uploading"
            class="mt-5 w-full rounded-2xl bg-[#1e4a49] py-3 text-sm font-bold text-white transition hover:bg-[#163635] disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <i v-if="uploading" class="fa-solid fa-circle-notch animate-spin"></i>
            <i v-else class="fa-solid fa-upload"></i>
            {{ uploading ? 'Import en cours…' : 'Importer les données' }}
          </button>

          <!-- Existing data info -->
          <div v-if="existingData" class="mt-5 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 text-xs text-slate-500 space-y-1">
            <p class="font-semibold text-slate-700">Dernière importation</p>
            <p>Fichier : <span class="font-medium text-slate-800">{{ existingData.nom_fichier || '—' }}</span></p>
            <p>Mis à jour : <span class="font-medium text-slate-800">{{ existingData.updated_at ? new Date(existingData.updated_at).toLocaleDateString('fr-FR') : '—' }}</span></p>
          </div>
        </div>

        <!-- Map preview -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col">
          <h2 class="mb-4 text-sm font-extrabold uppercase tracking-widest text-slate-500">Aperçu sur la carte</h2>
          <div id="sig-map" class="flex-1 rounded-2xl overflow-hidden" style="min-height:360px"></div>
          <p v-if="!existingData" class="mt-3 text-center text-xs text-slate-400">
            Importez des données pour les visualiser ici
          </p>
        </div>

      </div>
    </template>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <i class="fa-solid fa-circle-notch animate-spin text-3xl text-[#d6e87a]"></i>
    </div>

  </div>
</template>
