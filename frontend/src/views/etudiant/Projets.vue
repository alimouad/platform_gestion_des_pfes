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

// Available projects = soumis or brouillon (not en_cours, valide, soutenu, rejete)
const projetsDisponibles = computed(() => {
  let list = projets.value.filter(p => ['brouillon', 'soumis'].includes(p.statut))
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
  <div class="w-full space-y-8 pb-12">
    <header class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 border-b border-slate-100 pb-8">
      <div class="space-y-1">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#1e4a49]/5 border border-[#1e4a49]/10 mb-2">
          <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#D4E98D] opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-[#D4E98D]"></span>
          </span>
          <span class="text-[10px] font-bold uppercase tracking-[0.15em] text-[#1e4a49]">Catalogue PFE Ouvert</span>
        </div>
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900">Projets disponibles</h1>
        <p class="text-sm font-medium text-slate-500">
          Explorez <span class="text-slate-900 font-semibold">{{ projetsDisponibles.length }} opportunités</span> académiques pour votre fin d'études.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
        <div class="relative flex-1 md:w-72 group">
          <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-[#1e4a49] transition-colors"></i>
          <input v-model="search" 
                 placeholder="Mots-clés, titre..." 
                 class="w-full bg-white border border-slate-200 rounded-2xl py-3 pl-11 pr-4 text-sm font-medium outline-none focus:border-[#D4E98D] focus:ring-4 focus:ring-[#D4E98D]/10 transition-all shadow-sm" />
        </div>
        <select v-model="filterDomaine" 
                class="bg-white border border-slate-200 rounded-2xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-[#D4E98D] shadow-sm cursor-pointer">
          <option value="">Tous les domaines</option>
          <option v-for="d in domaines" :key="d" :value="d">{{ d }}</option>
        </select>
      </div>
    </header>

    <Transition name="fade-slide">
      <div v-if="monProjetId" class="relative overflow-hidden w-full rounded-[2.5rem] bg-[#1e4a49] p-8 text-white shadow-2xl shadow-[#1e4a49]/20 border border-white/10 flex flex-col md:flex-row items-center gap-8">
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-[#D4E98D]/10 rounded-full blur-3xl"></div>
        
        <div class="h-16 w-16 rounded-[1.5rem] bg-[#D4E98D] flex items-center justify-center text-[#1e4a49] shrink-0 shadow-lg">
          <i class="fa-solid fa-trophy text-2xl"></i>
        </div>
        <div class="flex-1 text-center md:text-left space-y-1">
          <h4 class="font-semibold text-xl tracking-tight">Candidature acceptée !</h4>
          <p class="text-white/70 text-sm font-medium leading-relaxed max-w-2xl">
            Votre projet PFE a été validé. Vous pouvez continuer à consulter les autres offres, mais les nouvelles postulations sont désormais désactivées.
          </p>
        </div>
        <button @click="router.push('/mon-projet')" 
                class="rounded-xl bg-white/10 hover:bg-white/20 px-6 py-3 text-[11px] font-bold uppercase tracking-widest transition-all backdrop-blur-md border border-white/5 whitespace-nowrap">
          Consulter mon projet
        </button>
      </div>
    </Transition>

    <div v-if="loading" class="w-full py-24 flex flex-col items-center justify-center space-y-4">
      <div class="w-12 h-12 border-4 border-slate-100 border-t-[#D4E98D] rounded-full animate-spin"></div>
      <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Initialisation du catalogue...</p>
    </div>

    <div v-else-if="projetsDisponibles.length === 0" class="w-full py-40 rounded-[3rem] border-2 border-dashed border-slate-100 bg-slate-50/50 flex flex-col items-center justify-center">
      <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-sm mb-6">
        <i class="fa-solid fa-folder-open text-3xl text-slate-200"></i>
      </div>
      <p class="text-lg font-semibold text-slate-900">Aucun projet disponible</p>
      <p class="text-sm text-slate-400 mt-1">Essayez de modifier vos filtres ou revenez plus tard.</p>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <article v-for="p in projetsDisponibles" :key="p.id"
        class="group flex flex-col bg-white border border-slate-100 rounded-[2.5rem] p-7 transition-all duration-300 hover:shadow-2xl hover:shadow-slate-200/50 hover:border-[#D4E98D]/50 hover:-translate-y-2 overflow-hidden">
        
        <div class="flex items-start justify-between mb-6">
          <span class="rounded-lg px-3 py-1 text-[10px] font-bold uppercase tracking-wider" :class="domaineColor(p.domaine)">
            {{ p.domaine || '—' }}
          </span>
          <div v-if="postulationStatus(p.id)" 
               class="flex items-center gap-1.5 px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-widest"
               :class="postulationStatus(p.id) === 'accepte' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">
            <i class="fa-solid fa-circle-check"></i>
            {{ postulationStatus(p.id) === 'accepte' ? 'Validé' : 'Postulé' }}
          </div>
        </div>

        <h3 class="text-lg font-semibold text-slate-900 leading-snug group-hover:text-[#1e4a49] transition-colors mb-3 line-clamp-2">
          {{ p.titre }}
        </h3>
        <p class="text-sm text-slate-500 mb-6 line-clamp-3 flex-1 font-medium leading-relaxed">
          {{ p.description || 'Consultez les détails pour découvrir les objectifs de ce sujet.' }}
        </p>

        <div class="pt-6 border-t border-slate-50 space-y-3 mb-6">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-2xl bg-slate-50 flex items-center justify-center text-[#1e4a49] font-bold text-xs group-hover:bg-[#D4E98D]/20 transition-colors">
              {{ p.professeur?.utilisateur?.nom?.charAt(0) }}{{ p.professeur?.utilisateur?.prenom?.charAt(0) }}
            </div>
            <div class="min-w-0">
              <p class="text-xs font-semibold text-slate-900 truncate">
                {{ p.professeur?.utilisateur?.prenom }} {{ p.professeur?.utilisateur?.nom }}
              </p>
              <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Encadrant</p>
            </div>
          </div>
        </div>

        <div class="flex gap-2">
          <button @click="showDetails = p"
            class="px-4 py-3 rounded-xl border border-slate-100 text-[11px] font-bold text-slate-600 hover:bg-slate-50 transition-all uppercase tracking-widest">
            Détails
          </button>
          
          <button v-if="!postulationStatus(p.id) && !monProjetId"
            @click="postuler(p.id)"
            class="flex-1 rounded-xl bg-gray-900 text-white text-[11px] font-bold tracking-[0.15em] uppercase py-3 transition-all hover:bg-[#1e4a49] hover:shadow-lg active:scale-95">
            <i class="fa-solid fa-paper-plane mr-2 text-[#D4E98D]"></i>Postuler
          </button>
          
          <div v-else class="flex-1 flex items-center justify-center rounded-xl bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-400">
            {{ postulationStatus(p.id) ? 'Dossier transmis' : 'Fermé' }}
          </div>
        </div>
      </article>
    </div>

    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showDetails" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md p-4" @click.self="showDetails = null">
          <div class="w-full max-w-2xl bg-white rounded-[3rem] shadow-2xl overflow-hidden animate-modal-in">
            <div class="p-10 space-y-8">
              <div class="flex justify-between items-start">
                <div class="space-y-3">
                  <span class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-widest" :class="domaineColor(showDetails.domaine)">
                    {{ showDetails.domaine }}
                  </span>
                  <h2 class="text-3xl font-semibold text-slate-900 tracking-tight leading-tight">
                    {{ showDetails.titre }}
                  </h2>
                </div>
                <button @click="showDetails = null" class="h-10 w-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-slate-900 transition-all">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>

              <div class="space-y-6">
                <div>
                  <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Missions du Projet</h5>
                  <p class="text-sm font-medium text-slate-600 leading-relaxed bg-slate-50/50 p-6 rounded-3xl border border-slate-100 whitespace-pre-line">
                    {{ showDetails.description }}
                  </p>
                </div>

                <div class="grid grid-cols-2 gap-8 py-8 border-y border-slate-100">
                  <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-2xl bg-slate-50 flex items-center justify-center text-[#A3B18A] text-xl">
                      <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <div>
                      <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Encadrant</p>
                      <p class="text-sm font-semibold text-slate-900">{{ showDetails.professeur?.utilisateur?.prenom }} {{ showDetails.professeur?.utilisateur?.nom }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-2xl bg-slate-50 flex items-center justify-center text-[#A3B18A] text-xl">
                      <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div>
                      <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Promotion</p>
                      <p class="text-sm font-semibold text-slate-900">{{ showDetails.annee_universitaire?.annee }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex gap-4 pt-4">
                <button @click="showDetails = null" class="flex-1 py-4 rounded-2xl border border-slate-100 text-sm font-semibold text-slate-500 hover:bg-slate-50 transition-all">
                  Retour
                </button>
                <button v-if="!postulationStatus(showDetails.id) && !monProjetId"
                  @click="postuler(showDetails.id); showDetails = null"
                  class="flex-[2] py-4 rounded-2xl bg-gray-900 text-white text-sm font-semibold tracking-widest uppercase hover:bg-[#1e4a49] shadow-xl shadow-gray-200 transition-all active:scale-95">
                  Confirmer ma postulation
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
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
