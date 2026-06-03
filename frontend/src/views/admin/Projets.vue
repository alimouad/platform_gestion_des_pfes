<script setup>
import { onMounted, ref, computed } from 'vue'
import { useCrud } from '@/composables/useCrud'
import api from '@/services/api'

const defaultForm = { titre: '', description: '', domaine: '', statut: 'brouillon', professeur_id: '', annee_universitaire_id: '', filiere_id: '' }
const { items, loading, search, filtered, showModal, editing, form, error, fetchAll, save, remove, openCreate, openEdit, closeModal } = useCrud('projets', defaultForm)

const professeurs = ref([])
const annees = ref([])
const filieres = ref([])
const filterStatut = ref('')

onMounted(async () => {
  await fetchAll()
  try {
    const [pr, ar, fr] = await Promise.all([api.get('/professeurs'), api.get('/annees-universitaires'), api.get('/filieres')])
    professeurs.value = pr.data.data
    annees.value = ar.data.data
    filieres.value = fr.data.data
  } catch {}
})

const statutLabel = { brouillon: 'Brouillon', soumis: 'Soumis', en_cours: 'En cours', valide: 'Validé', soutenu: 'Soutenu', rejete: 'Rejeté' }
const statutColor = { brouillon: 'bg-slate-100 text-slate-600', soumis: 'bg-blue-100 text-blue-700', en_cours: 'bg-amber-100 text-amber-700', valide: 'bg-green-100 text-green-700', soutenu: 'bg-[#d6e87a] text-slate-800', rejete: 'bg-red-100 text-red-600' }

const displayList = computed(() => {
  let list = filtered.value
  if (filterStatut.value) list = list.filter(p => p.statut === filterStatut.value)
  return list
})

// Group by département → filière
const groupedByDeptFiliere = computed(() => {
  const depts = {}
  for (const p of displayList.value) {
    const dept = p.filiere?.departement?.nom
      || p.professeur?.utilisateur?.departement?.nom
      || 'Sans département'
    const fil = p.filiere?.nom || 'Sans filière'
    if (!depts[dept]) depts[dept] = {}
    if (!depts[dept][fil]) depts[dept][fil] = []
    depts[dept][fil].push(p)
  }
  return Object.entries(depts).sort(([a], [b]) => a.localeCompare(b))
    .map(([dept, filieres]) => ({
      dept,
      filieres: Object.entries(filieres).sort(([a], [b]) => a.localeCompare(b)),
      total: Object.values(filieres).flat().length,
    }))
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
      <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm flex-1 min-w-50">
        <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
        <input v-model="search" placeholder="Rechercher un projet…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
      </div>
      <select v-model="filterStatut" class="rounded-2xl border border-white/70 bg-white/90 px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm outline-none">
        <option value="">Tous les statuts</option>
        <option v-for="(label, key) in statutLabel" :key="key" :value="key">{{ label }}</option>
      </select>
    </div>

    <!-- Table -->
    <div v-if="loading" class="rounded-4xl border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400 shadow-sm">Chargement…</div>
    <div v-else-if="displayList.length === 0" class="rounded-4xl border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400 shadow-sm">Aucun projet trouvé</div>
    <div v-else class="space-y-8">
      <section v-for="deptGroup in groupedByDeptFiliere" :key="deptGroup.dept">

        <!-- Département header -->
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2 rounded-2xl bg-[#1e4a49] px-4 py-2">
            <i class="fa-solid fa-building-columns text-[#d6e87a] text-xs"></i>
            <span class="text-xs font-black text-white">{{ deptGroup.dept }}</span>
            <span class="rounded-md bg-[#d6e87a] text-[#1e4a49] px-1.5 py-0.5 text-[10px] font-black">{{ deptGroup.total }}</span>
          </div>
          <div class="flex-1 h-px bg-slate-200"></div>
        </div>

        <!-- Filière sub-groups -->
        <div v-for="([filiere, projets]) in deptGroup.filieres" :key="filiere" class="pl-4 space-y-2">
          <div class="flex items-center gap-2 mb-2">
            <div class="flex items-center gap-2 rounded-xl bg-[#1e4a49]/10 px-3 py-1">
              <i class="fa-solid fa-layer-group text-[#1e4a49] text-[10px]"></i>
              <span class="text-[11px] font-bold text-[#1e4a49]">{{ filiere }}</span>
              <span class="rounded bg-[#1e4a49] text-[#d6e87a] px-1 py-0.5 text-[9px] font-black">{{ projets.length }}</span>
            </div>
            <div class="flex-1 h-px bg-slate-100"></div>
          </div>
          <div class="rounded-4xl border border-white/70 bg-white/90 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-slate-50/70 text-left text-[10px] font-bold uppercase tracking-widest text-slate-400">
                  <th class="px-6 py-3">Titre</th>
                  <th class="px-4 py-3">Domaine</th>
                  <th class="px-4 py-3">Statut</th>
                  <th class="px-4 py-3">Professeur</th>
                  <th class="px-4 py-3">Année</th>
                  <th class="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in projets" :key="p.id" class="border-t border-slate-100 hover:bg-slate-50/60 transition">
                  <td class="px-6 py-3 max-w-55">
                    <p class="font-semibold text-slate-800 truncate">{{ p.titre }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ p.description?.slice(0,60) }}…</p>
                  </td>
                  <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ p.domaine || '—' }}</td>
                  <td class="px-4 py-3">
                    <span class="rounded-lg px-2.5 py-1 text-[11px] font-bold" :class="statutColor[p.statut]">
                      {{ statutLabel[p.statut] || p.statut }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-slate-500">{{ p.professeur?.utilisateur?.prenom }} {{ p.professeur?.utilisateur?.nom }}</td>
                  <td class="px-4 py-3 text-slate-500">{{ p.anneeUniversitaire?.annee || '—' }}</td>
                  <td class="px-4 py-3 text-right whitespace-nowrap">
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
        </div>
      </section>
    </div>

    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="w-full max-w-lg rounded-4xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
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
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Filière cible</label>
              <select v-model="form.filiere_id" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                <option value="">— Toutes —</option>
                <option v-for="f in filieres" :key="f.id" :value="f.id">{{ f.nom }}</option>
              </select>
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
