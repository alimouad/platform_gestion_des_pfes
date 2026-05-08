<script setup>
import { onMounted, ref, computed } from 'vue'
import { useCrud } from '@/composables/useCrud'
import api from '@/services/api'

const defaultForm = { user_id: '', specialite: '', grade: '', bureau: '' }
const { items, loading, search, filtered, showModal, editing, form, error, fetchAll, save, remove, openCreate, openEdit, closeModal } = useCrud('professeurs', defaultForm)

const users = ref([])

onMounted(async () => {
  await fetchAll()
  try {
    const res = await api.get('/users')
    users.value = res.data.data.filter(u => u.role === 'professeur')
  } catch {}
})

// When editing, only keep the fields the API accepts
function handleEdit(p) {
  openEdit({
    id: p.id,
    user_id: p.user_id,
    specialite: p.specialite ?? '',
    grade: p.grade ?? '',
    bureau: p.bureau ?? '',
  })
}

const gradeColors = {
  'Maître de conférences': 'bg-purple-50 text-purple-700',
  'Professeur':            'bg-blue-50 text-blue-700',
  'Assistant':             'bg-slate-50 text-slate-600',
}
</script>

<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Professeurs</h1>
        <p class="text-sm text-slate-400">{{ items.length }} enseignants enregistrés</p>
      </div>
      <button @click="openCreate"
        class="flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-700 transition">
        <i class="fa-solid fa-plus"></i> Nouveau professeur
      </button>
    </div>

    <!-- Search -->
    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher un professeur…"
        class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <!-- Table -->
    <div class="rounded-[2rem] border border-white/70 bg-white/90 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-10 text-center text-sm text-slate-400">Chargement…</div>
      <div v-else-if="filtered.length === 0" class="p-10 text-center text-sm text-slate-400">Aucun professeur trouvé</div>
      <table v-else class="w-full text-sm">
        <thead>
          <tr class="bg-slate-50/70 text-left text-[10px] font-bold uppercase tracking-widest text-slate-400">
            <th class="px-6 py-4">Professeur</th>
            <th class="px-4 py-4">Spécialité</th>
            <th class="px-4 py-4">Grade</th>
            <th class="px-4 py-4">Bureau</th>
            <th class="px-4 py-4">Département</th>
            <th class="px-4 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in filtered" :key="p.id"
            class="border-t border-slate-100 hover:bg-slate-50/60 transition">
            <td class="px-6 py-3.5">
              <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-xs font-black text-blue-700">
                  {{ (p.utilisateur?.prenom || 'P')[0].toUpperCase() }}{{ (p.utilisateur?.nom || '')[0]?.toUpperCase() }}
                </div>
                <div>
                  <p class="font-semibold text-slate-800">{{ p.utilisateur?.prenom }} {{ p.utilisateur?.nom }}</p>
                  <p class="text-xs text-slate-400">{{ p.utilisateur?.courriel }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3.5 text-slate-500">{{ p.specialite || '—' }}</td>
            <td class="px-4 py-3.5">
              <span v-if="p.grade" class="rounded-lg px-2.5 py-1 text-[11px] font-bold"
                :class="gradeColors[p.grade] || 'bg-slate-100 text-slate-600'">
                {{ p.grade }}
              </span>
              <span v-else class="text-slate-400">—</span>
            </td>
            <td class="px-4 py-3.5 text-slate-500">{{ p.bureau || '—' }}</td>
            <td class="px-4 py-3.5 text-slate-500">{{ p.utilisateur?.departement?.nom || '—' }}</td>
            <td class="px-4 py-3.5 text-right">
              <button @click="handleEdit(p)"
                class="mr-2 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-200 transition">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button @click="remove(p.id)"
                class="rounded-xl bg-red-50 px-3 py-1.5 text-xs font-bold text-red-500 hover:bg-red-100 transition">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-[2rem] bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
            <h2 class="text-base font-extrabold text-slate-900">
              {{ editing ? 'Modifier' : 'Nouveau' }} professeur
            </h2>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-700">
              <i class="fa-solid fa-xmark text-lg"></i>
            </button>
          </div>
          <form @submit.prevent="save" class="space-y-4 p-6">
            <div v-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">{{ error }}</div>

            <!-- User selector — shown only on create -->
            <div v-if="!editing">
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Utilisateur (rôle professeur)</label>
              <select v-model="form.user_id" required
                class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                <option value="">— Sélectionner —</option>
                <option v-for="u in users" :key="u.id" :value="u.id">
                  {{ u.prenom }} {{ u.nom }} — {{ u.courriel }}
                </option>
              </select>
            </div>

            <!-- On edit, show the linked user as read-only -->
            <div v-else class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
              <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-0.5">Utilisateur lié</p>
              <p class="text-sm font-semibold text-slate-700">
                {{ users.find(u => u.id === form.user_id)?.prenom }}
                {{ users.find(u => u.id === form.user_id)?.nom }}
              </p>
            </div>

            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Spécialité</label>
              <input v-model="form.specialite"
                class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]"
                placeholder="ex: Informatique, Mathématiques…" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Grade</label>
                <select v-model="form.grade"
                  class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                  <option value="">— Aucun —</option>
                  <option value="Assistant">Assistant</option>
                  <option value="Maître de conférences">Maître de conférences</option>
                  <option value="Professeur">Professeur</option>
                </select>
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Bureau</label>
                <input v-model="form.bureau"
                  class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]"
                  placeholder="ex: B204" />
              </div>
            </div>

            <div class="flex gap-3 pt-2">
              <button type="button" @click="closeModal"
                class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">
                Annuler
              </button>
              <button type="submit"
                class="flex-1 rounded-xl bg-slate-900 py-2.5 text-sm font-bold text-white hover:bg-slate-700 transition">
                Enregistrer
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
