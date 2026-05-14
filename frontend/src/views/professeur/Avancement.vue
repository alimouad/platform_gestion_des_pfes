<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const projets     = ref([])
const postulations = ref([])
const depots      = ref([])
const soutenances = ref([])
const loading     = ref(false)
const search      = ref('')
const selected    = ref(null)

const REQUIRED_DEPOTS = ['rapport', 'donnees', 'presentation']

async function fetchAll() {
  loading.value = true
  try {
    const me = await api.get('/me')
    user.value = me.data?.data || {}
    localStorage.setItem('admin_user', JSON.stringify(user.value))

    const profId = Number(user.value?.professeur?.id)
    if (!profId) { loading.value = false; return }

    const [pr, po, de, so] = await Promise.all([
      api.get('/projets'),
      api.get('/postulations'),
      api.get('/depots'),
      api.get('/soutenances'),
    ])

    projets.value      = (pr.data.data || []).filter(p => Number(p.professeur_id) === profId)
    const ids          = projets.value.map(p => Number(p.id))
    postulations.value = (po.data.data || []).filter(p => ids.includes(Number(p.projet_id)))
    depots.value       = (de.data.data || []).filter(d => ids.includes(Number(d.projet_id)))
    soutenances.value  = (so.data.data || []).filter(s => ids.includes(Number(s.projet_id)))
  } catch (e) {
    console.error(e)
  }
  loading.value = false
}

// Per-project computed data
const projetCards = computed(() => {
  const q = search.value.toLowerCase()
  return projets.value
    .filter(p => !q || p.titre?.toLowerCase().includes(q) || p.domaine?.toLowerCase().includes(q))
    .map(p => {
      const pid = Number(p.id)
      const myDepots = depots.value.filter(d => Number(d.projet_id) === pid)
      const myPost   = postulations.value.filter(x => Number(x.projet_id) === pid)
      const accepted = myPost.find(x => x.statut === 'accepte')
      const soutenance = soutenances.value.find(s => Number(s.projet_id) === pid)

      // Depot progress
      const depotsValides = REQUIRED_DEPOTS.filter(t =>
        myDepots.some(d => d.type_depot === t && d.statut_validation === 'valide')
      )
      const depotsEnAttente = myDepots.filter(d => d.statut_validation === 'en_attente').length
      const depotPct = Math.round((depotsValides.length / REQUIRED_DEPOTS.length) * 100)

      // Overall stage
      let stage = 0
      if (accepted)                   stage = 1
      if (depotsValides.length > 0)   stage = 2
      if (depotPct === 100)           stage = 3
      if (soutenance)                 stage = 4
      if (soutenance?.statut === 'terminee') stage = 5

      return { ...p, myDepots, myPost, accepted, soutenance, depotsValides, depotsEnAttente, depotPct, stage }
    })
})

const stages = [
  { label: 'Projet créé',       icon: 'fa-folder-plus',      color: 'bg-slate-100 text-slate-500',    active: 'bg-[#1e4a49] text-white' },
  { label: 'Étudiant assigné',  icon: 'fa-user-check',       color: 'bg-slate-100 text-slate-500',    active: 'bg-blue-600 text-white' },
  { label: 'Dépôts en cours',   icon: 'fa-cloud-arrow-up',   color: 'bg-slate-100 text-slate-500',    active: 'bg-amber-500 text-white' },
  { label: 'Dépôts validés',    icon: 'fa-circle-check',     color: 'bg-slate-100 text-slate-500',    active: 'bg-[#4a7a30] text-white' },
  { label: 'Soutenance planif.', icon: 'fa-calendar-check',  color: 'bg-slate-100 text-slate-500',    active: 'bg-purple-600 text-white' },
  { label: 'Soutenu',           icon: 'fa-graduation-cap',   color: 'bg-slate-100 text-slate-500',    active: 'bg-[#d6e87a] text-[#1e4a49]' },
]

const statutProjet = {
  soumis:   { label: 'Soumis',   badge: 'bg-blue-100 text-blue-700' },
  en_cours: { label: 'En cours', badge: 'bg-amber-100 text-amber-700' },
  valide:   { label: 'Validé',   badge: 'bg-green-100 text-green-700' },
  soutenu:  { label: 'Soutenu',  badge: 'bg-[#f0f5e0] text-[#4a7a30]' },
  rejete:   { label: 'Rejeté',   badge: 'bg-red-100 text-red-600' },
}
function sp(s) { return statutProjet[s] || { label: s, badge: 'bg-slate-100 text-slate-500' } }

const typeDepotConfig = {
  rapport:      { label: 'Rapport',       icon: 'fa-file-pdf',        color: 'text-red-500' },
  donnees:      { label: 'Données',       icon: 'fa-file-code',       color: 'text-blue-500' },
  presentation: { label: 'Présentation',  icon: 'fa-file-powerpoint', color: 'text-orange-500' },
}

function fmtDate(d) {
  return d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
}

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-6 text-white relative overflow-hidden">
      <div class="absolute -right-8 -top-8 h-44 w-44 rounded-full bg-white/5"></div>
      <div class="absolute -bottom-6 right-28 h-28 w-28 rounded-full bg-[#d6e87a]/10"></div>
      <div class="relative">
        <p class="text-[11px] font-black uppercase tracking-widest text-[#d6e87a]">Tableau de bord pédagogique</p>
        <h1 class="mt-1 text-2xl font-black">Suivi de l'avancement des projets</h1>
        <p class="mt-1 text-sm text-white/60">{{ projets.length }} projet{{ projets.length !== 1 ? 's' : '' }} encadrés</p>
      </div>
    </div>

    <!-- Search -->
    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher un projet ou domaine…"
        class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <div v-if="loading" class="rounded-3xl border border-white/70 bg-white/90 p-16 text-center text-sm text-slate-400">Chargement…</div>

    <div v-else-if="projetCards.length === 0" class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-white/60 py-24 text-center">
      <i class="fa-solid fa-chart-line text-5xl text-slate-300"></i>
      <p class="mt-4 text-base font-extrabold text-slate-700">Aucun projet à suivre</p>
      <p class="mt-1 text-sm text-slate-400">Vos projets apparaîtront ici dès qu'ils seront créés.</p>
    </div>

    <!-- Project cards -->
    <div v-else class="space-y-4">
      <article v-for="p in projetCards" :key="p.id"
        @click="selected = p"
        class="group cursor-pointer rounded-3xl border border-white/70 bg-white/90 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#d6e87a] hover:shadow-lg overflow-hidden">

        <!-- Progress bar top -->
        <div class="h-1.5 w-full bg-slate-100 relative">
          <div class="absolute inset-y-0 left-0 transition-all duration-700 rounded-full"
            :class="p.depotPct === 100 ? 'bg-[#d6e87a]' : p.depotPct > 0 ? 'bg-amber-400' : 'bg-slate-200'"
            :style="`width:${Math.max(p.depotPct, p.stage * 20)}%`"></div>
        </div>

        <div class="p-6">
          <div class="flex flex-wrap items-start gap-4">

            <!-- Left: info -->
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-black" :class="sp(p.statut).badge">
                  {{ sp(p.statut).label }}
                </span>
                <span v-if="p.domaine" class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-500">
                  {{ p.domaine }}
                </span>
                <span v-if="p.depotsEnAttente > 0" class="flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-black text-amber-700">
                  <i class="fa-solid fa-clock text-[8px]"></i> {{ p.depotsEnAttente }} en attente
                </span>
              </div>
              <h3 class="text-base font-black text-slate-900 leading-snug line-clamp-1">{{ p.titre }}</h3>

              <!-- Student -->
              <div class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                <i class="fa-solid fa-user-graduate text-slate-300"></i>
                <span v-if="p.accepted">
                  <span class="font-bold text-slate-700">
                    {{ p.accepted.etudiant?.utilisateur?.prenom }} {{ p.accepted.etudiant?.utilisateur?.nom }}
                  </span>
                </span>
                <span v-else class="italic text-slate-400">Aucun étudiant assigné</span>
              </div>

              <!-- Soutenance -->
              <div v-if="p.soutenance" class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                <i class="fa-solid fa-calendar-check text-purple-400"></i>
                <span class="font-semibold text-purple-700">Soutenance : {{ fmtDate(p.soutenance.date) }}
                  <span v-if="p.soutenance.heure">à {{ p.soutenance.heure.slice(0,5) }}</span>
                </span>
              </div>
            </div>

            <!-- Right: depot progress circles -->
            <div class="flex items-center gap-3">
              <div v-for="type in REQUIRED_DEPOTS" :key="type" class="flex flex-col items-center gap-1">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl border-2 transition-all"
                  :class="p.depotsValides.includes(type)
                    ? 'border-[#d6e87a] bg-[#f0f5e0] text-[#4a7a30]'
                    : p.myDepots.some(d => d.type_depot === type)
                      ? 'border-amber-300 bg-amber-50 text-amber-600'
                      : 'border-slate-200 bg-slate-50 text-slate-300'">
                  <i :class="`fa-solid ${typeDepotConfig[type]?.icon || 'fa-file'} text-xs`"></i>
                </div>
                <span class="text-[8px] font-bold uppercase tracking-wide text-slate-400">{{ typeDepotConfig[type]?.label }}</span>
              </div>

              <!-- Pct -->
              <div class="ml-2 flex flex-col items-center justify-center w-14 h-14 rounded-2xl border-2"
                :class="p.depotPct === 100 ? 'border-[#d6e87a] bg-[#f0f5e0]' : 'border-slate-200 bg-slate-50'">
                <span class="text-lg font-black" :class="p.depotPct === 100 ? 'text-[#4a7a30]' : 'text-slate-500'">{{ p.depotPct }}%</span>
                <span class="text-[8px] font-bold text-slate-400">validé</span>
              </div>
            </div>
          </div>

          <!-- Stage pipeline -->
          <div class="mt-5 flex items-center gap-0">
            <template v-for="(st, i) in stages" :key="i">
              <div class="flex flex-col items-center gap-1 flex-1">
                <div class="flex h-8 w-8 items-center justify-center rounded-full transition-all"
                  :class="i <= p.stage ? st.active : st.color">
                  <i :class="`fa-solid ${st.icon} text-[10px]`"></i>
                </div>
                <span class="text-[8px] font-bold text-center leading-tight text-slate-400 w-16">{{ st.label }}</span>
              </div>
              <div v-if="i < stages.length - 1"
                class="h-0.5 flex-1 mb-4 transition-all"
                :class="i < p.stage ? 'bg-[#d6e87a]' : 'bg-slate-200'"></div>
            </template>
          </div>
        </div>

        <!-- Click hint -->
        <div class="border-t border-slate-100 px-6 py-2.5 flex justify-end">
          <span class="text-[10px] font-bold text-slate-300 group-hover:text-[#1e4a49] transition">
            Voir le détail <i class="fa-solid fa-chevron-right text-[8px] ml-1"></i>
          </span>
        </div>
      </article>
    </div>

    <!-- DETAIL MODAL -->
    <Teleport to="body">
      <div v-if="selected" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="selected = null"></div>
        <div class="relative z-10 w-full max-w-2xl rounded-3xl bg-white shadow-2xl overflow-hidden" style="max-height:90vh;overflow-y:auto">

          <!-- Modal header -->
          <div class="bg-[#1e4a49] px-6 py-5 flex items-start justify-between sticky top-0 z-10">
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-[#d6e87a]">Avancement projet</p>
              <h2 class="mt-0.5 text-base font-black text-white leading-snug line-clamp-2">{{ selected.titre }}</h2>
            </div>
            <button @click="selected = null" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-white/60 hover:bg-white/10 transition ml-3">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="p-6 space-y-5">

            <!-- Stage pipeline full -->
            <div class="flex items-center gap-0">
              <template v-for="(st, i) in stages" :key="i">
                <div class="flex flex-col items-center gap-1.5 flex-1">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full shadow-sm transition-all"
                    :class="i <= selected.stage ? st.active : st.color">
                    <i :class="`fa-solid ${st.icon} text-xs`"></i>
                  </div>
                  <span class="text-[9px] font-bold text-center leading-tight text-slate-500 w-16">{{ st.label }}</span>
                </div>
                <div v-if="i < stages.length - 1"
                  class="h-0.5 flex-1 mb-5"
                  :class="i < selected.stage ? 'bg-[#d6e87a]' : 'bg-slate-200'"></div>
              </template>
            </div>

            <!-- Student info -->
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
              <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Étudiant assigné</p>
              <div v-if="selected.accepted" class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#d6e87a] text-sm font-black text-[#1e4a49]">
                  {{ (selected.accepted.etudiant?.utilisateur?.prenom?.[0] || '') + (selected.accepted.etudiant?.utilisateur?.nom?.[0] || '') }}
                </div>
                <div>
                  <p class="font-black text-slate-900">{{ selected.accepted.etudiant?.utilisateur?.prenom }} {{ selected.accepted.etudiant?.utilisateur?.nom }}</p>
                  <p class="text-xs text-slate-400">{{ selected.accepted.etudiant?.utilisateur?.courriel }}</p>
                </div>
              </div>
              <p v-else class="text-sm italic text-slate-400">Aucun étudiant assigné pour l'instant.</p>
            </div>

            <!-- Depots -->
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
              <div class="flex items-center justify-between mb-3">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Dépôts</p>
                <span class="text-xs font-black" :class="selected.depotPct === 100 ? 'text-[#4a7a30]' : 'text-slate-500'">
                  {{ selected.depotPct }}% validé
                </span>
              </div>

              <div v-if="selected.myDepots.length === 0" class="text-sm italic text-slate-400">Aucun fichier déposé.</div>

              <div v-else class="space-y-2">
                <div v-for="d in selected.myDepots" :key="d.id"
                  class="flex items-center gap-3 rounded-xl bg-white border border-slate-100 px-4 py-3">
                  <i :class="`fa-solid ${typeDepotConfig[d.type_depot]?.icon || 'fa-file'} ${typeDepotConfig[d.type_depot]?.color || 'text-slate-400'}`"></i>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-black text-slate-800">{{ typeDepotConfig[d.type_depot]?.label || d.type_depot }}</p>
                    <p class="text-[10px] text-slate-400">{{ fmtDate(d.depose_le || d.created_at) }}</p>
                  </div>
                  <span class="rounded-lg px-2.5 py-1 text-[10px] font-black"
                    :class="d.statut_validation === 'valide' ? 'bg-green-100 text-green-700' : d.statut_validation === 'rejete' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-700'">
                    {{ d.statut_validation === 'valide' ? 'Validé' : d.statut_validation === 'rejete' ? 'Rejeté' : 'En attente' }}
                  </span>
                </div>
              </div>

              <!-- Missing types -->
              <div class="mt-3 flex flex-wrap gap-2">
                <span v-for="type in REQUIRED_DEPOTS.filter(t => !selected.myDepots.some(d => d.type_depot === t))" :key="type"
                  class="flex items-center gap-1.5 rounded-xl bg-slate-100 px-3 py-1.5 text-[10px] font-bold text-slate-400">
                  <i :class="`fa-solid ${typeDepotConfig[type]?.icon} text-slate-300`"></i>
                  {{ typeDepotConfig[type]?.label }} manquant
                </span>
              </div>
            </div>

            <!-- Soutenance -->
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
              <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Soutenance</p>
              <div v-if="selected.soutenance" class="grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-white border border-slate-100 px-3 py-2.5">
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide">Date</p>
                  <p class="text-sm font-black text-slate-800">{{ fmtDate(selected.soutenance.date) }}</p>
                </div>
                <div class="rounded-xl bg-white border border-slate-100 px-3 py-2.5">
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide">Heure</p>
                  <p class="text-sm font-black text-slate-800">{{ selected.soutenance.heure?.slice(0,5) || '—' }}</p>
                </div>
                <div class="rounded-xl bg-white border border-slate-100 px-3 py-2.5">
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide">Salle</p>
                  <p class="text-sm font-black text-slate-800">{{ selected.soutenance.salle || '—' }}</p>
                </div>
                <div class="rounded-xl bg-white border border-slate-100 px-3 py-2.5">
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide">Statut</p>
                  <p class="text-sm font-black text-slate-800 capitalize">{{ selected.soutenance.statut }}</p>
                </div>
                <div v-if="selected.soutenance.note_finale != null" class="col-span-2 rounded-xl border-2 border-[#d6e87a] bg-[#f8faef] px-4 py-3 text-center">
                  <p class="text-[10px] font-black text-[#6a8a40] uppercase tracking-widest">Note finale</p>
                  <p class="text-4xl font-black text-[#1e4a49]">{{ selected.soutenance.note_finale }}<span class="text-xl text-slate-400">/20</span></p>
                </div>
              </div>
              <p v-else class="text-sm italic text-slate-400">Aucune soutenance planifiée.</p>
            </div>

          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>
