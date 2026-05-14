<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const profId = computed(() => user.value?.professeur?.id)

const projets = ref([])
const postulations = ref([])
const depots = ref([])
const loading = ref(false)
const search  = ref('')
const selected = ref(null)

const REQUIRED_TYPES = ['rapport', 'donnees', 'presentation']
const typeConfig = {
  rapport:      { label: 'Rapport',      icon: 'fa-file-pdf',        color: 'text-red-500',    bg: 'bg-red-50' },
  donnees:      { label: 'Données',      icon: 'fa-file-code',       color: 'text-blue-500',   bg: 'bg-blue-50' },
  presentation: { label: 'Présentation', icon: 'fa-file-powerpoint', color: 'text-orange-500', bg: 'bg-orange-50' },
}
function fmtDate(d) {
  return d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
}

async function fetchAll() {
  loading.value = true
  try {
    const me = await api.get('/me')
    user.value = me.data?.data || {}

    const [pr, po, de] = await Promise.all([
      api.get('/projets'),
      api.get('/postulations'),
      api.get('/depots'),
    ])
    projets.value = pr.data.data.filter(p => p.professeur_id === profId.value)
    const ids = projets.value.map(p => p.id)
    postulations.value = po.data.data.filter(p => ids.includes(p.projet_id))
    depots.value = de.data.data.filter(d => ids.includes(d.projet_id))
  } catch {}
  loading.value = false
}

// Encadrés = étudiants avec postulation acceptée sur un de mes projets
const encadres = computed(() => {
  const accepted = postulations.value.filter(p => p.statut === 'accepte')
  return accepted.map(p => {
    const etudiantDepots = depots.value.filter(d => d.etudiant_id === p.etudiant_id && d.projet_id === p.projet_id)
    const valide = etudiantDepots.filter(d => d.statut_validation === 'valide').length
    const total = etudiantDepots.length
    return {
      ...p.etudiant,
      projet: p.projet,
      depotsValides: valide,
      depotsTotal: total,
      depotsDetail: etudiantDepots,
      progression: Math.round((valide / 3) * 100),
    }
  })
})

const filtered = computed(() => {
  if (!search.value) return encadres.value
  const q = search.value.toLowerCase()
  return encadres.value.filter(e => JSON.stringify(e).toLowerCase().includes(q))
})

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-6 text-white relative overflow-hidden">
      <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/5"></div>
      <div class="absolute -bottom-6 right-24 h-24 w-24 rounded-full bg-[#d6e87a]/10"></div>
      <div class="relative">
        <p class="text-[11px] font-black uppercase tracking-widest text-[#d6e87a]">Supervision académique</p>
        <h1 class="mt-1 text-2xl font-black">Mes étudiants encadrés</h1>
        <p class="mt-1 text-sm text-white/60">{{ encadres.length }} étudiant{{ encadres.length !== 1 ? 's' : '' }} sous votre supervision</p>
      </div>
    </div>

    <!-- Search -->
    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher un étudiant…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <div v-if="loading" class="rounded-3xl border border-white/70 bg-white/90 p-16 text-center text-sm text-slate-400">Chargement…</div>

    <div v-else-if="filtered.length === 0" class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-white/60 py-20 text-center">
      <i class="fa-solid fa-user-graduate text-5xl text-slate-300"></i>
      <p class="mt-4 text-base font-extrabold text-slate-700">Aucun étudiant encadré</p>
      <p class="mt-1 text-sm text-slate-400">Ils apparaîtront ici une fois leur postulation acceptée.</p>
    </div>

    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article v-for="e in filtered" :key="e.id"
        @click="selected = e"
        class="group cursor-pointer rounded-3xl border border-white/70 bg-white/90 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#d6e87a] hover:shadow-lg overflow-hidden">

        <!-- Progress bar top -->
        <div class="h-1.5 w-full bg-slate-100">
          <div class="h-full bg-[#d6e87a] transition-all duration-700" :style="`width:${e.progression}%`"></div>
        </div>

        <div class="p-5">
          <!-- Avatar + name -->
          <div class="flex items-center gap-3 mb-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#d6e87a] text-sm font-black text-[#1e4a49]">
              {{ (e.utilisateur?.prenom || 'E')[0] }}{{ (e.utilisateur?.nom || '')[0] }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-black text-slate-900 truncate">{{ e.utilisateur?.prenom }} {{ e.utilisateur?.nom }}</p>
              <p class="text-xs font-mono text-slate-400 truncate">{{ e.code_etudiant }}</p>
            </div>
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-400 group-hover:bg-[#1e4a49] group-hover:text-white transition">
              <i class="fa-solid fa-chevron-right text-xs"></i>
            </div>
          </div>

          <!-- Info -->
          <div class="space-y-1.5 text-xs text-slate-500 mb-4">
            <p class="flex items-center gap-2 truncate">
              <i class="fa-regular fa-envelope w-4 shrink-0 text-slate-400"></i>
              {{ e.utilisateur?.courriel }}
            </p>
            <p class="flex items-center gap-2">
              <i class="fa-solid fa-user-graduate w-4 shrink-0 text-slate-400"></i>
              {{ e.niveau || '—' }}
              <span v-if="e.filiere" class="text-slate-300">·</span>
              <span v-if="e.filiere">{{ e.filiere }}</span>
            </p>
          </div>

          <!-- Projet -->
          <div class="rounded-2xl bg-slate-50 px-3 py-2.5 mb-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Projet PFE</p>
            <p class="text-xs font-bold text-slate-800 line-clamp-2 leading-snug">{{ e.projet?.titre || '—' }}</p>
          </div>

          <!-- Depot types -->
          <div class="flex items-center justify-between">
            <div class="flex gap-2">
              <div v-for="type in REQUIRED_TYPES" :key="type"
                class="flex h-8 w-8 items-center justify-center rounded-xl border-2 transition"
                :class="e.depotsDetail?.some(d => d.type_depot === type && d.statut_validation === 'valide')
                  ? 'border-[#d6e87a] bg-[#f0f5e0] text-[#4a7a30]'
                  : e.depotsDetail?.some(d => d.type_depot === type)
                    ? 'border-amber-300 bg-amber-50 text-amber-600'
                    : 'border-slate-200 bg-white text-slate-300'">
                <i :class="`fa-solid ${typeConfig[type].icon} text-[10px]`"></i>
              </div>
            </div>
            <span class="text-xs font-black" :class="e.progression === 100 ? 'text-[#4a7a30]' : 'text-slate-500'">
              {{ e.progression }}% validé
            </span>
          </div>
        </div>
      </article>
    </div>

    <!-- DETAIL MODAL -->
    <Teleport to="body">
      <div v-if="selected" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="selected = null"></div>
        <div class="relative z-10 w-full max-w-lg rounded-3xl bg-white shadow-2xl overflow-hidden" style="max-height:90vh;overflow-y:auto">

          <!-- Header -->
          <div class="bg-[#1e4a49] px-6 py-5 flex items-center justify-between sticky top-0 z-10">
            <div class="flex items-center gap-4">
              <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#d6e87a] text-base font-black text-[#1e4a49]">
                {{ (selected.utilisateur?.prenom || 'E')[0] }}{{ (selected.utilisateur?.nom || '')[0] }}
              </div>
              <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-[#d6e87a]">Fiche étudiant</p>
                <h2 class="text-base font-black text-white">{{ selected.utilisateur?.prenom }} {{ selected.utilisateur?.nom }}</h2>
              </div>
            </div>
            <button @click="selected = null" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-white/60 hover:bg-white/10 transition ml-3">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- Progress bar -->
          <div class="h-1.5 w-full bg-slate-200">
            <div class="h-full bg-[#d6e87a] transition-all duration-700" :style="`width:${selected.progression}%`"></div>
          </div>

          <div class="p-6 space-y-5">

            <!-- Identity grid -->
            <div class="grid grid-cols-2 gap-3">
              <div class="rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Code étudiant</p>
                <p class="text-sm font-black font-mono text-slate-800">{{ selected.code_etudiant || '—' }}</p>
              </div>
              <div class="rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Niveau</p>
                <p class="text-sm font-black text-slate-800">{{ selected.niveau || '—' }}</p>
              </div>
              <div class="col-span-2 rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Email</p>
                <p class="text-sm font-bold text-slate-800 break-all">{{ selected.utilisateur?.courriel || '—' }}</p>
              </div>
              <div v-if="selected.filiere" class="col-span-2 rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Filière</p>
                <p class="text-sm font-black text-slate-800">{{ selected.filiere }}</p>
              </div>
            </div>

            <!-- Projet -->
            <div class="rounded-2xl border border-[#d6e87a]/40 bg-[#f8faef] px-4 py-4">
              <p class="text-[10px] font-black uppercase tracking-widest text-[#6a8a40] mb-1">Projet PFE encadré</p>
              <p class="text-sm font-black text-[#1e4a49] leading-snug">{{ selected.projet?.titre || '—' }}</p>
              <p v-if="selected.projet?.domaine" class="mt-1 text-xs text-[#6a8a40]">{{ selected.projet.domaine }}</p>
            </div>

            <!-- Depots -->
            <div>
              <div class="flex items-center justify-between mb-3">
                <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">Dépôts</p>
                <span class="text-xs font-black" :class="selected.progression === 100 ? 'text-[#4a7a30]' : 'text-slate-500'">
                  {{ selected.depotsValides }}/3 validés · {{ selected.progression }}%
                </span>
              </div>

              <!-- Required types -->
              <div class="space-y-2">
                <div v-for="type in REQUIRED_TYPES" :key="type">
                  <div v-if="selected.depotsDetail?.filter(d => d.type_depot === type).length">
                    <div v-for="d in selected.depotsDetail.filter(d => d.type_depot === type)" :key="d.id"
                      class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                      <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" :class="typeConfig[type].bg">
                        <i :class="`fa-solid ${typeConfig[type].icon} text-sm ${typeConfig[type].color}`"></i>
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="text-xs font-black text-slate-800">{{ typeConfig[type].label }}</p>
                        <p class="text-[10px] text-slate-400">{{ fmtDate(d.depose_le || d.created_at) }}</p>
                        <p v-if="d.commentaire" class="mt-1 rounded-lg bg-red-50 border border-red-100 px-2 py-1 text-[10px] italic text-red-600">
                          « {{ d.commentaire }} »
                        </p>
                      </div>
                      <span class="shrink-0 rounded-xl px-2.5 py-1 text-[10px] font-black"
                        :class="d.statut_validation === 'valide' ? 'bg-green-100 text-green-700' : d.statut_validation === 'rejete' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-700'">
                        {{ d.statut_validation === 'valide' ? 'Validé' : d.statut_validation === 'rejete' ? 'Rejeté' : 'En attente' }}
                      </span>
                    </div>
                  </div>
                  <!-- Missing -->
                  <div v-else class="flex items-center gap-3 rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100">
                      <i :class="`fa-solid ${typeConfig[type].icon} text-sm text-slate-300`"></i>
                    </div>
                    <div>
                      <p class="text-xs font-black text-slate-400">{{ typeConfig[type].label }}</p>
                      <p class="text-[10px] text-slate-300 italic">Non déposé</p>
                    </div>
                    <span class="ml-auto shrink-0 rounded-xl bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-400">Manquant</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>
