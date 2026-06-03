<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { Doughnut, Bar, PolarArea } from 'vue-chartjs'
import {
  Chart as ChartJS, ArcElement, Tooltip, Legend,
  CategoryScale, LinearScale, BarElement, Title,
  RadialLinearScale, PolarAreaController,
} from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title, RadialLinearScale, PolarAreaController)

const annees       = ref([])
const selectedAnnee = ref(null)
const stats        = ref(null)
const loading      = ref(false)
const calculating  = ref(false)
const error        = ref('')
const projets      = ref([])
const postulations = ref([])
const depots       = ref([])

onMounted(async () => {
  try {
    const [anneesRes, projetsRes, poRes, deRes] = await Promise.all([
      api.get('/annees-universitaires'),
      api.get('/projets'),
      api.get('/postulations'),
      api.get('/depots'),
    ])
    annees.value       = anneesRes.data.data
    projets.value      = projetsRes.data.data || []
    postulations.value = poRes.data.data || []
    depots.value       = deRes.data.data || []
    const active = annees.value.find(a => a.statut === 'active')
    if (active) { selectedAnnee.value = active.id; await loadStats() }
  } catch {}
})

async function loadStats() {
  if (!selectedAnnee.value) return
  loading.value = true; error.value = ''
  try {
    const res = await api.get('/statistiques')
    stats.value = res.data.data?.find(s => s.annee_universitaire_id === selectedAnnee.value) || null
  } catch { error.value = 'Erreur de chargement' }
  loading.value = false
}

async function calculer() {
  if (!selectedAnnee.value) return
  calculating.value = true; error.value = ''
  try {
    await api.post(`/statistiques/calculer/${selectedAnnee.value}`)
    await loadStats()
  } catch (e) { error.value = e.response?.data?.message || 'Erreur de calcul' }
  calculating.value = false
}

const PALETTE = ['#d6e87a','#1e4a49','#4a8c6c','#7aab7c','#a8c47c','#f0cc7d','#e8a87a','#7c6ad6','#38bdf8','#fb923c']

// ── KPI Cards ─────────────────────────────────────────────────
const kpis = computed(() => {
  if (!stats.value) return []
  const total = stats.value.total_projets || 0
  const valides = stats.value.projets_valides || 0
  const enCours = stats.value.projets_en_cours || 0
  const soutenus = stats.value.projets_soutenus || 0
  const acceptRate = total ? Math.round((valides / total) * 100) : 0
  const depotValide = depots.value.filter(d => d.statut_validation === 'valide').length
  const postAccepte = postulations.value.filter(p => p.statut === 'accepte').length

  return [
    { label: 'Total projets',     value: total,        icon: 'fa-folder-open',       border: 'border-slate-200',  num: 'text-slate-900', sub: 'pour l\'année' },
    { label: 'Projets validés',   value: valides,      icon: 'fa-circle-check',      border: 'border-green-200',  num: 'text-green-700', sub: `${acceptRate}% du total` },
    { label: 'En cours',          value: enCours,      icon: 'fa-spinner',           border: 'border-amber-200',  num: 'text-amber-600', sub: 'en progression' },
    { label: 'Soutenus',          value: soutenus,     icon: 'fa-graduation-cap',    border: 'border-[#d6e87a]',  num: 'text-[#1e4a49]', sub: 'finalisés' },
    { label: 'Postulations acc.', value: postAccepte,  icon: 'fa-user-check',        border: 'border-blue-200',   num: 'text-blue-700',  sub: 'étudiants assignés' },
    { label: 'Dépôts validés',    value: depotValide,  icon: 'fa-file-circle-check', border: 'border-purple-200', num: 'text-purple-700',sub: 'livrables acceptés' },
  ]
})

// ── Donut : domaines ──────────────────────────────────────────
const domaineChartData = computed(() => {
  if (!stats.value?.projets_par_domaine) return null
  const obj = stats.value.projets_par_domaine
  const labels = Object.keys(obj)
  return {
    labels,
    datasets: [{
      data: Object.values(obj),
      backgroundColor: PALETTE.slice(0, labels.length),
      borderWidth: 3,
      borderColor: '#f0f3eb',
      hoverOffset: 8,
    }],
  }
})

// ── Bar : statuts ─────────────────────────────────────────────
const statutChartData = computed(() => {
  if (!stats.value) return null
  return {
    labels: ['Brouillon','Soumis','En cours','Validé','Soutenu','Rejeté'],
    datasets: [{
      label: 'Projets',
      data: [
        stats.value.projets_brouillon ?? 0,
        stats.value.projets_soumis ?? 0,
        stats.value.projets_en_cours ?? 0,
        stats.value.projets_valides ?? 0,
        stats.value.projets_soutenus ?? 0,
        stats.value.projets_rejetes ?? 0,
      ],
      backgroundColor: ['#94a3b8','#60a5fa','#fbbf24','#34d399','#d6e87a','#f87171'],
      borderRadius: 10,
      borderSkipped: false,
    }],
  }
})

// ── PolarArea : dépôts par type ───────────────────────────────
const depotChartData = computed(() => {
  const types = { rapport: 0, donnees: 0, presentation: 0, autre: 0 }
  depots.value.forEach(d => { if (types[d.type_depot] !== undefined) types[d.type_depot]++ })
  return {
    labels: ['Rapport','Données','Présentation','Autre'],
    datasets: [{
      data: Object.values(types),
      backgroundColor: ['rgba(239,68,68,.7)','rgba(59,130,246,.7)','rgba(249,115,22,.7)','rgba(148,163,184,.7)'],
      borderWidth: 0,
    }],
  }
})

// ── Villes ────────────────────────────────────────────────────
const villeData = computed(() => {
  const counts = {}
  projets.value.forEach(p => { if (p.ville) counts[p.ville] = (counts[p.ville] || 0) + 1 })
  const entries = Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, 8)
  const max = entries[0]?.[1] || 1
  return entries.map(([ville, count]) => ({ ville, count, pct: Math.round((count / max) * 100) }))
})

// ── Chart options ─────────────────────────────────────────────
const donutOpts = {
  responsive: true, maintainAspectRatio: false, cutout: '68%',
  plugins: {
    legend: { position: 'bottom', labels: { font: { size: 11, weight: '700' }, padding: 14, boxWidth: 12, boxHeight: 12 } },
    tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} projet${ctx.parsed > 1 ? 's' : ''}` } },
  },
}
const barOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } },
    y: { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 11 }, stepSize: 1, precision: 0 } },
  },
}
const polarOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom', labels: { font: { size: 11, weight: '700' }, padding: 12, boxWidth: 12 } } },
  scales: { r: { ticks: { display: false }, grid: { color: 'rgba(0,0,0,.06)' } } },
}

const selectedAnneeLabel = computed(() => annees.value.find(a => a.id === selectedAnnee.value)?.annee || '—')
</script>

<template>
  <div class="space-y-6">

    <!-- Hero banner -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-7 text-white relative overflow-hidden">
      <div class="absolute -right-10 -top-10 h-52 w-52 rounded-full bg-white/5"></div>
      <div class="absolute -bottom-8 right-32 h-32 w-32 rounded-full bg-[#d6e87a]/10"></div>
      <div class="relative flex flex-wrap items-center justify-between gap-5">
        <div>
          <p class="text-[11px] font-black uppercase tracking-widest text-[#d6e87a]">Tableau de bord analytique</p>
          <h1 class="mt-1 text-2xl font-black">Statistiques PFE</h1>
          <p class="mt-1 text-sm text-white/60">Analyse complète · Année {{ selectedAnneeLabel }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <select v-model="selectedAnnee" @change="loadStats"
            class="rounded-2xl bg-white/10 border border-white/20 px-4 py-2.5 text-sm font-bold text-white outline-none focus:bg-white/20 transition cursor-pointer">
            <option :value="null" class="text-slate-900">— Choisir une année —</option>
            <option v-for="a in annees" :key="a.id" :value="a.id" class="text-slate-900">{{ a.annee }}</option>
          </select>
          <button @click="calculer" :disabled="!selectedAnnee || calculating"
            class="flex items-center gap-2 rounded-2xl bg-[#d6e87a] px-5 py-2.5 text-sm font-black text-[#1e4a49] hover:brightness-105 transition disabled:opacity-50 active:scale-95">
            <i class="fa-solid fa-calculator" :class="{ 'fa-spin': calculating }"></i>
            {{ calculating ? 'Calcul…' : 'Recalculer' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="error" class="rounded-2xl bg-red-50 border border-red-100 px-5 py-3 text-sm font-bold text-red-600">
      <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ error }}
    </div>

    <!-- Loading -->
    <div v-if="loading" class="rounded-3xl border border-white/70 bg-white/90 p-16 text-center text-sm text-slate-400">
      <i class="fa-solid fa-circle-notch fa-spin text-3xl text-[#d6e87a] block mb-3"></i>
      Calcul en cours…
    </div>

    <!-- Empty state -->
    <div v-else-if="!stats" class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-white/60 py-24 text-center">
      <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-100 mb-4">
        <i class="fa-solid fa-chart-pie text-4xl text-slate-300"></i>
      </div>
      <p class="text-base font-extrabold text-slate-700">Aucune statistique disponible</p>
      <p class="mt-1 text-sm text-slate-400">Sélectionnez une année puis cliquez sur « Recalculer »</p>
    </div>

    <div v-else class="space-y-6">

      <!-- KPI grid -->
      <div class="grid grid-cols-2 gap-4 xl:grid-cols-3">
        <div v-for="k in kpis" :key="k.label"
          class="rounded-3xl border bg-white/90 p-6 flex items-center gap-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
          :class="k.border">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-50">
            <i :class="[`fa-solid ${k.icon} text-xl`, k.num]"></i>
          </div>
          <div>
            <p class="text-4xl font-black leading-none text-slate-900">{{ k.value ?? 0 }}</p>
            <p class="text-[11px] font-bold text-slate-500 mt-1">{{ k.label }}</p>
            <p class="text-[10px] mt-0.5" :class="k.num">{{ k.sub }}</p>
          </div>
        </div>
      </div>

      <!-- Charts row 1: donut + bar -->
      <div class="grid gap-5 md:grid-cols-2">

        <!-- Donut : domaines -->
        <article class="rounded-3xl border border-white/70 bg-white/90 shadow-sm p-6">
          <div class="flex items-center gap-3 mb-5">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#1e4a49]">
              <i class="fa-solid fa-circle-half-stroke text-[#d6e87a] text-sm"></i>
            </div>
            <div>
              <p class="text-base font-black text-slate-900">Projets par domaine</p>
              <p class="text-xs text-slate-400">Répartition thématique</p>
            </div>
          </div>
          <div v-if="domaineChartData" style="height:260px">
            <Doughnut :data="domaineChartData" :options="donutOpts" />
          </div>
          <div v-else class="flex h-52 items-center justify-center text-sm text-slate-400 italic">Aucune donnée</div>
        </article>

        <!-- Bar : statuts -->
        <article class="rounded-3xl border border-white/70 bg-white/90 shadow-sm p-6">
          <div class="flex items-center gap-3 mb-5">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#1e4a49]">
              <i class="fa-solid fa-chart-column text-[#d6e87a] text-sm"></i>
            </div>
            <div>
              <p class="text-base font-black text-slate-900">Projets par statut</p>
              <p class="text-xs text-slate-400">État d'avancement global</p>
            </div>
          </div>
          <div v-if="statutChartData" style="height:260px">
            <Bar :data="statutChartData" :options="barOpts" />
          </div>
        </article>
      </div>

      <!-- Charts row 2: polar + villes -->
      <div class="grid gap-5 md:grid-cols-2">

        <!-- PolarArea : dépôts par type -->
        <article class="rounded-3xl border border-white/70 bg-white/90 shadow-sm p-6">
          <div class="flex items-center gap-3 mb-5">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#1e4a49]">
              <i class="fa-solid fa-cloud-arrow-up text-[#d6e87a] text-sm"></i>
            </div>
            <div>
              <p class="text-base font-black text-slate-900">Dépôts par type</p>
              <p class="text-xs text-slate-400">Rapport · Données · Présentation</p>
            </div>
          </div>
          <div style="height:260px">
            <PolarArea :data="depotChartData" :options="polarOpts" />
          </div>
        </article>

        <!-- Villes SIG -->
        <article class="rounded-3xl border border-white/70 bg-white/90 shadow-sm p-6">
          <div class="flex items-center gap-3 mb-5">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#1e4a49]">
              <i class="fa-solid fa-map-location-dot text-[#d6e87a] text-sm"></i>
            </div>
            <div>
              <p class="text-base font-black text-slate-900">Top villes</p>
              <p class="text-xs text-slate-400">Projets géolocalisés par ville</p>
            </div>
          </div>
          <div v-if="villeData.length" class="space-y-3">
            <div v-for="(item, i) in villeData" :key="item.ville" class="flex items-center gap-3">
              <span class="w-5 shrink-0 text-center text-[10px] font-black"
                :class="i === 0 ? 'text-[#d6e87a]' : i === 1 ? 'text-slate-400' : 'text-slate-300'">
                {{ i + 1 }}
              </span>
              <span class="w-24 shrink-0 truncate text-xs font-bold text-slate-700">{{ item.ville }}</span>
              <div class="flex-1 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700"
                  :style="{ width: `${item.pct}%`, background: i === 0 ? '#1e4a49' : '#d6e87a' }"></div>
              </div>
              <span class="w-6 shrink-0 text-right text-xs font-black text-slate-700">{{ item.count }}</span>
            </div>
          </div>
          <div v-else class="flex h-52 items-center justify-center text-sm text-slate-400 italic">
            Aucun projet géolocalisé
          </div>
        </article>
      </div>

      <!-- Régions -->
      <article v-if="stats.projets_par_region && Object.keys(stats.projets_par_region).length"
        class="rounded-3xl border border-white/70 bg-white/90 shadow-sm p-6">
        <div class="flex items-center gap-3 mb-5">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#1e4a49]">
            <i class="fa-solid fa-globe text-[#d6e87a] text-sm"></i>
          </div>
          <div>
            <p class="text-base font-black text-slate-900">Projets par région</p>
            <p class="text-xs text-slate-400">Distribution géographique nationale</p>
          </div>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
          <div v-for="(count, region) in stats.projets_par_region" :key="region"
            class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4 flex items-center gap-3">
            <i class="fa-solid fa-location-dot text-[#d6e87a] text-sm shrink-0"></i>
            <div>
              <p class="text-xs font-bold text-slate-500 truncate">{{ region }}</p>
              <p class="text-2xl font-black text-slate-900">{{ count }}</p>
            </div>
          </div>
        </div>
      </article>

      <!-- Footer timestamp -->
      <p v-if="stats.updated_at" class="text-center text-xs text-slate-400">
        <i class="fa-regular fa-clock mr-1"></i>
        Dernière mise à jour : {{ new Date(stats.updated_at).toLocaleString('fr-FR') }}
      </p>
    </div>
  </div>
</template>
