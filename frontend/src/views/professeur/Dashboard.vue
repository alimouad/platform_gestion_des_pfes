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

const profId = computed(() => user.value?.professeur?.id)

// Filter to only this professor's data
const mesProjets = computed(() =>
  projets.value.filter(p => p.professeur_id === profId.value)
)

const mesProjetIds = computed(() => mesProjets.value.map(p => p.id))

const mesPostulations = computed(() =>
  postulations.value.filter(po => mesProjetIds.value.includes(po.projet_id))
)

const mesDepots = computed(() =>
  depots.value.filter(d => mesProjetIds.value.includes(d.projet_id))
)

const stats = computed(() => ({
  projets:        mesProjets.value.length,
  enCours:        mesProjets.value.filter(p => ['en_cours', 'valide'].includes(p.statut)).length,
  postulAttente:  mesPostulations.value.filter(p => p.statut === 'en_attente').length,
  depotsAttente:  mesDepots.value.filter(d => d.statut_validation === 'en_attente').length,
}))

const projetsParStatut = computed(() => {
  const map = { brouillon: 0, soumis: 0, en_cours: 0, valide: 0, soutenu: 0, rejete: 0 }
  mesProjets.value.forEach(p => { if (map[p.statut] !== undefined) map[p.statut]++ })
  return map
})

const depotsAValider = computed(() =>
  mesDepots.value.filter(d => d.statut_validation === 'en_attente').slice(0, 5)
)

const recentPostulations = computed(() =>
  mesPostulations.value
    .filter(p => p.statut === 'en_attente')
    .slice(0, 5)
)

// Etudiants encadrés (postulations acceptées)
const etudiantsEncadres = computed(() => {
  const accepted = mesPostulations.value.filter(p => p.statut === 'accepte')
  const seen = new Set()
  return accepted.filter(p => {
    const id = p.etudiant?.id
    if (!id || seen.has(id)) return false
    seen.add(id)
    return true
  })
})

const statutLabel = { brouillon: 'Brouillon', soumis: 'Soumis', en_cours: 'En cours', valide: 'Validé', soutenu: 'Soutenu', rejete: 'Rejeté' }

async function fetchAll() {
  loading.value = true
  try {
    const me = await api.get('/me')
    user.value = me.data?.data || {}
    localStorage.setItem('admin_user', JSON.stringify(user.value))

    const [pr, po, de] = await Promise.all([
      api.get('/projets'),
      api.get('/postulations'),
      api.get('/depots'),
    ])
    projets.value = pr.data.data
    postulations.value = po.data.data
    depots.value = de.data.data
  } catch {}
  loading.value = false
}

async function validerDepot(id) {
  if (!confirm('Valider ce dépôt ?')) return
  try {
    await api.post(`/depots/${id}/valider`)
    await fetchAll()
  } catch (e) { alert(e.response?.data?.message || 'Erreur') }
}

async function rejeterDepot(id) {
  const c = prompt('Commentaire (optionnel) :')
  if (c === null) return
  try {
    await api.post(`/depots/${id}/rejeter`, c ? { commentaire: c } : {})
    await fetchAll()
  } catch (e) { alert(e.response?.data?.message || 'Erreur') }
}

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-6 pb-10">

    <!-- Hero Section -->
    <section class="relative overflow-hidden rounded-[3rem] bg-[#1e4a49] shadow-2xl transition-all duration-500">
      <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=2070&q=80"
           class="absolute inset-0 h-full w-full object-cover opacity-30 mix-blend-overlay" alt="" />
      <div class="absolute inset-0 bg-gradient-to-br from-[#1e4a49] via-[#1e4a49]/80 to-transparent"></div>
      
      <div class="relative z-10 p-8 sm:p-12">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <div class="mb-4 inline-flex items-center rounded-full bg-white/10 px-3 py-1 backdrop-blur-md">
              <span class="mr-2 h-2 w-2 animate-ping rounded-full bg-[#d6e87a]"></span>
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/80">
                {{ user.professeur?.grade || 'Professeur' }}
                <template v-if="user.professeur?.specialite"> · {{ user.professeur.specialite }}</template>
              </p>
            </div>
            <h1 class="text-4xl font-black leading-tight tracking-tight text-white sm:text-6xl">
              Bonjour <span class="text-[#d6e87a]">{{ user.prenom || '' }}</span>,
            </h1>
            <p class="mt-4 text-lg font-medium text-white/80">
              <template v-if="stats.depotsAttente > 0 || stats.postulAttente > 0">
                Vous avez <strong class="text-white">{{ stats.depotsAttente + stats.postulAttente }} action{{ (stats.depotsAttente + stats.postulAttente) > 1 ? 's' : '' }}</strong> en attente.
              </template>
              <template v-else>Tout est à jour ✨</template>
            </p>
          </div>
          
          <div class="flex flex-wrap gap-4">
            <button @click="router.push('/professeur/projets')"
              class="group flex items-center rounded-2xl bg-[#d6e87a] px-6 py-4 text-sm font-black text-slate-900 shadow-xl transition-all hover:scale-105 active:scale-95">
              <i class="fa-solid fa-plus mr-2 opacity-50 group-hover:rotate-90 transition-transform"></i> Nouveau projet
            </button>
            <button @click="router.push('/professeur/depots')"
              class="flex items-center rounded-2xl bg-white/10 px-6 py-4 text-sm font-black text-white backdrop-blur-md border border-white/20 transition-all hover:bg-white/20">
              <i class="fa-solid fa-cloud-arrow-up mr-2 opacity-50"></i> Dépôts
            </button>
          </div>
        </div>

        <!-- Stats Grid -->
        <div class="mt-12 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div v-for="item in [
            { label:'Mes projets',       value: stats.projets,        icon:'fa-folder-open',    color:'text-amber-500' },
            { label:'En supervision',    value: stats.enCours,        icon:'fa-eye',            color:'text-emerald-500' },
            { label:'Postulations',      value: stats.postulAttente,  icon:'fa-file-signature', color:'text-blue-500' },
            { label:'Dépôts à valider',  value: stats.depotsAttente,  icon:'fa-cloud-arrow-up', color:'text-purple-500' },
          ]" :key="item.label"
            class="group relative overflow-hidden rounded-[2rem] bg-white p-6 transition-all hover:-translate-y-1 hover:shadow-2xl">
            <div class="flex justify-between items-start">
              <div class="h-10 w-10 rounded-xl bg-slate-50 flex items-center justify-center transition-colors group-hover:bg-[#d6e87a]/20">
                <i :class="`fa-solid ${item.icon} ${item.color}`"></i>
              </div>
              <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ item.label }}</span>
            </div>
            <div class="mt-4">
              <div v-if="loading" class="h-8 w-16 animate-pulse rounded-lg bg-slate-100"></div>
              <p v-else class="text-3xl font-black text-slate-900">{{ item.value }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Main Layout -->
    <div class="grid gap-6 xl:grid-cols-12">
      
      <!-- Center Content -->
      <div class="xl:col-span-8 space-y-6">

        <!-- Dépôts à valider -->
        <article class="overflow-hidden rounded-[2.5rem] bg-white shadow-sm border border-slate-100">
          <div class="flex items-center justify-between p-8 border-b border-slate-50">
            <div>
              <h3 class="text-xl font-black text-slate-800">Dépôts à valider</h3>
              <p class="mt-1 text-xs font-medium text-slate-400">Documents en attente de votre validation</p>
            </div>
            <button @click="router.push('/professeur/depots')"
              class="rounded-xl bg-slate-900 px-5 py-2.5 text-[10px] font-black uppercase tracking-widest text-white transition-all hover:bg-slate-700 active:scale-95">
              Tout voir
            </button>
          </div>
          <div v-if="loading" class="p-10 text-center text-sm font-bold text-slate-400 animate-pulse">Chargement…</div>
          <div v-else-if="depotsAValider.length === 0" class="p-10 text-center">
            <i class="fa-solid fa-circle-check text-5xl text-green-300"></i>
            <p class="mt-3 text-sm font-black text-slate-600">Tout est validé</p>
            <p class="text-xs font-medium text-slate-400">Aucun dépôt en attente.</p>
          </div>
          <div v-else>
            <div v-for="d in depotsAValider" :key="d.id"
              class="group flex flex-wrap items-center gap-4 px-8 py-5 transition-colors hover:bg-slate-50/50 border-b border-slate-50 last:border-0">
              <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 group-hover:bg-blue-50 group-hover:text-blue-500 transition-colors">
                <i class="fa-solid fa-file-pdf text-xl"></i>
              </div>
              <div class="flex-1 min-w-[180px]">
                <p class="text-sm font-bold text-slate-800 truncate group-hover:text-[#1e4a49] transition-colors">{{ d.projet?.titre || '—' }}</p>
                <p class="mt-0.5 text-xs font-medium text-slate-400">
                  <span class="uppercase tracking-wider text-[10px] font-black text-slate-500">{{ d.type_depot }}</span>
                  <span class="mx-2 text-slate-300">•</span>
                  {{ d.etudiant?.utilisateur?.prenom }} {{ d.etudiant?.utilisateur?.nom }}
                </p>
              </div>
              <a :href="d.chemin_fichier" target="_blank" rel="noopener" title="Voir le document"
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-500 hover:bg-[#1e4a49] hover:text-white transition-all active:scale-95">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
              </a>
              <div class="flex gap-2 shrink-0 ml-2">
                <button @click="validerDepot(d.id)"
                  class="flex h-10 items-center gap-2 rounded-xl bg-emerald-50 px-4 text-xs font-bold text-emerald-600 hover:bg-emerald-500 hover:text-white transition-all active:scale-95">
                  <i class="fa-solid fa-check"></i> Valider
                </button>
                <button @click="rejeterDepot(d.id)" title="Rejeter"
                  class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all active:scale-95">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
            </div>
          </div>
        </article>

        <!-- Postulations récentes -->
        <article class="overflow-hidden rounded-[2.5rem] bg-white shadow-sm border border-slate-100">
          <div class="flex items-center justify-between p-8 border-b border-slate-50">
            <div>
              <h3 class="text-xl font-black text-slate-800">Postulations récentes</h3>
              <p class="mt-1 text-xs font-medium text-slate-400">Étudiants intéressés par vos projets</p>
            </div>
            <button @click="router.push('/professeur/postulations')"
              class="rounded-xl bg-slate-900 px-5 py-2.5 text-[10px] font-black uppercase tracking-widest text-white transition-all hover:bg-slate-700 active:scale-95">
              Tout voir
            </button>
          </div>
          <div v-if="loading" class="p-10 text-center text-sm font-bold text-slate-400 animate-pulse">Chargement…</div>
          <div v-else-if="recentPostulations.length === 0" class="p-10 text-center text-sm text-slate-400">Aucune postulation en attente.</div>
          <div v-else>
            <div v-for="p in recentPostulations" :key="p.id" 
                 class="group flex items-center gap-4 px-8 py-5 transition-colors hover:bg-slate-50/50 border-b border-slate-50 last:border-0">
              <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#d6e87a]/20 text-[#1e4a49] font-black text-sm">
                {{ (p.etudiant?.utilisateur?.prenom || 'E')[0] }}{{ (p.etudiant?.utilisateur?.nom || '')[0] }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-slate-800 truncate group-hover:text-[#1e4a49] transition-colors">
                  {{ p.etudiant?.utilisateur?.prenom }} {{ p.etudiant?.utilisateur?.nom }}
                  <span class="ml-2 rounded bg-slate-100 px-2 py-0.5 text-[10px] font-mono text-slate-500">{{ p.etudiant?.code_etudiant }}</span>
                </p>
                <p class="mt-0.5 text-xs font-medium text-slate-400 truncate">
                  <i class="fa-solid fa-folder-open mr-1.5 opacity-50"></i>{{ p.projet?.titre || '—' }}
                </p>
              </div>
              <span class="shrink-0 rounded-xl bg-amber-100 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest text-amber-700">
                En attente
              </span>
            </div>
          </div>
        </article>
      </div>

      <!-- Right Column -->
      <aside class="xl:col-span-4 space-y-6">

        <!-- Progression (État de mes projets) -->
        <article class="rounded-[2.5rem] bg-white p-8 shadow-sm border border-slate-100">
          <h3 class="text-xl font-black text-slate-800">Progression</h3>
          <p class="mb-8 text-xs font-medium text-slate-400">État d'avancement de vos projets</p>
          
          <div class="space-y-5">
            <div v-for="(count, statut) in projetsParStatut" :key="statut">
              <div class="mb-1.5 flex justify-between px-1">
                <span class="text-[10px] font-black uppercase text-slate-500">{{ statutLabel[statut] }}</span>
                <span class="text-[10px] font-black text-slate-900">{{ count }}</span>
              </div>
              <div class="h-2 w-full overflow-hidden rounded-full bg-slate-50">
                <div class="h-full rounded-full bg-[#d6e87a] transition-all duration-1000 ease-out"
                     :style="{ width: stats.projets ? `${(count/stats.projets)*100}%` : '0%' }">
                </div>
              </div>
            </div>
          </div>
        </article>

        <!-- Mes étudiants -->
        <article class="rounded-[2.5rem] bg-slate-900 p-8 shadow-xl text-white">
          <div class="flex items-start justify-between gap-3 mb-6">
            <div>
              <h3 class="text-xl font-black">Mes étudiants</h3>
              <p class="mt-1 text-xs font-medium text-white/40">{{ etudiantsEncadres.length }} encadré{{ etudiantsEncadres.length > 1 ? 's' : '' }}</p>
            </div>
            <button @click="router.push('/professeur/etudiants')"
              class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 hover:bg-[#d6e87a] hover:text-slate-900 transition-colors">
              <i class="fa-solid fa-arrow-right text-xs"></i>
            </button>
          </div>
          
          <div v-if="etudiantsEncadres.length === 0" class="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-white/50 text-center">
            Aucun étudiant encadré pour le moment.
          </div>
          <div v-else class="space-y-3">
            <div v-for="p in etudiantsEncadres.slice(0, 5)" :key="p.id"
              class="flex items-center gap-4 rounded-2xl bg-white/5 p-3 border border-white/5 hover:bg-white/10 transition-colors">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#d6e87a] text-slate-900 font-black text-xs">
                {{ (p.etudiant?.utilisateur?.prenom || 'E')[0] }}{{ (p.etudiant?.utilisateur?.nom || '')[0] }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold truncate text-white">
                  {{ p.etudiant?.utilisateur?.prenom }} {{ p.etudiant?.utilisateur?.nom }}
                </p>
                <p class="mt-0.5 text-[10px] text-white/50 truncate">{{ p.projet?.titre }}</p>
              </div>
            </div>
          </div>
        </article>

        <!-- Quick actions -->
        <article class="rounded-[2.5rem] bg-white p-8 shadow-sm border border-slate-100">
          <h3 class="text-xl font-black text-slate-800">Actions rapides</h3>
          <p class="mb-6 text-xs font-medium text-slate-400">Raccourcis vers vos outils</p>
          
          <div class="space-y-3">
            <button @click="router.push('/professeur/projets')"
              class="group flex w-full items-center gap-4 rounded-2xl border border-slate-100 p-3 hover:border-[#1e4a49] hover:bg-slate-50 transition-all">
              <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#1e4a49]/5 text-[#1e4a49] group-hover:bg-[#1e4a49] group-hover:text-[#d6e87a] transition-colors">
                <i class="fa-solid fa-folder-plus text-lg"></i>
              </div>
              <div class="text-left">
                <p class="text-sm font-bold text-slate-800">Proposer un projet</p>
                <p class="mt-0.5 text-[10px] font-medium text-slate-400">Créer un nouveau sujet PFE</p>
              </div>
            </button>
        
            <button @click="router.push('/professeur/postulations')"
              class="group flex w-full items-center gap-4 rounded-2xl border border-slate-100 p-3 hover:border-[#1e4a49] hover:bg-slate-50 transition-all">
              <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                <i class="fa-solid fa-file-signature text-lg"></i>
              </div>
              <div class="text-left">
                <p class="text-sm font-bold text-slate-800">Voir les candidatures</p>
                <p class="mt-0.5 text-[10px] font-medium text-slate-400">Gérer les demandes reçues</p>
              </div>
            </button>
          </div>
        </article>
      </aside>
    </div>
  </div>
</template>
