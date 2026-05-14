<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import api from '@/services/api'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const projets = ref([])
const loading = ref(true)
const search = ref('')
const filterAnnee = ref('')
const filterDomaine = ref('')

const selected = ref(null)
const selectedSig = ref([])
const sigLoading = ref(false)
const showSigMap = ref(false)

let sigMap = null

async function fetchArchive() {
  loading.value = true
  try {
    const res = await api.get('/projets/archive')
    projets.value = res.data.data
  } catch {}
  loading.value = false
}

onMounted(fetchArchive)
onUnmounted(() => { if (sigMap) { sigMap.remove(); sigMap = null } })

const annees = computed(() => [...new Set(projets.value.map(p => p.anneeUniversitaire?.annee).filter(Boolean))].sort().reverse())
const domaines = computed(() => [...new Set(projets.value.map(p => p.domaine).filter(Boolean))].sort())

const filtered = computed(() => {
  let list = projets.value
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(p =>
      p.titre?.toLowerCase().includes(q) ||
      p.domaine?.toLowerCase().includes(q) ||
      p.professeur?.utilisateur?.nom?.toLowerCase().includes(q)
    )
  }
  if (filterAnnee.value) list = list.filter(p => p.anneeUniversitaire?.annee === filterAnnee.value)
  if (filterDomaine.value) list = list.filter(p => p.domaine === filterDomaine.value)
  return list
})

const etudiantAccepte = (p) => p.postulations?.find(po => po.statut === 'accepte')

async function openDetail(p) {
  selected.value = p
  sigLoading.value = true
  selectedSig.value = []
  try {
    const res = await api.get(`/sig/projet/${p.id}`)
    selectedSig.value = res.data.data || []
  } catch {}
  sigLoading.value = false
}

function closeDetail() {
  selected.value = null
  selectedSig.value = []
  closeSigMap()
}

async function openSigMap() {
  showSigMap.value = true
  await nextTick()
  if (sigMap) { sigMap.remove(); sigMap = null }
  const el = document.getElementById('archive-sig-map')
  if (!el) return

  const p = selected.value
  const center = p?.latitude && p?.longitude ? [p.latitude, p.longitude] : [29, -8]
  sigMap = L.map(el).setView(center, p?.latitude ? 7 : 5)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(sigMap)

  if (p?.zone_etude) {
    const z = p.zone_etude
    L.rectangle([[z.south, z.west], [z.north, z.east]], {
      color: '#1e4a49', fillColor: '#d6e87a', fillOpacity: 0.15, weight: 2, dashArray: '6'
    }).addTo(sigMap)
  }

  const colors = ['#1e4a49', '#4a5e20', '#2d6a4f', '#386641']
  const layers = []
  selectedSig.value.forEach((sig, i) => {
    if (!sig.geojson) return
    const color = colors[i % colors.length]
    try {
      const data = typeof sig.geojson === 'string' ? JSON.parse(sig.geojson) : sig.geojson
      const layer = L.geoJSON(data, {
        style: { color, weight: 2, fillColor: '#d6e87a', fillOpacity: 0.3 },
        pointToLayer: (_f, latlng) => L.circleMarker(latlng, { radius: 6, fillColor: '#d6e87a', color, weight: 2, fillOpacity: 0.9 }),
        onEachFeature: (_f, l) => {
          if (_f.properties && Object.keys(_f.properties).length) {
            const rows = Object.entries(_f.properties).filter(([, v]) => v != null).slice(0, 5)
              .map(([k, v]) => `<tr><td style="color:#94a3b8;font-size:11px;padding-right:8px">${k}</td><td style="font-size:11px;font-weight:600;color:#1e293b">${v}</td></tr>`).join('')
            l.bindPopup(`<div style="font-family:system-ui"><p style="font-weight:800;font-size:12px;margin:0 0 6px">${sig.nom_fichier || ''}</p><table>${rows}</table></div>`)
          }
        }
      }).addTo(sigMap)
      layers.push(layer)
    } catch {}
  })

  if (layers.length) {
    const group = L.featureGroup(layers)
    if (group.getBounds().isValid()) sigMap.fitBounds(group.getBounds(), { padding: [40, 40] })
  }
}

function closeSigMap() {
  if (sigMap) { sigMap.remove(); sigMap = null }
  showSigMap.value = false
}

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
}

const domaineIcon = (d) => {
  if (!d) return 'fa-folder'
  const dl = d.toLowerCase()
  if (dl.includes('info') || dl.includes('data') || dl.includes('ia')) return 'fa-microchip'
  if (dl.includes('math')) return 'fa-square-root-variable'
  if (dl.includes('réseau') || dl.includes('web')) return 'fa-globe'
  if (dl.includes('géo') || dl.includes('sig')) return 'fa-map-location-dot'
  return 'fa-folder-open'
}
</script>

<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-6 text-white relative overflow-hidden">
      <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/5"></div>
      <div class="absolute -bottom-6 right-24 h-24 w-24 rounded-full bg-[#d6e87a]/10"></div>
      <div class="relative flex items-center justify-between">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-widest text-[#d6e87a]">Bibliothèque PFE</p>
          <h1 class="mt-1 text-2xl font-black">Archives des projets soutenus</h1>
          <p class="mt-1 text-sm text-white/60">{{ projets.length }} projet{{ projets.length !== 1 ? 's' : '' }} archivé{{ projets.length !== 1 ? 's' : '' }}</p>
        </div>
        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#d6e87a]/20">
          <i class="fa-solid fa-box-archive text-2xl text-[#d6e87a]"></i>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3">
      <div class="flex flex-1 min-w-[200px] items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
        <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
        <input v-model="search" placeholder="Titre, domaine, encadrant…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
      </div>
      <select v-model="filterAnnee" class="rounded-2xl border border-white/70 bg-white/90 px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm outline-none">
        <option value="">Toutes les années</option>
        <option v-for="a in annees" :key="a" :value="a">{{ a }}</option>
      </select>
      <select v-model="filterDomaine" class="rounded-2xl border border-white/70 bg-white/90 px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm outline-none">
        <option value="">Tous les domaines</option>
        <option v-for="d in domaines" :key="d" :value="d">{{ d }}</option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <i class="fa-solid fa-circle-notch animate-spin text-3xl text-[#d6e87a]"></i>
    </div>

    <!-- Empty -->
    <div v-else-if="!filtered.length" class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-[#d6e87a]/40 bg-white/60 py-20 text-center">
      <i class="fa-solid fa-box-archive text-4xl text-slate-300"></i>
      <p class="mt-4 text-lg font-extrabold text-slate-600">Aucun projet archivé</p>
      <p class="mt-1 text-sm text-slate-400">Les projets soutenus apparaîtront ici.</p>
    </div>

    <!-- Grid -->
    <div v-else class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="p in filtered" :key="p.id"
        @click="openDetail(p)"
        class="group flex flex-col rounded-3xl border border-white/70 bg-white/90 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#d6e87a] hover:shadow-lg overflow-hidden cursor-pointer"
      >
        <!-- Top accent -->
        <div class="h-1.5 w-full bg-[#d6e87a]"></div>

        <div class="flex flex-1 flex-col p-5">
          <div class="flex items-start justify-between gap-3 mb-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#f0f3eb]">
              <i :class="`fa-solid ${domaineIcon(p.domaine)} text-[#6a8a40] text-sm`"></i>
            </div>
            <span class="text-[10px] font-bold bg-[#d6e87a]/40 text-[#4a5e20] px-2.5 py-1 rounded-lg">
              {{ p.anneeUniversitaire?.annee || '—' }}
            </span>
          </div>

          <h3 class="text-base font-extrabold text-slate-900 leading-snug mb-1 line-clamp-2">{{ p.titre }}</h3>
          <p v-if="p.domaine" class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-[#6a8a40]">
            <i class="fa-solid fa-tag mr-1 text-[#d6e87a]"></i>{{ p.domaine }}
          </p>
          <p class="text-sm text-slate-500 line-clamp-2 flex-1 leading-relaxed">{{ p.description || 'Aucune description.' }}</p>

          <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
            <!-- Student -->
            <div class="flex items-center gap-2 min-w-0">
              <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#d6e87a] text-[10px] font-black text-[#4a5e20]">
                {{ (etudiantAccepte(p)?.etudiant?.utilisateur?.prenom || '?')[0] }}{{ (etudiantAccepte(p)?.etudiant?.utilisateur?.nom || '?')[0] }}
              </div>
              <span class="truncate text-xs font-semibold text-slate-600">
                {{ etudiantAccepte(p)?.etudiant?.utilisateur?.prenom }} {{ etudiantAccepte(p)?.etudiant?.utilisateur?.nom }}
              </span>
            </div>
            <!-- Badges -->
            <div class="flex items-center gap-2 shrink-0">
              <span v-if="p.depots?.length" class="flex items-center gap-1 text-[10px] font-bold text-slate-400">
                <i class="fa-solid fa-file text-slate-300"></i>{{ p.depots.length }}
              </span>
              <span v-if="p.donneeSpatiale" class="flex h-6 w-6 items-center justify-center rounded-lg bg-[#1e4a49]/10">
                <i class="fa-solid fa-map-location-dot text-[#1e4a49] text-[10px]"></i>
              </span>
            </div>
          </div>
        </div>
      </article>
    </div>

    <!-- DETAIL MODAL -->
    <Teleport to="body">
      <div v-if="selected" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeDetail"></div>
        <div class="relative z-10 flex w-full max-w-2xl flex-col rounded-3xl bg-white shadow-2xl overflow-hidden" style="max-height:88vh">

          <!-- Header -->
          <div class="flex items-center justify-between bg-[#1e4a49] px-6 py-5 shrink-0">
            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#d6e87a]">
                <i :class="`fa-solid ${domaineIcon(selected.domaine)} text-[#4a5e20] text-sm`"></i>
              </div>
              <div class="min-w-0">
                <p class="truncate text-sm font-extrabold text-white">{{ selected.titre }}</p>
                <p class="text-xs text-white/50">{{ selected.domaine }} · {{ selected.anneeUniversitaire?.annee }}</p>
              </div>
            </div>
            <button @click="closeDetail" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-white/60 hover:bg-white/10 transition">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="flex-1 overflow-y-auto p-6 space-y-6">

            <!-- Meta -->
            <div class="grid grid-cols-3 gap-3">
              <div class="rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Encadrant</p>
                <p class="mt-1 text-sm font-bold text-slate-700">{{ selected.professeur?.utilisateur?.prenom }} {{ selected.professeur?.utilisateur?.nom }}</p>
              </div>
              <div class="rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Étudiant</p>
                <p class="mt-1 text-sm font-bold text-slate-700 truncate">
                  {{ etudiantAccepte(selected)?.etudiant?.utilisateur?.prenom }}
                  {{ etudiantAccepte(selected)?.etudiant?.utilisateur?.nom || '—' }}
                </p>
              </div>
              <div class="rounded-2xl bg-[#f0f3eb] border border-[#d6e87a]/40 px-4 py-3">
                <p class="text-[10px] font-bold uppercase tracking-widest text-[#6a8a40]">Soutenance</p>
                <p class="mt-1 text-sm font-bold text-[#4a5e20]">{{ formatDate(selected.soutenance?.date_soutenance) }}</p>
              </div>
            </div>

            <!-- Description -->
            <div v-if="selected.description">
              <p class="mb-2 text-[11px] font-black uppercase tracking-widest text-slate-400">Description</p>
              <p class="text-sm text-slate-600 leading-relaxed">{{ selected.description }}</p>
            </div>

            <!-- Dépôts -->
            <div>
              <p class="mb-3 text-[11px] font-black uppercase tracking-widest text-slate-400 flex items-center gap-2">
                <i class="fa-solid fa-file-arrow-down text-[#d6e87a]"></i> Fichiers déposés ({{ selected.depots?.length || 0 }})
              </p>
              <div v-if="!selected.depots?.length" class="rounded-2xl border border-dashed border-slate-200 py-5 text-center text-xs text-slate-400">
                Aucun fichier déposé
              </div>
              <div v-else class="space-y-2">
                <div v-for="depot in selected.depots" :key="depot.id"
                  class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                  <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#d6e87a]">
                    <i class="fa-solid fa-file text-[#4a5e20] text-xs"></i>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-800">{{ depot.type_depot || 'Rapport' }}</p>
                    <p class="text-xs text-slate-400">{{ formatDate(depot.created_at) }}</p>
                  </div>
                  <a v-if="depot.chemin_fichier" :href="depot.chemin_fichier" target="_blank"
                    class="flex items-center gap-1.5 rounded-xl bg-[#1e4a49] px-3 py-1.5 text-[11px] font-bold text-[#d6e87a] hover:bg-[#163836] transition" @click.stop>
                    <i class="fa-solid fa-download text-xs"></i> Télécharger
                  </a>
                </div>
              </div>
            </div>

            <!-- SIG -->
            <div>
              <div class="mb-3 flex items-center justify-between">
                <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 flex items-center gap-2">
                  <i class="fa-solid fa-map-location-dot text-[#d6e87a]"></i> Données SIG ({{ selectedSig.length }})
                </p>
                <button v-if="selectedSig.length" @click="openSigMap"
                  class="flex items-center gap-1.5 rounded-xl bg-[#1e4a49] px-3 py-1.5 text-[11px] font-bold text-[#d6e87a] hover:bg-[#163836] transition">
                  <i class="fa-solid fa-map text-xs"></i> Voir sur la carte
                </button>
              </div>
              <div v-if="sigLoading" class="flex justify-center py-5">
                <i class="fa-solid fa-circle-notch animate-spin text-[#d6e87a] text-xl"></i>
              </div>
              <div v-else-if="!selectedSig.length" class="rounded-2xl border border-dashed border-slate-200 py-5 text-center text-xs text-slate-400">
                Aucune donnée SIG pour ce projet
              </div>
              <div v-else class="space-y-2">
                <div v-for="(sig, i) in selectedSig" :key="sig.id"
                  class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                  <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#1e4a49] text-xs font-black text-[#d6e87a]">{{ i + 1 }}</div>
                  <div class="flex-1 min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-800">{{ sig.nom_fichier || 'Données SIG' }}</p>
                    <p class="text-xs text-slate-400">{{ sig.type_geometrie }} · {{ formatDate(sig.created_at) }}</p>
                  </div>
                  <i class="fa-solid fa-layer-group text-[#d6e87a] shrink-0"></i>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </Teleport>

    <!-- SIG MAP MODAL -->
    <Teleport to="body">
      <div v-if="showSigMap" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeSigMap"></div>
        <div class="relative z-10 flex w-full max-w-5xl flex-col rounded-3xl overflow-hidden shadow-2xl" style="height:82vh">
          <div class="flex items-center justify-between bg-[#1e4a49] px-6 py-4 shrink-0">
            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#d6e87a]">
                <i class="fa-solid fa-map text-[#4a5e20] text-sm"></i>
              </div>
              <div>
                <p class="text-sm font-extrabold text-white">{{ selected?.titre }}</p>
                <p class="text-xs text-white/50">{{ selectedSig.length }} couche(s) SIG</p>
              </div>
            </div>
            <button @click="closeSigMap" class="flex h-8 w-8 items-center justify-center rounded-xl text-white/60 hover:bg-white/10 transition">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
          <div id="archive-sig-map" class="flex-1"></div>
        </div>
      </div>
    </Teleport>

  </div>
</template>
