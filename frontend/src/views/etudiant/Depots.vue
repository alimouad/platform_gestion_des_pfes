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
  <div class="space-y-6 pb-12">

    <!-- Hero banner -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-7 text-white relative overflow-hidden">
      <div class="absolute -right-10 -top-10 h-52 w-52 rounded-full bg-white/5"></div>
      <div class="absolute -bottom-8 right-32 h-32 w-32 rounded-full bg-[#d6e87a]/10"></div>
      <div class="relative flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-[11px] font-black uppercase tracking-widest text-[#d6e87a]">Centre de dépôt PFE</p>
          <h1 class="mt-1 text-2xl font-black">Mes dépôts</h1>
          <p class="mt-1 text-sm text-white/60">{{ items.length }} fichier{{ items.length !== 1 ? 's' : '' }} soumis</p>
        </div>
        <button @click="openCreate"
          class="flex items-center gap-2 rounded-2xl bg-[#d6e87a] px-6 py-3 text-sm font-black text-[#1e4a49] shadow-lg hover:brightness-105 transition active:scale-95">
          <i class="fa-solid fa-cloud-arrow-up"></i> Nouveau dépôt
        </button>
      </div>
    </div>

    <!-- No project warning -->
    <div v-if="!monProjet" class="flex items-center gap-4 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
      <i class="fa-solid fa-triangle-exclamation text-amber-500 text-lg shrink-0"></i>
      <p class="text-sm font-semibold text-amber-800">Vous devez avoir un projet PFE accepté pour déposer des fichiers.</p>
      <router-link to="/etudiant/projets" class="ml-auto shrink-0 rounded-xl bg-amber-500 px-4 py-2 text-xs font-black text-white hover:bg-amber-600 transition">
        Voir les projets
      </router-link>
    </div>

    <!-- Project + progress -->
    <div v-if="monProjet" class="rounded-3xl border border-white/70 bg-white/90 shadow-sm p-6 space-y-5">
      <!-- Project title -->
      <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#f0f3eb] text-[#6a8a40]">
          <i class="fa-solid fa-folder-open"></i>
        </div>
        <div>
          <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Projet PFE</p>
          <p class="text-sm font-black text-slate-800">{{ monProjet.titre }}</p>
        </div>
        <div class="ml-auto flex items-center gap-2">
          <div class="relative h-12 w-12 flex items-center justify-center shrink-0">
            <svg class="h-full w-full -rotate-90">
              <circle cx="24" cy="24" r="20" stroke="#f1f5f9" stroke-width="5" fill="transparent"/>
              <circle cx="24" cy="24" r="20" stroke="#d6e87a" stroke-width="5" fill="transparent" stroke-linecap="round"
                :style="{ strokeDasharray: 126, strokeDashoffset: 126 - (126 * completionPct) / 100 }"/>
            </svg>
            <span class="absolute text-[10px] font-black text-[#4a7a30]">{{ completionPct }}%</span>
          </div>
        </div>
      </div>

      <!-- Required types status -->
      <div class="grid grid-cols-3 gap-3">
        <div v-for="req in requiredStatus" :key="req.type"
          class="flex items-center gap-3 rounded-2xl border p-4 transition"
          :class="req.state === 'valide'     ? 'border-green-100 bg-green-50'
                : req.state === 'rejete'     ? 'border-red-100 bg-red-50'
                : req.state === 'en_attente' ? 'border-amber-100 bg-amber-50'
                :                             'border-slate-100 bg-slate-50'">
          <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
            :class="req.state === 'valide'     ? 'bg-green-500 text-white'
                  : req.state === 'rejete'     ? 'bg-red-500 text-white'
                  : req.state === 'en_attente' ? 'bg-amber-500 text-white'
                  :                             'bg-slate-200 text-slate-400'">
            <i :class="`fa-solid ${ti(req.type).icon} text-sm`"></i>
          </div>
          <div>
            <p class="text-xs font-black text-slate-700 capitalize">{{ req.type }}</p>
            <p class="text-[10px] font-bold"
              :class="req.state === 'valide' ? 'text-green-600' : req.state === 'rejete' ? 'text-red-500' : req.state === 'en_attente' ? 'text-amber-600' : 'text-slate-400'">
              {{ req.state === 'valide' ? 'Validé' : req.state === 'rejete' ? 'À refaire' : req.state === 'en_attente' ? 'En attente' : 'Manquant' }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="rounded-3xl border border-white/70 bg-white/90 p-16 text-center text-sm text-slate-400">
      <i class="fa-solid fa-circle-notch fa-spin text-2xl text-[#d6e87a] mb-3 block"></i>Chargement…
    </div>

    <!-- Empty -->
    <div v-else-if="items.length === 0 && monProjet" class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-white/60 py-20 text-center">
      <i class="fa-solid fa-cloud-arrow-up text-5xl text-slate-300"></i>
      <p class="mt-4 text-base font-extrabold text-slate-700">Aucun fichier déposé</p>
      <p class="mt-1 text-sm text-slate-400">Soumettez votre premier document.</p>
      <button @click="openCreate" class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-[#1e4a49] px-6 py-2.5 text-sm font-black text-white hover:bg-[#163836] transition">
        <i class="fa-solid fa-plus"></i> Déposer un fichier
      </button>
    </div>

    <!-- Depot cards -->
    <div v-else-if="items.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
      <article v-for="d in items" :key="d.id"
        class="group flex flex-col rounded-3xl border border-white/70 bg-white/90 shadow-sm overflow-hidden transition hover:-translate-y-0.5 hover:border-[#d6e87a] hover:shadow-lg">

        <!-- Top color bar -->
        <div class="h-1.5 w-full"
          :class="d.statut_validation === 'valide' ? 'bg-[#d6e87a]' : d.statut_validation === 'rejete' ? 'bg-red-300' : 'bg-amber-300'"></div>

        <div class="p-6 flex flex-col flex-1">
          <div class="flex items-start justify-between mb-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl" :class="ti(d.type_depot).color">
              <i :class="`fa-solid ${ti(d.type_depot).icon} text-xl`"></i>
            </div>
            <span class="rounded-xl px-3 py-1 text-[10px] font-black uppercase tracking-wide" :class="statutColor[d.statut_validation]">
              {{ statutLabel[d.statut_validation] }}
            </span>
          </div>

          <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Type de document</p>
          <h3 class="text-lg font-black text-slate-900 capitalize mb-1">{{ d.type_depot }}</h3>
          <p class="text-xs text-slate-400 flex-1">
            Déposé le {{ new Date(d.depose_le || d.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}
          </p>

          <div v-if="d.commentaire && d.statut_validation === 'rejete'" class="mt-3 rounded-2xl bg-red-50 border border-red-100 px-3 py-2.5">
            <p class="text-[10px] font-black text-red-400 uppercase tracking-widest mb-1">Note de l'encadrant</p>
            <p class="text-xs italic text-red-600">« {{ d.commentaire }} »</p>
          </div>

          <a :href="d.chemin_fichier" target="_blank" rel="noopener"
            class="mt-5 flex items-center justify-center gap-2 rounded-2xl bg-slate-50 py-3 text-xs font-black text-slate-600 hover:bg-[#1e4a49] hover:text-white transition">
            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> Ouvrir le document
          </a>
        </div>
      </article>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" @click.self="showModal = false">
        <div class="w-full max-w-lg rounded-3xl bg-white shadow-2xl overflow-hidden">
          <div class="bg-[#1e4a49] px-6 py-5 flex items-center justify-between">
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-[#d6e87a]">Document PFE</p>
              <h2 class="text-base font-black text-white">Nouveau dépôt</h2>
            </div>
            <button @click="showModal = false" class="flex h-8 w-8 items-center justify-center rounded-xl text-white/60 hover:bg-white/10 transition">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <form @submit.prevent="save" class="p-6 space-y-5">
            <div v-if="error" class="rounded-2xl bg-red-50 border border-red-100 px-4 py-3 text-sm font-semibold text-red-600">
              <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ error }}
            </div>

            <div>
              <label class="mb-3 block text-[11px] font-black uppercase tracking-widest text-slate-400">Type de document</label>
              <div class="grid grid-cols-3 gap-3">
                <label v-for="(meta, key) in typeIcon" :key="key"
                  class="flex flex-col items-center gap-2 rounded-2xl border-2 px-3 py-4 cursor-pointer transition"
                  :class="form.type_depot === key ? 'border-[#d6e87a] bg-[#f0f5e0]' : 'border-slate-100 hover:border-slate-200'">
                  <input type="radio" :value="key" v-model="form.type_depot" class="sr-only"/>
                  <div class="h-10 w-10 rounded-xl flex items-center justify-center"
                    :class="form.type_depot === key ? 'bg-[#d6e87a] text-[#1e4a49]' : 'bg-slate-50 text-slate-400'">
                    <i :class="`fa-solid ${meta.icon} text-lg`"></i>
                  </div>
                  <span class="text-[10px] font-black uppercase tracking-wide text-center">{{ key }}</span>
                </label>
              </div>
            </div>

            <div>
              <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Lien du document</label>
              <div class="relative">
                <i class="fa-solid fa-link absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                <input v-model="form.chemin_fichier" type="url" required placeholder="https://drive.google.com/..."
                  class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-10 py-3 text-sm outline-none focus:border-[#d6e87a] focus:bg-white transition"/>
              </div>
              <p class="mt-1.5 text-[10px] text-slate-400 flex items-center gap-1.5">
                <i class="fa-solid fa-circle-info text-[#d6e87a]"></i> Le document doit être en accès public.
              </p>
            </div>

            <div class="flex gap-3 pt-2">
              <button type="button" @click="showModal = false" class="flex-1 rounded-2xl border border-slate-200 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">Annuler</button>
              <button type="submit" class="flex-2 rounded-2xl bg-[#1e4a49] py-3 text-sm font-black text-white hover:bg-[#163836] transition">
                <i class="fa-solid fa-cloud-arrow-up mr-1.5"></i> Valider le dépôt
              </button>
            </div>
          </form>
        </div>
      </div>
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