<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const etudiantId = computed(() => user.value?.etudiant?.id)

const items = ref([])
const postulations = ref([])
const loading = ref(false)
const showModal = ref(false)
const error = ref('')
const form = ref({ projet_id: '', type_depot: 'rapport', chemin_fichier: '' })

async function fetchAll() {
  loading.value = true
  try {
    const [de, po] = await Promise.all([
      api.get('/depots'),
      api.get('/postulations'),
    ])
    items.value = de.data.data.filter(d => d.etudiant_id === etudiantId.value)
    postulations.value = po.data.data.filter(p => p.etudiant_id === etudiantId.value)
  } catch {}
  loading.value = false
}

const monProjet = computed(() => {
  const accepted = postulations.value.find(p => p.statut === 'accepte')
  return accepted?.projet || null
})

const REQUIRED_TYPES = ['rapport', 'donnees', 'presentation']

const requiredStatus = computed(() => {
  return REQUIRED_TYPES.map(type => {
    const depots = items.value.filter(d => d.type_depot === type)
    const valide = depots.find(d => d.statut_validation === 'valide')
    const enAttente = depots.find(d => d.statut_validation === 'en_attente')
    const rejete = depots.find(d => d.statut_validation === 'rejete')
    let state = 'missing'
    if (valide) state = 'valide'
    else if (enAttente) state = 'en_attente'
    else if (rejete) state = 'rejete'
    return { type, state, count: depots.length }
  })
})

const completionPct = computed(() => {
  const done = requiredStatus.value.filter(r => r.state === 'valide').length
  return Math.round((done / REQUIRED_TYPES.length) * 100)
})

function openCreate() {
  if (!monProjet.value) {
    alert("Vous devez d'abord avoir un projet PFE accepté avant de pouvoir déposer des fichiers.")
    return
  }
  error.value = ''
  form.value = { projet_id: monProjet.value.id, type_depot: 'rapport', chemin_fichier: '' }
  showModal.value = true
}

async function save() {
  error.value = ''
  if (!etudiantId.value) {
    error.value = 'Profil étudiant introuvable.'
    return
  }
  try {
    await api.post('/depots', {
      ...form.value,
      etudiant_id: etudiantId.value,
    })
    showModal.value = false
    await fetchAll()
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors du dépôt'
    if (e.response?.data?.errors) {
      error.value = Object.values(e.response.data.errors).flat().join(' | ')
    }
  }
}

const typeIcon = {
  rapport:      { icon: 'fa-file-pdf',         color: 'bg-red-50 text-red-600' },
  donnees:         { icon: 'fa-file-code',        color: 'bg-blue-50 text-blue-600' },
  presentation: { icon: 'fa-file-powerpoint',  color: 'bg-orange-50 text-orange-600' },
}
function ti(t) { return typeIcon[t] || { icon: 'fa-file', color: 'bg-slate-100 text-slate-600' } }

const statutLabel = { en_attente: 'En attente', valide: 'Validé', rejete: 'Rejeté' }
const statutColor = { en_attente: 'bg-amber-100 text-amber-700', valide: 'bg-green-100 text-green-700', rejete: 'bg-red-100 text-red-600' }

onMounted(fetchAll)
</script>
<template>
  <div class="w-full space-y-8 pb-12">
    <header class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 border-b border-slate-100 pb-8">
      <div>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#1e4a49]/5 border border-[#1e4a49]/10 mb-3">
          <span class="text-[10px] font-bold uppercase tracking-widest text-[#1e4a49]">Centre de Dépôt PFE</span>
        </div>
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900">Mes dépôts</h1>
        <p class="mt-2 text-sm font-medium text-slate-500">
          Gérez et suivez l'état de vos <span class="text-slate-900 font-semibold">documents officiels</span>.
        </p>
      </div>
      
      <button @click="openCreate"
        class="flex items-center gap-3 rounded-2xl bg-slate-900 px-6 py-3.5 text-xs font-semibold uppercase tracking-widest text-white hover:bg-[#1e4a49] hover:shadow-xl transition-all active:scale-95 shadow-lg shadow-slate-200">
        <i class="fa-solid fa-cloud-arrow-up text-[#D4E98D]"></i> Nouveau dépôt
      </button>
    </header>

    <div v-if="monProjet" class="group relative overflow-hidden rounded-[2rem] bg-white border border-slate-100 p-6 flex items-center gap-6 shadow-sm transition-all hover:shadow-md">
      <div class="absolute right-0 top-0 h-full w-1.5 bg-[#D4E98D]"></div>
      <div class="h-12 w-12 rounded-2xl bg-slate-50 flex items-center justify-center text-[#A3B18A] text-xl shrink-0 transition-colors group-hover:bg-[#D4E98D]/20">
        <i class="fa-solid fa-folder-tree"></i>
      </div>
      <div>
        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Projet de Fin d'Études</p>
        <p class="text-lg font-semibold text-slate-800 leading-tight">{{ monProjet.titre }}</p>
      </div>
    </div>

    <section v-if="monProjet" class="rounded-[2.5rem] border border-white bg-white/70 p-8 shadow-sm backdrop-blur-sm">
      <div class="flex flex-col md:flex-row items-center justify-between mb-8 gap-6">
        <div class="text-center md:text-left">
          <h3 class="text-xl font-semibold text-slate-900 tracking-tight">Progression des dépôts</h3>
          <p class="text-xs font-medium text-slate-400 mt-1 uppercase tracking-widest">Suivi de complétude du dossier</p>
        </div>
        
        <div class="flex items-center gap-6">
          <div class="text-right">
            <p class="text-4xl font-semibold text-slate-900 tracking-tighter leading-none">
              {{ completionPct }}<span class="text-xl text-slate-300 font-normal">%</span>
            </p>
            <p class="text-[10px] font-bold uppercase tracking-widest text-[#A3B18A] mt-1">Validité globale</p>
          </div>
          <div class="relative h-16 w-16 flex items-center justify-center">
            <svg class="h-full w-full transform -rotate-90">
              <circle cx="32" cy="32" r="28" stroke="#f1f5f9" stroke-width="6" fill="transparent" />
              <circle cx="32" cy="32" r="28" stroke="#D4E98D" stroke-width="6" fill="transparent" stroke-linecap="round" 
                :style="{ strokeDasharray: 176, strokeDashoffset: 176 - (176 * completionPct) / 100 }" />
            </svg>
            <i class="fa-solid fa-check absolute text-[#A3B18A] text-xs" v-if="completionPct === 100"></i>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div v-for="req in requiredStatus" :key="req.type"
          class="group relative flex items-center gap-4 rounded-3xl border p-5 transition-all duration-300"
          :class="req.state === 'valide'    ? 'border-emerald-100 bg-emerald-50/30'
                : req.state === 'rejete'    ? 'border-rose-100 bg-rose-50/30'
                : req.state === 'en_attente'? 'border-amber-100 bg-amber-50/30'
                                            : 'border-slate-100 bg-white/50 hover:bg-white'">
          
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-lg shadow-sm transition-transform group-hover:scale-110"
            :class="req.state === 'valide'    ? 'bg-emerald-500 text-white shadow-emerald-200'
                  : req.state === 'rejete'    ? 'bg-rose-500 text-white shadow-rose-200'
                  : req.state === 'en_attente'? 'bg-amber-500 text-white shadow-amber-200'
                                              : 'bg-slate-100 text-slate-400'">
            <i :class="`fa-solid ${ti(req.type).icon}`"></i>
          </div>
          
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-slate-900 capitalize">{{ req.type }}</p>
            <div class="flex items-center gap-2 mt-0.5">
              <span class="text-[9px] font-bold uppercase tracking-widest transition-colors"
                :class="req.state === 'valide'    ? 'text-emerald-600'
                      : req.state === 'rejete'    ? 'text-rose-600'
                      : req.state === 'en_attente'? 'text-amber-700'
                                                  : 'text-slate-400'">
                {{ req.state === 'valide' ? '✓ Validé' : req.state === 'rejete' ? '✗ À refaire' : req.state === 'en_attente' ? '⏳ En attente' : 'Manquant' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div v-else class="rounded-[2.5rem] border-2 border-dashed border-amber-200 bg-amber-50/50 p-12 text-center flex flex-col items-center">
      <div class="h-16 w-16 bg-white rounded-full flex items-center justify-center text-amber-500 shadow-sm mb-4">
        <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
      </div>
      <h3 class="text-lg font-semibold text-amber-900">Projet Non Assigné</h3>
      <p class="mt-1 text-sm font-medium text-amber-700 max-w-sm">
        Vous devez avoir un projet PFE accepté par un encadrant pour déverrouiller l'espace de dépôt.
      </p>
    </div>

    <div v-if="loading" class="w-full py-24 flex flex-col items-center justify-center space-y-4">
      <div class="w-12 h-12 border-4 border-slate-100 border-t-[#D4E98D] rounded-full animate-spin"></div>
      <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Accès à l'espace de stockage...</p>
    </div>

    <div v-else-if="items.length === 0" class="w-full py-32 rounded-[3rem] border-2 border-dashed border-slate-100 bg-white/50 flex flex-col items-center justify-center">
      <div class="w-20 h-20 bg-white rounded-[2rem] flex items-center justify-center shadow-sm mb-6">
        <i class="fa-solid fa-cloud-arrow-up text-3xl text-slate-200"></i>
      </div>
      <p class="text-lg font-semibold text-slate-900">Aucun fichier déposé</p>
      <p class="text-sm text-slate-400 mt-1">Commencez par soumettre votre premier document officiel.</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <article v-for="d in items" :key="d.id"
        class="group relative flex flex-col bg-white border border-slate-100 rounded-[2.5rem] p-7 transition-all duration-300 hover:shadow-2xl hover:shadow-slate-200/50 hover:border-[#D4E98D]/50 hover:-translate-y-1.5">
        
        <div class="flex items-start justify-between mb-6">
          <div class="flex h-14 w-14 items-center justify-center rounded-[1.25rem] shadow-inner transition-transform group-hover:rotate-6" :class="ti(d.type_depot).color">
            <i :class="`fa-solid ${ti(d.type_depot).icon} text-2xl`"></i>
          </div>
          <span class="rounded-xl px-4 py-1.5 text-[10px] font-bold uppercase tracking-widest shadow-sm" :class="statutColor[d.statut_validation]">
            {{ statutLabel[d.statut_validation] || d.statut_validation }}
          </span>
        </div>

        <div class="flex-1">
          <p class="text-xs font-bold text-[#A3B18A] uppercase tracking-widest mb-1">Type de document</p>
          <h3 class="text-xl font-semibold text-slate-900 capitalize">{{ d.type_depot }}</h3>
          <p class="text-xs text-slate-400 mt-2 font-medium">
            Déposé le {{ new Date(d.depose_le || d.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}
          </p>
          
          <div v-if="d.commentaire && d.statut_validation === 'rejete'" class="mt-4 rounded-2xl bg-rose-50 p-4 border border-rose-100">
            <p class="text-[10px] font-bold text-rose-400 uppercase tracking-widest mb-1">Note de l'encadrant</p>
            <p class="text-xs font-medium italic text-rose-600 leading-relaxed">
              « {{ d.commentaire }} »
            </p>
          </div>
        </div>

        <a :href="d.chemin_fichier" target="_blank" rel="noopener"
          class="mt-8 flex items-center justify-center gap-3 rounded-2xl bg-slate-50 py-4 text-xs font-semibold text-slate-700 hover:bg-slate-900 hover:text-white transition-all shadow-sm">
          <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> Ouvrir le document
        </a>
      </article>
    </div>

    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md p-4" @click.self="showModal = false">
          <div class="w-full max-w-lg rounded-[3rem] bg-white shadow-2xl overflow-hidden animate-modal-in">
            <div class="flex items-center justify-between border-b border-slate-50 px-10 py-8">
              <div>
                <h2 class="text-2xl font-semibold text-slate-900 tracking-tight">Nouveau dépôt</h2>
                <p class="text-xs font-medium text-slate-400 mt-1 uppercase tracking-widest">Document PFE</p>
              </div>
              <button @click="showModal = false" class="h-10 w-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-slate-900 transition-all">
                <i class="fa-solid fa-xmark text-lg"></i>
              </button>
            </div>

            <form @submit.prevent="save" class="p-10 space-y-8">
              <div v-if="error" class="rounded-2xl bg-rose-50 px-6 py-4 border border-rose-100 text-sm font-semibold text-rose-600 animate-shake">
                <i class="fa-solid fa-circle-exclamation mr-2"></i> {{ error }}
              </div>

              <div>
                <label class="mb-4 block text-[11px] font-bold text-slate-400 uppercase tracking-widest">Sélectionner le type</label>
                <div class="grid grid-cols-3 gap-3">
                  <label v-for="(meta, key) in typeIcon" :key="key"
                    class="group flex flex-col items-center gap-3 rounded-2xl border-2 px-3 py-5 cursor-pointer transition-all duration-200"
                    :class="form.type_depot === key ? 'border-[#D4E98D] bg-[#D4E98D]/5 shadow-inner' : 'border-slate-50 hover:border-slate-100 hover:bg-slate-50/50'">
                    <input type="radio" :value="key" v-model="form.type_depot" class="sr-only" />
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center transition-colors"
                      :class="form.type_depot === key ? 'bg-[#D4E98D] text-[#1e4a49]' : 'bg-slate-50 text-slate-400 group-hover:text-slate-600'">
                      <i :class="`fa-solid ${meta.icon} text-xl`"></i>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-center">{{ key }}</span>
                  </label>
                </div>
              </div>

              <div>
                <label class="mb-2 block text-[11px] font-bold text-slate-400 uppercase tracking-widest px-1">Lien vers le document</label>
                <div class="relative group">
                  <i class="fa-solid fa-link absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-[#A3B18A] transition-colors"></i>
                  <input v-model="form.chemin_fichier" type="url" required
                    placeholder="https://cloud.university.edu/..."
                    class="w-full rounded-2xl bg-slate-50 border border-slate-50 px-12 py-4 text-sm font-medium outline-none focus:bg-white focus:border-[#D4E98D] transition-all shadow-inner" />
                </div>
                <p class="mt-3 text-[10px] text-slate-400 italic px-1 flex items-center gap-2">
                  <i class="fa-solid fa-info-circle text-[#D4E98D]"></i> Le document doit être en mode "Lecture Publique".
                </p>
              </div>

              <div class="flex gap-4 pt-4">
                <button type="button" @click="showModal = false" class="flex-1 rounded-2xl border border-slate-100 py-4 text-sm font-semibold text-slate-500 hover:bg-slate-50 transition-all">
                  Annuler
                </button>
                <button type="submit" 
                  class="flex-[2] rounded-2xl bg-slate-900 py-4 text-sm font-semibold tracking-widest uppercase text-white hover:bg-[#1e4a49] shadow-xl shadow-slate-200 transition-all active:scale-95">
                  Valider le dépôt
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
/* Animations */
article {
  animation: slideUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.modal-enter-active, .modal-leave-active { transition: opacity 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }

@keyframes modalIn {
  from { opacity: 0; transform: scale(0.97) translateY(10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-modal-in { animation: modalIn 0.4s cubic-bezier(0.2, 0.8, 0.2, 1) both; }

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}
.animate-shake { animation: shake 0.4s ease-in-out; }

article:nth-child(n) { animation-delay: calc(var(--i) * 0.05s); }
</style>