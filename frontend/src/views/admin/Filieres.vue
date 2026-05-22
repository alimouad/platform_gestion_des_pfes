<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'

const items       = ref([])
const departements = ref([])
const loading     = ref(false)
const search      = ref('')
const showModal   = ref(false)
const editing     = ref(null)
const error       = ref('')
const defaultForm = () => ({ nom: '', description: '', departement_id: '' })
const form        = ref(defaultForm())

const filtered = computed(() => {
  if (!search.value) return items.value
  const q = search.value.toLowerCase()
  return items.value.filter(f => f.nom.toLowerCase().includes(q) || f.departement?.nom?.toLowerCase().includes(q))
})

async function fetchAll() {
  loading.value = true
  try {
    const [f, d] = await Promise.all([api.get('/filieres'), api.get('/departements')])
    items.value       = f.data.data || []
    departements.value = d.data.data || []
  } catch {}
  loading.value = false
}

function openCreate() { editing.value = null; form.value = defaultForm(); error.value = ''; showModal.value = true }
function openEdit(f)  { editing.value = f; form.value = { nom: f.nom, description: f.description || '', departement_id: f.departement_id || '' }; error.value = ''; showModal.value = true }
function closeModal() { showModal.value = false }

async function save() {
  error.value = ''
  try {
    const payload = { ...form.value, departement_id: form.value.departement_id || null }
    if (editing.value) await api.put(`/filieres/${editing.value.id}`, payload)
    else               await api.post('/filieres', payload)
    showModal.value = false
    await fetchAll()
  } catch (e) {
    error.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat().join(' | ') || 'Erreur'
  }
}

async function remove(id) {
  if (!confirm('Supprimer cette filière ?')) return
  try { await api.delete(`/filieres/${id}`); await fetchAll() } catch {}
}

onMounted(fetchAll)

const palette = ['bg-[#f0f5e0] text-[#4a7a30]', 'bg-blue-50 text-blue-600', 'bg-purple-50 text-purple-600', 'bg-amber-50 text-amber-600', 'bg-rose-50 text-rose-600', 'bg-cyan-50 text-cyan-600']
function colorFor(id) { return palette[(id - 1) % palette.length] }
</script>

<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-6 text-white relative overflow-hidden">
      <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/5"></div>
      <div class="absolute -bottom-6 right-24 h-24 w-24 rounded-full bg-[#d6e87a]/10"></div>
      <div class="relative flex items-center justify-between">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-widest text-[#d6e87a]">Structure académique</p>
          <h1 class="mt-1 text-2xl font-black">Filières</h1>
          <p class="mt-1 text-sm text-white/60">{{ items.length }} filière{{ items.length !== 1 ? 's' : '' }} enregistrée{{ items.length !== 1 ? 's' : '' }}</p>
        </div>
        <button @click="openCreate"
          class="flex items-center gap-2 rounded-2xl bg-[#d6e87a] px-5 py-2.5 text-sm font-black text-[#1e4a49] shadow hover:brightness-105 transition">
          <i class="fa-solid fa-plus"></i> Nouvelle filière
        </button>
      </div>
    </div>

    <!-- Search -->
    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher une filière…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <div v-if="loading" class="rounded-3xl border border-white/70 bg-white/90 p-16 text-center text-sm text-slate-400">Chargement…</div>

    <div v-else-if="filtered.length === 0" class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-white/60 py-20 text-center">
      <i class="fa-solid fa-layer-group text-5xl text-slate-300"></i>
      <p class="mt-4 text-base font-extrabold text-slate-700">Aucune filière trouvée</p>
      <p class="mt-1 text-sm text-slate-400">Créez votre première filière</p>
    </div>

    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article v-for="f in filtered" :key="f.id"
        class="group rounded-3xl border border-white/70 bg-white/90 p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#d6e87a] hover:shadow-lg">

        <div class="mb-5 flex items-start justify-between">
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-xl" :class="colorFor(f.id)">
            <i class="fa-solid fa-layer-group"></i>
          </div>
          <div class="flex gap-2">
            <button @click="openEdit(f)" class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition">
              <i class="fa-solid fa-pen text-xs"></i>
            </button>
            <button @click="remove(f.id)" class="flex h-8 w-8 items-center justify-center rounded-xl bg-red-50 text-red-400 hover:bg-red-100 transition">
              <i class="fa-solid fa-trash text-xs"></i>
            </button>
          </div>
        </div>

        <h3 class="text-base font-black text-slate-900 leading-snug">{{ f.nom }}</h3>
        <p class="mt-1 text-sm text-slate-400 line-clamp-2">{{ f.description || 'Aucune description' }}</p>

        <div class="mt-4 pt-4 border-t border-slate-100 flex items-center gap-2">
          <span v-if="f.departement" class="rounded-xl bg-[#f0f5e0] px-2.5 py-1 text-[11px] font-bold text-[#4a7a30]">
            <i class="fa-solid fa-building-columns mr-1 text-[10px]"></i>{{ f.departement.nom }}
          </span>
          <span v-else class="text-xs text-slate-300 italic">Aucun département</span>
        </div>
      </article>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-3xl bg-white shadow-2xl overflow-hidden">
          <div class="bg-[#1e4a49] px-6 py-5 flex items-center justify-between">
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-[#d6e87a]">Filière</p>
              <h2 class="mt-0.5 text-base font-black text-white">{{ editing ? 'Modifier la filière' : 'Nouvelle filière' }}</h2>
            </div>
            <button @click="closeModal" class="flex h-8 w-8 items-center justify-center rounded-xl text-white/60 hover:bg-white/10 transition">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <form @submit.prevent="save" class="space-y-4 p-6">
            <div v-if="error" class="rounded-2xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">{{ error }}</div>

            <div>
              <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Nom de la filière *</label>
              <input v-model="form.nom" required placeholder="ex: Master Géomatique"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none focus:border-[#d6e87a] focus:bg-white transition" />
            </div>

            <div>
              <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Département</label>
              <select v-model="form.departement_id"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none focus:border-[#d6e87a] focus:bg-white transition">
                <option value="">— Aucun département —</option>
                <option v-for="d in departements" :key="d.id" :value="d.id">{{ d.nom }}</option>
              </select>
            </div>

            <div>
              <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Description</label>
              <textarea v-model="form.description" rows="3" placeholder="Description optionnelle…"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-[#d6e87a] focus:bg-white transition resize-none"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
              <button type="button" @click="closeModal"
                class="flex-1 rounded-2xl border border-slate-200 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">Annuler</button>
              <button type="submit"
                class="flex-1 rounded-2xl bg-[#1e4a49] py-3 text-sm font-black text-white hover:bg-[#163938] transition">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
