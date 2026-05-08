<script setup>
import { onMounted } from 'vue'
import { useCrud } from '@/composables/useCrud'

const defaultForm = { annee: '', statut: 'inactive', date_debut: '', date_fin: '' }
const { items, loading, search, filtered, showModal, editing, form, error, fetchAll, save, remove, openCreate, openEdit, closeModal } = useCrud('annees-universitaires', defaultForm)

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Années universitaires</h1>
        <p class="text-sm text-slate-400">Gestion des années académiques</p>
      </div>
      <button @click="openCreate" class="flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-700 transition">
        <i class="fa-solid fa-plus"></i> Nouvelle année
      </button>
    </div>

    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <div v-if="loading" class="rounded-[2rem] border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400 shadow-sm">Chargement…</div>
    <div v-else-if="filtered.length === 0" class="rounded-[2rem] border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400 shadow-sm">Aucune année trouvée</div>
    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article v-for="a in filtered" :key="a.id"
        class="rounded-[2rem] border bg-white/90 p-6 shadow-sm transition"
        :class="a.statut === 'active' ? 'border-[#d6e87a]' : 'border-white/70'">
        <div class="mb-4 flex items-start justify-between">
          <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#f0f5e0] text-[#6a8a40]">
            <i class="fa-solid fa-calendar-days text-lg"></i>
          </div>
          <div class="flex items-center gap-2">
            <span class="rounded-lg px-2.5 py-1 text-[10px] font-bold"
              :class="a.statut === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'">
              {{ a.statut === 'active' ? 'Active' : 'Inactive' }}
            </span>
            <button @click="openEdit(a)" class="rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-200 transition">
              <i class="fa-solid fa-pen"></i>
            </button>
            <button @click="remove(a.id)" class="rounded-xl bg-red-50 px-3 py-1.5 text-xs font-bold text-red-500 hover:bg-red-100 transition">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </div>
        <h3 class="text-xl font-extrabold text-slate-900">{{ a.annee }}</h3>
        <p v-if="a.date_debut && a.date_fin" class="mt-1 text-sm text-slate-400">
          {{ new Date(a.date_debut).toLocaleDateString('fr-FR') }} → {{ new Date(a.date_fin).toLocaleDateString('fr-FR') }}
        </p>
        <div v-if="a.statistique" class="mt-4 grid grid-cols-2 gap-2">
          <div class="rounded-xl bg-slate-50 px-3 py-2 text-center">
            <p class="text-lg font-extrabold text-slate-900">{{ a.statistique.total_projets }}</p>
            <p class="text-[10px] text-slate-400">Total projets</p>
          </div>
          <div class="rounded-xl bg-[#f0f5e0] px-3 py-2 text-center">
            <p class="text-lg font-extrabold text-[#4a7a30]">{{ a.statistique.projets_valides }}</p>
            <p class="text-[10px] text-slate-400">Validés</p>
          </div>
        </div>
      </article>
    </div>

    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-[2rem] bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
            <h2 class="text-base font-extrabold text-slate-900">{{ editing ? 'Modifier' : 'Nouvelle' }} année universitaire</h2>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button>
          </div>
          <form @submit.prevent="save" class="space-y-4 p-6">
            <div v-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">{{ error }}</div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Année (ex: 2024-2025)</label>
              <input v-model="form.annee" required placeholder="2024-2025" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Statut</label>
              <select v-model="form.statut" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                <option value="inactive">Inactive</option>
                <option value="active">Active</option>
                <option value="terminee">Terminée</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Date début</label>
                <input v-model="form.date_debut" type="date" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Date fin</label>
                <input v-model="form.date_fin" type="date" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
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
