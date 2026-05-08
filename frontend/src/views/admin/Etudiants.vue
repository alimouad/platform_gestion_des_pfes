<script setup>
import { onMounted, ref } from 'vue'
import { useCrud } from '@/composables/useCrud'
import api from '@/services/api'

const defaultForm = { user_id: '', code_etudiant: '', filiere: '', niveau: 'L3' }
const { items, loading, search, filtered, showModal, editing, form, error, fetchAll, save, remove, openCreate, openEdit, closeModal } = useCrud('etudiants', defaultForm)

const users = ref([])
onMounted(async () => {
  await fetchAll()
  try {
    const res = await api.get('/users')
    users.value = res.data.data.filter(u => u.role === 'etudiant')
  } catch {}
})
</script>

<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Étudiants</h1>
        <p class="text-sm text-slate-400">{{ items.length }} étudiants enregistrés</p>
      </div>
      <button @click="openCreate" class="flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-700 transition">
        <i class="fa-solid fa-plus"></i> Nouvel étudiant
      </button>
    </div>

    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher un étudiant…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <div class="rounded-[2rem] border border-white/70 bg-white/90 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-10 text-center text-sm text-slate-400">Chargement…</div>
      <div v-else-if="filtered.length === 0" class="p-10 text-center text-sm text-slate-400">Aucun étudiant trouvé</div>
      <table v-else class="w-full text-sm">
        <thead>
          <tr class="bg-slate-50/70 text-left text-[10px] font-bold uppercase tracking-widest text-slate-400">
            <th class="px-6 py-4">Étudiant</th>
            <th class="px-4 py-4">Code</th>
            <th class="px-4 py-4">Filière</th>
            <th class="px-4 py-4">Niveau</th>
            <th class="px-4 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="e in filtered" :key="e.id" class="border-t border-slate-100 hover:bg-slate-50/60 transition">
            <td class="px-6 py-3.5">
              <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#d6e87a] text-xs font-black text-slate-700">
                  {{ (e.utilisateur?.prenom || 'E')[0] }}{{ (e.utilisateur?.nom || '')[0] }}
                </div>
                <div>
                  <p class="font-semibold text-slate-800">{{ e.utilisateur?.prenom }} {{ e.utilisateur?.nom }}</p>
                  <p class="text-xs text-slate-400">{{ e.utilisateur?.courriel }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3.5 font-mono text-xs text-slate-600">{{ e.code_etudiant }}</td>
            <td class="px-4 py-3.5 text-slate-500">{{ e.filiere || '—' }}</td>
            <td class="px-4 py-3.5">
              <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700">{{ e.niveau }}</span>
            </td>
            <td class="px-4 py-3.5 text-right">
              <button @click="openEdit(e)" class="mr-2 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-200 transition">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button @click="remove(e.id)" class="rounded-xl bg-red-50 px-3 py-1.5 text-xs font-bold text-red-500 hover:bg-red-100 transition">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-[2rem] bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
            <h2 class="text-base font-extrabold text-slate-900">{{ editing ? 'Modifier' : 'Nouvel' }} étudiant</h2>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button>
          </div>
          <form @submit.prevent="save" class="space-y-4 p-6">
            <div v-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">{{ error }}</div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Utilisateur</label>
              <select v-model="form.user_id" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                <option value="">— Sélectionner —</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.prenom }} {{ u.nom }} ({{ u.courriel }})</option>
              </select>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Code étudiant</label>
              <input v-model="form.code_etudiant" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Filière</label>
              <input v-model="form.filiere" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Niveau</label>
              <select v-model="form.niveau" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                <option value="L1">L1</option>
                <option value="L2">L2</option>
                <option value="L3">L3</option>
                <option value="M1">M1</option>
                <option value="M2">M2</option>
              </select>
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
