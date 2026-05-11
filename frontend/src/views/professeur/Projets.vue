<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import api from '@/services/api'
import 'leaflet/dist/leaflet.css'
import L from 'leaflet'

import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({ iconUrl: markerIcon, iconRetinaUrl: markerIcon2x, shadowUrl: markerShadow })

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const profId = computed(() => user.value?.professeur?.id)

const items = ref([])
const annees = ref([])
const loading = ref(false)
const search = ref('')
const filterStatut = ref('')
const showModal = ref(false)
const editing = ref(null)
const error = ref('')
const form = ref({})
const viewMode = ref('grid')
const mapRef = ref(null)
let leafletMap = null
let markers = []

const showZonePicker = ref(false)
const zonePickerRef = ref(null)
let pickerMap = null
let pickerMarker = null
let pickerRect = null
let drawStart = null
let isDrawing = false

const defaultForm = () => ({
  titre: '', description: '', domaine: '',
  statut: 'soumis',
  professeur_id: profId.value,
  annee_universitaire_id: '',
  ville: '', latitude: '', longitude: '',
  zone_etude: null,
})

async function fetchAll() {
  loading.value = true
  try {
    const me = await api.get('/me')
    user.value = me.data?.data || {}
    localStorage.setItem('admin_user', JSON.stringify(user.value))
    const [pr, ar] = await Promise.all([
      api.get('/projets'),
      api.get('/annees-universitaires').catch(() => ({ data: { data: [] } })),
    ])
    items.value = pr.data.data.filter(p => p.professeur_id === profId.value)
    annees.value = ar.data.data
  } catch {}
  loading.value = false
}

const filtered = computed(() => {
  let list = items.value
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(p => JSON.stringify(p).toLowerCase().includes(q))
  }
  if (filterStatut.value) list = list.filter(p => p.statut === filterStatut.value)
  return list
})

const mappableProjects = computed(() => filtered.value.filter(p => p.latitude && p.longitude))

function openCreate() {
  editing.value = null
  form.value = defaultForm()
  error.value = ''
  showModal.value = true
}

function openEdit(p) {
  editing.value = p
  form.value = {
    titre: p.titre, description: p.description, domaine: p.domaine,
    statut: p.statut, professeur_id: profId.value,
    annee_universitaire_id: p.annee_universitaire_id,
    ville: p.ville || '', latitude: p.latitude || '', longitude: p.longitude || '',
    zone_etude: p.zone_etude || null,
  }
  error.value = ''
  showModal.value = true
}

async function save() {
  error.value = ''
  try {
    const payload = { ...form.value }
    if (payload.latitude === '') payload.latitude = null
    if (payload.longitude === '') payload.longitude = null
    if (editing.value) {
      await api.put(`/projets/${editing.value.id}`, payload)
    } else {
      await api.post('/projets', payload)
    }
    showModal.value = false
    await fetchAll()
    if (viewMode.value === 'map') await nextTick(() => initMap())
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur'
    if (e.response?.data?.errors) error.value = Object.values(e.response.data.errors).flat().join(' | ')
  }
}

async function remove(id) {
  if (!confirm('Supprimer ce projet ?')) return
  try {
    await api.delete(`/projets/${id}`)
    await fetchAll()
    if (viewMode.value === 'map') await nextTick(() => initMap())
  } catch (e) { alert(e.response?.data?.message || 'Erreur') }
}

async function initMap() {
  if (!mapRef.value) return
  if (!leafletMap) {
    leafletMap = L.map(mapRef.value).setView([28.0, 2.5], 5)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(leafletMap)
  }
  markers.forEach(m => m.remove())
  markers = []
  const customIcon = L.divIcon({
    className: '',
    html: `<div style="width:28px;height:28px;background:#1e4a49;border:3px solid #d6e87a;border-radius:50% 50% 50% 0;transform:rotate(-45deg);box-shadow:0 2px 8px rgba(0,0,0,0.25)"><div style="width:8px;height:8px;background:#d6e87a;border-radius:50%;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%)"></div></div>`,
    iconSize: [28, 28], iconAnchor: [14, 28], popupAnchor: [0, -30],
  })

  // Fetch SIG data for all mappable projects in parallel
  const sigResults = await Promise.allSettled(
    mappableProjects.value.map(p => api.get(`/sig/projet/${p.id}`).catch(() => null))
  )

  mappableProjects.value.forEach((p, i) => {
    // Overlay student GeoJSON if available
    const sigData = sigResults[i]?.value?.data?.data
    if (sigData?.geojson) {
      try {
        const geojson = typeof sigData.geojson === 'string' ? JSON.parse(sigData.geojson) : sigData.geojson
        const layer = L.geoJSON(geojson, {
          style: { color: '#1e4a49', weight: 2, fillColor: '#d6e87a', fillOpacity: 0.3 },
          pointToLayer: (f, latlng) => L.circleMarker(latlng, { radius: 5, fillColor: '#d6e87a', color: '#1e4a49', weight: 2, fillOpacity: 0.9 }),
          onEachFeature: (_f, l) => {
            if (_f.properties && Object.keys(_f.properties).length) {
              const props = Object.entries(_f.properties).filter(([,v]) => v != null).slice(0, 4)
                .map(([k,v]) => `<p style="font-size:11px;color:#64748b;margin:0">${k}: ${v}</p>`).join('')
              l.bindPopup(`<div style="font-family:system-ui"><p style="font-weight:700;font-size:12px;color:#1e293b;margin:0 0 4px">${p.titre}</p>${props}</div>`)
            }
          }
        }).addTo(leafletMap)
        markers.push(layer)
      } catch {}
    }

    // Draw zone rectangle if defined
    if (p.zone_etude) {
      const z = p.zone_etude
      const rect = L.rectangle([[z.south, z.west], [z.north, z.east]], {
        color: '#1e4a49', fillColor: '#d6e87a', fillOpacity: 0.15, weight: 2, dashArray: '6'
      }).addTo(leafletMap)
      markers.push(rect)
    }
    const m = L.marker([p.latitude, p.longitude], { icon: customIcon })
      .addTo(leafletMap)
      .bindPopup(`<div style="font-family:system-ui;min-width:180px"><p style="font-weight:800;font-size:13px;color:#1e293b;margin:0 0 4px">${p.titre}</p><p style="font-size:11px;color:#64748b;margin:0 0 2px">${p.domaine || ''}</p><p style="font-size:11px;color:#64748b;margin:0">📍 ${p.ville || ''}</p>${p.zone_etude ? '<p style="font-size:10px;color:#6a8a40;margin:4px 0 0;font-weight:700">📐 Zone d\'étude définie</p>' : ''}${sigResults[i]?.value?.data?.data ? '<p style="font-size:10px;color:#1e4a49;margin:4px 0 0;font-weight:700">🗺 Données SIG importées</p>' : ''}<span style="display:inline-block;margin-top:6px;padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700;background:#d6e87a;color:#1e293b">${statutLabel[p.statut] || p.statut}</span></div>`)
    markers.push(m)
  })
  if (markers.length > 0) leafletMap.fitBounds(L.featureGroup(markers).getBounds().pad(0.2))
}

async function switchView(mode) {
  viewMode.value = mode
  if (mode === 'map') { await nextTick(); initMap() }
}

// ── ZONE PICKER ──────────────────────────────────────────────
async function openZonePicker() {
  showZonePicker.value = true
  await nextTick()
  if (pickerMap) {
    pickerMap.remove()
    pickerMap = null
    pickerMarker = null
    pickerRect = null
  }
  pickerMap = L.map(zonePickerRef.value).setView([28.0, 2.5], 5)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(pickerMap)

  // If existing zone, show it
  if (form.value.zone_etude) {
    const z = form.value.zone_etude
    pickerRect = L.rectangle([[z.south, z.west], [z.north, z.east]], {
      color: '#1e4a49', fillColor: '#d6e87a', fillOpacity: 0.25, weight: 2
    }).addTo(pickerMap)
    pickerMap.fitBounds(pickerRect.getBounds().pad(0.1))
  } else if (form.value.latitude && form.value.longitude) {
    pickerMap.setView([form.value.latitude, form.value.longitude], 8)
  }

  // Draw rectangle on click+drag
  let startLatLng = null
  pickerMap.on('mousedown', (e) => {
    isDrawing = true
    startLatLng = e.latlng
    if (pickerRect) { pickerRect.remove(); pickerRect = null }
    if (pickerMarker) { pickerMarker.remove(); pickerMarker = null }
  })
  pickerMap.on('mousemove', (e) => {
    if (!isDrawing || !startLatLng) return
    if (pickerRect) pickerRect.remove()
    pickerRect = L.rectangle([startLatLng, e.latlng], {
      color: '#1e4a49', fillColor: '#d6e87a', fillOpacity: 0.25, weight: 2, dashArray: '6'
    }).addTo(pickerMap)
  })
  pickerMap.on('mouseup', (e) => {
    if (!isDrawing) return
    isDrawing = false
    if (!startLatLng) return
    const bounds = L.latLngBounds(startLatLng, e.latlng)
    if (pickerRect) pickerRect.remove()
    pickerRect = L.rectangle(bounds, {
      color: '#1e4a49', fillColor: '#d6e87a', fillOpacity: 0.3, weight: 2
    }).addTo(pickerMap)
    const center = bounds.getCenter()
    drawStart = { bounds, center }
    startLatLng = null
  })
}

function confirmZone() {
  if (drawStart) {
    const { bounds, center } = drawStart
    form.value.latitude  = parseFloat(center.lat.toFixed(6))
    form.value.longitude = parseFloat(center.lng.toFixed(6))
    form.value.zone_etude = {
      north: parseFloat(bounds.getNorth().toFixed(6)),
      south: parseFloat(bounds.getSouth().toFixed(6)),
      east:  parseFloat(bounds.getEast().toFixed(6)),
      west:  parseFloat(bounds.getWest().toFixed(6)),
    }
  }
  closeZonePicker()
}

function clearZone() {
  form.value.zone_etude = null
  form.value.latitude = ''
  form.value.longitude = ''
  drawStart = null
  if (pickerRect) { pickerRect.remove(); pickerRect = null }
}

function closeZonePicker() {
  showZonePicker.value = false
  drawStart = null
  isDrawing = false
}

const statutLabel = { brouillon: 'Brouillon', soumis: 'Soumis', en_cours: 'En cours', valide: 'Validé', soutenu: 'Soutenu', rejete: 'Rejeté' }
const statutColor = { brouillon: 'bg-slate-100 text-slate-600', soumis: 'bg-[#eaf4c0] text-[#4a6a20]', en_cours: 'bg-amber-100 text-amber-700', valide: 'bg-green-100 text-green-700', soutenu: 'bg-[#d6e87a] text-slate-800', rejete: 'bg-red-100 text-red-600' }
const statutDot = { brouillon: 'bg-slate-400', soumis: 'bg-[#8ab830]', en_cours: 'bg-amber-400', valide: 'bg-green-500', soutenu: 'bg-[#4a6a20]', rejete: 'bg-red-400' }
const stats = computed(() => ({
  total: items.value.length,
  soumis: items.value.filter(p => p.statut === 'soumis').length,
  en_cours: items.value.filter(p => p.statut === 'en_cours').length,
  soutenu: items.value.filter(p => p.statut === 'soutenu').length,
}))
const domaineIcon = (d) => {
  if (!d) return 'fa-folder'
  const dl = d.toLowerCase()
  if (dl.includes('info') || dl.includes('data') || dl.includes('ia')) return 'fa-microchip'
  if (dl.includes('math')) return 'fa-square-root-variable'
  if (dl.includes('réseau') || dl.includes('web')) return 'fa-globe'
  if (dl.includes('géo') || dl.includes('sig')) return 'fa-map-location-dot'
  return 'fa-folder-open'
}

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-6">

    <!-- HERO BANNER -->
    <div class="relative overflow-hidden rounded-[2rem] bg-[#1e4a49] px-8 py-8 shadow-xl">
      <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-[#d6e87a]/10"></div>
      <div class="pointer-events-none absolute -bottom-8 right-24 h-32 w-32 rounded-full bg-[#d6e87a]/5"></div>
      <div class="pointer-events-none absolute bottom-4 left-1/3 h-16 w-16 rounded-full bg-white/5"></div>
      <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
        <div>
          <div class="mb-1">
            <span class="rounded-lg bg-[#d6e87a]/20 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-[#d6e87a]">Encadrement PFE</span>
          </div>
          <h1 class="text-3xl font-black text-white">Mes projets</h1>
          <p class="mt-1 text-sm text-white/50">{{ user.prenom }} {{ user.nom }} · {{ items.length }} projet{{ items.length !== 1 ? 's' : '' }} encadré{{ items.length !== 1 ? 's' : '' }}</p>
        </div>
        <button @click="openCreate" class="flex shrink-0 items-center gap-2 rounded-2xl bg-[#d6e87a] px-6 py-3 text-sm font-black text-slate-900 shadow-lg transition hover:bg-[#c8dc60]">
          <i class="fa-solid fa-plus text-base"></i> Proposer un projet
        </button>
      </div>
      <div class="relative mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-2xl bg-white/10 px-4 py-3"><p class="text-2xl font-black text-white">{{ stats.total }}</p><p class="text-[11px] font-semibold text-white/50">Total</p></div>
        <div class="rounded-2xl bg-[#d6e87a]/15 px-4 py-3"><p class="text-2xl font-black text-[#d6e87a]">{{ stats.soumis }}</p><p class="text-[11px] font-semibold text-[#d6e87a]/60">Soumis</p></div>
        <div class="rounded-2xl bg-amber-400/10 px-4 py-3"><p class="text-2xl font-black text-amber-300">{{ stats.en_cours }}</p><p class="text-[11px] font-semibold text-amber-300/60">En cours</p></div>
        <div class="rounded-2xl bg-green-400/10 px-4 py-3"><p class="text-2xl font-black text-green-300">{{ stats.soutenu }}</p><p class="text-[11px] font-semibold text-green-300/60">Soutenus</p></div>
      </div>
    </div>

    <!-- FILTERS + VIEW TOGGLE -->
    <div class="flex flex-wrap gap-3">
      <div class="flex flex-1 min-w-[200px] items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
        <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
        <input v-model="search" placeholder="Rechercher un projet…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
      </div>
      <select v-model="filterStatut" class="rounded-2xl border border-white/70 bg-white/90 px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm outline-none">
        <option value="">Tous les statuts</option>
        <option v-for="(label, key) in statutLabel" :key="key" :value="key">{{ label }}</option>
      </select>
      <div class="flex rounded-2xl border border-white/70 bg-white/90 shadow-sm overflow-hidden">
        <button @click="switchView('grid')" class="px-4 py-3 text-sm font-bold transition" :class="viewMode === 'grid' ? 'bg-[#1e4a49] text-[#d6e87a]' : 'text-slate-400 hover:text-slate-700'">
          <i class="fa-solid fa-grip"></i>
        </button>
        <button @click="switchView('map')" class="px-4 py-3 text-sm font-bold transition border-l border-slate-100" :class="viewMode === 'map' ? 'bg-[#1e4a49] text-[#d6e87a]' : 'text-slate-400 hover:text-slate-700'">
          <i class="fa-solid fa-map-location-dot"></i>
        </button>
      </div>
    </div>

    <div v-if="loading" class="rounded-[2rem] border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400 shadow-sm">Chargement…</div>

    <template v-else>

      <!-- MAP VIEW -->
      <div v-if="viewMode === 'map'" class="relative rounded-[2rem] overflow-hidden border border-white/70 shadow-sm" style="height:520px">
        <div ref="mapRef" style="height:100%;width:100%"></div>
        <div v-if="mappableProjects.length === 0" class="absolute inset-0 z-[500] flex flex-col items-center justify-center bg-white/80 backdrop-blur-sm">
          <i class="fa-solid fa-map-location-dot text-4xl text-slate-300"></i>
          <p class="mt-3 text-sm font-bold text-slate-500">Aucun projet géolocalisé</p>
          <p class="text-xs text-slate-400 mt-1">Ajoutez ville + coordonnées lors de la création.</p>
        </div>
      </div>

      <!-- GRID VIEW -->
      <template v-else>
        <div v-if="filtered.length === 0" class="flex flex-col items-center justify-center rounded-[2rem] border-2 border-dashed border-[#d6e87a]/50 bg-white/60 py-20 text-center">
          <div class="flex h-20 w-20 items-center justify-center rounded-full bg-[#d6e87a]/20">
            <i class="fa-solid fa-folder-open text-3xl text-[#6a8a40]"></i>
          </div>
          <p class="mt-5 text-lg font-extrabold text-slate-700">Aucun projet trouvé</p>
          <p class="mt-1 text-sm text-slate-400">Proposez un projet PFE pour commencer.</p>
          <button @click="openCreate" class="mt-6 rounded-2xl bg-[#d6e87a] px-6 py-2.5 text-sm font-black text-slate-900 transition hover:bg-[#c8dc60]">
            <i class="fa-solid fa-plus mr-1.5"></i> Nouveau projet
          </button>
        </div>

        <div v-else class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
          <article v-for="p in filtered" :key="p.id" class="group relative flex flex-col rounded-[2rem] border border-white/70 bg-white/90 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#d6e87a] hover:shadow-lg overflow-hidden">
            <div class="h-1.5 w-full" :class="statutDot[p.statut] || 'bg-slate-200'"></div>
            <div class="flex flex-1 flex-col p-5">
              <div class="flex items-start justify-between gap-3 mb-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#f0f3eb]">
                  <i :class="`fa-solid ${domaineIcon(p.domaine)} text-[#6a8a40] text-sm`"></i>
                </div>
                <span class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[10px] font-bold" :class="statutColor[p.statut]">
                  <span class="h-1.5 w-1.5 rounded-full" :class="statutDot[p.statut]"></span>
                  {{ statutLabel[p.statut] || p.statut }}
                </span>
              </div>
              <h3 class="text-base font-extrabold text-slate-900 leading-snug mb-1.5 line-clamp-2">{{ p.titre }}</h3>
              <p v-if="p.domaine" class="mb-3 text-[11px] font-semibold uppercase tracking-wide text-[#6a8a40]">
                <i class="fa-solid fa-tag mr-1 text-[#d6e87a]"></i>{{ p.domaine }}
              </p>
              <p class="text-sm text-slate-500 line-clamp-3 flex-1 leading-relaxed">{{ p.description || 'Aucune description renseignée.' }}</p>
              <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3.5">
                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400">
                  <span v-if="p.anneeUniversitaire" class="flex items-center gap-1">
                    <i class="fa-solid fa-calendar-days text-slate-300"></i>{{ p.anneeUniversitaire?.annee }}
                  </span>
                  <span class="flex items-center gap-1">
                    <i class="fa-solid fa-users text-slate-300"></i>{{ p.postulations?.length || 0 }} postulation{{ (p.postulations?.length || 0) !== 1 ? 's' : '' }}
                  </span>
                  <span v-if="p.ville" class="flex items-center gap-1 font-semibold text-[#6a8a40]">
                    <i class="fa-solid fa-location-dot text-[#d6e87a]"></i>{{ p.ville }}
                  </span>
                </div>
                <div class="flex gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                  <button @click="openEdit(p)" class="rounded-xl bg-[#f0f3eb] px-3 py-1.5 text-xs font-bold text-[#4a5e20] hover:bg-[#d6e87a] transition"><i class="fa-solid fa-pen"></i></button>
                  <button @click="remove(p.id)" class="rounded-xl bg-red-50 px-3 py-1.5 text-xs font-bold text-red-400 hover:bg-red-100 transition"><i class="fa-solid fa-trash"></i></button>
                </div>
              </div>
            </div>
          </article>
        </div>
      </template>
    </template>

    <!-- MODAL -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-lg rounded-[2rem] bg-white shadow-2xl max-h-[92vh] overflow-y-auto">
          <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#d6e87a]">
                <i class="fa-solid fa-folder-plus text-[#4a5e20] text-sm"></i>
              </div>
              <h2 class="text-base font-extrabold text-slate-900">{{ editing ? 'Modifier le projet' : 'Proposer un projet' }}</h2>
            </div>
            <button @click="showModal = false" class="flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
          <form @submit.prevent="save" class="space-y-4 p-6">
            <div v-if="error" class="flex items-start gap-2 rounded-xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
              <i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0"></i> {{ error }}
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Titre <span class="text-red-400">*</span></label>
              <input v-model="form.titre" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a] transition" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Description</label>
              <textarea v-model="form.description" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a] resize-none transition"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Domaine</label>
                <input v-model="form.domaine" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a] transition" />
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Statut</label>
                <select v-model="form.statut" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a] transition">
                  <option v-for="(label, key) in statutLabel" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Année universitaire <span class="text-red-400">*</span></label>
              <select v-model="form.annee_universitaire_id" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a] transition">
                <option value="">— Sélectionner —</option>
                <option v-for="a in annees" :key="a.id" :value="a.id">{{ a.annee }}</option>
              </select>
            </div>
            <!-- SIG Location -->
            <div class="rounded-2xl border border-[#d6e87a]/50 bg-[#f0f3eb] p-4 space-y-3">
              <div class="flex items-center justify-between">
                <p class="text-[11px] font-black uppercase tracking-widest text-[#4a5e20] flex items-center gap-1.5">
                  <i class="fa-solid fa-map-location-dot text-[#d6e87a]"></i> Localisation SIG
                </p>
                <div class="flex gap-2">
                  <button type="button" @click="openZonePicker"
                    class="flex items-center gap-1.5 rounded-xl bg-[#1e4a49] px-3 py-1.5 text-[11px] font-bold text-[#d6e87a] hover:bg-[#163836] transition">
                    <i class="fa-solid fa-draw-polygon"></i> Dessiner une zone
                  </button>
                  <button v-if="form.zone_etude" type="button" @click="clearZone"
                    class="flex items-center gap-1 rounded-xl bg-red-50 px-2.5 py-1.5 text-[11px] font-bold text-red-400 hover:bg-red-100 transition">
                    <i class="fa-solid fa-xmark"></i>
                  </button>
                </div>
              </div>

              <!-- Zone preview badge -->
              <div v-if="form.zone_etude" class="flex items-center gap-2 rounded-xl bg-[#d6e87a]/30 border border-[#d6e87a] px-3 py-2">
                <i class="fa-solid fa-vector-square text-[#4a5e20] text-sm"></i>
                <span class="text-xs font-semibold text-[#4a5e20]">Zone définie · cliquez "Dessiner" pour modifier</span>
              </div>

              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Ville / Région</label>
                <input v-model="form.ville" placeholder="ex: Alger, Oran…" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a] transition" />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="mb-1.5 block text-xs font-bold text-slate-600">Latitude (centre)</label>
                  <input v-model="form.latitude" type="number" step="any" placeholder="36.7370" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a] transition" />
                </div>
                <div>
                  <label class="mb-1.5 block text-xs font-bold text-slate-600">Longitude (centre)</label>
                  <input v-model="form.longitude" type="number" step="any" placeholder="3.0870" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a] transition" />
                </div>
              </div>
            </div>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="showModal = false" class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">Annuler</button>
              <button type="submit" class="flex-1 rounded-xl bg-[#1e4a49] py-2.5 text-sm font-bold text-white hover:bg-[#163836] transition shadow">
                <i class="fa-solid fa-floppy-disk mr-1.5"></i>{{ editing ? 'Mettre à jour' : 'Proposer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ZONE PICKER MODAL -->
    <Teleport to="body">
      <div v-if="showZonePicker" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="w-full max-w-3xl rounded-[2rem] bg-white shadow-2xl overflow-hidden flex flex-col" style="height:580px">
          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#1e4a49]">
                <i class="fa-solid fa-draw-polygon text-[#d6e87a] text-sm"></i>
              </div>
              <div>
                <h2 class="text-base font-extrabold text-slate-900">Dessiner la zone d'étude</h2>
                <p class="text-xs text-slate-400">Cliquez et faites glisser sur la carte pour délimiter la zone</p>
              </div>
            </div>
            <button @click="closeZonePicker" class="flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 transition">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- Instruction bar -->
          <div class="flex items-center gap-2 bg-[#f0f3eb] px-5 py-2.5 text-xs font-semibold text-[#4a5e20] shrink-0">
            <i class="fa-solid fa-hand-pointer text-[#d6e87a]"></i>
            Maintenez le clic et faites glisser pour dessiner un rectangle · Recommencez pour redessiner
          </div>

          <!-- Map -->
          <div ref="zonePickerRef" class="flex-1" style="cursor:crosshair"></div>

          <!-- Footer -->
          <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-slate-100 shrink-0 bg-white">
            <div class="text-xs text-slate-400">
              <span v-if="drawStart">
                <i class="fa-solid fa-check-circle text-green-500 mr-1"></i>
                Zone sélectionnée · N {{ drawStart.bounds.getNorth().toFixed(4) }} S {{ drawStart.bounds.getSouth().toFixed(4) }}
              </span>
              <span v-else class="text-slate-400">Aucune zone dessinée</span>
            </div>
            <div class="flex gap-3">
              <button type="button" @click="closeZonePicker"
                class="rounded-xl border border-slate-200 px-5 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">
                Annuler
              </button>
              <button type="button" @click="confirmZone" :disabled="!drawStart"
                class="rounded-xl px-5 py-2 text-sm font-bold transition shadow"
                :class="drawStart ? 'bg-[#1e4a49] text-[#d6e87a] hover:bg-[#163836]' : 'bg-slate-100 text-slate-400 cursor-not-allowed'">
                <i class="fa-solid fa-check mr-1.5"></i> Confirmer la zone
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
