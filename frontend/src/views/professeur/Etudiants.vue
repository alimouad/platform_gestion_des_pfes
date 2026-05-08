<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const profId = computed(() => user.value?.professeur?.id)

const projets = ref([])
const postulations = ref([])
const depots = ref([])
const loading = ref(false)
const search = ref('')

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
      progression: total > 0 ? Math.round((valide / 3) * 100) : 0,
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
  <div class="space-y-5">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900">Mes étudiants encadrés</h1>
      <p class="text-sm text-slate-400">{{ encadres.length }} étudiant{{ encadres.length > 1 ? 's' : '' }} sous votre supervision</p>
    </div>

    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher un étudiant…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <div v-if="loading" class="rounded-[2rem] border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400 shadow-sm">Chargement…</div>
    <div v-else-if="filtered.length === 0" class="rounded-[2rem] border-2 border-dashed border-slate-200 bg-white/60 p-10 text-center">
      <i class="fa-solid fa-user-graduate text-5xl text-slate-300"></i>
      <p class="mt-4 text-base font-extrabold text-slate-700">Aucun étudiant encadré</p>
      <p class="mt-1 text-sm text-slate-400">Ils apparaîtront ici une fois leur postulation acceptée.</p>
    </div>
    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article v-for="e in filtered" :key="e.id"
        class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm hover:border-[#d6e87a] transition">
        <div class="flex items-start gap-3 mb-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-sm font-black text-white">
            {{ (e.utilisateur?.prenom || 'E')[0] }}{{ (e.utilisateur?.nom || '')[0] }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-extrabold text-slate-900 truncate">
              {{ e.utilisateur?.prenom }} {{ e.utilisateur?.nom }}
            </p>
            <p class="text-xs text-slate-400 font-mono truncate">{{ e.code_etudiant }}</p>
          </div>
        </div>

        <div class="space-y-1.5 mb-4 text-xs text-slate-500">
          <p class="flex items-center gap-2">
            <i class="fa-regular fa-envelope w-4 text-slate-400"></i>
            <span class="truncate">{{ e.utilisateur?.courriel }}</span>
          </p>
          <p v-if="e.filiere"><i class="fa-solid fa-tags w-4 text-slate-400"></i>{{ e.filiere }}</p>
          <p><i class="fa-solid fa-user-graduate w-4 text-slate-400"></i>{{ e.niveau }}</p>
        </div>

        <div class="border-t border-slate-100 pt-3">
          <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Projet PFE</p>
          <p class="text-xs font-bold text-slate-800 leading-tight line-clamp-2">{{ e.projet?.titre }}</p>
        </div>

        <div class="border-t border-slate-100 pt-3 mt-3">
          <div class="flex items-center justify-between mb-1.5">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Dépôts validés</p>
            <p class="text-xs font-bold text-slate-700">{{ e.depotsValides }}/3</p>
          </div>
          <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full bg-slate-900 transition-all duration-700"
              :style="{ width: `${(e.depotsValides / 3) * 100}%` }"></div>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>
