<script setup>
import { onMounted, ref } from 'vue'
import { useCrud } from '@/composables/useCrud'

const defaultForm = { nom: '', prenom: '', courriel: '', mot_de_passe: '', role: 'etudiant', departement_id: '' }
const { items, loading, search, filtered, showModal, editing, form, error, fetchAll, save, remove, openCreate, openEdit, closeModal } = useCrud('users', defaultForm)
const currentUserId = JSON.parse(localStorage.getItem('admin_user') || '{}')?.id

const departements = ref([])
onMounted(async () => {
  await fetchAll()
  try {
    const { default: api } = await import('@/services/api')
    const res = await api.get('/departements')
    departements.value = res.data.data
  } catch {}
})

const roleColors = {
  superadmin: 'bg-[#d6e87a] text-slate-800',
  coordinateur: 'bg-purple-100 text-purple-700',
  professeur: 'bg-blue-100 text-blue-700',
  etudiant: 'bg-slate-100 text-slate-600',
}
const roleLabel = {
  superadmin: 'Super Admin',
  coordinateur: 'Coordinateur',
  professeur: 'Professeur',
  etudiant: 'Étudiant',
}
</script>

<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Utilisateurs</h1>
        <p class="text-sm text-slate-400">Gestion des comptes utilisateurs</p>
      </div>
      <button @click="openCreate" class="flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-700 transition">
        <i class="fa-solid fa-plus"></i> Nouvel utilisateur
      </button>
    </div>

    <!-- Search -->
    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher un utilisateur…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <!-- Table -->
    <div class="rounded-[2rem] border border-white/70 bg-white/90 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-10 text-center text-sm text-slate-400">Chargement…</div>
      <div v-else-if="filtered.length === 0" class="p-10 text-center text-sm text-slate-400">Aucun utilisateur trouvé</div>
      <table v-else class="w-full text-sm">
        <thead>
          <tr class="bg-slate-50/70 text-left text-[10px] font-bold uppercase tracking-widest text-slate-400">
            <th class="px-6 py-4">Nom</th>
            <th class="px-4 py-4">Email</th>
            <th class="px-4 py-4">Rôle</th>
            <th class="px-4 py-4">Département</th>
            <th class="px-4 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in filtered" :key="u.id" class="border-t border-slate-100 hover:bg-slate-50/60 transition">
            <td class="px-6 py-3.5">
              <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#d6e87a] text-xs font-black text-slate-700">
                  {{ (u.prenom || 'U')[0] }}{{ (u.nom || '')[0] }}
                </div>
                <div>
                  <p class="font-semibold text-slate-800">{{ u.prenom }} {{ u.nom }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3.5 text-slate-500">{{ u.courriel }}</td>
            <td class="px-4 py-3.5">
              <span class="rounded-lg px-2.5 py-1 text-[11px] font-bold" :class="roleColors[u.role] || 'bg-slate-100 text-slate-600'">
                {{ roleLabel[u.role] || u.role }}
              </span>
            </td>
            <td class="px-4 py-3.5 text-slate-500">{{ u.departement?.nom || '—' }}</td>
            <td class="px-4 py-3.5 text-right">
              <button @click="openEdit(u)" class="mr-2 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-200 transition">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button v-if="u.id !== currentUserId" @click="remove(u.id)" class="rounded-xl bg-red-50 px-3 py-1.5 text-xs font-bold text-red-500 hover:bg-red-100 transition">
                <i class="fa-solid fa-trash"></i>
              </button>
              <span v-else class="rounded-xl bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-300 cursor-not-allowed" title="Impossible de supprimer votre propre compte">
                <i class="fa-solid fa-trash"></i>
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-[2rem] bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
            <h2 class="text-base font-extrabold text-slate-900">{{ editing ? 'Modifier' : 'Nouvel' }} utilisateur</h2>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button>
          </div>
          <form @submit.prevent="save" class="space-y-4 p-6">
            <div v-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">{{ error }}</div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Prénom</label>
                <input v-model="form.prenom" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Nom</label>
                <input v-model="form.nom" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
              </div>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Email</label>
              <input v-model="form.courriel" type="email" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Mot de passe {{ editing ? '(laisser vide = inchangé)' : '' }}</label>
              <input v-model="form.mot_de_passe" type="password" :required="!editing" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Rôle</label>
                <select v-model="form.role" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                  <option value="etudiant">Étudiant</option>
                  <option value="professeur">Professeur</option>
                  <option value="coordinateur">Coordinateur</option>
                  <option value="superadmin">Super Admin</option>
                </select>
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Département</label>
                <select v-model="form.departement_id" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                  <option value="">— Aucun —</option>
                  <option v-for="d in departements" :key="d.id" :value="d.id">{{ d.nom }}</option>
                </select>
              </div>
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
