<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const profId = computed(() => user.value?.professeur?.id)

const items = ref([])
const projets = ref([])
const loading = ref(false)
const search = ref('')
const selected = ref(null) // selected student group

async function fetchAll() {
  loading.value = true
  try {
    const me = await api.get('/me')
    user.value = me.data?.data || {}
    localStorage.setItem('admin_user', JSON.stringify(user.value))
    if (!profId.value) { loading.value = false; return }
    const [de, pr] = await Promise.all([api.get('/depots'), api.get('/projets')])
    const myProjetIds = pr.data.data.filter(p => p.professeur_id === profId.value).map(p => p.id)
    items.value = de.data.data.filter(d => myProjetIds.includes(d.projet_id))
    projets.value = pr.data.data.filter(p => p.professeur_id === profId.value)
  } catch {}
  loading.value = false
}

// Group depots by student
const studentGroups = computed(() => {
  const groups = {}
  let list = items.value
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(d =>
      d.etudiant?.utilisateur?.nom?.toLowerCase().includes(q) ||
      d.etudiant?.utilisateur?.prenom?.toLowerCase().includes(q) ||
      d.projet?.titre?.toLowerCase().includes(q)
    )
  }
  list.forEach(d => {
    const eid = d.etudiant_id
    if (!groups[eid]) {
      groups[eid] = {
        etudiant: d.etudiant,
        projet: d.projet,
        depots: [],
      }
    }
    groups[eid].depots.push(d)
  })
  return Object.values(groups)
})

const REQUIRED_TYPES = ['rapport', 'donnees', 'presentation']

function completionOf(depots) {
  const valide = REQUIRED_TYPES.filter(t => depots.some(d => d.type_depot === t && d.statut_validation === 'valide'))
  return Math.round((valide.length / REQUIRED_TYPES.length) * 100)
}

function pendingCount(depots) {
  return depots.filter(d => d.statut_validation === 'en_attente').length
}

function initials(etudiant) {
  const p = etudiant?.utilisateur?.prenom || '?'
  const n = etudiant?.utilisateur?.nom || '?'
  return p[0] + n[0]
}

async function valider(id) {
  if (!confirm('Valider ce dépôt ?')) return
  try {
    await api.post(`/depots/${id}/valider`)
    await fetchAll()
    // refresh selected group
    if (selected.value) {
      const eid = selected.value.etudiant?.id
      selected.value = studentGroups.value.find(g => g.etudiant?.id === eid) || null
    }
  } catch (e) { alert(e.response?.data?.message || 'Erreur') }
}

async function rejeter(id) {
  const c = prompt('Commentaire de rejet (optionnel) :')
  if (c === null) return
  try {
    await api.post(`/depots/${id}/rejeter`, c ? { commentaire: c } : {})
    await fetchAll()
    if (selected.value) {
      const eid = selected.value.etudiant?.id
      selected.value = studentGroups.value.find(g => g.etudiant?.id === eid) || null
    }
  } catch (e) { alert(e.response?.data?.message || 'Erreur') }
}

const statutLabel = { en_attente: 'En attente', valide: 'Validé', rejete: 'Rejeté' }
const statutColor = { en_attente: 'bg-amber-100 text-amber-700', valide: 'bg-green-100 text-green-700', rejete: 'bg-red-100 text-red-600' }
const statutDot   = { en_attente: 'bg-amber-400', valide: 'bg-green-500', rejete: 'bg-red-400' }

const typeIcon = {
  rapport:      { icon: 'fa-file-pdf',         color: 'bg-red-50 text-red-500',    label: 'Rapport' },
  donnees:      { icon: 'fa-file-code',         color: 'bg-blue-50 text-blue-500',  label: 'Données' },
  presentation: { icon: 'fa-file-powerpoint',   color: 'bg-orange-50 text-orange-500', label: 'Présentation' },
}
function ti(t) { return typeIcon[t] || { icon: 'fa-file', color: 'bg-slate-100 text-slate-500', label: t } }

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
}

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-6 text-white relative overflow-hidden">
      <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/5"></div>
      <div class="absolute -bottom-6 right-24 h-24 w-24 rounded-full bg-[#d6e87a]/10"></div>
      <div class="relative">
        <p class="text-[11px] font-bold uppercase tracking-widest text-[#d6e87a]">Suivi des soumissions</p>
        <h1 class="mt-1 text-2xl font-black">Dépôts des étudiants</h1>
        <p class="mt-1 text-sm text-white/60">{{ studentGroups.length }} étudiant{{ studentGroups.length !== 1 ? 's' : '' }} · {{ items.length }} dépôt{{ items.length !== 1 ? 's' : '' }}</p>
      </div>
    </div>

    <!-- Search -->
    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher un étudiant ou projet…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <div v-if="loading" class="rounded-3xl border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400">Chargement…</div>

    <div v-else-if="studentGroups.length === 0" class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-white/60 py-20 text-center">
      <i class="fa-solid fa-cloud-arrow-up text-5xl text-slate-300"></i>
      <p class="mt-4 text-base font-extrabold text-slate-700">Aucun dépôt</p>
      <p class="text-sm text-slate-400 mt-1">Vos étudiants n'ont pas encore soumis de fichiers.</p>
    </div>

    <!-- Student cards grid -->
    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="g in studentGroups" :key="g.etudiant?.id"
        @click="selected = g"
        class="group cursor-pointer flex flex-col rounded-3xl border border-white/70 bg-white/90 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#d6e87a] hover:shadow-lg overflow-hidden"
      >
        <!-- Top accent with completion bar -->
        <div class="h-1.5 w-full bg-slate-100 relative shrink-0">
          <div class="absolute inset-y-0 left-0 bg-[#d6e87a] transition-all duration-500" :style="`width:${completionOf(g.depots)}%`"></div>
        </div>

        <div class="p-8 flex flex-col gap-4">
          <!-- Student info -->
          <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#d6e87a] text-sm font-black text-[#4a5e20]">
              {{ initials(g.etudiant) }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-extrabold text-slate-900 truncate">{{ g.etudiant?.utilisateur?.prenom }} {{ g.etudiant?.utilisateur?.nom }}</p>
              <p class="text-xs text-slate-400 truncate">{{ g.projet?.titre || '—' }}</p>
            </div>
            <!-- Pending badge -->
            <div v-if="pendingCount(g.depots)" class="flex h-6 min-w-6 items-center justify-center rounded-full bg-amber-400 px-1.5 text-[10px] font-black text-white">
              {{ pendingCount(g.depots) }}
            </div>
          </div>

          <!-- Depot type pills -->
          <div class="flex flex-wrap gap-2">
            <div v-for="type in REQUIRED_TYPES" :key="type" class="flex items-center gap-1.5">
              <template v-if="g.depots.find(d => d.type_depot === type)">
                <span
                  v-for="d in g.depots.filter(d => d.type_depot === type)" :key="d.id"
                  class="flex items-center gap-1.5 rounded-xl px-2.5 py-1 text-[10px] font-bold"
                  :class="statutColor[d.statut_validation]"
                >
                  <span class="h-1.5 w-1.5 rounded-full" :class="statutDot[d.statut_validation]"></span>
                  {{ ti(type).label }}
                </span>
              </template>
              <span v-else class="flex items-center gap-1.5 rounded-xl bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-400">
                <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>{{ ti(type).label }}
              </span>
            </div>
          </div>

          <!-- Completion + arrow -->
          <div class="flex items-center justify-between border-t border-slate-100 pt-3">
            <div>
              <span class="text-xs font-bold text-slate-700">{{ completionOf(g.depots) }}%</span>
              <span class="ml-1 text-[10px] text-slate-400">complété</span>
            </div>
            <span class="text-[10px] font-bold text-slate-400">{{ g.depots.length }} fichier{{ g.depots.length !== 1 ? 's' : '' }} <i class="fa-solid fa-chevron-right ml-1 text-[8px] group-hover:text-[#1e4a49] transition"></i></span>
          </div>
        </div>
      </article>
    </div>

    <!-- DEPOT DETAIL MODAL -->
    <Teleport to="body">
      <div v-if="selected" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="selected = null"></div>
        <div class="relative z-10 flex w-full max-w-2xl flex-col rounded-3xl bg-white shadow-2xl overflow-hidden" style="max-height:88vh">

          <!-- Header -->
          <div class="flex items-center justify-between bg-[#1e4a49] px-6 py-5 shrink-0">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#d6e87a] text-sm font-black text-[#4a5e20]">
                {{ initials(selected.etudiant) }}
              </div>
              <div>
                <p class="font-extrabold text-white">{{ selected.etudiant?.utilisateur?.prenom }} {{ selected.etudiant?.utilisateur?.nom }}</p>
                <p class="text-xs text-white/50">{{ selected.projet?.titre }}</p>
              </div>
            </div>
            <button @click="selected = null" class="flex h-8 w-8 items-center justify-center rounded-xl text-white/60 hover:bg-white/10 transition">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- Progress bar -->
          <div class="h-1.5 w-full bg-slate-100 shrink-0">
            <div class="h-full bg-[#d6e87a] transition-all duration-500" :style="`width:${completionOf(selected.depots)}%`"></div>
          </div>

          <!-- Depot list -->
          <div class="flex-1 overflow-y-auto p-6 space-y-3">
            <div class="flex items-center justify-between mb-2">
              <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">{{ selected.depots.length }} dépôt{{ selected.depots.length !== 1 ? 's' : '' }}</p>
              <span class="text-xs font-bold text-slate-500">{{ completionOf(selected.depots) }}% validé</span>
            </div>

            <div v-for="d in selected.depots" :key="d.id"
              class="flex items-center gap-4 rounded-2xl border border-slate-100 bg-slate-50 px-5 py-4">

              <!-- Type icon -->
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl" :class="ti(d.type_depot).color">
                <i :class="`fa-solid ${ti(d.type_depot).icon} text-base`"></i>
              </div>

              <!-- Info -->
              <div class="flex-1 min-w-0">
                <p class="font-bold text-slate-800">{{ ti(d.type_depot).label }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ formatDate(d.depose_le || d.created_at) }}</p>
                <p v-if="d.commentaire" class="mt-1.5 rounded-lg bg-red-50 border border-red-100 px-2.5 py-1.5 text-xs italic text-red-600">
                  « {{ d.commentaire }} »
                </p>
              </div>

              <!-- Status + actions -->
              <div class="flex flex-col items-end gap-2 shrink-0">
                <span class="rounded-lg px-2.5 py-1 text-[10px] font-bold" :class="statutColor[d.statut_validation]">
                  {{ statutLabel[d.statut_validation] }}
                </span>
                <a :href="d.chemin_fichier" target="_blank" rel="noopener"
                  class="flex items-center gap-1 text-[11px] font-bold text-slate-400 hover:text-[#1e4a49] transition">
                  <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> Ouvrir
                </a>
              </div>

              <!-- Validate / Reject buttons -->
              <div v-if="d.statut_validation === 'en_attente'" class="flex flex-col gap-1.5 shrink-0">
                <button @click="valider(d.id)"
                  class="flex items-center gap-1.5 rounded-xl bg-green-50 border border-green-100 px-3 py-1.5 text-xs font-bold text-green-700 hover:bg-green-100 transition">
                  <i class="fa-solid fa-check text-[10px]"></i> Valider
                </button>
                <button @click="rejeter(d.id)"
                  class="flex items-center gap-1.5 rounded-xl bg-red-50 border border-red-100 px-3 py-1.5 text-xs font-bold text-red-500 hover:bg-red-100 transition">
                  <i class="fa-solid fa-xmark text-[10px]"></i> Rejeter
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </Teleport>

  </div>
</template>
