<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const profId = computed(() => user.value?.professeur?.id)

const items = ref([])
const projets = ref([])
const loading = ref(false)
const filter = ref('en_attente')
const search = ref('')

async function fetchAll() {
  loading.value = true
  try {
    const me = await api.get('/me')
    user.value = me.data?.data || {}
    localStorage.setItem('admin_user', JSON.stringify(user.value))

    if (!profId.value) {
      loading.value = false
      return
    }

    const [de, pr] = await Promise.all([
      api.get('/depots'),
      api.get('/projets'),
    ])
    const myProjetIds = pr.data.data
      .filter(p => p.professeur_id === profId.value)
      .map(p => p.id)
    items.value = de.data.data.filter(d => myProjetIds.includes(d.projet_id))
    projets.value = pr.data.data.filter(p => p.professeur_id === profId.value)
  } catch {}
  loading.value = false
}

const filtered = computed(() => {
  let list = items.value
  if (filter.value) list = list.filter(d => d.statut_validation === filter.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(d => JSON.stringify(d).toLowerCase().includes(q))
  }
  return list
})

const counts = computed(() => ({
  en_attente: items.value.filter(d => d.statut_validation === 'en_attente').length,
  valide:     items.value.filter(d => d.statut_validation === 'valide').length,
  rejete:     items.value.filter(d => d.statut_validation === 'rejete').length,
}))

async function valider(id) {
  if (!confirm('Valider ce dépôt ?')) return
  try {
    await api.post(`/depots/${id}/valider`)
    await fetchAll()
  } catch (e) { alert(e.response?.data?.message || 'Erreur') }
}

async function rejeter(id) {
  const c = prompt('Commentaire de rejet (optionnel) :')
  if (c === null) return
  try {
    await api.post(`/depots/${id}/rejeter`, c ? { commentaire: c } : {})
    await fetchAll()
  } catch (e) { alert(e.response?.data?.message || 'Erreur') }
}

const statutLabel = { en_attente: 'En attente', valide: 'Validé', rejete: 'Rejeté' }
const statutColor = { en_attente: 'bg-amber-100 text-amber-700', valide: 'bg-green-100 text-green-700', rejete: 'bg-red-100 text-red-600' }

const typeIcon = {
  rapport: { icon: 'fa-file-pdf',         color: 'bg-red-50 text-red-600' },
  code:    { icon: 'fa-file-code',        color: 'bg-blue-50 text-blue-600' },
  presentation: { icon: 'fa-file-powerpoint', color: 'bg-orange-50 text-orange-600' },
}
function ti(t) { return typeIcon[t] || { icon: 'fa-file', color: 'bg-slate-100 text-slate-600' } }

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-5">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900">Dépôts à examiner</h1>
      <p class="text-sm text-slate-400">{{ items.length }} dépôt{{ items.length > 1 ? 's' : '' }} sur mes projets</p>
    </div>

    <div class="flex flex-wrap gap-2">
      <button @click="filter = ''"
        class="rounded-2xl px-4 py-2.5 text-sm font-bold transition"
        :class="!filter ? 'bg-slate-900 text-white' : 'bg-white/90 text-slate-600 hover:bg-slate-100'">
        Tous
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

    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <div v-if="loading" class="rounded-[2rem] border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400 shadow-sm">Chargement…</div>
    <div v-else-if="filtered.length === 0" class="rounded-[2rem] border-2 border-dashed border-slate-200 bg-white/60 p-10 text-center">
      <i class="fa-solid fa-cloud-arrow-up text-5xl text-slate-300"></i>
      <p class="mt-4 text-base font-extrabold text-slate-700">Aucun dépôt</p>
    </div>
    <div v-else class="space-y-3">
      <article v-for="d in filtered" :key="d.id"
        class="flex flex-wrap items-center gap-4 rounded-[1.6rem] border border-white/70 bg-white/90 p-5 shadow-sm">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl" :class="ti(d.type_depot).color">
          <i :class="`fa-solid ${ti(d.type_depot).icon} text-lg`"></i>
        </div>
        <div class="flex-1 min-w-[200px]">
          <p class="text-sm font-bold text-slate-800">{{ d.projet?.titre || '—' }}</p>
          <p class="text-xs text-slate-400 mt-0.5">
            <span class="capitalize font-semibold">{{ d.type_depot }}</span>
            <span class="mx-1.5 text-slate-300">·</span>
            {{ d.etudiant?.utilisateur?.prenom }} {{ d.etudiant?.utilisateur?.nom }}
          </p>
          <p class="mt-1 text-[10px] text-slate-400">
            Déposé le {{ new Date(d.depose_le || d.created_at).toLocaleDateString('fr-FR') }}
          </p>
          <p v-if="d.commentaire" class="mt-1.5 rounded-lg bg-red-50 px-2 py-1 text-xs italic text-red-600">
            « {{ d.commentaire }} »
          </p>
        </div>
        <a :href="d.chemin_fichier" target="_blank" rel="noopener"
          class="flex items-center gap-1.5 rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 transition">
          <i class="fa-solid fa-arrow-up-right-from-square"></i> Ouvrir
        </a>
        <span class="rounded-lg px-3 py-1 text-[11px] font-bold" :class="statutColor[d.statut_validation]">
          {{ statutLabel[d.statut_validation] || d.statut_validation }}
        </span>
        <div v-if="d.statut_validation === 'en_attente'" class="flex gap-2">
          <button @click="valider(d.id)"
            class="flex items-center gap-1.5 rounded-xl bg-green-50 px-3 py-2 text-xs font-bold text-green-700 hover:bg-green-100 transition">
            <i class="fa-solid fa-check"></i> Valider
          </button>
          <button @click="rejeter(d.id)"
            class="flex items-center gap-1.5 rounded-xl bg-red-50 px-3 py-2 text-xs font-bold text-red-500 hover:bg-red-100 transition">
            <i class="fa-solid fa-xmark"></i> Rejeter
          </button>
        </div>
      </article>
    </div>
  </div>
</template>
