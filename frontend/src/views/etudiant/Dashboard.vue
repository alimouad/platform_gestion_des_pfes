<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const loading = ref(true)
const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))

const projets       = ref([])
const postulations  = ref([])
const depots        = ref([])
const soutenances   = ref([])

const etudiantId = computed(() => user.value?.etudiant?.id)

// My active project (accepted postulation)
const monProjet = computed(() => {
  const accepted = postulations.value.find(p => p.statut === 'accepte')
  return accepted?.projet || null
})

const mesPostulations  = computed(() => postulations.value)
const mesDepots        = computed(() => depots.value)
const maSoutenance     = computed(() =>
  soutenances.value.find(s => s.projet_id === monProjet.value?.id) || null
)

const stats = computed(() => ({
  postulations: mesPostulations.value.length,
  enAttente:    mesPostulations.value.filter(p => p.statut === 'en_attente').length,
  depots:       mesDepots.value.length,
  depotsValides:mesDepots.value.filter(d => d.statut_validation === 'valide').length,
}))

const projetsDisponibles = computed(() => {
  // Projets ouverts à postulation = soumis ou brouillon, sans postulation acceptée
  return projets.value
    .filter(p => ['soumis', 'brouillon'].includes(p.statut))
    .filter(p => !mesPostulations.value.some(po => po.projet_id === p.id))
    .slice(0, 4)
})

async function fetchAll() {
  loading.value = true
  try {
    // Refresh user too in case etudiant_id changed
    const me = await api.get('/me')
    user.value = me.data?.data || {}
    localStorage.setItem('admin_user', JSON.stringify(user.value))

    const [pr, po, de, so] = await Promise.all([
      api.get('/projets'),
      api.get('/postulations'),
      api.get('/depots'),
      api.get('/soutenances'),
    ])
    projets.value = pr.data.data
    // Filter to only this student's data (in case API returns all)
    postulations.value = po.data.data.filter(p => p.etudiant_id === etudiantId.value)
    depots.value       = de.data.data.filter(d => d.etudiant_id === etudiantId.value)
    soutenances.value  = so.data.data
  } catch {}
  loading.value = false
}

async function postuler(projetId) {
  if (!etudiantId.value) {
    alert('Profil étudiant introuvable. Contactez votre coordinateur.')
    return
  }
  try {
    await api.post('/postulations', {
      etudiant_id: etudiantId.value,
      projet_id: projetId,
    })
    await fetchAll()
  } catch (e) {
    alert(e.response?.data?.message || 'Erreur lors de la postulation')
  }
}

const statutLabel = { brouillon: 'Brouillon', soumis: 'Soumis', en_cours: 'En cours', valide: 'Validé', soutenu: 'Soutenu', rejete: 'Rejeté' }
const statutColor = { brouillon: 'bg-slate-100 text-slate-600', soumis: 'bg-blue-100 text-blue-700', en_cours: 'bg-amber-100 text-amber-700', valide: 'bg-green-100 text-green-700', soutenu: 'bg-[#d6e87a] text-slate-800', rejete: 'bg-red-100 text-red-600' }

const postulationLabel = { en_attente: 'En attente', accepte: 'Acceptée', rejete: 'Rejetée' }
const postulationColor = { en_attente: 'bg-amber-100 text-amber-700', accepte: 'bg-green-100 text-green-700', rejete: 'bg-red-100 text-red-600' }

function joursAvantSoutenance(date) {
  const d = new Date(date)
  const now = new Date()
  return Math.ceil((d - now) / (1000 * 60 * 60 * 24))
}

// Progression PFE — 5 étapes
const REQUIRED_DEPOSITS = ['rapport', 'code', 'presentation']

const progression = computed(() => {
  const hasPostulated   = mesPostulations.value.length > 0
  const hasProjet       = !!monProjet.value
  const validatedDepots = mesDepots.value.filter(d => d.statut_validation === 'valide').map(d => d.type_depot)
  const allDepotsValid  = REQUIRED_DEPOSITS.every(t => validatedDepots.includes(t))
  const hasSoutenance   = !!maSoutenance.value
  const isSoutenu       = monProjet.value?.statut === 'soutenu'

  return [
    { key: 'postulate', label: 'Postuler',           desc: 'Candidater à un projet',     icon: 'fa-paper-plane',     done: hasPostulated },
    { key: 'assigned',  label: 'Projet assigné',     desc: 'Postulation acceptée',       icon: 'fa-folder-tree',     done: hasProjet },
    { key: 'deposits',  label: 'Dépôts validés',     desc: 'Rapport, code, présentation', icon: 'fa-cloud-arrow-up',  done: allDepotsValid, partial: validatedDepots.length, total: REQUIRED_DEPOSITS.length },
    { key: 'scheduled', label: 'Soutenance fixée',   desc: 'Date programmée',            icon: 'fa-calendar-check',  done: hasSoutenance },
    { key: 'defended',  label: 'Soutenu',            desc: 'Projet présenté',            icon: 'fa-graduation-cap',  done: isSoutenu },
  ]
})

const currentStepIdx = computed(() => {
  const idx = progression.value.findIndex(s => !s.done)
  return idx === -1 ? progression.value.length - 1 : idx
})
const progressionPct = computed(() => {
  const done = progression.value.filter(s => s.done).length
  return Math.round((done / progression.value.length) * 100)
})

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-6">

    <!-- Hero -->
    <section class="relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-[#1e4a49] via-[#2a5e5d] to-[#3d7a6f] shadow-[0_30px_80px_rgba(20,51,49,0.25)]">
      <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=2070&q=80"
           class="absolute inset-0 h-full w-full object-cover opacity-25" alt="" />
      <div class="absolute inset-0 bg-gradient-to-r from-[#1e4a49]/95 via-[#1e4a49]/70 to-transparent"></div>

      <div class="relative z-10 flex flex-col gap-6 p-8 sm:p-10">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div class="text-white max-w-xl">
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.3em] text-[#d6e87a]">
              {{ user.etudiant?.code_etudiant || '—' }} · {{ user.etudiant?.filiere || 'Étudiant' }}
            </p>
            <h1 class="text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
              Bonjour <span class="text-[#d6e87a]">{{ user.prenom || '' }}</span>
              <span class="block text-2xl font-semibold text-white/80 mt-2">
                <template v-if="monProjet">Bonne continuation sur votre PFE !</template>
                <template v-else>Trouvons votre PFE.</template>
              </span>
            </h1>
          </div>

          <div class="flex flex-wrap gap-3">
            <button v-if="!monProjet" @click="router.push('/etudiant/projets')"
              class="rounded-2xl bg-[#d6e87a] px-5 py-3 text-sm font-bold text-slate-800 shadow hover:brightness-105 transition">
              <i class="fa-solid fa-compass mr-2"></i>Explorer les projets
            </button>
            <button v-else @click="router.push('/etudiant/depots')"
              class="rounded-2xl bg-[#d6e87a] px-5 py-3 text-sm font-bold text-slate-800 shadow hover:brightness-105 transition">
              <i class="fa-solid fa-cloud-arrow-up mr-2"></i>Déposer un document
            </button>
          </div>
        </div>

        <!-- Stats cards -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div v-for="item in [
            { label:'Postulations',   value: stats.postulations,   icon:'fa-file-signature',   bg:'bg-white/90' },
            { label:'En attente',     value: stats.enAttente,      icon:'fa-clock',            bg:'bg-[#d6e87a]/90' },
            { label:'Mes dépôts',     value: stats.depots,         icon:'fa-cloud-arrow-up',   bg:'bg-white/90' },
            { label:'Validés',        value: stats.depotsValides,  icon:'fa-circle-check',     bg:'bg-white/90' },
          ]" :key="item.label"
            class="rounded-[1.6rem] border border-white/40 p-5 backdrop-blur-md"
            :class="item.bg"
          >
            <div class="mb-3 flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-slate-500">
              <span>{{ item.label }}</span>
              <i :class="`fa-solid ${item.icon} text-slate-400`"></i>
            </div>
            <div class="text-3xl font-extrabold tracking-tight text-slate-900">
              <span v-if="loading">—</span>
              <span v-else>{{ item.value }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Soutenance countdown banner -->
    <section v-if="maSoutenance" class="rounded-[2rem] border-2 border-[#d6e87a] bg-gradient-to-r from-[#f8faef] to-[#ecf2d6] p-6 shadow-sm">
      <div class="flex flex-wrap items-center gap-6">
        <div class="flex h-20 w-20 shrink-0 flex-col items-center justify-center rounded-2xl bg-[#1e4a49] text-white shadow-lg">
          <p class="text-2xl font-extrabold leading-none">{{ joursAvantSoutenance(maSoutenance.date) }}</p>
          <p class="mt-1 text-[9px] font-bold uppercase tracking-widest">Jours</p>
        </div>
        <div class="flex-1 min-w-[200px]">
          <p class="text-[10px] font-bold uppercase tracking-widest text-[#1e4a49]">Soutenance prévue</p>
          <p class="text-xl font-extrabold text-slate-900 leading-tight">
            {{ new Date(maSoutenance.date).toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long', year:'numeric' }) }}
          </p>
          <p class="mt-0.5 text-sm font-semibold text-slate-600">
            <i class="fa-regular fa-clock mr-1.5"></i>{{ maSoutenance.heure?.slice(0,5) }}
            <span class="mx-2 text-slate-300">·</span>
            <i class="fa-solid fa-door-open mr-1.5"></i>Salle {{ maSoutenance.salle }}
          </p>
        </div>
        <button @click="router.push('/etudiant/soutenance')"
          class="rounded-2xl bg-[#1e4a49] px-5 py-3 text-sm font-bold text-[#d6e87a] hover:brightness-110 transition">
          Voir détails <i class="fa-solid fa-arrow-right ml-1"></i>
        </button>
      </div>
    </section>

    <!-- Progression PFE -->
    <section class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
          <p class="text-[10px] font-bold uppercase tracking-widest text-[#6a8a40]">Mon parcours PFE</p>
          <h2 class="text-base font-extrabold text-slate-900 mt-1">Progression</h2>
        </div>
        <div class="flex items-center gap-3">
          <div class="text-right leading-tight">
            <p class="text-2xl font-extrabold text-slate-900 leading-none">{{ progressionPct }}<span class="text-sm text-slate-400">%</span></p>
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Complété</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center"
            :style="{ background: `conic-gradient(#d6e87a 0% ${progressionPct}%, #f1f5f9 ${progressionPct}% 100%)` }">
            <div class="h-9 w-9 rounded-full bg-white flex items-center justify-center text-[10px] font-extrabold text-slate-700">
              {{ progression.filter(s => s.done).length }}/{{ progression.length }}
            </div>
          </div>
        </div>
      </div>

      <!-- Steps timeline -->
      <div class="relative">
        <!-- Connecting line -->
        <div class="absolute left-0 right-0 top-6 h-1 rounded-full bg-slate-100"></div>
        <div class="absolute left-0 top-6 h-1 rounded-full bg-[#d6e87a] transition-all duration-700"
          :style="{ width: `${(currentStepIdx / (progression.length - 1)) * 100}%` }"></div>

        <div class="relative grid grid-cols-5 gap-2">
          <div v-for="(step, i) in progression" :key="step.key"
            class="flex flex-col items-center text-center">
            <div class="relative h-12 w-12 rounded-2xl flex items-center justify-center text-base shadow-sm transition-all"
              :class="step.done
                ? 'bg-[#d6e87a] text-[#4a5e20]'
                : i === currentStepIdx
                  ? 'bg-[#1e4a49] text-[#d6e87a] ring-4 ring-[#d6e87a]/30 animate-pulse'
                  : 'bg-slate-100 text-slate-400'">
              <i :class="`fa-solid ${step.icon}`"></i>
              <span v-if="step.done" class="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-green-500 text-white text-[10px] flex items-center justify-center shadow">
                <i class="fa-solid fa-check"></i>
              </span>
            </div>
            <p class="mt-3 text-xs font-extrabold text-slate-800">{{ step.label }}</p>
            <p class="text-[10px] text-slate-400 leading-tight">{{ step.desc }}</p>
            <p v-if="step.partial != null" class="mt-1 text-[10px] font-bold"
              :class="step.done ? 'text-green-600' : 'text-amber-600'">
              {{ step.partial }}/{{ step.total }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Body grid -->
    <div class="grid gap-5 xl:grid-cols-[1fr_360px]">

      <div class="space-y-5">

        <!-- Mon projet -->
        <article v-if="monProjet" class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
              <p class="text-[10px] font-bold uppercase tracking-widest text-[#6a8a40]">Votre projet PFE</p>
              <h2 class="mt-1 text-xl font-extrabold text-slate-900 leading-snug">{{ monProjet.titre }}</h2>
              <p class="mt-2 text-sm text-slate-500 line-clamp-2">{{ monProjet.description || 'Aucune description' }}</p>
              <div class="mt-4 flex flex-wrap items-center gap-3">
                <span class="rounded-lg px-2.5 py-1 text-[11px] font-bold" :class="statutColor[monProjet.statut]">
                  {{ statutLabel[monProjet.statut] || monProjet.statut }}
                </span>
                <span class="text-xs font-semibold text-slate-500">
                  <i class="fa-solid fa-tags mr-1 text-slate-400"></i>{{ monProjet.domaine }}
                </span>
                <span class="text-xs font-semibold text-slate-500">
                  <i class="fa-solid fa-chalkboard-user mr-1 text-slate-400"></i>
                  {{ monProjet.professeur?.utilisateur?.prenom }} {{ monProjet.professeur?.utilisateur?.nom }}
                </span>
              </div>
            </div>
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#d6e87a] text-2xl text-[#4a5e20]">
              <i class="fa-solid fa-folder-tree"></i>
            </div>
          </div>
        </article>

        <!-- Empty state if no project -->
        <article v-else class="rounded-[2rem] border-2 border-dashed border-slate-200 bg-white/60 p-10 text-center">
          <i class="fa-solid fa-compass text-5xl text-slate-300"></i>
          <p class="mt-4 text-base font-extrabold text-slate-700">Vous n'avez pas encore de projet PFE</p>
          <p class="mt-1 text-sm text-slate-400">Postulez aux projets disponibles ci-dessous</p>
          <button @click="router.push('/etudiant/projets')"
            class="mt-5 rounded-2xl bg-slate-900 px-6 py-3 text-sm font-bold text-white hover:bg-slate-700 transition">
            <i class="fa-solid fa-arrow-right mr-1.5"></i>Découvrir les projets
          </button>
        </article>

        <!-- Mes postulations -->
        <article class="rounded-[2rem] border border-white/70 bg-white/90 shadow-sm overflow-hidden">
          <div class="flex items-center justify-between px-6 pt-5 pb-3">
            <div>
              <p class="text-base font-extrabold">Mes postulations</p>
              <p class="text-xs text-slate-400">{{ stats.postulations }} candidature{{ stats.postulations > 1 ? 's' : '' }}</p>
            </div>
            <button @click="router.push('/etudiant/postulations')"
              class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-700 transition">
              Voir tout
            </button>
          </div>
          <div v-if="loading" class="p-6 text-center text-sm text-slate-400">Chargement…</div>
          <div v-else-if="mesPostulations.length === 0" class="p-6 text-center text-sm text-slate-400">Aucune postulation pour l'instant</div>
          <div v-else class="divide-y divide-slate-100">
            <div v-for="p in mesPostulations.slice(0, 4)" :key="p.id" class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50/60 transition">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <i class="fa-solid fa-file-signature"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-slate-800 truncate">{{ p.projet?.titre || '—' }}</p>
                <p class="text-xs text-slate-400">
                  Postulé le {{ new Date(p.date_candidature || p.created_at).toLocaleDateString('fr-FR') }}
                </p>
              </div>
              <span class="rounded-lg px-2.5 py-1 text-[11px] font-bold" :class="postulationColor[p.statut]">
                {{ postulationLabel[p.statut] || p.statut }}
              </span>
            </div>
          </div>
        </article>
      </div>

      <!-- RIGHT: projets disponibles + dépôts -->
      <div class="space-y-5">

        <!-- Projets disponibles -->
        <article v-if="!monProjet" class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
          <div class="flex items-start justify-between gap-3 mb-4">
            <div>
              <p class="text-base font-extrabold">À découvrir</p>
              <p class="text-xs text-slate-400">Projets ouverts</p>
            </div>
            <button @click="router.push('/etudiant/projets')"
              class="shrink-0 rounded-xl bg-slate-900 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-white">
              Voir tout
            </button>
          </div>
          <div v-if="projetsDisponibles.length === 0" class="text-center py-4 text-sm text-slate-400">
            Aucun projet disponible
          </div>
          <div v-else class="space-y-3">
            <article v-for="p in projetsDisponibles" :key="p.id"
              class="rounded-[1.4rem] border border-slate-100 bg-white px-4 py-3.5 hover:border-[#d6e87a] hover:bg-[#f8faef] transition">
              <p class="text-[10px] font-bold uppercase tracking-widest text-[#6a8a40]">
                {{ p.domaine || '—' }}
              </p>
              <p class="mt-1 text-sm font-bold text-slate-800 leading-snug line-clamp-2">{{ p.titre }}</p>
              <p class="text-xs text-slate-400 mb-3">
                {{ p.professeur?.utilisateur?.prenom }} {{ p.professeur?.utilisateur?.nom }}
              </p>
              <button @click="postuler(p.id)"
                class="w-full rounded-xl bg-[#d6e87a] py-2 text-xs font-bold text-slate-800 hover:brightness-105 transition">
                <i class="fa-solid fa-paper-plane mr-1"></i>Postuler
              </button>
            </article>
          </div>
        </article>

        <!-- Mes dépôts récents -->
        <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
          <div class="flex items-start justify-between gap-3 mb-4">
            <div>
              <p class="text-base font-extrabold">Derniers dépôts</p>
              <p class="text-xs text-slate-400">{{ stats.depots }} fichier{{ stats.depots > 1 ? 's' : '' }}</p>
            </div>
            <button @click="router.push('/etudiant/depots')"
              class="shrink-0 rounded-xl bg-slate-900 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-white">
              Voir tout
            </button>
          </div>
          <div v-if="mesDepots.length === 0" class="text-center py-4 text-sm text-slate-400">
            Aucun dépôt
          </div>
          <div v-else class="space-y-2">
            <div v-for="d in mesDepots.slice(0, 4)" :key="d.id"
              class="flex items-center gap-3 rounded-xl border border-slate-100 px-3 py-2.5 hover:bg-slate-50 transition">
              <i class="fa-solid fa-file-pdf text-blue-500 text-lg"></i>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-slate-800 truncate">{{ d.type_depot }}</p>
                <p class="text-[10px] text-slate-400">{{ new Date(d.depose_le || d.created_at).toLocaleDateString('fr-FR') }}</p>
              </div>
              <span class="rounded-md px-2 py-0.5 text-[10px] font-bold"
                :class="d.statut_validation === 'valide' ? 'bg-green-100 text-green-700'
                  : d.statut_validation === 'rejete' ? 'bg-red-100 text-red-600'
                  : 'bg-amber-100 text-amber-700'">
                {{ d.statut_validation === 'valide' ? '✓' : d.statut_validation === 'rejete' ? '✗' : '…' }}
              </span>
            </div>
          </div>
        </article>
      </div>
    </div>
  </div>
</template>
