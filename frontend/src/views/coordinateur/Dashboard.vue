<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const loading = ref(true)
const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))

const projets      = ref([])
const postulations = ref([])
const depots       = ref([])
const soutenances  = ref([])

const stats = computed(() => ({
  projets:             projets.value.length,
  postulationsAttente: postulations.value.filter(p => p.statut === 'en_attente').length,
  depotsAttente:       depots.value.filter(d => d.statut_validation === 'en_attente').length,
  soutenancesPlanif:   soutenances.value.filter(s => s.statut === 'planifiee').length,
}))

const recentPostulations = computed(() =>
  postulations.value.filter(p => p.statut === 'en_attente').slice(0, 5)
)

const depotsAValider = computed(() =>
  depots.value.filter(d => d.statut_validation === 'en_attente').slice(0, 5)
)

const upcomingSoutenances = computed(() =>
  soutenances.value
    .filter(s => new Date(s.date) >= new Date())
    .sort((a, b) => new Date(a.date) - new Date(b.date))
    .slice(0, 4)
)

const projetsParStatut = computed(() => {
  const map = { brouillon: 0, soumis: 0, en_cours: 0, valide: 0, soutenu: 0, rejete: 0 }
  projets.value.forEach(p => { if (map[p.statut] !== undefined) map[p.statut]++ })
  return map
})

const statutLabel = { brouillon: 'Brouillon', soumis: 'Soumis', en_cours: 'En cours', valide: 'Validé', soutenu: 'Soutenu', rejete: 'Rejeté' }

async function fetchAll() {
  loading.value = true
  try {
    const [pr, po, de, so] = await Promise.all([
      api.get('/projets'),
      api.get('/postulations'),
      api.get('/depots'),
      api.get('/soutenances'),
    ])
    projets.value      = pr.data.data
    postulations.value = po.data.data
    depots.value       = de.data.data
    soutenances.value  = so.data.data
  } catch {}
  loading.value = false
}

async function accepterPostulation(id) {
  try {
    await api.post(`/postulations/${id}/accepter`)
    await fetchAll()
  } catch (e) {
    alert(e.response?.data?.message || 'Erreur')
  }
}

async function rejeterPostulation(id) {
  try {
    await api.post(`/postulations/${id}/rejeter`)
    await fetchAll()
  } catch (e) {
    alert(e.response?.data?.message || 'Erreur')
  }
}

async function validerDepot(id) {
  try {
    await api.post(`/depots/${id}/valider`)
    await fetchAll()
  } catch (e) {
    alert(e.response?.data?.message || 'Erreur')
  }
}

async function rejeterDepot(id) {
  const c = prompt('Commentaire de rejet (optionnel) :')
  try {
    await api.post(`/depots/${id}/rejeter`, c ? { commentaire: c } : {})
    await fetchAll()
  } catch (e) {
    alert(e.response?.data?.message || 'Erreur')
  }
}

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-6">

    <!-- Hero -->
    <section class="relative overflow-hidden rounded-[2.5rem] bg-[#1e4a49] shadow-[0_30px_80px_rgba(20,51,49,0.25)]">
      <img src="https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=2070&q=80"
           class="absolute inset-0 h-full w-full object-cover opacity-30" alt="" />
      <div class="absolute inset-0 bg-gradient-to-r from-[#1e4a49]/95 via-[#1e4a49]/70 to-transparent"></div>
      <div class="relative z-10 flex flex-col gap-6 p-8 sm:p-10">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div class="text-white">
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.3em] text-[#d6e87a]">Espace Coordinateur</p>
            <h1 class="text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
              Bonjour {{ user.prenom || '' }},<br />
              <span class="text-[#d6e87a]">{{ stats.postulationsAttente + stats.depotsAttente }} actions</span> en attente
            </h1>
          </div>
          <div class="flex flex-wrap gap-3">
            <button @click="router.push('/coordinateur/postulations')"
              class="rounded-2xl bg-[#d6e87a] px-5 py-3 text-sm font-bold text-slate-800 shadow hover:brightness-105 transition">
              <i class="fa-solid fa-file-signature mr-2"></i>Postulations
            </button>
            <button @click="router.push('/coordinateur/depots')"
              class="rounded-2xl bg-white/15 px-5 py-3 text-sm font-bold text-white backdrop-blur hover:bg-white/25 transition">
              <i class="fa-solid fa-cloud-arrow-up mr-2"></i>Dépôts
            </button>
          </div>
        </div>

        <!-- Stats cards -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div v-for="item in [
            { label:'Projets',         value: stats.projets,             icon:'fa-folder-open',      bg:'bg-white/90' },
            { label:'Postulations',    value: stats.postulationsAttente, icon:'fa-file-signature',   bg:'bg-[#d6e87a]/90' },
            { label:'Dépôts à valider',value: stats.depotsAttente,       icon:'fa-cloud-arrow-up',   bg:'bg-white/90' },
            { label:'Soutenances',     value: stats.soutenancesPlanif,   icon:'fa-person-chalkboard',bg:'bg-white/90' },
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

    <!-- Body grid -->
    <div class="grid gap-5 xl:grid-cols-[1fr_360px]">

      <div class="space-y-5">

        <!-- Postulations en attente -->
        <article class="rounded-[2rem] border border-white/70 bg-white/90 shadow-sm overflow-hidden">
          <div class="flex items-center justify-between px-6 pt-5 pb-3">
            <div>
              <p class="text-base font-extrabold">Postulations en attente</p>
              <p class="text-xs text-slate-400">Candidatures à examiner</p>
            </div>
            <button @click="router.push('/coordinateur/postulations')"
              class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-700 transition">
              Voir tout
            </button>
          </div>
          <div v-if="loading" class="p-6 text-center text-sm text-slate-400">Chargement…</div>
          <div v-else-if="recentPostulations.length === 0" class="p-6 text-center text-sm text-slate-400">Aucune postulation en attente</div>
          <div v-else class="divide-y divide-slate-100">
            <div v-for="p in recentPostulations" :key="p.id" class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50/60 transition">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#d6e87a] text-xs font-black text-slate-700">
                {{ (p.etudiant?.utilisateur?.prenom || 'E')[0] }}{{ (p.etudiant?.utilisateur?.nom || '')[0] }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-slate-800 truncate">
                  {{ p.etudiant?.utilisateur?.prenom }} {{ p.etudiant?.utilisateur?.nom }}
                </p>
                <p class="text-xs text-slate-400 truncate">→ {{ p.projet?.titre || '—' }}</p>
              </div>
              <div class="flex gap-2 shrink-0">
                <button @click="accepterPostulation(p.id)"
                  class="rounded-xl bg-green-50 px-3 py-1.5 text-xs font-bold text-green-700 hover:bg-green-100 transition">
                  <i class="fa-solid fa-check"></i>
                </button>
                <button @click="rejeterPostulation(p.id)"
                  class="rounded-xl bg-red-50 px-3 py-1.5 text-xs font-bold text-red-500 hover:bg-red-100 transition">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
            </div>
          </div>
        </article>

        <!-- Dépôts à valider -->
        <article class="rounded-[2rem] border border-white/70 bg-white/90 shadow-sm overflow-hidden">
          <div class="flex items-center justify-between px-6 pt-5 pb-3">
            <div>
              <p class="text-base font-extrabold">Dépôts à valider</p>
              <p class="text-xs text-slate-400">Documents en attente de validation</p>
            </div>
            <button @click="router.push('/coordinateur/depots')"
              class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-700 transition">
              Voir tout
            </button>
          </div>
          <div v-if="loading" class="p-6 text-center text-sm text-slate-400">Chargement…</div>
          <div v-else-if="depotsAValider.length === 0" class="p-6 text-center text-sm text-slate-400">Aucun dépôt à valider</div>
          <div v-else class="divide-y divide-slate-100">
            <div v-for="d in depotsAValider" :key="d.id" class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50/60 transition">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <i class="fa-solid fa-file-pdf"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-slate-800 truncate">{{ d.projet?.titre || '—' }}</p>
                <p class="text-xs text-slate-400">
                  {{ d.type_depot }} • {{ d.etudiant?.utilisateur?.prenom }} {{ d.etudiant?.utilisateur?.nom }}
                </p>
              </div>
              <div class="flex gap-2 shrink-0">
                <button @click="validerDepot(d.id)"
                  class="rounded-xl bg-green-50 px-3 py-1.5 text-xs font-bold text-green-700 hover:bg-green-100 transition">
                  <i class="fa-solid fa-check"></i>
                </button>
                <button @click="rejeterDepot(d.id)"
                  class="rounded-xl bg-red-50 px-3 py-1.5 text-xs font-bold text-red-500 hover:bg-red-100 transition">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
            </div>
          </div>
        </article>

        <!-- Etat projets -->
        <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
          <p class="text-base font-extrabold">État des projets</p>
          <p class="text-xs text-slate-400">Vue globale par statut</p>
          <div class="mt-6 space-y-3">
            <div v-for="(count, statut) in projetsParStatut" :key="statut" class="flex items-center gap-3">
              <span class="w-20 shrink-0 text-xs font-semibold text-slate-600">{{ statutLabel[statut] }}</span>
              <div class="flex-1 rounded-full bg-slate-100 h-2.5">
                <div class="h-2.5 rounded-full bg-[#1e4a49] transition-all duration-700"
                     :style="{width: stats.projets ? `${Math.round(count/stats.projets*100)}%` : '0%'}"></div>
              </div>
              <span class="w-5 text-right text-xs font-bold text-slate-700">{{ count }}</span>
            </div>
          </div>
        </article>
      </div>

      <!-- RIGHT panel -->
      <div class="space-y-5">
        <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-lg font-extrabold tracking-tight">Soutenances à venir</p>
              <p class="mt-0.5 text-xs text-slate-400">Planifiées prochainement</p>
            </div>
            <button @click="router.push('/coordinateur/soutenances')"
              class="shrink-0 rounded-xl bg-slate-900 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-white">
              Voir tout
            </button>
          </div>
          <div class="mt-5 space-y-3">
            <div v-if="upcomingSoutenances.length === 0" class="text-center py-4 text-sm text-slate-400">
              Aucune soutenance planifiée
            </div>
            <article v-for="s in upcomingSoutenances" :key="s.id"
              class="rounded-[1.4rem] border border-slate-100 bg-white px-4 py-3.5 hover:border-[#d6e87a] hover:bg-[#f8faef] transition">
              <p class="text-[10px] font-bold uppercase tracking-widest text-[#1e4a49]">
                {{ new Date(s.date).toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long' }) }}
                · {{ s.heure?.slice(0,5) }}
              </p>
              <p class="mt-1 text-sm font-bold text-slate-800 leading-snug truncate">
                {{ s.projet?.titre || '—' }}
              </p>
              <p class="text-xs text-slate-400">Salle {{ s.salle }}</p>
            </article>
          </div>
        </article>

        <!-- Quick actions -->
        <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
          <p class="text-base font-extrabold">Actions rapides</p>
          <p class="text-xs text-slate-400 mb-4">Raccourcis</p>
          <div class="space-y-2">
            <button @click="router.push('/coordinateur/soutenances')"
              class="flex w-full items-center gap-3 rounded-2xl border border-slate-100 px-4 py-3 text-sm font-bold text-slate-700 hover:border-[#d6e87a] hover:bg-[#f8faef] transition">
              <i class="fa-solid fa-plus h-9 w-9 flex items-center justify-center rounded-xl bg-[#f0f5e0] text-[#6a8a40]"></i>
              Planifier une soutenance
            </button>
            <button @click="router.push('/coordinateur/projets')"
              class="flex w-full items-center gap-3 rounded-2xl border border-slate-100 px-4 py-3 text-sm font-bold text-slate-700 hover:border-[#d6e87a] hover:bg-[#f8faef] transition">
              <i class="fa-solid fa-folder-plus h-9 w-9 flex items-center justify-center rounded-xl bg-blue-50 text-blue-600"></i>
              Gérer les projets
            </button>
            <button @click="router.push('/coordinateur/statistiques')"
              class="flex w-full items-center gap-3 rounded-2xl border border-slate-100 px-4 py-3 text-sm font-bold text-slate-700 hover:border-[#d6e87a] hover:bg-[#f8faef] transition">
              <i class="fa-solid fa-chart-line h-9 w-9 flex items-center justify-center rounded-xl bg-purple-50 text-purple-600"></i>
              Voir les statistiques
            </button>
          </div>
        </article>
      </div>
    </div>
  </div>
</template>
