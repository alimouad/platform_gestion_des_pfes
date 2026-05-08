<script setup>
import { onMounted, ref, computed } from 'vue'
import { useCrud } from '@/composables/useCrud'
import api from '@/services/api'

const defaultForm = { titre: '', description: '', domaine: '', statut: 'brouillon', professeur_id: '', annee_universitaire_id: '' }
const { items, loading, search, filtered, showModal, editing, form, error, fetchAll, save, remove, openCreate, openEdit, closeModal } = useCrud('projets', defaultForm)

const professeurs = ref([])
const annees = ref([])
const filterStatut = ref('')

onMounted(async () => {
  await fetchAll()
  try {
    const [pr, ar] = await Promise.all([api.get('/professeurs'), api.get('/annees-universitaires')])
    professeurs.value = pr.data.data
    annees.value = ar.data.data
  } catch {}
})

const statutLabel = { brouillon: 'Brouillon', soumis: 'Soumis', en_cours: 'En cours', valide: 'Validé', soutenu: 'Soutenu', rejete: 'Rejeté' }
const statutColor = { brouillon: 'bg-slate-100 text-slate-600', soumis: 'bg-blue-100 text-blue-700', en_cours: 'bg-amber-100 text-amber-700', valide: 'bg-green-100 text-green-700', soutenu: 'bg-[#d6e87a] text-slate-800', rejete: 'bg-red-100 text-red-600' }

const displayList = computed(() => {
  let list = filtered.value
  if (filterStatut.value) list = list.filter(p => p.statut === filterStatut.value)
  return list
})
</script>

<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Projets PFE</h1>
        <p class="text-sm text-slate-400">{{ items.length }} projets au total</p>
      </div>
      <button @click="openCreate" class="flex items-center gap-2 rounded-2xl btn-primary px-5 py-2.5 text-sm font-bold shadow transition">
        <i class="fa-solid fa-plus"></i> Nouveau projet
      </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3">
      <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm flex-1 min-w-[200px]">
        <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
        <input v-model="search" placeholder="Rechercher un projet…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
      </div>
      <select v-model="filterStatut" class="rounded-2xl border border-white/70 bg-white/90 px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm outline-none">
        <option value="">Tous les statuts</option>
        <option v-for="(label, key) in statutLabel" :key="key" :value="key">{{ label }}</option>
      </select>
    </div>

    <!-- Table -->
    <div class="rounded-[2rem] border border-white/70 bg-white/90 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-10 text-center text-sm text-slate-400">Chargement…</div>
      <div v-else-if="displayList.length === 0" class="p-10 text-center text-sm text-slate-400">Aucun projet trouvé</div>
      <table v-else class="w-full text-sm">
        <thead>
          <tr class="bg-slate-50/70 text-left text-[10px] font-bold uppercase tracking-widest text-slate-400">
            <th class="px-6 py-4">Titre</th>
            <th class="px-4 py-4">Domaine</th>
            <th class="px-4 py-4">Statut</th>
            <th class="px-4 py-4">Professeur</th>
            <th class="px-4 py-4">Année</th>
            <th class="px-4 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in displayList" :key="p.id" class="border-t border-slate-100 hover:bg-slate-50/60 transition">
            <td class="px-6 py-3.5 max-w-[220px]">
              <p class="font-semibold text-slate-800 truncate">{{ p.titre }}</p>
              <p class="text-xs text-slate-400 truncate">{{ p.description?.slice(0,60) }}…</p>
            </td>
            <td class="px-4 py-3.5 text-slate-500 whitespace-nowrap">{{ p.domaine || '—' }}</td>
            <td class="px-4 py-3.5">
              <span class="rounded-lg px-2.5 py-1 text-[11px] font-bold" :class="statutColor[p.statut]">
                {{ statutLabel[p.statut] || p.statut }}
              </span>
            </td>
            <td class="px-4 py-3.5 text-slate-500">{{ p.professeur?.utilisateur?.prenom }} {{ p.professeur?.utilisateur?.nom }}</td>
            <td class="px-4 py-3.5 text-slate-500">{{ p.anneeUniversitaire?.annee || '—' }}</td>
            <td class="px-4 py-3.5 text-right whitespace-nowrap">
              <button @click="openEdit(p)" class="mr-2 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-200 transition">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button @click="remove(p.id)" class="rounded-xl bg-red-50 px-3 py-1.5 text-xs font-bold text-red-500 hover:bg-red-100 transition">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="w-full max-w-lg rounded-[2rem] bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
            <h2 class="text-base font-extrabold text-slate-900">{{ editing ? 'Modifier' : 'Nouveau' }} projet</h2>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button>
          </div>
          <form @submit.prevent="save" class="space-y-4 p-6">
            <div v-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">{{ error }}</div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Titre du projet</label>
              <input v-model="form.titre" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Description</label>
              <textarea v-model="form.description" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a] resize-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Domaine</label>
                <input v-model="form.domaine" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Statut</label>
                <select v-model="form.statut" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                  <option v-for="(label, key) in statutLabel" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Professeur encadrant</label>
              <select v-model="form.professeur_id" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                <option value="">— Sélectionner —</option>
                <option v-for="p in professeurs" :key="p.id" :value="p.id">{{ p.utilisateur?.prenom }} {{ p.utilisateur?.nom }}</option>
              </select>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Année universitaire</label>
              <select v-model="form.annee_universitaire_id" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                <option value="">— Sélectionner —</option>
                <option v-for="a in annees" :key="a.id" :value="a.id">{{ a.annee }}</option>
              </select>
            </div>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="closeModal" class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">Annuler</button>
              <button type="submit" class="flex-1 rounded-xl btn-primary py-2.5 text-sm font-bold transition">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
