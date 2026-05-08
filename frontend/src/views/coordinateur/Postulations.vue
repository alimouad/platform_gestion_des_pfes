<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const items = ref([])
const loading = ref(false)
const filter = ref('en_attente')
const search = ref('')

async function fetchAll() {
  loading.value = true
  try {
    const res = await api.get('/postulations')
    items.value = res.data.data
  } catch {}
  loading.value = false
}

const filtered = computed(() => {
  let list = items.value
  if (filter.value) list = list.filter(p => p.statut === filter.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(p =>
      JSON.stringify(p).toLowerCase().includes(q)
    )
  }
  return list
})

const counts = computed(() => ({
  en_attente: items.value.filter(p => p.statut === 'en_attente').length,
  accepte:    items.value.filter(p => p.statut === 'accepte').length,
  rejete:     items.value.filter(p => p.statut === 'rejete').length,
}))

async function accepter(id) {
  if (!confirm('Accepter cette postulation ? Les autres candidatures pour ce projet seront rejetées.')) return
  try {
    await api.post(`/postulations/${id}/accepter`)
    await fetchAll()
  } catch (e) { alert(e.response?.data?.message || 'Erreur') }
}

async function rejeter(id) {
  if (!confirm('Rejeter cette postulation ?')) return
  try {
    await api.post(`/postulations/${id}/rejeter`)
    await fetchAll()
  } catch (e) { alert(e.response?.data?.message || 'Erreur') }
}

const statutLabel = { en_attente: 'En attente', accepte: 'Acceptée', rejete: 'Rejetée' }
const statutColor = { en_attente: 'bg-amber-100 text-amber-700', accepte: 'bg-green-100 text-green-700', rejete: 'bg-red-100 text-red-600' }

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Postulations</h1>
        <p class="text-sm text-slate-400">{{ items.length }} candidatures au total</p>
      </div>
    </div>

    <!-- Filter pills -->
    <div class="flex flex-wrap gap-2">
      <button v-for="(label, key) in statutLabel" :key="key"
        @click="filter = key"
        class="flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-bold transition"
        :class="filter === key ? 'bg-[#1e4a49] text-[#d6e87a]' : 'bg-white/90 text-slate-600 hover:bg-slate-100'">
        {{ label }}
        <span class="rounded-lg bg-white/20 px-2 py-0.5 text-[11px]">{{ counts[key] }}</span>
      </button>
      <button @click="filter = ''"
        class="rounded-2xl px-4 py-2.5 text-sm font-bold transition"
        :class="!filter ? 'bg-[#1e4a49] text-[#d6e87a]' : 'bg-white/90 text-slate-600 hover:bg-slate-100'">
        Toutes
      </button>
    </div>

    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher étudiant ou projet…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <div v-if="loading" class="rounded-[2rem] border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400 shadow-sm">Chargement…</div>
    <div v-else-if="filtered.length === 0" class="rounded-[2rem] border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400 shadow-sm">Aucune postulation</div>
    <div v-else class="space-y-3">
      <article v-for="p in filtered" :key="p.id"
        class="flex flex-wrap items-center gap-4 rounded-[1.6rem] border border-white/70 bg-white/90 p-5 shadow-sm">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#d6e87a] text-sm font-black text-slate-700">
          {{ (p.etudiant?.utilisateur?.prenom || 'E')[0] }}{{ (p.etudiant?.utilisateur?.nom || '')[0] }}
        </div>
        <div class="flex-1 min-w-[200px]">
          <p class="text-sm font-bold text-slate-800">
            {{ p.etudiant?.utilisateur?.prenom }} {{ p.etudiant?.utilisateur?.nom }}
            <span class="ml-2 text-xs font-mono text-slate-400">{{ p.etudiant?.code_etudiant }}</span>
          </p>
          <p class="text-xs text-slate-400">
            <i class="fa-solid fa-arrow-right mr-1"></i>{{ p.projet?.titre || '—' }}
          </p>
          <p class="mt-1 text-[10px] text-slate-400">
            Postulé le {{ new Date(p.date_candidature || p.created_at).toLocaleDateString('fr-FR') }}
          </p>
        </div>
        <span class="rounded-lg px-3 py-1 text-[11px] font-bold" :class="statutColor[p.statut]">
          {{ statutLabel[p.statut] || p.statut }}
        </span>
        <div v-if="p.statut === 'en_attente'" class="flex gap-2">
          <button @click="accepter(p.id)"
            class="flex items-center gap-1.5 rounded-xl bg-green-50 px-3 py-2 text-xs font-bold text-green-700 hover:bg-green-100 transition">
            <i class="fa-solid fa-check"></i> Accepter
          </button>
          <button @click="rejeter(p.id)"
            class="flex items-center gap-1.5 rounded-xl bg-red-50 px-3 py-2 text-xs font-bold text-red-500 hover:bg-red-100 transition">
            <i class="fa-solid fa-xmark"></i> Rejeter
          </button>
        </div>
      </article>
    </div>
  </div>
</template>
