<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const annees = ref([])
const selectedAnnee = ref(null)
const stats = ref(null)
const loading = ref(false)
const calculating = ref(false)
const error = ref('')

onMounted(async () => {
  try {
    const res = await api.get('/annees-universitaires')
    annees.value = res.data.data
    const active = annees.value.find(a => a.statut === 'active')
    if (active) {
      selectedAnnee.value = active.id
      await loadStats()
    }
  } catch {}
})

async function loadStats() {
  if (!selectedAnnee.value) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get('/statistiques')
    const found = res.data.data?.find(s => s.annee_universitaire_id === selectedAnnee.value)
    stats.value = found || null
  } catch (e) {
    error.value = 'Erreur de chargement'
  }
  loading.value = false
}

async function calculer() {
  if (!selectedAnnee.value) return
  calculating.value = true
  error.value = ''
  try {
    await api.post(`/statistiques/calculer/${selectedAnnee.value}`)
    await loadStats()
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur de calcul'
  }
  calculating.value = false
}

const domaineData = computed(() => {
  if (!stats.value?.projets_par_domaine) return []
  const obj = stats.value.projets_par_domaine
  const colors = ['#d6e87a','#a8c47c','#7aab7c','#4a8c6c','#1a6c5c','#f0cc7d','#e8a87a','#c4a87a']
  const total = Object.values(obj).reduce((a, b) => a + b, 0) || 1
  return Object.entries(obj).map(([label, count], i) => ({
    label, count, color: colors[i % colors.length],
    pct: Math.round((count / total) * 100),
  }))
})

const conicGradient = computed(() => {
  if (!domaineData.value.length) return 'conic-gradient(#e2e8f0 0% 100%)'
  let pos = 0
  const parts = domaineData.value.map(d => {
    const segment = `${d.color} ${pos}% ${pos + d.pct}%`
    pos += d.pct
    return segment
  })
  return `conic-gradient(${parts.join(', ')})`
})

const statCards = computed(() => {
  if (!stats.value) return []
  return [
    { label: 'Total projets',   value: stats.value.total_projets,    icon: 'fa-folder-open',  color: 'bg-white' },
    { label: 'Projets validés', value: stats.value.projets_valides,  icon: 'fa-check-circle', color: 'bg-[#d6e87a]/30' },
    { label: 'En cours',        value: stats.value.projets_en_cours, icon: 'fa-spinner',      color: 'bg-amber-50' },
    { label: 'Soutenus',        value: stats.value.projets_soutenus, icon: 'fa-graduation-cap', color: 'bg-green-50' },
  ]
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Statistiques</h1>
        <p class="text-sm text-slate-400">Analyse des projets par année universitaire</p>
      </div>
      <div class="flex items-center gap-3">
        <select v-model="selectedAnnee" @change="loadStats"
          class="rounded-2xl border border-white/70 bg-white/90 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm outline-none">
          <option :value="null">— Choisir une année —</option>
          <option v-for="a in annees" :key="a.id" :value="a.id">{{ a.annee }}</option>
        </select>
        <button @click="calculer" :disabled="!selectedAnnee || calculating"
          class="flex items-center gap-2 rounded-2xl bg-[#d6e87a] px-5 py-2.5 text-sm font-bold text-slate-800 shadow hover:brightness-105 transition disabled:opacity-50">
          <i class="fa-solid fa-calculator" :class="{ 'fa-spin': calculating }"></i>
          {{ calculating ? 'Calcul…' : 'Recalculer' }}
        </button>
      </div>
    </div>

    <div v-if="error" class="rounded-2xl bg-red-50 px-5 py-3 text-sm text-red-600">{{ error }}</div>

    <div v-if="loading" class="rounded-[2rem] border border-white/70 bg-white/90 p-16 text-center text-sm text-slate-400 shadow-sm">
      Chargement…
    </div>

    <div v-else-if="!stats" class="rounded-[2rem] border border-dashed border-slate-300 bg-white/60 p-16 text-center">
      <i class="fa-solid fa-chart-pie text-4xl text-slate-300 mb-4"></i>
      <p class="text-sm font-semibold text-slate-400">Sélectionnez une année puis cliquez sur "Recalculer"</p>
    </div>

    <div v-else class="space-y-5">
      <!-- KPI Cards -->
      <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div v-for="card in statCards" :key="card.label"
          class="rounded-[2rem] border border-white/70 p-6 shadow-sm"
          :class="card.color">
          <div class="mb-3 flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">{{ card.label }}</span>
            <i :class="`fa-solid ${card.icon} text-slate-300 text-lg`"></i>
          </div>
          <p class="text-4xl font-extrabold tracking-tight text-slate-900">{{ card.value ?? 0 }}</p>
        </div>
      </div>

      <!-- Charts row -->
      <div class="grid gap-5 md:grid-cols-2">

        <!-- Donut chart by domain -->
        <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
          <p class="text-base font-extrabold">Projets par domaine</p>
          <p class="text-xs text-slate-400">Répartition thématique</p>
          <div v-if="domaineData.length === 0" class="mt-8 text-center text-sm text-slate-400">Aucune donnée</div>
          <div v-else class="mt-6 flex items-center gap-6">
            <div class="relative shrink-0">
              <div class="h-36 w-36 rounded-full" :style="{ background: conicGradient }"></div>
              <div class="absolute inset-0 m-auto flex h-[58%] w-[58%] items-center justify-center rounded-full bg-white text-center">
                <div>
                  <p class="text-2xl font-extrabold text-slate-900">{{ stats.total_projets }}</p>
                  <p class="text-[9px] uppercase tracking-widest text-slate-400">Total</p>
                </div>
              </div>
            </div>
            <div class="flex flex-col gap-2 min-w-0">
              <div v-for="d in domaineData" :key="d.label" class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ background: d.color }"></span>
                <span class="truncate text-xs font-semibold text-slate-700">{{ d.label }}</span>
                <span class="ml-auto text-xs text-slate-400 shrink-0">{{ d.pct }}%</span>
              </div>
            </div>
          </div>
        </article>

        <!-- Bar chart by domain -->
        <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
          <p class="text-base font-extrabold">Volume par domaine</p>
          <p class="text-xs text-slate-400">Nombre de projets</p>
          <div v-if="domaineData.length === 0" class="mt-8 text-center text-sm text-slate-400">Aucune donnée</div>
          <div v-else class="mt-6 space-y-3">
            <div v-for="d in domaineData" :key="d.label" class="flex items-center gap-3">
              <span class="w-24 shrink-0 truncate text-xs font-semibold text-slate-600">{{ d.label }}</span>
              <div class="flex-1 rounded-full bg-slate-100 h-3">
                <div class="h-3 rounded-full transition-all duration-700"
                  :style="{ width: `${d.pct}%`, background: d.color }"></div>
              </div>
              <span class="w-6 text-right text-xs font-bold text-slate-700 shrink-0">{{ d.count }}</span>
            </div>
          </div>
        </article>
      </div>

      <!-- Projets par région -->
      <article v-if="stats.projets_par_region && Object.keys(stats.projets_par_region).length > 0"
        class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
        <p class="text-base font-extrabold">Projets par région</p>
        <p class="text-xs text-slate-400 mb-5">Distribution géographique</p>
        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
          <div v-for="(count, region) in stats.projets_par_region" :key="region"
            class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
            <p class="text-xs font-semibold text-slate-500">{{ region }}</p>
            <p class="text-2xl font-extrabold text-slate-900">{{ count }}</p>
          </div>
        </div>
      </article>

      <!-- Footer meta -->
      <p v-if="stats.updated_at" class="text-center text-xs text-slate-400">
        Dernière mise à jour : {{ new Date(stats.updated_at).toLocaleString('fr-FR') }}
      </p>
    </div>
  </div>
</template>
