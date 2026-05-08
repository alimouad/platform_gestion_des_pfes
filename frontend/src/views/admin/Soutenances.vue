<script setup>
import { onMounted, ref } from 'vue'
import { useCrud } from '@/composables/useCrud'
import api from '@/services/api'

const defaultForm = { projet_id: '', date: '', heure: '', salle: '', statut: 'planifiee', jury: '' }
const { items, loading, search, filtered, showModal, editing, form, error, fetchAll, save, remove, openCreate, openEdit, closeModal } = useCrud('soutenances', defaultForm)

const projets = ref([])
onMounted(async () => {
  await fetchAll()
  try {
    const res = await api.get('/projets')
    projets.value = res.data.data
  } catch {}
})

const statutLabel = { planifiee: 'Planifiée', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée' }
const statutColor = { planifiee: 'bg-blue-100 text-blue-700', en_cours: 'bg-amber-100 text-amber-700', terminee: 'bg-green-100 text-green-700', annulee: 'bg-red-100 text-red-600' }

function formatDate(d) {
  return new Date(d).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}
</script>

<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Soutenances</h1>
        <p class="text-sm text-slate-400">{{ items.length }} soutenances enregistrées</p>
      </div>
      <button @click="openCreate" class="flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-700 transition">
        <i class="fa-solid fa-plus"></i> Planifier une soutenance
      </button>
    </div>

    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher une soutenance…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <div v-if="loading" class="rounded-[2rem] border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400 shadow-sm">Chargement…</div>
    <div v-else-if="filtered.length === 0" class="rounded-[2rem] border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400 shadow-sm">Aucune soutenance trouvée</div>
    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article v-for="s in filtered" :key="s.id"
        class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm hover:border-[#d6e87a] transition">
        <div class="mb-3 flex items-start justify-between gap-2">
          <span class="rounded-lg px-2.5 py-1 text-[10px] font-bold" :class="statutColor[s.statut] || 'bg-slate-100 text-slate-600'">
            {{ statutLabel[s.statut] || s.statut }}
          </span>
          <div class="flex gap-2">
            <button @click="openEdit(s)" class="rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-200 transition">
              <i class="fa-solid fa-pen"></i>
            </button>
            <button @click="remove(s.id)" class="rounded-xl bg-red-50 px-3 py-1.5 text-xs font-bold text-red-500 hover:bg-red-100 transition">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </div>
        <h3 class="text-sm font-extrabold text-slate-900 leading-snug">{{ s.projet?.titre || '—' }}</h3>
        <div class="mt-3 space-y-1.5">
          <div class="flex items-center gap-2 text-xs text-slate-500">
            <i class="fa-regular fa-calendar w-4 text-center text-slate-400"></i>
            <span>{{ formatDate(s.date) }}</span>
          </div>
          <div class="flex items-center gap-2 text-xs text-slate-500">
            <i class="fa-regular fa-clock w-4 text-center text-slate-400"></i>
            <span>{{ s.heure?.slice(0,5) }}</span>
          </div>
          <div class="flex items-center gap-2 text-xs text-slate-500">
            <i class="fa-solid fa-door-open w-4 text-center text-slate-400"></i>
            <span>Salle {{ s.salle }}</span>
          </div>
        </div>
      </article>
    </div>

    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-[2rem] bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
            <h2 class="text-base font-extrabold text-slate-900">{{ editing ? 'Modifier' : 'Planifier' }} une soutenance</h2>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button>
          </div>
          <form @submit.prevent="save" class="space-y-4 p-6">
            <div v-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">{{ error }}</div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Projet</label>
              <select v-model="form.projet_id" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                <option value="">— Sélectionner un projet —</option>
                <option v-for="p in projets" :key="p.id" :value="p.id">{{ p.titre }}</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Date</label>
                <input v-model="form.date" type="date" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Heure</label>
                <input v-model="form.heure" type="time" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Salle</label>
                <input v-model="form.salle" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Statut</label>
                <select v-model="form.statut" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                  <option v-for="(label, key) in statutLabel" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Jury (noms séparés par virgule)</label>
              <input v-model="form.jury" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
            </div>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="closeModal" class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">Annuler</button>
              <button type="submit" class="flex-1 rounded-xl bg-slate-900 py-2.5 text-sm font-bold text-white hover:bg-slate-700 transition">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
