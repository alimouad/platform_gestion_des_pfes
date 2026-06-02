<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const etudiantId = computed(() => user.value?.etudiant?.id)

const projets = ref([])
const postulations = ref([])
const loading = ref(false)
const search = ref('')
const filterDomaine = ref('')
const showDetails = ref(null)

async function refreshUser() {
  try {
    const res = await api.get('/me')
    user.value = res.data?.data || {}
    localStorage.setItem('admin_user', JSON.stringify(user.value))
  } catch {}
}

async function fetchAll() {
  loading.value = true
  try {
    const [pr, po] = await Promise.all([
      api.get('/projets'),
      api.get('/postulations'),
    ])
    projets.value = pr.data.data
    postulations.value = po.data.data.filter(p => p.etudiant_id === etudiantId.value)
  } catch {}
  loading.value = false
}

// Available projects = soumis or brouillon, filtered to student's filière
const etudiantFiliereId = computed(() => Number(user.value?.etudiant?.filiere_id) || null)

const projetsDisponibles = computed(() => {
  let list = projets.value.filter(p => ['brouillon', 'soumis'].includes(p.statut))
  if (etudiantFiliereId.value) {
    list = list.filter(p => Number(p.filiere_id) === etudiantFiliereId.value)
  }
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(p => JSON.stringify(p).toLowerCase().includes(q))
  }
  if (filterDomaine.value) {
    list = list.filter(p => p.domaine === filterDomaine.value)
  }
  return list
})

const domaines = computed(() => {
  const set = new Set(projets.value.map(p => p.domaine).filter(Boolean))
  return Array.from(set).sort()
})

const monProjetId = computed(() => {
  const accepted = postulations.value.find(p => p.statut === 'accepte')
  return accepted?.projet_id
})

function postulationStatus(projetId) {
  return postulations.value.find(p => p.projet_id === projetId)?.statut
}

async function postuler(projetId) {
  try {
    await api.post('/postulations', { projet_id: projetId })
    await fetchAll()
  } catch (e) {
    alert(e.response?.data?.message || 'Erreur')
  }
}

const domaineColors = {
  'Informatique': 'bg-blue-50 text-blue-700',
  'SIG': 'bg-[#d6e87a]/40 text-[#4a5e20]',
  'Géomatique': 'bg-emerald-50 text-emerald-700',
  'IA': 'bg-purple-50 text-purple-700',
  'Data Science': 'bg-amber-50 text-amber-700',
}
function domaineColor(d) {
  return domaineColors[d] || 'bg-slate-100 text-slate-600'
}

onMounted(async () => { await refreshUser(); await fetchAll() })
</script>

<template>
  <div class="space-y-6 pb-12">

    <!-- Hero banner -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-7 text-white relative overflow-hidden">
      <div class="absolute -right-10 -top-10 h-52 w-52 rounded-full bg-white/5"></div>
      <div class="absolute -bottom-8 right-32 h-32 w-32 rounded-full bg-[#d6e87a]/10"></div>
      <div class="relative flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-[11px] font-black uppercase tracking-widest text-[#d6e87a]">Catalogue PFE</p>
          <h1 class="mt-1 text-2xl font-black">Projets disponibles</h1>
          <p class="mt-1 text-sm text-white/60">
            <span class="font-black text-white">{{ projetsDisponibles.length }}</span> projet{{ projetsDisponibles.length !== 1 ? 's' : '' }} disponible{{ projetsDisponibles.length !== 1 ? 's' : '' }} pour votre filière
          </p>
        </div>
        <!-- Search + filter -->
        <div class="flex flex-wrap items-center gap-3">
          <div class="relative">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-white/40"></i>
            <input v-model="search" placeholder="Rechercher…"
              class="rounded-2xl bg-white/10 border border-white/20 pl-10 pr-4 py-2.5 text-sm text-white placeholder:text-white/40 outline-none focus:bg-white/20 transition w-52" />
          </div>
          <select v-model="filterDomaine"
            class="rounded-2xl bg-white/10 border border-white/20 px-4 py-2.5 text-sm text-white outline-none focus:bg-white/20 transition cursor-pointer">
            <option value="" class="text-slate-900">Tous les domaines</option>
            <option v-for="d in domaines" :key="d" :value="d" class="text-slate-900">{{ d }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Accepted project banner -->
    <div v-if="monProjetId" class="flex flex-wrap items-center gap-4 rounded-2xl border border-green-200 bg-green-50 px-5 py-4">
      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-500 text-white">
        <i class="fa-solid fa-trophy text-sm"></i>
      </div>
      <div class="flex-1">
        <p class="text-sm font-black text-green-800">Candidature acceptée !</p>
        <p class="text-xs text-green-600">Votre projet PFE a été validé.</p>
      </div>
      <router-link to="/etudiant/mon-projet"
        class="rounded-xl bg-[#1e4a49] px-5 py-2.5 text-xs font-black text-[#d6e87a] hover:bg-[#163836] transition flex items-center gap-2 shrink-0">
        <i class="fa-solid fa-folder-open"></i> Voir mon projet
      </router-link>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="rounded-3xl border border-white/70 bg-white/90 p-16 text-center text-sm text-slate-400">
      <i class="fa-solid fa-circle-notch fa-spin text-2xl text-[#d6e87a] mb-3 block"></i>Chargement…
    </div>

    <!-- Empty -->
    <div v-else-if="projetsDisponibles.length === 0" class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-white/60 py-24 text-center">
      <i class="fa-solid fa-compass text-5xl text-slate-300"></i>
      <p class="mt-4 text-base font-extrabold text-slate-700">Aucun projet disponible</p>
      <p class="mt-1 text-sm text-slate-400">Essayez de modifier vos filtres ou revenez plus tard.</p>
    </div>

    <!-- Project cards -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <article v-for="p in projetsDisponibles" :key="p.id"
        class="group flex flex-col rounded-3xl border border-white/70 bg-white/90 shadow-sm overflow-hidden transition hover:-translate-y-0.5 hover:border-[#d6e87a] hover:shadow-lg">

        <!-- Top bar -->
        <div class="h-1.5 w-full bg-[#d6e87a]/40 group-hover:bg-[#d6e87a] transition-colors"></div>

        <div class="p-6 flex flex-col flex-1">
          <!-- Domain + status -->
          <div class="flex items-center justify-between mb-4">
            <span class="rounded-xl px-3 py-1 text-[10px] font-black uppercase tracking-wide" :class="domaineColor(p.domaine)">
              {{ p.domaine || '—' }}
            </span>
            <span v-if="postulationStatus(p.id)"
              class="flex items-center gap-1.5 rounded-xl px-2.5 py-1 text-[10px] font-black uppercase"
              :class="postulationStatus(p.id) === 'accepte' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'">
              <i class="fa-solid fa-circle-check text-[8px]"></i>
              {{ postulationStatus(p.id) === 'accepte' ? 'Validé' : 'Postulé' }}
            </span>
          </div>

          <!-- Title + desc -->
          <h3 class="text-base font-black text-slate-900 leading-snug line-clamp-2 mb-2 group-hover:text-[#1e4a49] transition">
            {{ p.titre }}
          </h3>
          <p class="text-sm text-slate-400 line-clamp-3 flex-1 leading-relaxed mb-5">
            {{ p.description || 'Aucune description renseignée.' }}
          </p>

          <!-- Prof -->
          <div class="flex items-center gap-3 border-t border-slate-100 pt-4 mb-4">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#1e4a49] text-[#d6e87a] text-xs font-black">
              {{ p.professeur?.utilisateur?.prenom?.[0] }}{{ p.professeur?.utilisateur?.nom?.[0] }}
            </div>
            <div class="min-w-0">
              <p class="text-xs font-black text-slate-800 truncate">
                {{ p.professeur?.utilisateur?.prenom }} {{ p.professeur?.utilisateur?.nom }}
              </p>
              <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Encadrant</p>
            </div>
            <span v-if="p.anneeUniversitaire" class="ml-auto text-[10px] font-bold text-slate-400 shrink-0">
              {{ p.anneeUniversitaire.annee }}
            </span>
          </div>

          <!-- Actions -->
          <div class="flex gap-2">
            <button @click="showDetails = p"
              class="rounded-xl border border-slate-200 px-4 py-2.5 text-[11px] font-black text-slate-600 hover:bg-slate-50 transition uppercase tracking-wide">
              Détails
            </button>
            <button v-if="!postulationStatus(p.id) && !monProjetId"
              @click="postuler(p.id)"
              class="flex-1 rounded-xl bg-[#1e4a49] text-[#d6e87a] text-[11px] font-black uppercase tracking-wide py-2.5 hover:bg-[#163836] transition active:scale-95">
              <i class="fa-solid fa-paper-plane mr-1.5"></i>Postuler
            </button>
            <div v-else class="flex-1 flex items-center justify-center rounded-xl bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-400">
              {{ postulationStatus(p.id) ? 'Dossier envoyé' : 'Indisponible' }}
            </div>
          </div>
        </div>
      </article>
    </div>

    <!-- Detail modal -->
    <Teleport to="body">
      <div v-if="showDetails" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" @click.self="showDetails = null">
        <div class="w-full max-w-xl rounded-3xl bg-white shadow-2xl overflow-hidden" style="max-height:88vh;overflow-y:auto">

          <!-- Header -->
          <div class="bg-[#1e4a49] px-6 py-5 flex items-start justify-between">
            <div>
              <span class="inline-block rounded-lg px-2.5 py-1 text-[10px] font-black uppercase tracking-wide mb-2" :class="domaineColor(showDetails.domaine)">
                {{ showDetails.domaine }}
              </span>
              <h2 class="text-base font-black text-white leading-snug line-clamp-2">{{ showDetails.titre }}</h2>
            </div>
            <button @click="showDetails = null" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-white/60 hover:bg-white/10 transition ml-3">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="p-6 space-y-5">
            <!-- Description -->
            <div v-if="showDetails.description" class="rounded-2xl bg-slate-50 border border-slate-100 px-5 py-4">
              <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Description</p>
              <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ showDetails.description }}</p>
            </div>

            <!-- Meta -->
            <div class="grid grid-cols-2 gap-3">
              <div class="rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Encadrant</p>
                <p class="text-sm font-black text-slate-800">{{ showDetails.professeur?.utilisateur?.prenom }} {{ showDetails.professeur?.utilisateur?.nom }}</p>
              </div>
              <div class="rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Année</p>
                <p class="text-sm font-black text-slate-800">{{ showDetails.anneeUniversitaire?.annee || '—' }}</p>
              </div>
              <div v-if="showDetails.ville" class="col-span-2 rounded-2xl bg-[#f0f3eb] border border-[#d6e87a]/40 px-4 py-3 flex items-center gap-2">
                <i class="fa-solid fa-location-dot text-[#d6e87a]"></i>
                <p class="text-sm font-bold text-[#4a5e20]">{{ showDetails.ville }}</p>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 pt-1">
              <button @click="showDetails = null" class="flex-1 rounded-2xl border border-slate-200 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">Retour</button>
              <button v-if="!postulationStatus(showDetails.id) && !monProjetId"
                @click="postuler(showDetails.id); showDetails = null"
                class="flex-2 rounded-2xl bg-[#1e4a49] py-3 text-sm font-black text-white hover:bg-[#163836] transition active:scale-95">
                <i class="fa-solid fa-paper-plane mr-1.5 text-[#d6e87a]"></i> Confirmer ma postulation
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.5s ease; }
.fade-slide-enter-from { opacity: 0; transform: translateY(-20px); }
.fade-slide-leave-to { opacity: 0; transform: translateY(-20px); }

@keyframes modalIn {
  from { opacity: 0; transform: scale(0.97) translateY(10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-modal-in { animation: modalIn 0.4s cubic-bezier(0.2, 0.8, 0.2, 1) both; }

article {
  animation: slideUp 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

article:nth-child(n) { animation-delay: calc(var(--i) * 0.05s); }
</style>
