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

const groupedByFiliere = computed(() => {
  const groups = {}
  for (const p of filtered.value) {
    const key = p.etudiant?.filiere?.nom || 'Sans filière'
    if (!groups[key]) groups[key] = []
    groups[key].push(p)
  }
  return Object.entries(groups).sort(([a], [b]) => a.localeCompare(b))
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

// ── Affectation directe ───────────────────────────────────────
const showAffecter   = ref(false)
const etudiants      = ref([])
const projets        = ref([])
const affectForm     = ref({ etudiant_id: '', projet_id: '' })
const affectError    = ref('')
const affectLoading  = ref(false)

async function openAffecter() {
  showAffecter.value = true
  affectError.value  = ''
  affectForm.value   = { etudiant_id: '', projet_id: '' }
  try {
    const [e, p] = await Promise.all([api.get('/etudiants'), api.get('/projets')])
    etudiants.value = e.data.data
    projets.value   = p.data.data.filter(p => ['soumis','brouillon','en_cours'].includes(p.statut))
  } catch {}
}

async function submitAffecter() {
  if (!affectForm.value.etudiant_id || !affectForm.value.projet_id) return
  affectLoading.value = true
  affectError.value   = ''
  try {
    await api.post('/postulations/affecter', affectForm.value)
    showAffecter.value = false
    await fetchAll()
  } catch (e) {
    affectError.value = e.response?.data?.message || 'Erreur'
  }
  affectLoading.value = false
}

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Postulations</h1>
        <p class="text-sm text-slate-400">{{ items.length }} candidatures au total</p>
      </div>
      <button @click="openAffecter"
        class="flex items-center gap-2 rounded-2xl bg-[#1e4a49] px-5 py-2.5 text-sm font-black text-[#d6e87a] hover:bg-[#163836] transition">
        <i class="fa-solid fa-user-plus"></i> Affecter un étudiant
      </button>
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
    <div v-else class="space-y-6">
      <section v-for="([filiere, posts]) in groupedByFiliere" :key="filiere">
        <div class="flex items-center gap-3 mb-3">
          <div class="flex items-center gap-2 rounded-2xl bg-[#1e4a49]/10 px-4 py-1.5">
            <i class="fa-solid fa-layer-group text-[#1e4a49] text-xs"></i>
            <span class="text-xs font-bold text-[#1e4a49]">{{ filiere }}</span>
            <span class="rounded-md bg-[#1e4a49] text-[#d6e87a] px-1.5 py-0.5 text-[10px] font-bold">{{ posts.length }}</span>
          </div>
          <div class="flex-1 h-px bg-slate-200"></div>
        </div>
        <div class="space-y-3">
      <article v-for="p in posts" :key="p.id"
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
      </section>
    </div>

    <!-- Modal : Affectation directe -->
    <Teleport to="body">
      <div v-if="showAffecter" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-3xl bg-white shadow-2xl overflow-hidden">

          <div class="bg-[#1e4a49] px-6 py-5 flex items-center justify-between">
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-[#d6e87a]">Coordinateur</p>
              <h2 class="text-base font-black text-white">Affecter un étudiant à un projet</h2>
            </div>
            <button @click="showAffecter = false" class="flex h-8 w-8 items-center justify-center rounded-xl text-white/60 hover:bg-white/10 transition">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <form @submit.prevent="submitAffecter" class="p-6 space-y-4">
            <div v-if="affectError" class="rounded-2xl bg-red-50 border border-red-100 px-4 py-3 text-sm font-bold text-red-600">
              <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ affectError }}
            </div>

            <div>
              <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Étudiant</label>
              <select v-model="affectForm.etudiant_id" required
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-[#1e4a49] transition">
                <option value="">— Sélectionner un étudiant —</option>
                <option v-for="e in etudiants" :key="e.id" :value="e.id">
                  {{ e.utilisateur?.prenom }} {{ e.utilisateur?.nom }} — {{ e.code_etudiant }} ({{ e.filiere?.nom || 'Sans filière' }})
                </option>
              </select>
            </div>

            <div>
              <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Projet PFE</label>
              <select v-model="affectForm.projet_id" required
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-[#1e4a49] transition">
                <option value="">— Sélectionner un projet —</option>
                <option v-for="p in projets" :key="p.id" :value="p.id">
                  {{ p.titre }} — {{ p.professeur?.utilisateur?.prenom }} {{ p.professeur?.utilisateur?.nom }}
                </option>
              </select>
            </div>

            <div class="rounded-2xl bg-amber-50 border border-amber-100 px-4 py-3 text-xs text-amber-700">
              <i class="fa-solid fa-triangle-exclamation mr-1.5"></i>
              Cette affectation est directe et immédiate. L'étudiant sera notifié automatiquement.
            </div>

            <div class="flex gap-3 pt-1">
              <button type="button" @click="showAffecter = false"
                class="flex-1 rounded-2xl border border-slate-200 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">
                Annuler
              </button>
              <button type="submit" :disabled="affectLoading || !affectForm.etudiant_id || !affectForm.projet_id"
                class="flex-1 rounded-2xl bg-[#1e4a49] py-3 text-sm font-black text-white hover:bg-[#163836] transition disabled:opacity-50 flex items-center justify-center gap-2">
                <i v-if="affectLoading" class="fa-solid fa-circle-notch fa-spin"></i>
                <i v-else class="fa-solid fa-user-check"></i>
                {{ affectLoading ? 'Affectation…' : 'Confirmer l\'affectation' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
