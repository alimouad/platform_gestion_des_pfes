<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const profId = computed(() => user.value?.professeur?.id)

const items = ref([])
const projets = ref([])
const loading = ref(false)
const filter = ref('')
const filterProjet = ref('')

async function fetchAll() {
  loading.value = true
  try {
    const me = await api.get('/me')
    user.value = me.data?.data || {}

    const [po, pr] = await Promise.all([
      api.get('/postulations'),
      api.get('/projets'),
    ])
    const myProjetIds = pr.data.data
      .filter(p => p.professeur_id === profId.value)
      .map(p => p.id)
    items.value = po.data.data.filter(p => myProjetIds.includes(p.projet_id))
    projets.value = pr.data.data.filter(p => p.professeur_id === profId.value)
  } catch {}
  loading.value = false
}

const filtered = computed(() => {
  let list = items.value
  if (filter.value) list = list.filter(p => p.statut === filter.value)
  if (filterProjet.value) list = list.filter(p => p.projet_id === Number(filterProjet.value))
  return list
})

const counts = computed(() => ({
  en_attente: items.value.filter(p => p.statut === 'en_attente').length,
  accepte:    items.value.filter(p => p.statut === 'accepte').length,
  rejete:     items.value.filter(p => p.statut === 'rejete').length,
}))

const statutLabel = { en_attente: 'En attente', accepte: 'Acceptée', rejete: 'Rejetée' }
const statutColor = { en_attente: 'bg-amber-100 text-amber-700', accepte: 'bg-green-100 text-green-700', rejete: 'bg-red-100 text-red-600' }
const statutIcon  = { en_attente: 'fa-clock', accepte: 'fa-circle-check', rejete: 'fa-circle-xmark' }

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-5">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900">Postulations sur mes projets</h1>
      <p class="text-sm text-slate-400">{{ items.length }} candidature{{ items.length > 1 ? 's' : '' }}</p>
    </div>

    <!-- Info banner -->
    <div class="rounded-2xl border border-blue-100 bg-blue-50 px-5 py-3 flex items-center gap-3">
      <i class="fa-solid fa-circle-info text-blue-600 text-lg"></i>
      <p class="text-sm font-semibold text-blue-900">
        L'acceptation des postulations est validée par le coordinateur. Vous pouvez consulter et recommander les candidatures.
      </p>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-2">
      <button @click="filter = ''"
        class="rounded-2xl px-4 py-2.5 text-sm font-bold transition"
        :class="!filter ? 'bg-slate-900 text-white' : 'bg-white/90 text-slate-600 hover:bg-slate-100'">
        Toutes
        <span class="ml-1.5 rounded-lg bg-white/20 px-2 py-0.5 text-[11px]">{{ items.length }}</span>
      </button>
      <button v-for="(label, key) in statutLabel" :key="key"
        @click="filter = key"
        class="flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-bold transition"
        :class="filter === key ? 'bg-slate-900 text-white' : 'bg-white/90 text-slate-600 hover:bg-slate-100'">
        {{ label }}
        <span class="rounded-lg bg-white/20 px-2 py-0.5 text-[11px]">{{ counts[key] }}</span>
      </button>
    </div>

    <select v-if="projets.length > 1" v-model="filterProjet"
      class="w-full md:w-auto rounded-2xl border border-white/70 bg-white/90 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm outline-none">
      <option value="">Tous mes projets</option>
      <option v-for="p in projets" :key="p.id" :value="p.id">{{ p.titre }}</option>
    </select>

    <div v-if="loading" class="rounded-[2rem] border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400 shadow-sm">Chargement…</div>
    <div v-else-if="filtered.length === 0" class="rounded-[2rem] border-2 border-dashed border-slate-200 bg-white/60 p-10 text-center">
      <i class="fa-solid fa-file-signature text-5xl text-slate-300"></i>
      <p class="mt-4 text-base font-extrabold text-slate-700">Aucune postulation</p>
      <p class="mt-1 text-sm text-slate-400">Les candidatures à vos projets apparaîtront ici.</p>
    </div>
    <div v-else class="space-y-3">
      <article v-for="p in filtered" :key="p.id"
        class="flex flex-wrap items-center gap-4 rounded-[1.6rem] border border-white/70 bg-white/90 p-5 shadow-sm">
        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl"
          :class="p.statut === 'accepte' ? 'bg-green-100 text-green-700'
            : p.statut === 'rejete' ? 'bg-red-100 text-red-600'
            : 'bg-amber-100 text-amber-700'">
          <i :class="`fa-solid ${statutIcon[p.statut]} text-xl`"></i>
        </div>
        <div class="flex-1 min-w-[200px]">
          <p class="text-sm font-bold text-slate-800">
            {{ p.etudiant?.utilisateur?.prenom }} {{ p.etudiant?.utilisateur?.nom }}
            <span class="ml-2 text-xs font-mono text-slate-400">{{ p.etudiant?.code_etudiant }}</span>
          </p>
          <p class="text-xs text-slate-400 mt-0.5">
            <i class="fa-solid fa-arrow-right mr-1"></i>{{ p.projet?.titre || '—' }}
          </p>
          <p class="mt-1.5 text-[10px] text-slate-400">
            <i class="fa-regular fa-envelope mr-1"></i>{{ p.etudiant?.utilisateur?.courriel }}
            <span class="mx-2 text-slate-300">·</span>
            Postulé le {{ new Date(p.date_candidature || p.created_at).toLocaleDateString('fr-FR') }}
          </p>
        </div>
        <span class="rounded-lg px-3 py-1 text-[11px] font-bold" :class="statutColor[p.statut]">
          {{ statutLabel[p.statut] || p.statut }}
        </span>
      </article>
    </div>
  </div>
</template>
